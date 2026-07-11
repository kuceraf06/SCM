<?php
/**
 * Sdílený layout administrace (hlavička + sidebar).
 * -----------------------------------------------------------------------------
 * Nahrazuje copy-paste hlavičky a menu z každého souboru staré administrace.
 * Použití v každé chráněné stránce:
 *
 *     $pageTitle = 'Hráči';
 *     $activeNav = 'players';
 *     require __DIR__ . '/../lib/layout_top.php';
 *     ... obsah ...
 *     require __DIR__ . '/../lib/layout_bottom.php';
 */

if (!isset($baseUrl)) {
    exit;
}
$pageTitle = $pageTitle ?? 'Administrace';
$activeNav = $activeNav ?? '';

/** Pomůcka: vrátí "active" pro aktivní položku menu. */
function nav_active(string $key, string $current): string
{
    return $key === $current ? ' class="active"' : '';
}
?><!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCM | Admin<?= $pageTitle ? ' - ' . e($pageTitle) : '' ?></title>
    <link rel="icon" type="image/png" href="<?= e($baseUrl) ?>public/images/common/favicon-32x32.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="<?= e($baseUrl) ?>admin/assets/admin.css">
</head>
<body>
<button class="menu-toggle" onclick="toggleSidebar()" aria-label="Menu"><i class="bx bx-menu"></i></button>
<nav class="sidebar hidden" id="sidebar">
    <button class="close-btn" onclick="toggleSidebar()" aria-label="Zavřít"><i class="bx bx-x"></i></button>
    <div class="side-header">
        <a href="<?= e($baseUrl) ?>admin/"><img src="<?= e($baseUrl) ?>public/images/common/SCM.png" alt="SCM logo"></a>
        <h2>Admin SCM</h2>
    </div>
    <hr>
    <ul>
        <li><a href="<?= e($baseUrl) ?>admin/"<?= nav_active('dashboard', $activeNav) ?>><i class='bx bxs-dashboard'></i>Přehled</a></li>
        <span class="side-label">Soupisky</span>
        <li><a href="<?= e($baseUrl) ?>admin/players/"<?= nav_active('players', $activeNav) ?>><i class='bx bxs-user'></i>Hráči</a></li>
        <li><a href="<?= e($baseUrl) ?>admin/staff/"<?= nav_active('staff', $activeNav) ?>><i class='bx bxs-user-voice'></i>Trenéři</a></li>
        <span class="side-label">Rozpisy</span>
        <li><a href="<?= e($baseUrl) ?>admin/schedule/?season=summer"<?= nav_active('summer', $activeNav) ?>><i class='bx bxs-sun'></i>Rozpis léto</a></li>
        <li><a href="<?= e($baseUrl) ?>admin/schedule/?season=winter"<?= nav_active('winter', $activeNav) ?>><i class='bx bx-cloud-snow'></i>Rozpis zima</a></li>
        <span class="side-label">Účet</span>
        <li><a href="<?= e($baseUrl) ?>admin/account/"<?= nav_active('account', $activeNav) ?>><i class='bx bxs-key'></i>Změna hesla</a></li>
    </ul>
    <a href="<?= e($baseUrl) ?>admin/logout/" class="logout" onclick="confirmLogout(event)">
        <i class='bx bx-user-x'></i>Odhlásit se
    </a>
</nav>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('hidden');
    }
    function confirmLogout(event) {
        event.preventDefault();
        if (confirm('Opravdu se chcete odhlásit?')) {
            window.location.href = '<?= e($baseUrl) ?>admin/logout/';
        }
    }
    // zavřít sidebar po kliku mimo
    document.addEventListener('click', function (event) {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.querySelector('.menu-toggle');
        if (sidebar && !sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
            sidebar.classList.add('hidden');
        }
    });
</script>

<div class="content">
<?php
// vypsat flash zprávy (pokud jsou)
foreach (flash_get() as $f) {
    $cls = $f['type'] === 'error' ? 'flash-error' : 'flash-success';
    echo '<div class="flash ' . $cls . '">' . e($f['message']) . '</div>';
}
