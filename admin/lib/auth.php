<?php
/**
 * Autentizace administrace.
 * -----------------------------------------------------------------------------
 * Opravuje nejvážnější díru staré administrace: přihlášení se dalo obejít
 * nastavením cookie `rememberMe=true`. Tady se přihlášení drží POUZE v server-
 * side session. "Zůstat přihlášen" používá kryptograficky PODEPSANÝ token
 * (HMAC se secretem ze serveru), který si útočník nemůže vyrobit.
 */

declare(strict_types=1);

const SCM_INACTIVITY_LIMIT = 30 * 60;          // 30 min nečinnosti
const SCM_REMEMBER_LIMIT   = 30 * 24 * 60 * 60; // 30 dní "zůstat přihlášen"

/**
 * Ověří heslo proti configu (hash) a přihlásí uživatele.
 * @return bool  true při úspěchu
 */
function auth_attempt(array $config, string $username, string $password): bool
{
    $userOk = hash_equals((string)($config['admin_username'] ?? ''), $username);
    $hash = (string)($config['admin_password_hash'] ?? '');
    $passOk = $hash !== '' && password_verify($password, $hash);

    // konstantní-časové vyhodnocení obou podmínek
    if ($userOk && $passOk) {
        session_regenerate_id(true);   // proti session fixation
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        return true;
    }
    return false;
}

/** Podepíše "remember me" token pomocí HMAC (nelze zfalšovat bez secretu). */
function auth_make_remember_token(array $config): string
{
    $expires = time() + SCM_REMEMBER_LIMIT;
    $payload = ($config['admin_username'] ?? '') . '|' . $expires;
    $sig = hash_hmac('sha256', $payload, (string)($config['app_secret'] ?? ''));
    return base64_encode($payload . '|' . $sig);
}

/** Ověří podepsaný "remember me" token. */
function auth_check_remember_token(array $config, string $token): bool
{
    $raw = base64_decode($token, true);
    if ($raw === false) {
        return false;
    }
    $parts = explode('|', $raw);
    if (count($parts) !== 3) {
        return false;
    }
    [$user, $expires, $sig] = $parts;

    $payload = $user . '|' . $expires;
    $expected = hash_hmac('sha256', $payload, (string)($config['app_secret'] ?? ''));
    if (!hash_equals($expected, $sig)) {
        return false;   // podpis nesedí -> padělek
    }
    if ((int)$expires < time()) {
        return false;   // vypršelo
    }
    if (!hash_equals((string)($config['admin_username'] ?? ''), $user)) {
        return false;
    }
    return true;
}

/** Nastaví "remember me" cookie s podepsaným tokenem. */
function auth_set_remember_cookie(array $config): void
{
    global $baseUrl;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie('scm_remember', auth_make_remember_token($config), [
        'expires' => time() + SCM_REMEMBER_LIMIT,
        'path' => $baseUrl,
        'httponly' => true,
        'secure' => $https,
        'samesite' => 'Lax',
    ]);
}

/** Smaže "remember me" cookie. */
function auth_clear_remember_cookie(): void
{
    global $baseUrl;
    setcookie('scm_remember', '', [
        'expires' => time() - 3600,
        'path' => $baseUrl,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/** Odhlásí uživatele úplně. */
function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    auth_clear_remember_cookie();
}

/**
 * STRÁŽCE: vyžaduje přihlášení. Voláme na začátku každé chráněné stránky.
 * Řeší i obnovu session z podepsaného remember tokenu a timeout nečinnosti.
 */
function auth_require(array $config): void
{
    global $baseUrl;

    // 1) obnova z podepsaného remember tokenu (bezpečná náhrada starého bypassu)
    if (empty($_SESSION['logged_in']) && !empty($_COOKIE['scm_remember'])) {
        if (auth_check_remember_token($config, (string)$_COOKIE['scm_remember'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $config['admin_username'] ?? '';
            $_SESSION['login_time'] = $_SESSION['login_time'] ?? time();
            $_SESSION['last_activity'] = time();
        } else {
            auth_clear_remember_cookie();   // padělaný/prošlý token pryč
        }
    }

    // 2) nepřihlášen -> na login
    if (empty($_SESSION['logged_in'])) {
        redirect($baseUrl . 'admin/login/');
    }

    // 3) timeout nečinnosti (jen když není aktivní "zůstat přihlášen")
    if (empty($_COOKIE['scm_remember'])) {
        $last = $_SESSION['last_activity'] ?? time();
        if (time() - $last > SCM_INACTIVITY_LIMIT) {
            auth_logout();
            redirect($baseUrl . 'admin/login/?message=inactive');
        }
    }

    $_SESSION['last_activity'] = time();
}
