<?php
/**
 * Správa hráčů - seznam, přidání, úprava, smazání.
 * Vše přes prepared statements, CSRF ochranu a bezpečný upload fotky.
 */

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/categories.php';
require_once __DIR__ . '/../lib/photo_field.php';
auth_require($config);

$categories = scm_player_categories();
$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// ---------- ZPRACOVÁNÍ FORMULÁŘŮ (POST) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $formAction = $_POST['form_action'] ?? '';

    // --- smazání ---
    if ($formAction === 'delete') {
        $id = post_int('id');
        $stmt = $db->prepare('SELECT photo FROM players WHERE id = ?');
        $stmt->execute([$id]);
        $photo = $stmt->fetchColumn();
        if ($photo !== false) {
            $db->prepare('DELETE FROM players WHERE id = ?')->execute([$id]);
            delete_uploaded_image($photo ?: null);
            flash_set('success', 'Hráč byl odebrán.');
        }
        redirect_admin('players/');
    }

    // --- vytvoření / úprava ---
    if ($formAction === 'save') {
        $id = post_int('id');
        $name = clean($_POST['name'] ?? '');
        $category = clean($_POST['category'] ?? '');
        $position = clean($_POST['position'] ?? '');

        $errors = [];
        if ($name === '') {
            $errors[] = 'Vyplňte jméno hráče.';
        }
        if (!isset($categories[$category])) {
            $errors[] = 'Vyberte platnou kategorii.';
        }

        // fotka (volitelná)
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
            redirect_admin('players/?action=' . ($id ? 'edit&id=' . $id : 'add'));
        }

        if ($id) {
            // úprava
            if ($photoPath !== null) {
                // nahradit starou fotku
                $old = $db->prepare('SELECT photo FROM players WHERE id = ?');
                $old->execute([$id]);
                $oldPhoto = $old->fetchColumn();
                $db->prepare('UPDATE players SET name=?, category=?, position=?, photo=? WHERE id=?')
                   ->execute([$name, $category, $position, $photoPath, $id]);
                delete_uploaded_image($oldPhoto ?: null);
            } else {
                $db->prepare('UPDATE players SET name=?, category=?, position=? WHERE id=?')
                   ->execute([$name, $category, $position, $id]);
            }
            flash_set('success', 'Změny byly uloženy.');
        } else {
            // nový
            $db->prepare('INSERT INTO players (name, category, position, photo) VALUES (?,?,?,?)')
               ->execute([$name, $category, $position, $photoPath ?? '']);
            flash_set('success', 'Hráč byl přidán.');
        }
        redirect_admin('players/');
    }
}

