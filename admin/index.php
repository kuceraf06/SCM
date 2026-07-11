<?php
/** Rozcestník administrace. */
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/lib/auth.php';
auth_require($config);

// počty záznamů pro přehled
$counts = [
    'players' => (int)$db->query('SELECT COUNT(*) FROM players')->fetchColumn(),
    'coaches' => (int)$db->query('SELECT COUNT(*) FROM coaches')->fetchColumn(),
    'summer'  => (int)$db->query('SELECT COUNT(*) FROM trainings WHERE season = "summer"')->fetchColumn(),
    'winter'  => (int)$db->query('SELECT COUNT(*) FROM trainings WHERE season = "winter"')->fetchColumn(),
];

$pageTitle = 'Přehled';
$activeNav = 'dashboard';
require __DIR__ . '/lib/layout_top.php';
?>
<h1>Administraci SCM</h1>
<p class="page-intro">Správa soupisek hráčů a trenérů a rozpisů tréninků.</p>

<div class="addBox">
    <a href="<?= e($baseUrl) ?>admin/players/" class="childBox">
        <i class='bx bxs-user'></i>
        <p>Hráči <span style="color:var(--scm-muted)">(<?= e(scm_plural($counts['players'], 'hráč', 'hráči', 'hráčů', 'žádní hráči')) ?>)</span></p>
    </a>
    <a href="<?= e($baseUrl) ?>admin/staff/" class="childBox">
        <i class='bx bxs-user-voice'></i>
        <p>Trenéři <span style="color:var(--scm-muted)">(<?= e(scm_plural($counts['coaches'], 'trenér', 'trenéři', 'trenérů', 'žádní trenéři')) ?>)</span></p>
    </a>
    <a href="<?= e($baseUrl) ?>admin/schedule/?season=summer" class="childBox">
        <i class='bx bxs-sun'></i>
        <p>Rozpis léto <span style="color:var(--scm-muted)">(<?= e(scm_plural($counts['summer'], 'trénink', 'tréninky', 'tréninků', 'žádné tréninky')) ?>)</span></p>
    </a>
    <a href="<?= e($baseUrl) ?>admin/schedule/?season=winter" class="childBox">
        <i class='bx bx-cloud-snow'></i>
        <p>Rozpis zima <span style="color:var(--scm-muted)">(<?= e(scm_plural($counts['winter'], 'trénink', 'tréninky', 'tréninků', 'žádné tréninky')) ?>)</span></p>
    </a>
</div>
<?php require __DIR__ . '/lib/layout_bottom.php'; ?>
