<?php
/**
 * Sdílené pomocné funkce administrace.
 * -----------------------------------------------------------------------------
 * Vše, co se ve staré administraci opakovalo copy-pastem nebo úplně chybělo
 * (CSRF, bezpečný upload, escaping), je tady na jednom místě.
 */

declare(strict_types=1);

// ============================================================
//  ESCAPING
// ============================================================

/** Bezpečný výpis do HTML (proti XSS). Zkráceně `e()`. */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ============================================================
//  CSRF OCHRANA
// ============================================================

/** Vrátí (a při první potřebě vytvoří) CSRF token pro tuto session. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Skryté pole s CSRF tokenem do formuláře. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Ověří CSRF token u POST požadavku. Když nesedí, požadavek se zamítne.
 * Volat na začátku každého zpracování formuláře.
 */
function csrf_verify(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf_token'] ?? '', $sent)) {
        http_response_code(419);
        exit('Neplatný bezpečnostní token (CSRF). Zkuste formulář načíst a odeslat znovu.');
    }
}

// ============================================================
//  FLASH ZPRÁVY (přežijí přesměrování)
// ============================================================

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Vrátí a vymaže nasbírané flash zprávy. */
function flash_get(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// ============================================================
//  PŘESMĚROVÁNÍ
// ============================================================

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Přesměrování na admin cestu relativně k baseUrl. */
function redirect_admin(string $path = ''): void
{
    global $baseUrl;
    redirect($baseUrl . 'admin/' . ltrim($path, '/'));
}

// ============================================================
//  BEZPEČNÝ UPLOAD OBRÁZKŮ
//  (řeší RCE díru ze staré administrace)
// ============================================================

/**
 * Zpracuje nahraný obrázek bezpečně:
 *  - ověří, že upload proběhl bez chyby,
 *  - ověří skutečný MIME typ přes getimagesize (ne jen příponu z prohlížeče),
 *  - povolí jen JPEG/PNG/WebP/GIF,
 *  - vygeneruje náhodný název (žádná kolize, žádný útočníkem zvolený název),
 *  - uloží do public/images/uploads/.
 *
 * @return string|null  relativní cesta pro uložení do DB (images/uploads/xxx.jpg)
 *                       nebo null když nebyl nahrán žádný soubor.
 * @throws RuntimeException při chybě/nepovoleném souboru.
 */
function handle_image_upload(string $field): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;   // nic nenahráno - to je v pořádku (fotka je volitelná)
    }

    $file = $_FILES[$field];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Nahrání souboru selhalo (kód ' . (int)$file['error'] . ').');
    }

    // limit velikosti (5 MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Obrázek je příliš velký (max 5 MB).');
    }

    // KLÍČOVÉ: ověříme skutečný obsah, ne příponu ani hlavičku od prohlížeče
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        throw new RuntimeException('Nahraný soubor není platný obrázek.');
    }

    $allowed = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
        IMAGETYPE_GIF  => 'gif',
    ];
    $type = $info[2] ?? null;
    if (!isset($allowed[$type])) {
        throw new RuntimeException('Nepovolený formát obrázku. Povoleno: JPG, PNG, WebP, GIF.');
    }
    $ext = $allowed[$type];

    // náhodný název -> nelze přepsat cizí soubor ani zvolit koncovku .php
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    $destDir = SCM_ROOT . '/public/images/uploads';
    if (!is_dir($destDir)) {
        @mkdir($destDir, 0775, true);
    }
    $dest = $destDir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Soubor se nepodařilo uložit.');
    }

    return 'images/uploads/' . $name;
}

/** Smaže nahraný obrázek (při mazání/změně záznamu). Ignoruje výchozí obrázky. */
function delete_uploaded_image(?string $relPath): void
{
    $relPath = trim((string)$relPath);
    // mažeme jen soubory z uploads/, ne výchozí obrázky projektu
    if ($relPath === '' || strpos($relPath, 'images/uploads/') !== 0) {
        return;
    }
    $full = SCM_ROOT . '/public/' . $relPath;
    if (is_file($full)) {
        @unlink($full);
    }
}

// ============================================================
//  DROBNOSTI
// ============================================================

/** Očistí a ořízne textový vstup. */
function clean(?string $s): string
{
    return trim((string)$s);
}

