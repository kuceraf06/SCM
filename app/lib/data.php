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

/**
 * Srozumitelná chybová stránka, když se nepodaří otevřít databázi.
 *
 * Proč to tu je: bez tohohle by se web vykreslil normálně, jen úplně prázdný,
 * a chyba by skončila jen v logu serveru. Ten, kdo web nasazoval, by neměl
 * šanci poznat, co je špatně. Skoro vždy jde o jedinou věc: webový server
 * nemá právo zápisu do složky, kde má databáze vzniknout.
 */
function scm_db_error_page(): void
{
    $root = dirname(__DIR__, 2);

    $slozky = [
        'storage/db'            => 'sem se ukládá databáze (bez ní web nefunguje)',
        'storage/logs'          => 'sem se zapisují chyby',
        'public/images/uploads' => 'sem se ukládají obrázky nahrané v administraci',
    ];

    $radky = '';
    foreach ($slozky as $cesta => $popis) {
        $plna     = $root . '/' . $cesta;
        $existuje = is_dir($plna);
        $zapis    = $existuje && is_writable($plna);
        $stav     = !$existuje ? 'složka neexistuje' : ($zapis ? 'v pořádku' : 'NELZE ZAPISOVAT');
        $trida    = $zapis ? 'ok' : 'bad';
        $radky   .= "<tr class=\"$trida\"><td><code>$cesta/</code></td><td>$stav</td><td>$popis</td></tr>";
    }

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }

    echo <<<HTML
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Web se nepodařilo spustit</title>
    <style>
        body { font-family: system-ui, "Segoe UI", Arial, sans-serif; background: #f4f4f4; color: #222;
               margin: 0; padding: 40px 20px; line-height: 1.6; }
        .box { max-width: 720px; margin: 0 auto; background: #fff; border-radius: 12px;
               padding: 36px 34px; box-shadow: 0 6px 22px rgba(0,0,0,.10); }
        h1 { font-size: 24px; margin: 0 0 6px; }
        .lead { color: #666; margin: 0 0 26px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 26px; font-size: 14px; }
        td { padding: 10px 12px; border-bottom: 1px solid #eee; vertical-align: top; }
        tr.bad td { background: #fdecea; }
        tr.bad td:nth-child(2) { color: #9d281c; font-weight: 700; }
        tr.ok td:nth-child(2) { color: #1e7d32; }
        code { background: #f2f2f2; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
        .fix { background: #f3f9e8; border: 1px solid #d5e8b5; border-radius: 8px; padding: 16px 18px; }
        .fix h2 { font-size: 16px; margin: 0 0 10px; }
        pre { background: #2b2b2b; color: #eee; padding: 12px 14px; border-radius: 6px;
              overflow-x: auto; font-size: 13px; margin: 8px 0 0; }
    </style>
</head>
<body>
    <div class="box">
        <h1>Web se nepodařilo spustit</h1>
        <p class="lead">Nepodařilo se otevřít ani vytvořit databázi. Web proto nemá odkud vzít obsah.</p>

        <table>$radky</table>

        <div class="fix">
            <h2>Jak to spravit</h2>
            <p style="margin:0">Webový server potřebuje právo zápisu do označených složek. Na serveru spusťte:</p>
            <pre>chmod -R 775 storage public/images/uploads
chown -R http:http storage public/images/uploads</pre>
            <p style="margin:10px 0 0">Uživatel <code>http</code> platí pro Synology; jinde bývá
            <code>www-data</code>. Pak stránku načtěte znovu – databáze se sama vytvoří i s obsahem.</p>
        </div>
    </div>
</body>
</html>
HTML;
    exit;
}
