<?php

declare(strict_types=1);

namespace Glesys\Trackula\Rules\Checksums;

/**
 * Weighted sum, then two moduli in turn, as FedEx uses.
 */
final class SumProduct
{
    /**
     * @param  array<string, mixed>  $params
     */
    public static function validate(string $serial, string $checkDigit, array $params): bool
    {
        $weights = Params::weights($params, []);
        $total = 0;

        foreach (str_split($serial) as $i => $character) {
            $total += (int) $character * ($weights[$i] ?? 0);
        }

        return (
            ($total % Params::int($params, 'modulo1', 11) % Params::int($params, 'modulo2', 10)) === (int) $checkDigit
        );
    }
}
