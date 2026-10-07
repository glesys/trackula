<?php

declare(strict_types=1);

namespace Glesys\Trackula\Rules\Checksums;

/**
 * ISO 7064 mod 37/36, from DPD's parcel label specification.
 */
final class Mod3736
{
    private const int MOD = 36;

    public static function validate(string $serial, string $checkDigit): bool
    {
        $cd = self::MOD;

        foreach (str_split($serial) as $character) {
            $cd = self::step($cd, ctype_alpha($character) ? ord($character) - ord('A') + 10 : (int) $character);
        }

        $cd = (self::MOD + 1 - $cd) % self::MOD;

        return ($cd >= 10 ? chr(ord('A') + $cd - 10) : (string) $cd) === $checkDigit;
    }

    private static function step(int $cd, int $value): int
    {
        $cd += $value;
        $cd = $cd > self::MOD ? $cd - self::MOD : $cd;
        $cd *= 2;

        return $cd > self::MOD ? $cd - (self::MOD + 1) : $cd;
    }
}
