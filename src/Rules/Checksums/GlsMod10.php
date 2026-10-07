<?php

declare(strict_types=1);

namespace Glesys\Trackula\Rules\Checksums;

/**
 * GLS parcel numbers: GS1 weighting (3 on the rightmost digit) plus one,
 * per GLS's Uni-Box technical documentation.
 */
final class GlsMod10
{
    public static function validate(string $serial, string $checkDigit): bool
    {
        $total = 1;

        foreach (array_reverse(str_split($serial)) as $i => $character) {
            $total += (int) $character * (($i % 2) === 0 ? 3 : 1);
        }

        return ((10 - ($total % 10)) % 10) === (int) $checkDigit;
    }
}
