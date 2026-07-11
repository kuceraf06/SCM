<?php
$pageTitle = 'SCM | Domovská Stránka';
$bodyClass = 'homepage-body';

$currentYear = date('Y');

$yearMax = $currentYear - 10;
$yearMin = $currentYear - 23;
?>
<main class="hero">
            <h1>SPORTOVNÍ CENTRUM MLÁDEŽE</h1>
            <br>
            <i>Pro ročníky <?= $yearMin ?>-<?= $yearMax ?></i>
            <div class="hero-buttons">
                <a href="<?= url('contact') ?>"><button type="button"><span></span>KONTAKT</button></a>
                <a href="<?= url('about') ?>"><button type="button"><span></span>O NÁS</button></a>
            </div>
    </main>
