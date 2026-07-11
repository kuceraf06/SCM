<?php
/**
 * SCM - front controller
 * Čeština = výchozí jazyk (bez prefixu v URL), angličtina = /en/...
 * Příklady:  /            -> cs/home
 *            /about       -> cs/about
 *            /en          -> en/home
 *            /en/about    -> en/about
 */

$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/') . '/';

$route = $_GET['route'] ?? '';
$route = trim($route, '/');

// ---- detekce jazyka z prefixu /en ----
$lang = 'cs';
if ($route === 'en' || strpos($route, 'en/') === 0) {
    $lang  = 'en';
    $route = trim(substr($route, 2), '/');
}

// ---- mapa: slug v URL -> soubor pohledu ----
$routes = [
    ''                => 'home',
    'about'           => 'about',
    'staff'           => 'staff',
    'players'         => 'players',
    'partners'        => 'partners',
    'schedule-summer' => 'summer',
    'schedule-winter' => 'winter',
    'contact'         => 'contact',
];

if (array_key_exists($route, $routes)) {
    $view = $routes[$route];
} else {
    http_response_code(404);
    $view = '404';
}

// =====================  POMOCNÉ FUNKCE PRO ODKAZY  =====================

/** Odkaz na stránku v aktuálním jazyce (do navbaru, tlačítek atd.) */
function url(string $slug = ''): string
{
    global $baseUrl, $lang;
    $prefix = ($lang === 'en') ? 'en/' : '';
    return $baseUrl . $prefix . $slug;
}

/** Odkaz na stejnou stránku v druhém jazyce (přepínač CZ/EN) */
function switchUrl(): string
{
    global $baseUrl, $lang, $route;
    if ($lang === 'en') {
        return $baseUrl . $route;                      // en -> cs (bez prefixu)
    }
    return $baseUrl . 'en' . ($route !== '' ? '/' . $route : ''); // cs -> en
}

/** Odkaz na statický soubor v public/ (css, js, obrázky) */
function asset(string $path): string
{
    global $baseUrl;
    return $baseUrl . 'public/' . ltrim($path, '/');
}

// =====================  VYKRESLENÍ  =====================

ob_start();
require __DIR__ . "/app/views/$lang/$view.php";   // pohled si nastaví $pageTitle a $bodyClass
$content = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<?php require __DIR__ . '/app/views/layout/head.php'; ?>
<body class="<?= $bodyClass ?? '' ?>">
    <?php require __DIR__ . '/app/views/layout/header.php'; ?>
    <?= $content ?>
    <?php require __DIR__ . '/app/views/layout/footer.php'; ?>

    <script src="<?= asset('js/main.js') ?>"></script>
</body>
</html>
