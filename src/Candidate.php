<?php

declare(strict_types=1);

namespace Glesys\Trackula;

final readonly class Candidate
{
    /**
     * @param  int  $score  For ordering and debugging only; branch on $confidence.
     * @param  ?bool  $checksumVerified  Null when the format has no check digit.
     */
    public function __construct(
        public Carrier $carrier,
        public string $format,
        public ?string $service,
        public Confidence $confidence,
        public int $score,
        public ?bool $checksumVerified,
        public ?string $trackingUrl,
    ) {}
}
