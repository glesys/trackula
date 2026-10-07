<?php

declare(strict_types=1);

namespace Glesys\Trackula\Rules\Checksums;

final class Luhn
{
    public static function validate(string $serial, string $checkDigit): bool
    {
        $total = 0;

        foreach (array_reverse(str_split($serial)) as $i => $character) {
            $value = (int) $character * (($i % 2) === 0 ? 2 : 1);
            $total += $value > 9 ? $value - 9 : $value;
        }

        return ((10 - ($total % 10)) % 10) === (int) $checkDigit;
    }
}
