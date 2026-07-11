<?php
/**
 * Správa rozpisů (tréninků) - léto / zima.
 * -----------------------------------------------------------------------------
 * Podle přání: den, čas a místo se zadávají POUZE česky a do angličtiny se
 * přeloží automaticky (slovník). Popis tréninku má DVĚ samostatná pole -
 * jedno české, jedno anglické. Přeložené hodnoty se ukládají do title_en/
 * day/time/place při uložení, aby je web mohl rovnou použít.
 */

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/translate.php';
require_once __DIR__ . '/../lib/photo_field.php';
auth_require($config);

// sezóna z URL (summer/winter), výchozí summer
$season = ($_GET['season'] ?? 'summer') === 'winter' ? 'winter' : 'summer';
$seasonLabel = $season === 'winter' ? 'zimní' : 'letní';
$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// ---------- POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $formAction = $_POST['form_action'] ?? '';
    $postSeason = ($_POST['season'] ?? 'summer') === 'winter' ? 'winter' : 'summer';

    if ($formAction === 'delete') {
        $id = post_int('id');
        $stmt = $db->prepare('SELECT photo FROM trainings WHERE id = ?');
        $stmt->execute([$id]);
        $photo = $stmt->fetchColumn();
        if ($photo !== false) {
            $db->prepare('DELETE FROM trainings WHERE id = ?')->execute([$id]);
            delete_uploaded_image($photo ?: null);
            flash_set('success', 'Trénink byl odebrán.');
        }
        redirect_admin('schedule/?season=' . $postSeason);
    }

    if ($formAction === 'save') {
        $id = post_int('id');
        $titleCs = clean($_POST['title_cs'] ?? '');
        $day = clean($_POST['day'] ?? '');
        $time = clean($_POST['time'] ?? '');
        $place = clean($_POST['place'] ?? '');
        $descCs = clean($_POST['desc_cs'] ?? '');
        $descEn = clean($_POST['desc_en'] ?? '');

        $errors = [];
        if ($titleCs === '') {
            $errors[] = 'Vyplňte název tréninku (např. místo).';
        }

        // AUTOMATICKÝ PŘEKLAD dne, času, místa a názvu do angličtiny
        $dayEn = scm_translate_day($day);
        $timeEn = scm_translate_time($time);
        $placeEn = scm_translate_place($place);
        $titleEn = scm_translate_place($titleCs);   // název bývá místo -> přeložit stejně

        $photoPath = null;
        if (empty($errors)) {
            try {
                $photoPath = handle_image_upload('photo');
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!empty($errors)) {
            foreach ($errors as $er) {
                flash_set('error', $er);
            }
            redirect_admin('schedule/?season=' . $postSeason . '&action=' . ($id ? 'edit&id=' . $id : 'add'));
        }

        if ($id) {
            if ($photoPath !== null) {
                $old = $db->prepare('SELECT photo FROM trainings WHERE id = ?');
                $old->execute([$id]);
                $oldPhoto = $old->fetchColumn();
                $db->prepare('UPDATE trainings SET season=?, title_cs=?, title_en=?, day=?, time=?, place=?, desc_cs=?, desc_en=?, photo=? WHERE id=?')
                   ->execute([$postSeason, $titleCs, $titleEn, $day, $time, $place, $descCs, $descEn, $photoPath, $id]);
                delete_uploaded_image($oldPhoto ?: null);
            } else {
                $db->prepare('UPDATE trainings SET season=?, title_cs=?, title_en=?, day=?, time=?, place=?, desc_cs=?, desc_en=? WHERE id=?')
                   ->execute([$postSeason, $titleCs, $titleEn, $day, $time, $place, $descCs, $descEn, $id]);
            }
            flash_set('success', 'Změny byly uloženy. Anglická verze se přeložila automaticky.');
        } else {
            $db->prepare('INSERT INTO trainings (season, title_cs, title_en, day, time, place, desc_cs, desc_en, photo) VALUES (?,?,?,?,?,?,?,?,?)')
               ->execute([$postSeason, $titleCs, $titleEn, $day, $time, $place, $descCs, $descEn, $photoPath ?? '']);
            flash_set('success', 'Trénink byl přidán. Anglická verze se přeložila automaticky.');
        }
        redirect_admin('schedule/?season=' . $postSeason);
    }
}