// ---------- FORMULÁŘ (přidat / upravit) ----------
if ($action === 'add' || $action === 'edit') {
    $player = ['id' => 0, 'name' => '', 'category' => '', 'position' => '', 'photo' => ''];
    if ($action === 'edit' && $editId) {
        $stmt = $db->prepare('SELECT * FROM players WHERE id = ?');
        $stmt->execute([$editId]);
        $found = $stmt->fetch();
        if (!$found) {
            flash_set('error', 'Hráč nebyl nalezen.');
            redirect_admin('players/');
        }
        $player = $found;
    }

    $pageTitle = $action === 'add' ? 'Přidat hráče' : 'Upravit hráče';
    $activeNav = 'players';
    require __DIR__ . '/../lib/layout_top.php';
    ?>
    <a href="<?= e($baseUrl) ?>admin/players/" class="back-link"><i class='bx bx-arrow-back'></i> Zpět na hráče</a>
    <h1><?= e($pageTitle) ?></h1>

    <form method="POST" enctype="multipart/form-data" class="card">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="save">
        <input type="hidden" name="id" value="<?= (int)$player['id'] ?>">

        <div class="form-row">
            <label>Jméno hráče</label>
            <input type="text" name="name" value="<?= e($player['name']) ?>" required>
        </div>

        <div class="form-two">
            <div class="form-row">
                <label>Kategorie</label>
                <select name="category" required>
                    <option value="">— vyberte —</option>
                    <?php foreach ($categories as $key => $cat): ?>
                        <option value="<?= e($key) ?>" <?= $player['category'] === $key ? 'selected' : '' ?>>
                            <?= e($cat['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <label>Pozice <span class="hint">(např. P, IF)</span></label>
                <input type="text" name="position" value="<?= e($player['position']) ?>">
            </div>
        </div>

        <div class="form-row">
            <label>Fotka <span class="hint">(volitelné, JPG/PNG/WebP, max 5 MB)</span></label>
            <?php scm_photo_upload_field($baseUrl, (string)$player['photo'], 'round'); ?>
        </div>

        <button type="submit" class="saveButton"><i class='bx bx-save'></i> Uložit</button>
    </form>
    <?php scm_photo_upload_script(); ?>
    <?php
    require __DIR__ . '/../lib/layout_bottom.php';
    exit;
}

// ---------- SEZNAM (výchozí) ----------
$rows = $db->query('SELECT * FROM players ORDER BY name COLLATE NOCASE')->fetchAll();

// seskupit podle kategorie
$grouped = [];
foreach ($rows as $r) {
    $grouped[$r['category']][] = $r;
}

$pageTitle = 'Hráči';
$activeNav = 'players';
require __DIR__ . '/../lib/layout_top.php';
?>
<div class="toolbar">
    <div>
        <h1>Hráči</h1>
        <p class="page-intro" style="margin:0">Celkem <?= e(scm_plural(count($rows), 'hráč', 'hráči', 'hráčů', 'žádní hráči')) ?></p>
    </div>
    <a href="<?= e($baseUrl) ?>admin/players/?action=add" class="saveButton"><i class='bx bx-plus'></i> Přidat hráče</a>
</div>

<?php if (empty($rows)): ?>
    <p class="empty-note">Zatím žádní hráči. Přidejte prvního tlačítkem výše.</p>
<?php else: ?>
    <?php foreach ($categories as $key => $cat): ?>
        <?php if (empty($grouped[$key])) continue; ?>
        <div class="record-group">
            <h3 class="<?= $cat['type'] === 'girls' ? 'is-green' : '' ?>"><?= e($cat['label']) ?></h3>
            <?php foreach ($grouped[$key] as $p): ?>
                <div class="record">
                    <div class="record-photo">
                        <img src="<?= e($p['photo'] ? $baseUrl . 'public/' . $p['photo'] : $baseUrl . 'public/images/common/team-mem-none.jpg') ?>" alt="">
                    </div>
                    <div class="record-body">
                        <strong><?= e($p['name']) ?></strong>
                        <span><?= e($p['position'] ?: '—') ?></span>
                    </div>
                    <div class="record-actions">
                        <a href="<?= e($baseUrl) ?>admin/players/?action=edit&id=<?= (int)$p['id'] ?>" class="icon-btn edit" title="Upravit"><i class='bx bx-edit'></i></a>
                        <form method="POST" onsubmit="return confirm('Opravdu odebrat hráče <?= e(addslashes($p['name'])) ?>?')" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="form_action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="icon-btn delete" title="Odebrat"><i class='bx bx-trash'></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>

    <?php // hráči v neznámé kategorii (kdyby po změně číselníku nějací zůstali) ?>
    <?php foreach ($grouped as $key => $list): ?>
        <?php if (isset($categories[$key])) continue; ?>
        <div class="record-group">
            <h3><?= e($key) ?> <span style="font-size:12px">(neznámá kategorie)</span></h3>
            <?php foreach ($list as $p): ?>
                <div class="record">
                    <div class="record-body"><strong><?= e($p['name']) ?></strong><span><?= e($p['position'] ?: '—') ?></span></div>
                    <div class="record-actions">
                        <a href="<?= e($baseUrl) ?>admin/players/?action=edit&id=<?= (int)$p['id'] ?>" class="icon-btn edit"><i class='bx bx-edit'></i></a>
                        <form method="POST" onsubmit="return confirm('Opravdu odebrat?')" style="display:inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="form_action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                            <button type="submit" class="icon-btn delete"><i class='bx bx-trash'></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/../lib/layout_bottom.php'; ?>
