<?php
$pageTitle = 'SCM | Trenéři';
$bodyClass = 'staff-body';
$pageCss   = 'staff';

/**
 * Trenéři jsou rozdělení na interní (SCM) a externí. Data jsou v polích
 * níže - přidání/odebrání trenéra = jen úprava pole. Prázdná licence nebo
 * kontakt se v kartě prostě nezobrazí.
 * Fotky, které nemáme, používají zástupný obrázek team-mem-none.jpg.
 */
$noPhoto = 'images/common/team-mem-none.jpg';

// Trenéři se čtou z databáze (spravují se v administraci).
require_once __DIR__ . '/../../lib/data.php';

/** Převede řádek trenéra z DB na pole pro renderCoach (české popisky). */
function scm_coach_row(array $r, string $noPhoto): array {
    return [
        'name'    => $r['name'],
        'role'    => $r['role_cs'],
        'license' => $r['license_cs'],
        'phone'   => $r['phone'],
        'email'   => $r['email'],
        'photo'   => $r['photo'] ?: $noPhoto,
    ];
}

$internalCoaches = array_map(fn($r) => scm_coach_row($r, $noPhoto), scm_coaches(false));
$externalCoaches = array_map(fn($r) => scm_coach_row($r, $noPhoto), scm_coaches(true));

// Vykreslí jednu vodorovnou kartu trenéra: fotka vlevo, jméno/role/licence
// uprostřed, kontakt (telefon + e-mail) vpravo
function renderCoach(array $c): void {
    $telHref = 'tel:+420' . preg_replace('/\s+/', '', $c['phone']);
    ?>
    <div class="coach-card">
        <div class="coach-photo">
            <img src="<?= asset($c['photo']) ?>" alt="<?= $c['name'] ?>">
        </div>
        <div class="coach-info">
            <h3><?= $c['name'] ?></h3>
            <p class="coach-role"><?= $c['role'] ?></p>
            <?php if ($c['license'] !== ''): ?>
                <p class="coach-license"><?= $c['license'] ?></p>
            <?php endif; ?>
        </div>
        <?php if ($c['phone'] !== '' || $c['email'] !== ''): ?>
            <div class="coach-contact">
                <?php if ($c['phone'] !== ''): ?>
                    <a href="<?= $telHref ?>"><?= $c['phone'] ?></a>
                <?php endif; ?>
                <?php if ($c['email'] !== ''): ?>
                    <a href="mailto:<?= $c['email'] ?>"><?= $c['email'] ?></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>
<main class="roster-page page-width">
            <div class="heading">
                <h1>TRENÉŘI</h1>
            </div>
            <h2 class="staff-section-title staff-section-title--internal">SCM – Trenéři</h2>
            <div class="coach-list">
                <?php foreach ($internalCoaches as $coach) renderCoach($coach); ?>
            </div>

            <h2 class="staff-section-title staff-section-title--external">SCM – Externí trenéři</h2>
            <div class="coach-list">
                <?php foreach ($externalCoaches as $coach) renderCoach($coach); ?>
            </div>
        </main>
