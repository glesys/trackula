<?php

declare(strict_types=1);

namespace Glesys\Trackula;

enum Region: string
{
    case Nordic = 'nordic';
    case European = 'european';
    case International = 'international';

    /**
     * Nordic carriers win ties, then European, then the rest.
     */
    public function rank(): int
    {
        return match ($this) {
            self::Nordic => 3,
            self::European => 2,
            self::International => 1,
        };
    }
}
