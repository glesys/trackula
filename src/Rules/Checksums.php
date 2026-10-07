<?php

declare(strict_types=1);

namespace Glesys\Trackula\Rules;

use Glesys\Trackula\Rules\Checksums\GlsMod10;
use Glesys\Trackula\Rules\Checksums\Luhn;
use Glesys\Trackula\Rules\Checksums\Mod10;
use Glesys\Trackula\Rules\Checksums\Mod3736;
use Glesys\Trackula\Rules\Checksums\Mod7;
use Glesys\Trackula\Rules\Checksums\S10;
use Glesys\Trackula\Rules\Checksums\SumProduct;
use InvalidArgumentException;

/**
 * Check-digit algorithms named in the rule data.
 *
 * The upstream algorithms mirror the Ruby reference implementation in
 * jkeen/tracking_number (CHECKSUM_ALGORITHMS.md); `gls_mod10` is ours.
 */
final class Checksums
{
    /**
     * @param  array<string, mixed>  $params  The rule's `validation.checksum` object.
     */
    public static function validate(string $serial, string $checkDigit, array $params): bool
    {
        $name = $params['name'] ?? null;

        return match ($name) {
            'mod10' => Mod10::validate($serial, $checkDigit, $params),
            'mod7' => Mod7::validate($serial, $checkDigit),
            'luhn' => Luhn::validate($serial, $checkDigit),
            's10' => S10::validate($serial, $checkDigit, $params),
            'sum_product_with_weightings_and_modulo' => SumProduct::validate($serial, $checkDigit, $params),
            'mod_37_36' => Mod3736::validate($serial, $checkDigit),
            'gls_mod10' => GlsMod10::validate($serial, $checkDigit),
            default => throw new InvalidArgumentException('Unknown checksum: '.(is_string($name) ? $name : 'none')),
        };
    }
}
