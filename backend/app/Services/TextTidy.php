<?php

namespace App\Services;

/**
 * Makes hand-typed schedule text consistent without changing its meaning:
 * title capitalization (keeping acronyms, brand names and Filipino particles),
 * known typos, spacing around punctuation, and room numbers. Running it twice
 * gives the same result.
 */
class TextTidy
{
    // Typos and inconsistent spellings found in the EMO's sheet (case-insensitive, whole words).
    private const FIXES = [
        'assestment' => 'assessment', 'counceling' => 'counseling', 'criminilogy' => 'criminology',
        'pinninig' => 'pinning', 'gratittude' => 'gratitude', 'emphatize' => 'empathize',
        'enggagement' => 'engagement', 'sthethoscope' => 'stethoscope', 'collge' => 'college',
        'architech' => 'architect', 'oathaking' => 'oath-taking', 'blldg' => 'bldg', 'mtro' => 'ministro',
        'dryrun' => 'dry run', 'teambuilding' => 'team building', 'greencondo' => 'green condo',
        'buwanang wika' => 'buwan ng wika', 'buwang pulong' => 'buwanang pulong',
        'heath awareness' => 'health awareness', 'th ecfa' => 'the CFA', 'indo pacific' => 'indo-pacific',
        'in house' => 'in-house', 'jr high' => 'junior high', 'flr' => 'floor', 'local ng' => 'lokal ng',
    ];

    // Words typed in caps lock that are ordinary words, not acronyms.
    private const CAPS_WORDS = ['and', 'graduation', 'moving', 'up', 'night', 'nurses', 'week', 'room', 'rm',
        'hindi', 'na', 'po', 'tuloy', 'ito', 'jr', 'bar', 'exam', 'no', 'classes'];

    // Acronyms sometimes typed in lowercase ("Neu", "Psb mph"). "is" is left out on purpose.
    private const ACRONYMS = ['neu', 'psb', 'mph', 'som', 'uhall', 'nstp', 'rotc', 'ojt', 'ptca', 'prc', 'cfa', 'bbq', 'ai'];

    // Stay lowercase inside a title (English and Filipino).
    private const SMALL = ['a', 'an', 'and', 'as', 'at', 'but', 'by', 'for', 'from', 'in', 'into', 'of', 'on', 'or',
        'the', 'to', 'vs', 'via', 'with', 'ng', 'mga', 'sa', 'ang', 'na', 'ni', 'kay', 'nang', 'po'];

