<?php
/**
 * Správa trenérů - seznam (interní / externí), přidání, úprava, smazání.
 * Stejný bezpečný princip jako u hráčů.
 */

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/photo_field.php';
auth_require($config);

$action = $_GET['action'] ?? 'list';
$editId = (int)($_GET['id'] ?? 0);

// ---------- POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $formAction = $_POST['form_action'] ?? '';

    if ($formAction === 'delete') {
        $id = post_int('id');
        $stmt = $db->prepare('SELECT photo FROM coaches WHERE id = ?');
        $stmt->execute([$id]);
        $photo = $stmt->fetchColumn();
        if ($photo !== false) {
            $db->prepare('DELETE FROM coaches WHERE id = ?')->execute([$id]);
            delete_uploaded_image($photo ?: null);
            flash_set('success', 'Trenér byl odebrán.');
        }
        redirect_admin('staff/');
    }

    if ($formAction === 'save') {
        $id = post_int('id');
        $name = clean($_POST['name'] ?? '');
        $roleCs = clean($_POST['role_cs'] ?? '');
        $roleEn = clean($_POST['role_en'] ?? '');
        $licCs = clean($_POST['license_cs'] ?? '');
        $licEn = clean($_POST['license_en'] ?? '');
        $phone = clean($_POST['phone'] ?? '');
        $email = clean($_POST['email'] ?? '');
        $isExternal = post_int('is_external') === 1 ? 1 : 0;

        $errors = [];
        if ($name === '') {
            $errors[] = 'Vyplňte jméno trenéra.';
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail nemá platný formát.';
        }

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
            redirect_admin('staff/?action=' . ($id ? 'edit&id=' . $id : 'add'));
        }

        if ($id) {
            if ($photoPath !== null) {
                $old = $db->prepare('SELECT photo FROM coaches WHERE id = ?');
                $old->execute([$id]);
                $oldPhoto = $old->fetchColumn();
                $db->prepare('UPDATE coaches SET name=?, role_cs=?, role_en=?, license_cs=?, license_en=?, phone=?, email=?, is_external=?, photo=? WHERE id=?')
                   ->execute([$name, $roleCs, $roleEn, $licCs, $licEn, $phone, $email, $isExternal, $photoPath, $id]);
                delete_uploaded_image($oldPhoto ?: null);
            } else {
                $db->prepare('UPDATE coaches SET name=?, role_cs=?, role_en=?, license_cs=?, license_en=?, phone=?, email=?, is_external=? WHERE id=?')
                   ->execute([$name, $roleCs, $roleEn, $licCs, $licEn, $phone, $email, $isExternal, $id]);
            }
            flash_set('success', 'Změny byly uloženy.');
        } else {
            $db->prepare('INSERT INTO coaches (name, role_cs, role_en, license_cs, license_en, phone, email, is_external, photo) VALUES (?,?,?,?,?,?,?,?,?)')
               ->execute([$name, $roleCs, $roleEn, $licCs, $licEn, $phone, $email, $isExternal, $photoPath ?? '']);
            flash_set('success', 'Trenér byl přidán.');
        }
        redirect_admin('staff/');
    }
}

