<?php

declare(strict_types=1);

namespace Glesys\Trackula;

/**
 * Uppercases and strips whitespace and the brackets around GS1 Application
 * Identifiers, so "(00) 3704 3810 …" and "003704 3810…" read the same.
 *
 * Dashes are kept: some formats contain them (LaserShip, Bring label-free
 * codes), and those rules allow them explicitly.
 */
final class Normalizer
{
    public static function normalize(string $number): string
    {
        return strtoupper(preg_replace('/[\s()]+/u', replacement: '', subject: $number) ?? '');
    }
}