    private const MONTHS = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september',
        'october', 'november', 'december', 'jan', 'feb', 'mar', 'apr', 'jun', 'jul', 'aug', 'sep', 'sept', 'oct', 'nov', 'dec'];

    private const HONORIFICS = ['ate', 'kuya', 'ka', 'sir', 'm', 'ms', 'mr', 'mrs'];

    /**
     * "Family Fun Day - HINDI NA PO TULOY ITO" ("won't push through") means the
     * event is cancelled: returns [clean name, note for the remarks] or [name, null].
     */
    public static function cancellation(string $name): array
    {
        if (preg_match('/^(.*?)\s*-\s*(hindi na (?:po )?tuloy(?: ito)?)\s*$/iu', $name, $m)) {
            return [$m[1], ucfirst(mb_strtolower($m[2]))];
        }

        return [$name, null];
    }

    public static function title(?string $text): ?string
    {
        $text = self::clean($text);
        if ($text === null) {
            return null;
        }
        $text = self::fixTypos($text);

        $out = [];
        $startOfPhrase = true;
        foreach (explode(' ', $text) as $token) {
            $out[] = self::caseToken($token, $startOfPhrase);
            // The next word starts a phrase after ":", ",", or " - ", e.g. "We Share, A System".
            $startOfPhrase = str_ends_with($token, ':') || str_ends_with($token, ',') || $token === '-' || $token === '–';
        }

        return implode(' ', $out);
    }

    /**
     * Free-text notes: keep their wording, just capitalize the start, month
     * names and names after honorifics ("c/o ate cha" → "c/o Ate Cha").
     */
    public static function remark(?string $text): ?string
    {
        $text = self::clean($text);
        if ($text === null) {
            return null;
        }
        $text = preg_replace('/(\d)(and)\b/i', '$1 $2', $text);

        $words = explode(' ', $text);
        foreach ($words as $i => $word) {
            $lower = strtolower($word);
            $afterHonorific = $i > 0 && in_array(strtolower(trim($words[$i - 1], '.,')), self::HONORIFICS, true);
            if (in_array($lower, self::MONTHS, true) || in_array($lower, self::HONORIFICS, true) || $afterHonorific) {
                $words[$i] = ucfirst($lower === $word ? $word : $word);
            }
        }
        $text = implode(' ', $words);

        return str_starts_with($text, 'c/o') ? $text : ucfirst($text);
    }

    /**
     * Room/details next to a venue: "rm201" → "Room 201", "505,506,507" → "Rooms 505, 506, 507".
     */
    public static function room(?string $text): ?string
    {
        $text = self::clean($text);
        if ($text === null) {
            return null;
        }
        $text = preg_replace('/\b(?:rm|room)s?\.?\s*(?=\d)/i', 'Room ', $text);
        $text = preg_replace('/(\d)\s*-\s*(\d)/', '$1-$2', $text);
        $text = preg_replace('/(\d)\s*,\s*(\d)/', '$1, $2', $text);
        $text = preg_replace('/(\d)\s*&\s*(\d)/', '$1 & $2', $text);
        if (preg_match('/^\d/', $text)) {
            $text = 'Room '.$text;
        }
        // Several rooms read as "Rooms 505-507".
        $text = preg_replace('/^Room (\d+(?:-\d+|(?:, | & )\d+)+)/', 'Rooms $1', $text);

        return self::title($text);
    }

    private static function clean(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }
        $text = trim(preg_replace('/\s+/', ' ', $text));
        if ($text === '' || ! preg_match('/[\p{L}\p{N}]/u', $text)) {
            return null;
        }
        $text = preg_replace('/\s+:/', ':', $text);                 // "Guidance : Seminar"
        $text = str_replace(' _ ', ' - ', $text);                   // "AI _ Buwan ng Wika"
        $text = preg_replace('/\b([A-Z0-9]{2,}) -([A-Z]{2,})\b/', '$1-$2', $text); // "CAS -GE" → "CAS-GE"
        $text = preg_replace('/(\S) -(?=\p{L})/u', '$1 - ', $text); // "KRA4 -Community"
        $text = preg_replace('/,(?=\S)/', ', ', $text);             // "SOIR,COC"
        $text = preg_replace('/(\S) \/(?=\S)|(?<=\S)\/ (\S)/', '$1/$2', $text); // "Graduation/ Dryrun"
        if (substr_count($text, '(') > substr_count($text, ')')) {
            $text .= ')';
        }

        return $text;
    }

    private static function fixTypos(string $text): string
    {
        $text = preg_replace('/\bLEads\b/', 'Leads', $text);
        foreach (self::FIXES as $wrong => $right) {
            $text = preg_replace('/\b'.preg_quote($wrong, '/').'\b/i', $right, $text);
        }

        return $text;
    }

    private static function caseToken(string $token, bool $startOfPhrase): string
    {
        // Leading/trailing punctuation such as "(rehearsal)" or "\"From".
        if (! preg_match('/^([^\p{L}\p{N}]*)(.*?)([^\p{L}\p{N}]*)$/u', $token, $m) || $m[2] === '') {
            return $token;
        }
        [, $lead, $word, $trail] = $m;
        if (strtolower($word) === 'c/o') {
            return $lead.'c/o'.$trail;
        }
        $startOfPhrase = $startOfPhrase || str_contains($lead, '(') || str_contains($lead, '"');

        $parts = preg_split('/([\/\-])/', $word, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => $part) {
            if ($part === '/' || $part === '-' || $part === '') {
                continue;
            }
            $first = $startOfPhrase && $i === 0;
            $afterSlash = $i > 0 && $parts[$i - 1] === '/';
            $parts[$i] = self::casePart($part, $first || $afterSlash || $i > 0);
        }

        return $lead.implode('', $parts).$trail;
    }

    private static function casePart(string $part, bool $capitalize): string
    {
        $lower = mb_strtolower($part);
        $letters = preg_replace('/[^\p{L}]/u', '', $part);

        if ($letters === '' || preg_match('/^\d/', $part)) {
            return $part;                                     // "4th", "2026", "'26"
        }
        if (preg_match('/^\p{Lu}$/u', $part)) {
            return $part;                                     // a label like "Bldg A" or "Room C"
        }
        if (mb_strtoupper($letters) === $letters && mb_strlen($letters) >= 2) {
            return in_array($lower, self::CAPS_WORDS, true) ? self::ucfirst($lower, $capitalize, $lower) : $part;
        }
        if (in_array($lower, self::ACRONYMS, true)) {
            return mb_strtoupper($part);
        }
        if (preg_match('/^.\p{Ll}*\p{Lu}/u', $part)) {
            return $part;                                     // "InNEUvation", "eCFA", "CoMEDICINE"
        }

        return self::ucfirst($part, $capitalize, $lower);
    }

    private static function ucfirst(string $part, bool $capitalize, string $lower): string
    {
        if (! $capitalize && in_array($lower, self::SMALL, true)) {
            return $lower;
        }

        return mb_strtoupper(mb_substr($part, 0, 1)).mb_substr($part, 1);
    }
}
