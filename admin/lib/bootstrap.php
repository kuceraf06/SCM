<?php
/**
 * Jádro administrace SCM - společný bootstrap.
 * -----------------------------------------------------------------------------
 * Nahrazuje roztroušené copy-paste (session_start, db_connect, paths, auth...)
 * z každého souboru staré administrace. Každá admin stránka jen udělá:
 *
 *     require_once __DIR__ . '/../lib/bootstrap.php';
 *
 * a má k dispozici: $config, $db (PDO), $baseUrl, a všechny helpery.
 */

declare(strict_types=1);

// ---- cesty ----
define('SCM_ROOT', dirname(__DIR__, 2));                 // kořen projektu
define('SCM_CONFIG', SCM_ROOT . '/config/config.php');
define('SCM_ADMIN', SCM_ROOT . '/admin');

// ---- načtení konfigurace ----
if (!is_file(SCM_CONFIG)) {
    http_response_code(500);
    exit('Chybí config/config.php. Vytvoř ho zkopírováním config/config.example.php a vyplň hodnoty.');
}
$config = require SCM_CONFIG;

// ---- zobrazování chyb podle configu ----
if (!empty($config['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');   // uživateli chyby nevypisujeme
    ini_set('log_errors', '1');
}

// ---- baseUrl (stejná logika jako hlavní web) ----
// Z /admin/... odvodíme kořen webu. SCRIPT_NAME je např. /admin/players/index.php
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
// odřízneme vše od /admin dál -> zůstane webový root
$pos = strpos($scriptDir, '/admin');
$baseUrl = ($pos !== false ? substr($scriptDir, 0, $pos) : $scriptDir);
$baseUrl = rtrim($baseUrl, '/') . '/';
if ($baseUrl === '//') {
    $baseUrl = '/';
}

// ---- bezpečná session ----
if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $baseUrl,
        'httponly' => true,          // JS se k session cookie nedostane
        'secure' => $https,          // přes HTTPS jen zabezpečeně
        'samesite' => 'Lax',         // ochrana proti CSRF z cizích stránek
    ]);
    session_name('scm_admin');
    session_start();
}

// ---- načtení helperů ----
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

// ---- připojení k databázi (a případná inicializace schématu) ----
$db = scm_db($config);
