<?php
/**
 * Přihlašovací stránka administrace.
 * Bezpečné přihlášení: heslo se ověřuje proti hashi z configu, "zůstat
 * přihlášen" používá podepsaný token (žádný cookie bypass jako ve staré verzi).
 */

require_once __DIR__ . '/../lib/bootstrap.php';
require_once __DIR__ . '/../lib/auth.php';

// už přihlášen? rovnou do administrace
if (!empty($_SESSION['logged_in'])) {
    redirect_admin();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = clean($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $remember = isset($_POST['rememberMe']);

    if (auth_attempt($config, $username, $password)) {
        if ($remember) {
            auth_set_remember_cookie($config);
        }
        redirect_admin();
    } else {
        $error = 'Špatné uživatelské jméno nebo heslo!';
        usleep(400000); // malé zpoždění proti hádání hesla
    }
}

$inactive = (($_GET['message'] ?? '') === 'inactive');
?><!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SCM | Přihlášení do administrace</title>
    <link rel="icon" type="image/png" href="<?= e($baseUrl) ?>public/images/common/favicon-32x32.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="<?= e($baseUrl) ?>admin/assets/admin.css">
</head>
<body class="login-body">
    <main class="adminMain">
        <img src="<?= e($baseUrl) ?>public/images/common/SCM.png" alt="SCM logo">
        <div class="loginForm">
            <p>Přihlášení do administrace</p>

            <?php if ($inactive): ?>
                <div class="login-warning">Byli jste odhlášeni kvůli neaktivitě.</div>
            <?php endif; ?>

            <form method="POST" action="" class="styleForm" autocomplete="off">
                <?= csrf_field() ?>
                <?php if ($error): ?>
                    <div class="loginFail"><?= e($error) ?></div>
                <?php endif; ?>
                <div class="input-container">
                    <i class='bx bxs-user'></i>
                    <input type="text" name="username" placeholder="Uživatelské jméno" required autofocus>
                </div>
                <div class="input-container">
                    <i class='bx bxs-lock-alt'></i>
                    <input type="password" name="password" placeholder="Heslo" required>
                </div>
                <label class="rememberMe">
                    <input type="checkbox" name="rememberMe">
                    Zůstat přihlášen
                </label>
                <button type="submit">Přihlásit se</button>
            </form>
        </div>
    </main>
</body>
</html>
