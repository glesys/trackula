<?php

declare(strict_types=1);

namespace Glesys\Trackula;

final readonly class Result
{
    /**
     * @param  list<Candidate>  $candidates  Best first.
     */
    public function __construct(
        public string $normalized,
        public array $candidates,
    ) {}

    public function best(): ?Candidate
    {
        return $this->candidates[0] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->candidates === [];
    }
}
