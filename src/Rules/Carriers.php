<?php

declare(strict_types=1);

namespace Glesys\Trackula\Rules;

use Glesys\Trackula\Carrier;
use Glesys\Trackula\Region;
use RuntimeException;

/**
 * The carriers from carriers.json, plus international carriers created on
 * demand for upstream couriers and S10 countries we have not configured.
 */
final class Carriers
{
    /** @var array<string, Carrier> */
    private array $carriers = [];

    /**
     * @param  array<string, array{name: string, region: string, tracking_url?: ?string, gs1_prefixes?: list<string>}>  $config
     * @param  array<string, string>  $s10Countries  Country code → carrier id.
     */
    public function __construct(
        array $config,
        private readonly array $s10Countries,
    ) {
        foreach ($config as $id => $carrier) {
            $this->carriers[$id] = new Carrier(
                id: $id,
                name: $carrier['name'],
                region: Region::from($carrier['region']),
                trackingUrl: $carrier['tracking_url'] ?? null,
                gs1Prefixes: $carrier['gs1_prefixes'] ?? [],
                priority: count($this->carriers),
            );
        }

        $this->assertKnown(array_values($s10Countries), 'carriers.json s10_countries');
    }

    /**
     * @return array<string, Carrier>
     */
    public function all(): array
    {
        return $this->carriers;
    }

    public function get(string $id): Carrier
    {
        return $this->carriers[$id] ?? throw new RuntimeException("Unknown carrier {$id}.");
    }

    /**
     * A configured carrier, or an international one named after its source.
     */
    public function getOrCreate(string $id, string $name): Carrier
    {
        return $this->carriers[$id] ??= new Carrier($id, $name, Region::International);
    }

    /**
     * The postal operator behind an S10 country code. Mapped countries
     * resolve to our carriers; the rest keep upstream's operator name.
     */
    public function forS10Country(string $countryCode, string $operatorName): Carrier
    {
        if (array_key_exists($countryCode, $this->s10Countries)) {
            return $this->get($this->s10Countries[$countryCode]);
        }

        return $this->getOrCreate('s10-'.strtolower($countryCode), $operatorName);
    }

    /**
     * @param  list<string>  $ids
     */
    public function assertKnown(array $ids, string $source): void
    {
        foreach ($ids as $id) {
            if (! array_key_exists($id, $this->carriers)) {
                throw new RuntimeException("{$source} refers to unknown carrier {$id}.");
            }
        }
    }
}
