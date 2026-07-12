<?php
/**
 * SKUTEČNÁ konfigurace administrace SCM.
 * TENTO SOUBOR SE NECOMMITUJE DO GITU (viz .gitignore).
 * Na serveru vznikne kopií config.example.php a vyplněním hodnot.
 */

return [

    /*
     * Hezké adresy (/players) místo náhradních (index.php?route=players).
     *
     * true  = potřebuje přepis adres na serveru:
     *           • Apache – zařídí .htaccess v kořeni, nic dalšího netřeba
     *           • nginx  – vlož přiložený nginx.conf.vzor
     * false = funguje na JAKÉMKOLI serveru bez nastavení, adresy jsou ošklivější
     *
     * KDYŽ PO NASAZENÍ FUNGUJE JEN ÚVODNÍ STRÁNKA A OSTATNÍ HLÁSÍ 404,
     * přepni tohle na false. Web začne fungovat okamžitě.
     */
    "pretty_urls" => true,
    "admin_username" => 'admin',
    "admin_password_hash" => '$2y$10$m8u7ft/t9J14Ogo2gRo6W.COPDOZ2hN7zfxmOn8ER5szkUYGu9Q36',
    "db_path" => "",
    "app_secret" => '649697ba32131dee597f41228f93b1d2505bdb40d111d2f9e9081ab5999b8cef',
    "debug" => false,
];
