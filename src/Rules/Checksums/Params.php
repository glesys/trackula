<?php

declare(strict_types=1);

namespace Glesys\Trackula\Rules\Checksums;

/**
 * Typed reads from a rule's `validation.checksum` object.
 */
final class Params
{
    /**
     * @param  array<string, mixed>  $params
     */
    public static function int(array $params, string $key, int $default): int
    {
        $value = $params[$key] ?? $default;

        return is_numeric($value) ? (int) $value : $default;
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  list<int>  $default
     * @return list<int>
     */
    public static function weights(array $params, array $default): array
    {
        $weights = $params['weightings'] ?? $default;

        return is_array($weights) ? array_map(intval(...), array_values($weights)) : $default;
    }
}
