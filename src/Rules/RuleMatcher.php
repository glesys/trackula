<?php

declare(strict_types=1);

namespace Glesys\Trackula\Rules;

/**
 * Matches one rule against a normalized number, following the Ruby
 * reference implementation: the whole number must match the pattern, the
 * check digit must verify, and every required lookup must find an entry.
 */
final class RuleMatcher
{
    public static function match(Rule $rule, string $number): ?RuleMatch
    {
        $matches = [];

        if (preg_match($rule->pattern, $number, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
            return null;
        }

        $groups = self::namedGroups($matches);
        $checksumVerified = self::verifyChecksum($rule, $groups);

        if ($checksumVerified === false) {
            return null;
        }

        $lookups = self::lookups($rule, $groups);

        if (array_diff($rule->requiredLookups, array_keys($lookups)) !== []) {
            return null;
        }

        return new RuleMatch($rule, $groups, $checksumVerified, $lookups);
    }

    /**
     * @param  array<array-key, ?string>  $matches
     * @return array<string, string>
     */
    private static function namedGroups(array $matches): array
    {
        $groups = [];

        foreach ($matches as $name => $value) {
            if (! is_string($name) || $value === null) {
                continue;
            }

            $groups[$name] = $value;
        }

        return $groups;
    }

    /**
     * @param  array<string, string>  $groups
     * @return ?bool  Null when the rule has no checksum.
     */
    private static function verifyChecksum(Rule $rule, array $groups): ?bool
    {
        if ($rule->checksum === null) {
            return null;
        }

        $serial = self::serialNumber($rule, $groups['SerialNumber'] ?? '');

        return Checksums::validate($serial, $groups['CheckDigit'] ?? '', $rule->checksum);
    }

    private static function serialNumber(Rule $rule, string $serial): string
    {
        $prepend = $rule->prependIf;

        if ($prepend !== null && preg_match(self::delimit($prepend['matches_regex']), $serial) === 1) {
            return $prepend['content'].$serial;
        }

        return $serial;
    }

    /**
     * @param  array<string, string>  $groups
     * @return array<string, array<string, mixed>>
     */
    private static function lookups(Rule $rule, array $groups): array
    {
        $found = [];

        foreach ($rule->lookupTables as $table) {
            $value = $groups[$table['group']] ?? null;

            if ($value === null) {
                continue;
            }

            foreach ($table['entries'] as $entry) {
                if (! self::entryMatches($entry, $value)) {
                    continue;
                }

                $found[$table['name']] = $entry;
                break;
            }
        }

        return $found;
    }

    /**
     * An entry matches by exact value (`matches`) or by regex (`matches_regex`).
     *
     * @param  array<string, mixed>  $entry
     */
    private static function entryMatches(array $entry, string $value): bool
    {
        $exact = $entry['matches'] ?? null;

        if (is_string($exact)) {
            return $value === $exact;
        }

        $regex = $entry['matches_regex'] ?? null;

        return is_string($regex) && preg_match(self::delimit($regex), $value) === 1;
    }

    private static function delimit(string $regex): string
    {
        return '~'.str_replace('~', replace: '\~', subject: $regex).'~';
    }
}
