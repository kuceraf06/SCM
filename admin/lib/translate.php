<?php
/**
 * Slovník pro automatický překlad rozpisů z češtiny do angličtiny.
 * -----------------------------------------------------------------------------
 * Podle přání se den, čas a místo zadávají jen česky a přeloží se samy.
 * Popisy tréninku mají vlastní pole CZ/EN (překládají se ručně), tohle řeší
 * jen krátké, opakující se údaje.
 *
 * Překlad je "best effort": co ve slovníku není, projde beze změny, takže to
 * nikdy nespadne a případný chybějící výraz jde snadno doplnit sem.
 */

declare(strict_types=1);

/** Zkratky a názvy dnů: CZ -> EN. */
function scm_translate_day(string $cs): string
{
    $cs = trim($cs);
    $map = [
        // zkratky
        'PO' => 'MON', 'ÚT' => 'TUE', 'UT' => 'TUE', 'ST' => 'WED',
        'ČT' => 'THU', 'CT' => 'THU', 'PÁ' => 'FRI', 'PA' => 'FRI',
        'SO' => 'SAT', 'NE' => 'SUN',
        // celé názvy
        'Pondělí' => 'Monday', 'Úterý' => 'Tuesday', 'Středa' => 'Wednesday',
        'Čtvrtek' => 'Thursday', 'Pátek' => 'Friday', 'Sobota' => 'Saturday',
        'Neděle' => 'Sunday',
    ];
    // case-insensitive shoda přes celý řetězec
    foreach ($map as $k => $v) {
        if (mb_strtolower($cs, 'UTF-8') === mb_strtolower($k, 'UTF-8')) {
            return $v;
        }
    }
    return $cs;
}

/**
 * Čas: "16:00 - 18:00" -> "4:00 - 6:00 PM".
 * Rozpozná rozsah i jednotlivý čas. Když formát nesedí, vrátí původní.
 */
function scm_translate_time(string $cs): string
{
    $cs = trim($cs);
    // najdi všechny HH:MM
    if (!preg_match_all('/(\d{1,2}):(\d{2})/', $cs, $m, PREG_SET_ORDER)) {
        return $cs;
    }
    $parts = [];
    foreach ($m as $t) {
        $h = (int)$t[1];
        $min = $t[2];
        $ampm = $h >= 12 ? 'PM' : 'AM';
        $h12 = $h % 12;
        if ($h12 === 0) {
            $h12 = 12;
        }
        $parts[] = $h12 . ':' . $min . ' ' . $ampm;
    }
    if (count($parts) >= 2) {
        // když mají oba časy stejné AM/PM, necháme ho jen u druhého (4:00 - 6:00 PM)
        $a = explode(' ', $parts[0]);
        $b = explode(' ', $parts[1]);
        if (count($a) === 2 && count($b) === 2 && $a[1] === $b[1]) {
            return $a[0] . ' - ' . $parts[1];
        }
        return $parts[0] . ' - ' . $parts[1];
    }
    return $parts[0] ?? $cs;
}

/**
 * Místo: přeloží známé výrazy uvnitř řetězce ("hala Brjanská" -> "Brjanská Hall").
 * Pracuje po slovech/frázích, neznámé nechá být.
 */
function scm_translate_place(string $cs): string
{
    $cs = trim($cs);
    if ($cs === '') {
        return $cs;
    }

    // fráze (delší napřed, ať se přeloží dřív než jednotlivá slova)
    $phrases = [
        'plavecký bazén' => 'swimming pool',
        'atletická hala' => 'athletics hall',
        'sportovní hala' => 'sports hall',
        'fotbalové hřiště' => 'football field',
        'baseballové hřiště' => 'baseball field',
    ];
    $result = $cs;
    foreach ($phrases as $csP => $enP) {
        $result = preg_replace('/' . preg_quote($csP, '/') . '/iu', $enP, $result);
    }

    // jednotlivá slova (přeložíme, ale zachováme velikost prvního písmene)
    $words = [
        'hala' => 'hall',
        'hřiště' => 'field',
        'posilovna' => 'gym',
        'bazén' => 'pool',
        'tělocvična' => 'gym',
        'stadion' => 'stadium',
        'atletika' => 'athletics',
        'atletická' => 'athletics',
        'plavání' => 'swimming',
        'plavecký' => 'swimming',
        'bazén' => 'pool',
        'a' => 'and',
    ];

    $result = preg_replace_callback('/\p{L}+/u', function ($mm) use ($words) {
        $w = $mm[0];
        $lower = mb_strtolower($w, 'UTF-8');
        if (isset($words[$lower])) {
            $en = $words[$lower];
            // zachovej velké první písmeno
            if (mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8') === mb_substr($w, 0, 1, 'UTF-8')) {
                return mb_strtoupper(mb_substr($en, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($en, 1, null, 'UTF-8');
            }
            return $en;
        }
        return $w;
    }, $result);

    return $result;
}
