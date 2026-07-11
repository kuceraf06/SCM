<?php
$pageTitle = 'SCM | Summer Schedule';
$bodyClass = 'schedule-body';
$pageCss   = 'schedule';

// Trainings are read from the database (managed in the admin panel).
require_once __DIR__ . '/../../lib/data.php';
require_once __DIR__ . '/../../../admin/lib/translate.php';
$trainings = scm_trainings('summer');
?>
<main class="schedule-page page-width">
            <div class="heading">
                <h1>SUMMER SCHEDULE</h1>
                <p>In the summer season, trainings run from April to October (except for the summer holidays).</p>
            </div>
            <div class="practice-list">
                <?php if (empty($trainings)): ?>
                    <p class="category-empty">Schedule coming soon.</p>
                <?php else: foreach ($trainings as $t): ?>
                    <?php
                        $titleEn = $t['title_en'] !== '' ? $t['title_en'] : $t['title_cs'];
                        $dayEn = scm_translate_day($t['day']);
                        $timeEn = scm_translate_time($t['time']);
                        $placeEn = scm_translate_place($t['place']);
                    ?>
                    <div class="practice-block">
                        <div class="practice-photo">
                            <h2><?= htmlspecialchars($titleEn, ENT_QUOTES, 'UTF-8') ?></h2>
                            <?php if (!empty($t['photo'])): ?>
                                <img src="<?= asset($t['photo']) ?>" alt="<?= htmlspecialchars($titleEn, ENT_QUOTES, 'UTF-8') ?>">
                            <?php endif; ?>
                        </div>
                        <div class="practice-text">
                            <strong><?= htmlspecialchars(trim($dayEn . ' ' . $timeEn), ENT_QUOTES, 'UTF-8') ?><?= $placeEn ? ', ' . htmlspecialchars($placeEn, ENT_QUOTES, 'UTF-8') : '' ?></strong>
                            <?php if (!empty($t['desc_en'])): ?>
                                <p><?= nl2br(htmlspecialchars($t['desc_en'], ENT_QUOTES, 'UTF-8')) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </main>
