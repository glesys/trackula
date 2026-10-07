<?php

declare(strict_types=1);

namespace Glesys\Trackula;

/**
 * Context that can sharpen the ranking of ambiguous numbers, such as the
 * origin country or the carriers a site usually receives from.
 *
 * Empty for now: it exists so hints can be added without changing the
 * signature of Trackula::detect().
 */
final readonly class Hints {}