// ---------- FORMULÁŘ ----------
if ($action === 'add' || $action === 'edit') {
    $coach = ['id' => 0, 'name' => '', 'role_cs' => '', 'role_en' => '', 'license_cs' => '',
              'license_en' => '', 'phone' => '', 'email' => '', 'is_external' => 0, 'photo' => ''];
    if ($action === 'edit' && $editId) {
        $stmt = $db->prepare('SELECT * FROM coaches WHERE id = ?');
        $stmt->execute([$editId]);
        $found = $stmt->fetch();
        if (!$found) {
            flash_set('error', 'Trenér nebyl nalezen.');
            redirect_admin('staff/');
        }
        $coach = $found;
    }

    $pageTitle = $action === 'add' ? 'Přidat trenéra' : 'Upravit trenéra';
    $activeNav = 'staff';
    require __DIR__ . '/../lib/layout_top.php';
    ?>
    <a href="<?= e($baseUrl) ?>admin/staff/" class="back-link"><i class='bx bx-arrow-back'></i> Zpět na trenéry</a>
    <h1><?= e($pageTitle) ?></h1>

    <form method="POST" enctype="multipart/form-data" class="card">
        <?= csrf_field() ?>
        <input type="hidden" name="form_action" value="save">
        <input type="hidden" name="id" value="<?= (int)$coach['id'] ?>">

        <div class="form-row">
            <label>Jméno trenéra</label>
            <input type="text" name="name" value="<?= e($coach['name']) ?>" required>
        </div>

        <div class="form-two">
            <div class="form-row">
                <label>Role <span class="hint">(česky)</span></label>
                <input type="text" name="role_cs" value="<?= e($coach['role_cs']) ?>" placeholder="např. Vedoucí trenér">
            </div>
            <div class="form-row">
                <label>Role <span class="hint">(anglicky)</span></label>
                <input type="text" name="role_en" value="<?= e($coach['role_en']) ?>" placeholder="e.g. Head Coach">
            </div>
        </div>

        <div class="form-two">
            <div class="form-row">
                <label>Licence <span class="hint">(česky, volitelné)</span></label>
                <input type="text" name="license_cs" value="<?= e($coach['license_cs']) ?>">
            </div>
            <div class="form-row">
                <label>Licence <span class="hint">(anglicky, volitelné)</span></label>
                <input type="text" name="license_en" value="<?= e($coach['license_en']) ?>">
            </div>
        </div>

        <div class="form-two">
            <div class="form-row">
                <label>Telefon</label>
                <input type="text" name="phone" value="<?= e($coach['phone']) ?>">
            </div>
            <div class="form-row">
                <label>E-mail</label>
                <input type="email" name="email" value="<?= e($coach['email']) ?>">
            </div>
        </div>

        <div class="form-row">
            <label>Zařazení</label>
            <select name="is_external">
                <option value="0" <?= (int)$coach['is_external'] === 0 ? 'selected' : '' ?>>Interní trenér</option>
                <option value="1" <?= (int)$coach['is_external'] === 1 ? 'selected' : '' ?>>Externí trenér</option>
            </select>
        </div>

        <div class="form-row">
            <label>Fotka <span class="hint">(volitelné, JPG/PNG/WebP, max 5 MB)</span></label>
            <?php scm_photo_upload_field($baseUrl, (string)$coach['photo'], 'round'); ?>
        </div>

        <button type="submit" class="saveButton"><i class='bx bx-save'></i> Uložit</button>
    </form>
    <?php scm_photo_upload_script(); ?>
    <?php
    require __DIR__ . '/../lib/layout_bottom.php';
    exit;
}

// ---------- SEZNAM ----------
$internal = $db->query('SELECT * FROM coaches WHERE is_external = 0 ORDER BY sort_order, id')->fetchAll();
$external = $db->query('SELECT * FROM coaches WHERE is_external = 1 ORDER BY sort_order, id')->fetchAll();
$total = count($internal) + count($external);

$pageTitle = 'Trenéři';
$activeNav = 'staff';
require __DIR__ . '/../lib/layout_top.php';

/** vykreslí jeden řádek trenéra */
function render_coach_row(array $c, string $baseUrl): void
{
    $noPhoto = $baseUrl . 'public/images/common/team-mem-none.jpg';
    ?>
    <div class="record">
        <div class="record-photo">
            <img src="<?= e($c['photo'] ? $baseUrl . 'public/' . $c['photo'] : $noPhoto) ?>" alt="">
        </div>
        <div class="record-body">
            <strong><?= e($c['name']) ?></strong>
            <span><?= e($c['role_cs'] ?: '—') ?><?= $c['email'] ? ' · ' . e($c['email']) : '' ?></span>
        </div>
        <div class="record-actions">
            <a href="<?= e($baseUrl) ?>admin/staff/?action=edit&id=<?= (int)$c['id'] ?>" class="icon-btn edit" title="Upravit"><i class='bx bx-edit'></i></a>
            <form method="POST" onsubmit="return confirm('Opravdu odebrat trenéra <?= e(addslashes($c['name'])) ?>?')" style="display:inline">
                <?= csrf_field() ?>
                <input type="hidden" name="form_action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <button type="submit" class="icon-btn delete" title="Odebrat"><i class='bx bx-trash'></i></button>
            </form>
        </div>
    </div>
    <?php
}
?>
<div class="toolbar">
    <div>
        <h1>Trenéři</h1>
        <p class="page-intro" style="margin:0">Celkem <?= e(scm_plural($total, 'trenér', 'trenéři', 'trenérů', 'žádní trenéři')) ?></p>
    </div>
    <a href="<?= e($baseUrl) ?>admin/staff/?action=add" class="saveButton"><i class='bx bx-plus'></i> Přidat trenéra</a>
</div>

<?php if ($total === 0): ?>
    <p class="empty-note">Zatím žádní trenéři. Přidejte prvního tlačítkem výše.</p>
<?php else: ?>
    <div class="record-group">
        <h3>Interní trenéři</h3>
        <?php if (empty($internal)): ?>
            <p class="empty-note">Žádní interní trenéři.</p>
        <?php else: foreach ($internal as $c) render_coach_row($c, $baseUrl); endif; ?>
    </div>
    <div class="record-group">
        <h3 class="is-green">Externí trenéři</h3>
        <?php if (empty($external)): ?>
            <p class="empty-note">Žádní externí trenéři.</p>
        <?php else: foreach ($external as $c) render_coach_row($c, $baseUrl); endif; ?>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../lib/layout_bottom.php'; ?>
