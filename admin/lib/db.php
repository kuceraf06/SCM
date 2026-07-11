<?php
/**
 * Databázová vrstva (SQLite přes PDO).
 * -----------------------------------------------------------------------------
 * Klíčová bezpečnostní vlastnost: databáze leží MIMO web root (ve storage/,
 * ne v public/), takže ji nelze stáhnout přes URL. Na produkci lze v configu
 * nastavit absolutní cestu úplně mimo git checkout ('db_path').
 *
 * Schéma se vytvoří automaticky při prvním připojení (CREATE TABLE IF NOT
 * EXISTS), takže není potřeba ručně nahrávat .sqlite soubor.
 */

declare(strict_types=1);

/**
 * Vrátí připojení k databázi. Cesta:
 *   1) config 'db_path', pokud je vyplněná (ideálně absolutní, mimo checkout)
 *   2) fallback: storage/db/scm.sqlite (mimo public/, ale uvnitř projektu)
 */
function scm_db(array $config): PDO
{
    static $conn = null;
    if ($conn instanceof PDO) {
        return $conn;
    }

    $dbPath = trim((string)($config['db_path'] ?? ''));
    if ($dbPath === '') {
        $dbPath = SCM_ROOT . '/storage/db/scm.sqlite';
    }

    // adresář musí existovat a být zapisovatelný
    $dir = dirname($dbPath);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
        http_response_code(500);
        exit('SQLite PDO driver (pdo_sqlite) není v PHP dostupný.');
    }

    try {
        $conn = new PDO('sqlite:' . $dbPath);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $conn->exec('PRAGMA foreign_keys = ON');   // vynutit cizí klíče
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('SCM DB: ' . $e->getMessage());
        exit('Chyba připojení k databázi.');
    }

    scm_db_init_schema($conn);
    return $conn;
}

/**
 * Vytvoří tabulky, pokud ještě neexistují, a naplní výchozí data.
 */
function scm_db_init_schema(PDO $db): void
{
    $db->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS players (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            name        TEXT NOT NULL,
            category    TEXT NOT NULL,
            position    TEXT NOT NULL DEFAULT '',
            photo       TEXT NOT NULL DEFAULT '',
            sort_order  INTEGER NOT NULL DEFAULT 0,
            created_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    SQL);

    $db->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS coaches (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            name        TEXT NOT NULL,
            role_cs     TEXT NOT NULL DEFAULT '',
            role_en     TEXT NOT NULL DEFAULT '',
            license_cs  TEXT NOT NULL DEFAULT '',
            license_en  TEXT NOT NULL DEFAULT '',
            phone       TEXT NOT NULL DEFAULT '',
            email       TEXT NOT NULL DEFAULT '',
            photo       TEXT NOT NULL DEFAULT '',
            is_external INTEGER NOT NULL DEFAULT 0,
            sort_order  INTEGER NOT NULL DEFAULT 0,
            created_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    SQL);

    $db->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS trainings (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            season      TEXT NOT NULL,
            title_cs    TEXT NOT NULL DEFAULT '',
            title_en    TEXT NOT NULL DEFAULT '',
            day         TEXT NOT NULL DEFAULT '',
            time        TEXT NOT NULL DEFAULT '',
            place       TEXT NOT NULL DEFAULT '',
            desc_cs     TEXT NOT NULL DEFAULT '',
            desc_en     TEXT NOT NULL DEFAULT '',
            photo       TEXT NOT NULL DEFAULT '',
            sort_order  INTEGER NOT NULL DEFAULT 0,
            created_at  TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    SQL);

    // Automatické naplnění výchozími daty při PRVNÍM spuštění (prázdné tabulky).
    // Díky tomu stačí projekt nahrát na server a otevřít web - není potřeba
    // spouštět nic z příkazové řádky. Na serveru s živými daty se přeskočí
    // (scm_seed_data plní jen prázdné tabulky), takže data se nikdy nepřepíšou.
    if (is_file(__DIR__ . '/seed_data.php')) {
        require_once __DIR__ . '/seed_data.php';
        try {
            scm_seed_data($db);
        } catch (\Throwable $e) {
            // seed je jen pohodlí - když selže, web musí běžet dál (prázdný)
            error_log('SCM seed: ' . $e->getMessage());
        }
    }
}
