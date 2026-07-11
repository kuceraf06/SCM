<?php
$pageTitle = 'SCM | Players';
$bodyClass = 'players-body';
$pageCss   = 'players';

/**
 * Players split by category (youngest to oldest). The boxes on top jump to
 * a category (anchors). Players have no contact, so on the right we show
 * category and position instead.
 *
 * 'type' drives the colour (box + title bar):
 *   'boys'  -> black
 *   'girls' -> green (categories ending in "s" or women)
 *
 * Empty categories (no players yet) still render. Photos aren't available -
 * everyone uses the placeholder team-mem-none.jpg.
 */
$noPhoto = 'images/common/team-mem-none.jpg';

// Player categories are read from the database (managed in the admin panel).
require_once __DIR__ . '/../../lib/data.php';
require_once __DIR__ . '/../../../admin/lib/categories.php';

// English labels for the two named categories
$enLabels = ['MUŽI' => 'MEN', 'ŽENY' => 'WOMEN'];

$byCat = scm_players_by_category();
$categories = [];
foreach (scm_player_categories() as $code => $meta) {
    $anchor = strtolower($code);
    $anchor = str_replace(['ž','ý'], ['z','y'], $anchor);
    $list = [];
    foreach ($byCat[$code] ?? [] as $row) {
        $list[] = [
            'name'  => $row['name'],
            'pos'   => $row['position'],
            'photo' => $row['photo'] ?: $noPhoto,
        ];
    }
    $categories[$anchor] = [
        'label'   => $enLabels[$code] ?? $meta['label'],
        'type'    => $meta['type'],
        'players' => $list,
    ];
}
$catLabel = 'Category';
$posLabel = 'Position';
$emptyText = 'No players yet.';
?>
<main class="roster-page page-width">
            <div class="heading">
                <h1>PLAYERS</h1>
            </div>

            <!-- jump boxes: click scrolls to a category -->
            <nav class="category-nav">
                <?php foreach ($categories as $id => $cat): ?>
                    <a href="#cat-<?= $id ?>" class="category-btn category-btn--<?= $cat['type'] ?>"><?= $cat['label'] ?></a>
                <?php endforeach; ?>
            </nav>

            <?php foreach ($categories as $id => $cat): ?>
                <h2 class="category-title category-title--<?= $cat['type'] ?>" id="cat-<?= $id ?>"><?= $cat['label'] ?></h2>
                <?php if (empty($cat['players'])): ?>
                    <p class="category-empty"><?= $emptyText ?></p>
                <?php else: ?>
                    <div class="player-list">
                        <?php foreach ($cat['players'] as $p): ?>
                            <div class="player-card">
                                <div class="player-photo">
                                    <img src="<?= asset($p['photo']) ?>" alt="<?= $p['name'] ?>">
                                </div>
                                <div class="player-info">
                                    <h3><?= $p['name'] ?></h3>
                                </div>
                                <div class="player-meta">
                                    <span><?= $posLabel ?>: <?= $p['pos'] ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </main>
