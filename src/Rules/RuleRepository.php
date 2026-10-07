<?php

declare(strict_types=1);

namespace Glesys\Trackula\Rules;

use RuntimeException;

/**
 * Loads upstream and Glesys rules and attributes each to its carriers.
 *
 * data/
 *   upstream/couriers/*.json  jkeen/tracking_number_data, untouched
 *   glesys/*.json             our rules, same schema; courier_code may list several carriers
 *   carriers.json             carriers, upstream mapping, S10 countries, disabled rules
 *
 * @phpstan-import-type Definition from Rule
 * @phpstan-type CourierFile array{name: string, courier_code: string|list<string>, tracking_numbers: list<Definition>}
 * @phpstan-type Config array{
 *     carriers: array<string, array{name: string, region: string, tracking_url?: ?string, gs1_prefixes?: list<string>}>,
 *     upstream?: array<string, string>,
 *     s10_countries?: array<string, string>,
 *     disabled?: array<string, string>,
 * }
 */
final class RuleRepository
{
    /** @var list<Rule> */
    private array $rules = [];

    private function __construct(
        private readonly Carriers $carriers,
    ) {}

    public static function fromDirectory(string $directory): self
    {
        /** @var Config $config */
        $config = self::readJson("{$directory}/carriers.json");

        $repository = new self(new Carriers($config['carriers'], $config['s10_countries'] ?? []));
        $repository->loadUpstreamRules(
            "{$directory}/upstream/couriers",
            $config['upstream'] ?? [],
            $config['disabled'] ?? [],
        );
        $repository->loadGlesysRules("{$directory}/glesys");

        return $repository;
    }

    /**
     * @return list<Rule>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    public function carriers(): Carriers
    {
        return $this->carriers;
    }

    /**
     * Upstream rules belong to the carrier carriers.json maps them to, or
     * to an international carrier named after the upstream courier.
     *
     * @param  array<string, string>  $mapping  "<courier>/<id>" or "<courier>/*" → carrier id.
     * @param  array<string, string>  $disabled  Rule key → reason.
     */
    private function loadUpstreamRules(string $directory, array $mapping, array $disabled): void
    {
        $this->carriers->assertKnown(array_values($mapping), 'carriers.json upstream');

        foreach (self::files($directory) as $file) {
            /** @var CourierFile $courier */
            $courier = self::readJson($file);
            $code = $courier['courier_code'];

            if (! is_string($code)) {
                throw new RuntimeException("{$file} must name exactly one courier_code.");
            }

            foreach ($courier['tracking_numbers'] as $definition) {
                $key = $code.'/'.($definition['id'] ?? self::slug($definition['name']));

                if (array_key_exists($key, $disabled)) {
                    continue;
                }

                $this->rules[] = Rule::fromDefinition(
                    $key,
                    'upstream',
                    $definition,
                    $this->upstreamCarrierIds($key, $code, $courier['name'], $mapping),
                );
            }
        }
    }

    /**
     * @param  array<string, string>  $mapping
     * @return list<string>
     */
    private function upstreamCarrierIds(string $key, string $code, string $courierName, array $mapping): array
    {
        // S10 resolves its carrier from the country code at match time.
        if ($key === 's10/s10') {
            return [];
        }

        $carrierId = $mapping[$key] ?? $mapping["{$code}/*"] ?? $code;

        return [$this->carriers->getOrCreate($carrierId, $courierName)->id];
    }

    private function loadGlesysRules(string $directory): void
    {
        foreach (self::files($directory) as $file) {
            /** @var CourierFile $courier */
            $courier = self::readJson($file);
            $carrierIds = (array) $courier['courier_code'];

            $this->carriers->assertKnown($carrierIds, $file);

            foreach ($courier['tracking_numbers'] as $definition) {
                $key = basename($file, suffix: '.json').'/'.($definition['id'] ?? self::slug($definition['name']));
                $this->rules[] = Rule::fromDefinition($key, 'glesys', $definition, $carrierIds);
            }
        }
    }

    /**
     * @return list<string>
     */
    private static function files(string $directory): array
    {
        $files = glob("{$directory}/*.json");

        if ($files === false) {
            return [];
        }

        sort($files);

        return $files;
    }

    /**
     * @return array<string, mixed>
     */
    private static function readJson(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("Cannot read {$path}.");
        }

        /** @var array<string, mixed> */
        return json_decode((string) file_get_contents($path), associative: true, flags: JSON_THROW_ON_ERROR);
    }

    private static function slug(string $name): string
    {
        return trim(
            (string) preg_replace('/[^a-z0-9]+/', replacement: '_', subject: strtolower($name)),
            characters: '_',
        );
    }
}
