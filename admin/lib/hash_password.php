<?php
/**
 * Pomůcka pro vygenerování hashe hesla do config.php.
 * -----------------------------------------------------------------------------
 * Použití z příkazové řádky:
 *     php admin/lib/hash_password.php
 * Skript se zeptá na heslo (neukazuje ho) a vytiskne hash, který vložíš
 * do config.php pod 'admin_password_hash'.
 *
 * Spustitelné jen z CLI, ne z webu.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Tento skript lze spustit jen z příkazové řádky.');
}

echo "Generátor hashe hesla pro administraci SCM\n";
echo "------------------------------------------\n";

// pokus o skryté zadání hesla
$password = '';
if (function_exists('shell_exec') && stripos(PHP_OS, 'WIN') === false) {
    echo 'Zadej heslo: ';
    shell_exec('stty -echo 2>/dev/null');
    $password = trim((string)fgets(STDIN));
    shell_exec('stty echo 2>/dev/null');
    echo "\n";
} else {
    echo 'Zadej heslo (bude viditelné): ';
    $password = trim((string)fgets(STDIN));
}

if ($password === '') {
    exit("Heslo nesmí být prázdné.\n");
}
if (strlen($password) < 8) {
    echo "VAROVÁNÍ: heslo je kratší než 8 znaků, zvaž delší.\n";
}

$hash = password_hash($password, PASSWORD_DEFAULT);
echo "\nHotovo. Vlož tento hash do config/config.php jako 'admin_password_hash':\n\n";
echo $hash . "\n\n";
