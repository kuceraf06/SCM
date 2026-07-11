<?php
$pageTitle = 'SCM | Staff';
$bodyClass = 'staff-body';
$pageCss   = 'staff';

/**
 * Coaches split into internal (SCM) and external. Data lives in the arrays
 * below - adding/removing a coach is just an array edit. Empty license or
 * contact simply won't render in the card.
 */
$noPhoto = 'images/common/team-mem-none.jpg';

// Coaches are read from the database (managed in the admin panel).
require_once __DIR__ . '/../../lib/data.php';

/** Map a coach DB row to the array renderCoach expects (English labels). */
function scm_coach_row(array $r, string $noPhoto): array {
    return [
        'name'    => $r['name'],
        'role'    => $r['role_en'] !== '' ? $r['role_en'] : $r['role_cs'],
        'license' => $r['license_en'] !== '' ? $r['license_en'] : $r['license_cs'],
        'phone'   => $r['phone'],
        'email'   => $r['email'],
        'photo'   => $r['photo'] ?: $noPhoto,
    ];
}

$internalCoaches = array_map(fn($r) => scm_coach_row($r, $noPhoto), scm_coaches(false));
$externalCoaches = array_map(fn($r) => scm_coach_row($r, $noPhoto), scm_coaches(true));

// Renders one horizontal coach card: photo left, name/role/license middle,
// contact (phone + e-mail) right
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
                <h1>OUR STAFF</h1>
            </div>
            <h2 class="staff-section-title staff-section-title--internal">SCM – Coaches</h2>
            <div class="coach-list">
                <?php foreach ($internalCoaches as $coach) renderCoach($coach); ?>
            </div>

            <h2 class="staff-section-title staff-section-title--external">SCM – External Coaches</h2>
            <div class="coach-list">
                <?php foreach ($externalCoaches as $coach) renderCoach($coach); ?>
            </div>
        </main>
