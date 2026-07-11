<?php
/**
 * Datová vrstva pro VEŘEJNÝ web.
 * -----------------------------------------------------------------------------
 * Pohledy (players.php, staff.php, summer.php, winter.php) tímto čtou data,
 * která se spravují v administraci, z SQLite databáze. Když databáze ještě
 * neexistuje nebo je prázdná, funkce vrátí prázdná pole a stránka se nerozbije.
 *
 * Připojení je jen pro čtení a záměrně samostatné (lehké), aby veřejný web
 * nemusel načítat celý admin bootstrap ani session.
 */

declare(strict_types=1);

/** Lehké read-only připojení ke stejné SQLite databázi jako administrace. */
function scm_public_db(): ?PDO
{
    static $conn = null;
    static $tried = false;
    if ($tried) {
        return $conn;
    }
    $tried = true;

    // stejná logika cesty jako v admin/lib/db.php
    $configFile = __DIR__ . '/../../config/config.php';
    $dbPath = '';
    if (is_file($configFile)) {
        $cfg = require $configFile;
        $dbPath = trim((string)($cfg['db_path'] ?? ''));
    }
    if ($dbPath === '') {
        $dbPath = dirname(__DIR__, 2) . '/storage/db/scm.sqlite';
    }

    if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
        return null;
    }

    // Když databáze ještě neexistuje (např. čerstvě nahraný projekt na server),
    // vytvoříme ji a naplníme výchozími daty - stejně jako administrace. Díky
    // tomu web funguje hned po nahrání, i když se do adminu nikdo nepřihlásil.
    $needsInit = !is_file($dbPath);
    if ($needsInit) {
        $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    try {
        $conn = new PDO('sqlite:' . $dbPath);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // pokud databáze právě vznikla, vytvoříme schéma a naplníme data
        if ($needsInit) {
            $dbLib = __DIR__ . '/../../admin/lib/db.php';
            if (is_file($dbLib)) {
                require_once $dbLib;
                if (function_exists('scm_db_init_schema')) {
                    scm_db_init_schema($conn);   // vytvoří tabulky + spustí seed
                }
            }
        }
    } catch (PDOException $e) {
        error_log('SCM public DB: ' . $e->getMessage());
        $conn = null;
    }
    return $conn;
}

/** Bezpečně spustí dotaz a vrátí řádky (prázdné pole při chybě). */
function scm_fetch_all(string $sql, array $params = []): array
{
    $db = scm_public_db();
    if ($db === null) {
        return [];
    }
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('SCM public query: ' . $e->getMessage());
        return [];
    }
}

/** Hráči seskupení podle kategorie (klíč = kód kategorie z číselníku). */
function scm_players_by_category(): array
{
    $rows = scm_fetch_all('SELECT name, category, position, photo FROM players ORDER BY name COLLATE NOCASE');
    $out = [];
    foreach ($rows as $r) {
        $out[$r['category']][] = $r;
    }
    return $out;
}

/** Trenéři: $external = false -> interní, true -> externí. */
function scm_coaches(bool $external): array
{
    return scm_fetch_all(
        'SELECT * FROM coaches WHERE is_external = ? ORDER BY sort_order, id',
        [$external ? 1 : 0]
    );
}

/** Tréninky pro danou sezónu ('summer'|'winter'). */
function scm_trainings(string $season): array
{
    return scm_fetch_all(
        'SELECT * FROM trainings WHERE season = ? ORDER BY sort_order, id',
        [$season]
    );
}
