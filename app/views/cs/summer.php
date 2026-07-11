<?php
$pageTitle = 'SCM | Letní Rozpis';
$bodyClass = 'schedule-body';
$pageCss   = 'schedule';

// Tréninky se čtou z databáze (spravují se v administraci).
require_once __DIR__ . '/../../lib/data.php';
$trainings = scm_trainings('summer');
?>
<main class="schedule-page page-width">
            <div class="heading">
                <h1>LETNÍ ROZPIS</h1>
                <p>V letní sezóně tréninky probíhají od dubna do října (s výjimkou letních prázdnin).</p>
            </div>
            <div class="practice-list">
                <?php if (empty($trainings)): ?>
                    <p class="category-empty">Rozpis se připravuje.</p>
                <?php else: foreach ($trainings as $t): ?>
                    <div class="practice-block">
                        <div class="practice-photo">
                            <h2><?= htmlspecialchars($t['title_cs'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <?php if (!empty($t['photo'])): ?>
                                <img src="<?= asset($t['photo']) ?>" alt="<?= htmlspecialchars($t['title_cs'], ENT_QUOTES, 'UTF-8') ?>">
                            <?php endif; ?>
                        </div>
                        <div class="practice-text">
                            <strong><?= htmlspecialchars(trim($t['day'] . ' ' . $t['time']), ENT_QUOTES, 'UTF-8') ?><?= $t['place'] ? ', ' . htmlspecialchars($t['place'], ENT_QUOTES, 'UTF-8') : '' ?></strong>
                            <?php if (!empty($t['desc_cs'])): ?>
                                <p><?= nl2br(htmlspecialchars($t['desc_cs'], ENT_QUOTES, 'UTF-8')) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </main>