// ---------- FORMULÁŘ ----------
if ($action === 'add' || $action === 'edit') {
    $t = ['id' => 0, 'season' => $season, 'title_cs' => '', 'title_en' => '', 'day' => '',
          'time' => '', 'place' => '', 'desc_cs' => '', 'desc_en' => '', 'photo' => ''];
    if ($action === 'edit' && $editId) {
        $stmt = $db->prepare('SELECT * FROM trainings WHERE id = ?');
        $stmt->execute([$editId]);
        $found = $stmt->fetch();
        if (!$found) {
            flash_set('error', 'Trénink nebyl nalezen.');
            redirect_admin('schedule/?season=' . $season);
        }
        $t = $found;
        $season = $t['season'];
    }

    $pageTitle = ($action === 'add' ? 'Přidat trénink' : 'Upravit trénink');
    $activeNav = $season;
    require __DIR__ . '/../lib/layout_top.php';
    ?>
    <a href="<?= e($baseUrl) ?>admin/schedule/?season=<?= e($season) ?>" class="back-link"><i class='bx bx-arrow-back'></i> Zpět na <?= e($seasonLabel) ?> rozpis</a>
    <h1><?= e($pageTitle) ?> (<?= e($seasonLabel) ?> rozpis)</h1>
    <p class="page-intro">Den, čas a místo zadávejte česky - do angličtiny se přeloží automaticky. Popis tréninku má vlastní pole pro češtinu i angličtinu.</p>

    <form method="POST" enctype="multipart/form-data" class="card">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="save">
        <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
        <input type="hidden" name="season" value="<?= e($season) ?>">

        <div class="form-row">
            <label>Název tréninku <span class="hint">(nadpis karty, obvykle místo - např. „Hala Brjanská")</span></label>
            <input type="text" name="title_cs" value="<?= e($t['title_cs']) ?>" required>
        </div>

        <div class="form-two">
            <div class="form-row">
                <label>Den <span class="hint">(např. PÁ, NE)</span></label>
                <input type="text" name="day" value="<?= e($t['day']) ?>" placeholder="PÁ">
            </div>
            <div class="form-row">
                <label>Čas <span class="hint">(např. 16:00 - 18:00)</span></label>
                <input type="text" name="time" value="<?= e($t['time']) ?>" placeholder="16:00 - 18:00">
            </div>
        </div>

        <div class="form-row">
            <label>Místo <span class="hint">(česky, např. „hala Brjanská")</span></label>
            <input type="text" name="place" value="<?= e($t['place']) ?>">
        </div>

        <div class="form-row">
            <label>Popis tréninku <span class="hint">(česky)</span></label>
            <textarea name="desc_cs"><?= e($t['desc_cs']) ?></textarea>
        </div>

        <div class="form-row">
            <label>Popis tréninku <span class="hint">(anglicky)</span></label>
            <textarea name="desc_en"><?= e($t['desc_en']) ?></textarea>
        </div>

        <div class="form-row">
            <label>Fotka <span class="hint">(volitelné, JPG/PNG/WebP, max 5 MB)</span></label>
            <?php scm_photo_upload_field($baseUrl, (string)$t['photo'], 'square'); ?>
        </div>

        <button type="submit" class="saveButton"><i class='bx bx-save'></i> Uložit</button>
    </form>
    <?php scm_photo_upload_script(); ?>
    <?php
    require __DIR__ . '/../lib/layout_bottom.php';
    exit;
}

// ---------- SEZNAM ----------
$stmt = $db->prepare('SELECT * FROM trainings WHERE season = ? ORDER BY sort_order, id');
$stmt->execute([$season]);
$rows = $stmt->fetchAll();

$pageTitle = 'Rozpis ' . $seasonLabel;
$activeNav = $season;
require __DIR__ . '/../lib/layout_top.php';
?>
<div class="toolbar">
    <div>
        <h1><?= e(ucfirst($seasonLabel)) ?> rozpis</h1>
        <p class="page-intro" style="margin:0"><?= e(scm_plural(count($rows), 'trénink', 'tréninky', 'tréninků', 'žádné tréninky')) ?></p>
    </div>
    <a href="<?= e($baseUrl) ?>admin/schedule/?season=<?= e($season) ?>&action=add" class="saveButton"><i class='bx bx-plus'></i> Přidat trénink</a>
</div>

<div class="toolbar" style="margin-top:-10px">
    <div style="display:flex;gap:10px">
        <a href="<?= e($baseUrl) ?>admin/schedule/?season=summer" class="btn-secondary" style="<?= $season === 'summer' ? 'border-color:var(--scm-green);color:var(--scm-green)' : '' ?>"><i class='bx bxs-sun'></i> Léto</a>
        <a href="<?= e($baseUrl) ?>admin/schedule/?season=winter" class="btn-secondary" style="<?= $season === 'winter' ? 'border-color:var(--scm-green);color:var(--scm-green)' : '' ?>"><i class='bx bx-cloud-snow'></i> Zima</a>
    </div>
</div>

<?php if (empty($rows)): ?>
    <p class="empty-note">Zatím žádné tréninky v <?= e($seasonLabel) ?>m rozpisu.</p>
<?php else: ?>
    <?php foreach ($rows as $t): ?>
        <div class="record">
            <div class="record-photo" style="border-radius:10px">
                <img src="<?= e($t['photo'] ? $baseUrl . 'public/' . $t['photo'] : $baseUrl . 'public/images/common/team-mem-none.jpg') ?>" alt="">
            </div>
            <div class="record-body">
                <strong><?= e($t['title_cs'] ?: '(bez názvu)') ?></strong>
                <span>
                    <?= e(trim($t['day'] . ' ' . $t['time'])) ?><?= $t['place'] ? ', ' . e($t['place']) : '' ?>
                </span>
                <?php if ($t['day'] || $t['time'] || $t['place']): ?>
                    <span style="color:var(--scm-green)">
                        EN: <?= e(trim(scm_translate_day($t['day']) . ' ' . scm_translate_time($t['time']))) ?><?= $t['place'] ? ', ' . e(scm_translate_place($t['place'])) : '' ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="record-actions">
                <a href="<?= e($baseUrl) ?>admin/schedule/?season=<?= e($season) ?>&action=edit&id=<?= (int)$t['id'] ?>" class="icon-btn edit" title="Upravit"><i class='bx bx-edit'></i></a>
                <form method="POST" onsubmit="return confirm('Opravdu odebrat tento trénink?')" style="display:inline">
                    <?= csrf_field() ?>
                    <input type="hidden" name="form_action" value="delete">
                    <input type="hidden" name="season" value="<?= e($season) ?>">
                    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                    <button type="submit" class="icon-btn delete" title="Odebrat"><i class='bx bx-trash'></i></button>
                </form>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/../lib/layout_bottom.php'; ?>
