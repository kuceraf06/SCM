<?php
$pageTitle = 'SCM | Home';
$bodyClass = 'homepage-body';

$currentYear = date('Y');

$yearMax = $currentYear - 10;
$yearMin = $currentYear - 23;
?>
<main class="hero">
                <h1>SPORT YOUTH CENTER</h1>
                <br>
                <i>For annuals <?= $yearMin ?>-<?= $yearMax ?></i>
                <div class="hero-buttons">
                    <a href="<?= url('contact') ?>"><button type="button"><span></span>CONTACT</button></a>
                    <a href="<?= url('about') ?>"><button type="button"><span></span>ABOUT US</button></a>
                </div>
        </main>
