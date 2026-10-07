<?php

declare(strict_types=1);

namespace Glesys\Trackula\Rules\Checksums;

final class Mod7
{
    public static function validate(string $serial, string $checkDigit): bool
    {
        // Digit by digit, since serials can exceed PHP_INT_MAX.
        $remainder = 0;

        foreach (str_split($serial) as $character) {
            $remainder = (($remainder * 10) + (int) $character) % 7;
        }

        return $remainder === (int) $checkDigit;
    }
}
