<?php

namespace App\Support;

/**
 * Helpers shared by every search box (the search() scopes and the DataTables endpoints).
 *
 * A search is split into words, and EVERY word must match at least one column,
 * so "ali support" finds Ali in the Support department.
 */
final class Search
{
    /**
     * "  ali   support " → ['ali', 'support']
     *
     * @return list<string>
     */
    public static function words(?string $term): array
    {
        return preg_split('/\s+/', trim((string) $term), -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * The LIKE pattern for a word. % and _ are escaped, so "50%" looks for the text "50%".
     */
    public static function like(string $word): string
    {
        return '%'.addcslashes($word, '%_\\').'%';
    }

    /**
     * Dates are typed the Arabic/European way and stored as yyyy-mm-dd:
     * "25/12/2026" → "2026-12-25" (a day), "12/2026" → "2026-12" (a month), anything else → null.
     * Used as LIKE "2026-12-25%" so it also matches datetime columns (created_at).
     */
    public static function date(string $word): ?string
    {
        if (preg_match('#^(\d{1,2})[/.-](\d{1,2})[/.-](\d{4})$#', $word, $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        if (preg_match('#^(\d{1,2})[/.-](\d{4})$#', $word, $m) && (int) $m[1] >= 1 && (int) $m[1] <= 12) {
            return sprintf('%04d-%02d', $m[2], $m[1]);
        }

        return null;
    }
}