/** Vrátí int z POST, s výchozí hodnotou. */
function post_int(string $key, int $default = 0): int
{
    return isset($_POST[$key]) ? (int)$_POST[$key] : $default;
}

// ============================================================
//  ČESKÉ SKLOŇOVÁNÍ POČTŮ
// ============================================================

/**
 * Vrátí počet se správně skloňovaným podstatným jménem podle českých pravidel:
 *   0        -> tvar "žádný" (many)   např. "žádní hráči"
 *   1        -> jednotné číslo (one)   např. "1 hráč"
 *   2, 3, 4  -> tvar few              např. "2 hráči"
 *   5+       -> tvar many             např. "5 hráčů"
 *
 * @param int    $count počet
 * @param string $one   tvar pro 1 (např. "hráč")
 * @param string $few   tvar pro 2-4 (např. "hráči")
 * @param string $many  tvar pro 0 a 5+ (např. "hráčů")
 * @param string $zero  volitelný tvar přímo pro 0 (např. "žádní hráči");
 *                      když je prázdný, použije se "0 {$many}"
 * @return string       např. "3 hráči", "1 hráč", "žádní hráči"
 */
function scm_plural(int $count, string $one, string $few, string $many, string $zero = ''): string
{
    if ($count === 0) {
        return $zero !== '' ? $zero : '0 ' . $many;
    }
    if ($count === 1) {
        return '1 ' . $one;
    }
    if ($count >= 2 && $count <= 4) {
        return $count . ' ' . $few;
    }
    return $count . ' ' . $many;
}

// ============================================================
//  ZÁPIS HODNOT DO CONFIG.PHP
//  (sdílí ho změna hesla i webový generátor v setup/)
// ============================================================

/**
 * Bezpečně přepíše hodnoty v config.php (jen daný klíč => 'hodnota').
 * Funguje na formát 'klic' => '...' i "klic" => "..." s jednoduchými i
 * dvojitými uvozovkami kolem hodnoty. Když klíč chybí, přidá ho.
 *
 * Používá preg_replace_callback, takže znaky $ a \ v hodnotě (třeba v bcrypt
 * hashi $2y$10$...) zůstanou nedotčené. Vrací true při úspěšném zápisu.
 *
 * @param array<string,string> $values klíč => nová hodnota
 */
function scm_write_config_values(string $file, array $values): bool
{
    if (!is_file($file) || !is_writable($file)) {
        return false;
    }
    $contents = file_get_contents($file);
    if ($contents === false) {
        return false;
    }
    foreach ($values as $key => $val) {
        $quoted = var_export($val, true);   // bezpečně uzávorkuje a escapuje pro PHP
        $keyQ = preg_quote($key, '/');

        // 'key' => '...' nebo "key" => "..." (jednoduché i dvojité uvozovky kolem hodnoty)
        $pattern = '/([\'"]' . $keyQ . '[\'"]\s*=>\s*)(\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*")/s';

        $count = 0;
        $new = preg_replace_callback($pattern, function ($m) use ($quoted) {
            return $m[1] . $quoted;
        }, $contents, 1, $count);

        if ($new === null) {
            return false;
        }
        if ($count === 0) {
            // klíč v configu vůbec není - přidáme ho před uzavírací ];
            $new = preg_replace_callback('/\n\];\s*$/s', function ($m) use ($key, $quoted) {
                return "\n    '" . $key . "' => " . $quoted . ",\n];";
            }, $contents, 1, $count);
            if ($new === null || $count === 0) {
                return false;
            }
        }
        $contents = $new;
    }
    return file_put_contents($file, $contents) !== false;
}

/**
 * Načte config ČERSTVĚ z disku (obejde OPcache).
 * Zásadní pro bezpečnostní kontroly ve webovém generátoru hesla - kontrola
 * "je heslo už nastavené?" musí vidět aktuální stav souboru, ne cache.
 */
function scm_read_config_fresh(string $file): array
{
    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate($file, true);
    }
    $code = @file_get_contents($file);
    if ($code === false) {
        return [];
    }
    $code = preg_replace('/^\s*<\?php/', '', $code, 1);
    try {
        $result = eval($code);
    } catch (\Throwable $e) {
        return [];
    }
    return is_array($result) ? $result : [];
}
