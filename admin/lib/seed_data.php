<?php
/**
 * Výchozí data (seed) + funkce pro jejich vložení.
 * -----------------------------------------------------------------------------
 * Tato data se do databáze vloží AUTOMATICKY při úplně prvním připojení
 * (viz scm_db_init_schema v db.php), takže po nahrání projektu na server
 * není potřeba spouštět nic z příkazové řádky - stačí otevřít web.
 *
 * KLÍČOVÉ pro bezpečnost dat: vkládá se JEN do prázdných tabulek. Jakmile
 * v databázi něco je (živá data na serveru), seed se přeskočí a NIKDY nic
 * nepřepíše. Proto opakované nahrání projektu ani spuštění seedu data neztratí.
 */

declare(strict_types=1);

/**
 * Naplní prázdné tabulky výchozími daty. Idempotentní - do neprázdné tabulky
 * nesahá. Vrací pole s počty vložených záznamů (pro případný výpis).
 *
 * @return array{players:int,coaches:int,trainings:int}
 */
function scm_seed_data(PDO $db): array
{
    $inserted = ['players' => 0, 'coaches' => 0, 'trainings' => 0];

    // překlad krátkých údajů (pokud je k dispozici)
    if (!function_exists('scm_translate_place')) {
        require_once __DIR__ . '/translate.php';
    }

    // ---- HRÁČI ----
    if ((int)$db->query('SELECT COUNT(*) FROM players')->fetchColumn() === 0) {
        $players = [
            ['Posselt Filip', 'U11', 'P, IF'], ['Rákosník Jan', 'U11', 'P, IF'],
            ['Peterka Jan', 'U11', 'P, IF'], ['Tománek Šimon', 'U11', 'P, IF'],
            ['Šuranyi Daniel', 'U11', 'P, OF'], ['Souček Vítek', 'U11', 'P, C'],
            ['Sýkora Vojtěch', 'U11', 'P, IF'],
            ['Rákosníková Julie', 'U11s', 'P, IF'],
            ['Lev Lukáš', 'U13', 'P, C'], ['Mňuk Vojtěch', 'U13', 'P, IF'],
            ['Souček Matěj', 'U13', 'P, IF'], ['Hamršmíd Jakub', 'U13', 'P, OF'],
            ['Klíma Ondřej', 'U13', 'IF, OF'], ['Beran Tobiáš', 'U13', 'P, IF'],
            ['Vostatek Matyáš', 'U13', 'C, IF'],
            ['Škardová Alice', 'U13s', 'IF'],
            ['Mehes Lukáš', 'U15', 'P, IF'], ['Fanta Adam', 'U15', 'P, OF'],
            ['Kučera Jáchym', 'U15', 'P, IF'], ['Šícho Šimon', 'U15', 'C, IF'],
            ['Král Matyáš', 'U15', 'IF, OF'], ['Novotný Filip', 'U15', 'P, OF'],
            ['Beran Šimon', 'U15', 'P, IF'], ['Doležal Matěj', 'U15', 'OF'],
            ['Škardová Anna', 'U16s', 'P, IF'],
            ['Kučera Filip', 'U18', 'P, IF'], ['Vostatek Ondřej', 'U18', 'OF'],
            ['Posselt Martin', 'U18', 'C'],
            ['Pražanová Veronika', 'ŽENY', 'IF'],
        ];
        $stmt = $db->prepare('INSERT INTO players (name, category, position, sort_order) VALUES (?,?,?,?)');
        foreach ($players as $i => $p) {
            $stmt->execute([$p[0], $p[1], $p[2], $i]);
        }
        $inserted['players'] = count($players);
    }

    // ---- TRENÉŘI ----
    if ((int)$db->query('SELECT COUNT(*) FROM coaches')->fetchColumn() === 0) {
        // [jméno, role_cs, role_en, licence_cs, licence_en, telefon, email, is_external]
        $coaches = [
            ['Petr Baroch', 'Ředitel SCM / Vedoucí trenér', 'SCM Director / Head Coach', 'II. třída licence Master', 'Class II Master License', '736 682 453', 'baroch@minerskladno.cz', 0],
            ['Michaela Čurillová', 'Trenér SCM', 'SCM Coach', 'II. třída licence Trenér', 'Class II Coach License', '604 658 667', 'curillova@minerskladno.cz', 0],
            ['Karel Kuklík', 'Trenér', 'Coach', '', '', '722 470 911', 'kuklik@minerskladno.cz', 0],
            ['Radka Jakubová', 'Trenér', 'Coach', 'II. třída licence Trenér', 'Class II Coach License', '737 272 813', 'jakubova@minerskladno.cz', 0],
            ['Filip Kučera', 'Asistent', 'Assistant', 'III. třída licence Trenér', 'Class III Coach License', '702 886 541', 'filipkucera06@gmail.com', 0],
            ['Vojtěch Kuklík', 'Asistent', 'Assistant', 'III. třída licence Trenér', 'Class III Coach License', '722 471 007', 'vojtakuklik18@email.cz', 0],
            ['Ondřej Vostatek', 'Asistent', 'Assistant', 'III. třída licence Trenér', 'Class III Coach License', '702 185 639', 'ondrejvostatek@gmail.com', 0],
            ['Martin Kučera', 'Asistent', 'Assistant', 'III. třída licence Trenér', 'Class III Coach License', '724 615 814', 'kucera@minerskladno.cz', 0],
            ['Martin Posselt', 'Asistent', 'Assistant', 'III. třída licence Trenér', 'Class III Coach License', '732 603 750', 'posselt@minerskladno.cz', 0],
        ];
        $externalCoaches = [
            ['Pavel Chadim', 'Externí trenér', 'External Coach', '', '', '', '', 1],
            ['Jan Novák', 'Externí trenér', 'External Coach', '', '', '', '', 1],
            ['Tomáš Svoboda', 'Externí trenér', 'External Coach', '', '', '', '', 1],
            ['Petr Dvořák', 'Externí trenér', 'External Coach', '', '', '', '', 1],
        ];
        $all = array_merge($coaches, $externalCoaches);
        $stmt = $db->prepare('INSERT INTO coaches (name, role_cs, role_en, license_cs, license_en, phone, email, is_external, sort_order) VALUES (?,?,?,?,?,?,?,?,?)');
        foreach ($all as $i => $c) {
            $stmt->execute([$c[0], $c[1], $c[2], $c[3], $c[4], $c[5], $c[6], $c[7], $i]);
        }
        $inserted['coaches'] = count($all);
    }

    // ---- TRÉNINKY (rozpisy) ----
    if ((int)$db->query('SELECT COUNT(*) FROM trainings')->fetchColumn() === 0) {
        // [sezóna, title_cs, den, čas, místo, popis_cs, popis_en, fotka]
        $trainings = [
            ['summer', 'Hřiště Rozdělov', 'PÁ', '16:00 - 18:00', 'hřiště Rozdělov',
                'V pátek trénujeme na hřišti v Rozdělově. Nadhazovače a catchery připravujeme na zápasy pod vedením trenérů a hráčů českého národního týmu, poziční hráči mají trénink pálky.',
                'On Fridays we train at the field in Rozdělov. Pitchers and catchers are prepared for games under the guidance of coaches and players from the Czech national team, while position players work on their batting.',
                'images/schedule/miners-field-1.jpg'],
            ['winter', 'Hala Brjanská', 'PÁ', '16:00 - 18:00', 'hala Brjanská',
                'V pátek trénujeme v hale na Brjanské. Věnujeme se herním dovednostem, nadhozu, pálce i obraně, abychom byli na jarní sezónu dobře připravení.',
                'On Fridays we train at the Brjanská hall. We focus on game skills, pitching, batting and defense so we are well prepared for the spring season.',
                'images/schedule/hala-1.jpg'],
            ['winter', 'Posilovna BIOS', 'PÁ', '18:30 - 19:30', 'posilovna BIOS',
                'V posilovně budujeme kondici, sílu a rychlost a zároveň předcházíme zraněním. Každý hráč trénuje podle plánu ušitého na míru jeho potřebám a věku.',
                'In the gym we build conditioning, strength and speed while also preventing injuries. Every player follows a plan tailored to their needs and age.',
                'images/schedule/bios-posilovna.jpg'],
            ['winter', 'Atletika a plavání', 'NE', '15:00 - 17:00', 'Atletická hala Sletiště / plavecký bazén Aquapark Kladno',
                'V neděli se věnujeme atletice v hale na Sletišti a plavání v Aquaparku Kladno. Zaměřujeme se na rychlost, obratnost a celkovou fyzickou kondici.',
                'On Sundays we focus on athletics at the Sletiště hall and swimming at Aquapark Kladno. We work on speed, agility and overall physical conditioning.',
                'images/schedule/hala-2.jpg'],
        ];
        $stmt = $db->prepare('INSERT INTO trainings (season, title_cs, title_en, day, time, place, desc_cs, desc_en, photo, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)');
        foreach ($trainings as $i => $t) {
            $titleEn = scm_translate_place($t[1]);
            $stmt->execute([$t[0], $t[1], $titleEn, $t[2], $t[3], $t[4], $t[5], $t[6], $t[7], $i]);
        }
        $inserted['trainings'] = count($trainings);
    }

    return $inserted;
}
