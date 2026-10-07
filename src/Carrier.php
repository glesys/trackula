<?php

declare(strict_types=1);

namespace Glesys\Trackula;

final readonly class Carrier
{
    /**
     * @param  list<string>  $gs1Prefixes  GS1 company prefixes the carrier issues itself.
     * @param  int  $priority  Tie-breaker within a region; lower wins. The order of carriers.json.
     */
    public function __construct(
        public string $id,
        public string $name,
        public Region $region,
        public ?string $trackingUrl = null,
        public array $gs1Prefixes = [],
        public int $priority = PHP_INT_MAX,
    ) {}

    public function issues(string $gs1Number): bool
    {
        foreach ($this->gs1Prefixes as $prefix) {
            if (str_starts_with($gs1Number, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
