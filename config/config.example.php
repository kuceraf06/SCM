<?php
/**
 * VZOR konfigurace administrace SCM.
 * -----------------------------------------------------------------------------
 * Toto je ŠABLONA, která PATŘÍ do gitu (neobsahuje žádná tajemství).
 * Na serveru z ní vytvoř kopii `config.php` (viz .gitignore) a vyplň skutečné
 * hodnoty. `config.php` se do gitu NIKDY necommituje.
 *
 * Postup nasazení:
 *   1) cp config/config.example.php config/config.php
 *   2) Vygeneruj hash hesla:  php admin/lib/hash_password.php
 *   3) Zkopíruj vytištěný hash do 'admin_password_hash' níže.
 *   4) Zkontroluj 'db_path' – měl by mířit MIMO web root i mimo git checkout
 *      (na sdíleném hostingu např. o úroveň výš než veřejná složka).
 */

return [
    // Přihlašovací jméno administrátora
    'admin_username' => 'zmen_me',

    // Hash hesla z password_hash() - NIKDY sem nedávej heslo v čitelné podobě.
    // Vygeneruj přes: php admin/lib/hash_password.php
    'admin_password_hash' => '',

    // Absolutní cesta k SQLite databázi. IDEÁLNĚ mimo web root i mimo git,
    // aby ji nešlo stáhnout z webu a deploy ji nemohl přepsat.
    // Např. na serveru: '/var/lib/scm/scm.sqlite'
    // Fallback (když necháš prázdné) je storage/db/ mimo public/ - funkční,
    // ale na produkci raději nastav absolutní cestu mimo checkout.
    'db_path' => '',

    // Náhodný tajný řetězec pro podpis "zůstat přihlášen" tokenů a další.
    // Vygeneruj např.:  php -r "echo bin2hex(random_bytes(32));"
    'app_secret' => '',

    // Zobrazovat detailní chyby? Na produkci VŽDY false.
    'debug' => false,
];
