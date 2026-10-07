<?php

declare(strict_types=1);

namespace Glesys\Trackula\Rules;

use RuntimeException;

/**
 * One tracking-number definition from the rule data, in
 * jkeen/tracking_number_data's schema.
 *
 * @phpstan-type Definition array{
 *     id?: ?string,
 *     name: string,
 *     regex: string|list<string>,
 *     validation?: array{checksum?: array<string, mixed>, serial_number_format?: array<string, mixed>, additional?: array{exists?: list<string>}},
 *     additional?: list<array<string, mixed>>,
 *     tracking_url?: ?string,
 *     test_numbers?: array{valid?: list<string>, invalid?: list<string>},
 * }
 */
final readonly class Rule
{
    /**
     * @param  string  $key  "<courier>/<id>", unique across all rule files.
     * @param  list<string>  $carrierIds  Carriers a match belongs to. Empty for S10, which resolves its carrier from the country code.
     * @param  ?array<string, mixed>  $checksum  The `validation.checksum` object.
     * @param  ?array{matches_regex: string, content: string}  $prependIf  From `validation.serial_number_format`: prefix added to matching serials before the checksum.
     * @param  list<string>  $requiredLookups  Names from `validation.additional.exists`.
     * @param  list<array{name: string, group: string, entries: list<array<string, mixed>>}>  $lookupTables  From `additional`: lookups keyed on named groups.
     * @param  array{valid: list<string>, invalid: list<string>}  $testNumbers
     */
    public function __construct(
        public string $key,
        public string $source,
        public string $name,
        public string $pattern,
        public array $carrierIds,
        public ?array $checksum = null,
        public ?array $prependIf = null,
        public array $requiredLookups = [],
        public array $lookupTables = [],
        public ?string $trackingUrl = null,
        public array $testNumbers = ['valid' => [], 'invalid' => []],
    ) {}

    /**
     * Builds a rule from one entry of a courier file's `tracking_numbers`.
     *
     * @param  Definition  $definition
     * @param  list<string>  $carrierIds
     */
    public static function fromDefinition(string $key, string $source, array $definition, array $carrierIds): Rule
    {
        $regex = is_array($definition['regex']) ? implode('', $definition['regex']) : $definition['regex'];

        if (str_contains($regex, '~')) {
            throw new RuntimeException("Rule {$key} uses the pattern delimiter ~.");
        }

        return new self(
            key: $key,
            source: $source,
            name: $definition['name'],
            pattern: '~^(?:'.$regex.')$~',
            carrierIds: $carrierIds,
            checksum: $definition['validation']['checksum'] ?? null,
            prependIf: self::prependIf($definition['validation']['serial_number_format'] ?? []),
            requiredLookups: $definition['validation']['additional']['exists'] ?? [],
            lookupTables: self::lookupTables($definition['additional'] ?? []),
            trackingUrl: $definition['tracking_url'] ?? null,
            testNumbers: [
                'valid' => $definition['test_numbers']['valid'] ?? [],
                'invalid' => $definition['test_numbers']['invalid'] ?? [],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $format
     * @return ?array{matches_regex: string, content: string}
     */
    private static function prependIf(array $format): ?array
    {
        $prepend = $format['prepend_if'] ?? null;

        if (
            ! is_array($prepend)
            || ! is_string($prepend['matches_regex'] ?? null)
            || ! is_string($prepend['content'] ?? null)
        ) {
            return null;
        }

        return ['matches_regex' => $prepend['matches_regex'], 'content' => $prepend['content']];
    }

    /**
     * @param  list<array<string, mixed>>  $additional
     * @return list<array{name: string, group: string, entries: list<array<string, mixed>>}>
     */
    private static function lookupTables(array $additional): array
    {
        $tables = [];

        foreach ($additional as $table) {
            $name = $table['name'] ?? null;
            $group = $table['regex_group_name'] ?? null;
            $entries = $table['lookup'] ?? null;

            if (is_string($name) && is_string($group) && is_array($entries)) {
                /** @var list<array<string, mixed>> $entries */
                $tables[] = ['name' => $name, 'group' => $group, 'entries' => array_values($entries)];
            }
        }

        return $tables;
    }

    public function isS10(): bool
    {
        return $this->key === 's10/s10';
    }
}
