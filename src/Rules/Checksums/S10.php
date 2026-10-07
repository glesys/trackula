<?php

declare(strict_types=1);

namespace Glesys\Trackula\Rules\Checksums;

/**
 * UPU S10 mod 11. Unlike the Ruby reference, `weightings` is honoured, so
 * PostNord's ten-digit numeric format can reuse it.
 */
final class S10
{
    /**
     * @param  array<string, mixed>  $params
     */
    public static function validate(string $serial, string $checkDigit, array $params): bool
    {
        $weights = Params::weights($params, [8, 6, 4, 2, 3, 5, 9, 7]);
        $total = 0;

        foreach (str_split($serial) as $i => $character) {
            $total += (int) $character * ($weights[$i] ?? 0);
        }

        $check = match ($remainder = $total % 11) {
            0 => 5,
            1 => 0,
            default => 11 - $remainder,
        };

        return $check === (int) $checkDigit;
    }
}
