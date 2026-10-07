<?php

declare(strict_types=1);

namespace Glesys\Trackula;

use Glesys\Trackula\Rules\RuleMatch;

/**
 * A rule match attributed to one carrier, before scoring.
 *
 * @internal
 */
final readonly class Hit
{
    /**
     * @param  bool  $ownRange  The GS1 number lies in a prefix the carrier issues itself.
     */
    public function __construct(
        public Carrier $carrier,
        public RuleMatch $match,
        public bool $ownRange = false,
    ) {}
}
