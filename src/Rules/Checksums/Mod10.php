<?php

declare(strict_types=1);

namespace Glesys\Trackula\Rules\Checksums;

/**
 * Weighted mod 10. Letters count as (ASCII - 3) mod 10, as UPS does.
 * With `reverse`, weighting starts at the rightmost character, which is
 * how GS1 keys (SSCC, GSIN) are checked.
 */
final class Mod10
{
    /**
     * @param  array<string, mixed>  $params
     */
    public static function validate(string $serial, string $checkDigit, array $params): bool
    {
        $characters = str_split($serial);

        if ($params['reverse'] ?? false) {
            $characters = array_reverse($characters);
        }

        $odds = Params::int($params, 'odds_multiplier', 1);
        $evens = Params::int($params, 'evens_multiplier', 1);
        $total = 0;

        foreach ($characters as $i => $character) {
            $value = ctype_digit($character) ? (int) $character : (ord($character) - 3) % 10;
            $total += $value * (($i % 2) === 1 ? $odds : $evens);
        }

        return ((10 - ($total % 10)) % 10) === (int) $checkDigit;
    }
}
