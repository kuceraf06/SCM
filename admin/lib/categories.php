<?php
/**
 * Číselník kategorií hráčů (sdílený administrací i webem).
 * -----------------------------------------------------------------------------
 * Pořadí od nejmladších po nejstarší. 'type' určuje barvu na webu:
 *   'boys'  = kluci -> černá
 *   'girls' = holky -> zelená (kategorie končící na "s" nebo ŽENY)
 */

declare(strict_types=1);

/** @return array<string,array{label:string,type:string}> klíč = hodnota v DB */
function scm_player_categories(): array
{
    return [
        'U11'  => ['label' => 'U11',  'type' => 'boys'],
        'U11s' => ['label' => 'U11s', 'type' => 'girls'],
        'U13'  => ['label' => 'U13',  'type' => 'boys'],
        'U13s' => ['label' => 'U13s', 'type' => 'girls'],
        'U15'  => ['label' => 'U15',  'type' => 'boys'],
        'U16s' => ['label' => 'U16s', 'type' => 'girls'],
        'U18'  => ['label' => 'U18',  'type' => 'boys'],
        'U18s' => ['label' => 'U18s', 'type' => 'girls'],
        'MUŽI' => ['label' => 'MUŽI', 'type' => 'boys'],
        'ŽENY' => ['label' => 'ŽENY', 'type' => 'girls'],
    ];
}

/** Pořadí kategorie pro řazení (menší = dřív). */
function scm_category_order(string $cat): int
{
    $keys = array_keys(scm_player_categories());
    $idx = array_search($cat, $keys, true);
    return $idx === false ? 999 : (int)$idx;
}

/** Role trenérů - jen rozdělení na interní/externí je v DB (is_external). */
