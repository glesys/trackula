<?php

declare(strict_types=1);

namespace Glesys\Trackula\Rules;

final readonly class RuleMatch
{
    /**
     * @param  array<string, string>  $groups  Named groups that matched.
     * @param  ?bool  $checksumVerified  Null when the rule has no checksum.
     * @param  array<string, array<string, mixed>>  $lookups  Matched lookup entries by name ("Service Type", "Courier", …).
     */
    public function __construct(
        public Rule $rule,
        public array $groups,
        public ?bool $checksumVerified,
        public array $lookups,
    ) {}

    public function group(string $name): ?string
    {
        return $this->groups[$name] ?? null;
    }

    public function service(): ?string
    {
        $name = $this->lookups['Service Type']['name'] ?? null;

        return is_string($name) ? $name : null;
    }
}
