<?php
$pageTitle = 'SCM | Hráči';
$bodyClass = 'players-body';
$pageCss   = 'players';

/**
 * Hráči rozdělení podle kategorií (od nejmladší po nejstarší). Nahoře jsou
 * odkazové boxy, které skočí na danou kategorii (kotvy). Každý hráč nemá
 * kontakt, takže vpravo místo něj ukazujeme kategorii a pozici.
 *
 * 'type' určuje barvu (box i pruh nadpisu):
 *   'boys'  = kluci  -> černá
 *   'girls' = holky  -> zelená (kategorie s "s" na konci nebo ženy)
 *
 * Prázdná kategorie (zatím bez hráčů) se stejně zobrazí. Fotku nemáme,
 * všichni používají zástupný obrázek team-mem-none.jpg.
 */
$noPhoto = 'images/common/team-mem-none.jpg';

// Kategorie hráčů se čtou z databáze (spravují se v administraci).
// Číselník kategorií drží pořadí a barvu (boys/girls); hráči se doplní z DB.
require_once __DIR__ . '/../../lib/data.php';
require_once __DIR__ . '/../../../admin/lib/categories.php';

$byCat = scm_players_by_category();          // ['U11' => [ {name,position,photo}, ... ], ...]
$categories = [];
foreach (scm_player_categories() as $code => $meta) {
    $anchor = strtolower($code);             // kotva: u11, u11s, zeny...
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
        'label'   => $meta['label'],
        'type'    => $meta['type'],
        'players' => $list,
    ];
}
$catLabel = 'Kategorie';
$posLabel = 'Pozice';
$emptyText = 'Zatím žádní hráči.';
?>
<main class="roster-page page-width">
            <div class="heading">
                <h1>HRÁČI</h1>
            </div>

            <!-- odkazové boxy: klik skočí na danou kategorii -->
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
