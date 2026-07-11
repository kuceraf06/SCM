<?php
/**
 * Změna hesla administrátora.
 * -----------------------------------------------------------------------------
 * Ověří staré heslo, pak vygeneruje nový hash a zapíše ho do config.php.
 * Když config.php nejde zapsat (např. práva na serveru), zobrazí nový hash
 * s instrukcí k ručnímu vložení - takže změna hesla nikdy „tiše" neselže.
 */

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/auth.php';
auth_require($config);

$newHashToShow = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    $errors = [];
    if (!password_verify($current, (string)($config['admin_password_hash'] ?? ''))) {
        $errors[] = 'Současné heslo není správné.';
    }
    if (strlen($new) < 8) {
        $errors[] = 'Nové heslo musí mít alespoň 8 znaků.';
    }
    if ($new !== $confirm) {
        $errors[] = 'Nové heslo a jeho potvrzení se neshodují.';
    }

    if (empty($errors)) {
        $newHash = password_hash($new, PASSWORD_DEFAULT);

        // zápis přes sdílenou funkci (zvládá jednoduché i dvojité uvozovky,
        // bezpečně escapuje $ a \ v hashi)
        $written = scm_write_config_values(SCM_CONFIG, ['admin_password_hash' => $newHash]);

        if ($written) {
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate(SCM_CONFIG, true);
            }
            flash_set('success', 'Heslo bylo změněno.');
            redirect_admin('account/');
        } else {
            // fallback: ukázat hash k ručnímu vložení
            $newHashToShow = $newHash;
            flash_set('error', 'Config nejde zapsat automaticky. Vlož nový hash ručně (viz níže).');
        }
    } else {
        foreach ($errors as $er) {
            flash_set('error', $er);
        }
    }
}

$pageTitle = 'Změna hesla';
$activeNav = 'account';
require __DIR__ . '/../lib/layout_top.php';
?>
<h1>Změna hesla</h1>
<p class="page-intro">Heslo se ukládá pouze jako bezpečný hash, nikdy v čitelné podobě.</p>

<?php if ($newHashToShow !== ''): ?>
    <div class="card" style="border-color:var(--scm-green)">
        <p style="margin-top:0"><strong>Vlož tento hash do <code>config/config.php</code></strong> pod <code>'admin_password_hash'</code>:</p>
        <textarea readonly style="width:100%;padding:12px;border-radius:9px;border:1px solid var(--scm-border);font-family:monospace"><?= e($newHashToShow) ?></textarea>
    </div>
<?php endif; ?>

<form method="POST" class="card">
    <?= csrf_field() ?>
    <div class="form-row">
        <label>Současné heslo</label>
        <input type="password" name="current_password" required autocomplete="current-password">
    </div>
    <div class="form-two">
        <div class="form-row">
            <label>Nové heslo <span class="hint">(min. 8 znaků)</span></label>
            <input type="password" name="new_password" required autocomplete="new-password">
        </div>
        <div class="form-row">
            <label>Potvrzení nového hesla</label>
            <input type="password" name="confirm_password" required autocomplete="new-password">
        </div>
    </div>
    <button type="submit" class="saveButton"><i class='bx bx-save'></i> Změnit heslo</button>
</form>
<?php require __DIR__ . '/../lib/layout_bottom.php'; ?>
