<?php

declare(strict_types=1);

namespace Glesys\Trackula;

use Glesys\Trackula\Rules\RuleMatch;
use Glesys\Trackula\Rules\RuleMatcher;
use Glesys\Trackula\Rules\RuleRepository;

/**
 * Guesses the carrier behind a tracking number, offline.
 *
 *     $result = Trackula::create()->detect('1Z999AA10123456784');
 *     $result->best()?->carrier->name; // "UPS"
 */
final class Trackula
{
    public function __construct(
        private readonly RuleRepository $rules,
    ) {}

    /**
     * Uses the bundled rule data, read once per process.
     */
    public static function create(): self
    {
        static $rules = null;

        $rules ??= RuleRepository::fromDirectory(dirname(__DIR__).'/data');

        return new self($rules);
    }

    /**
     * Never throws on bad input: an unknown number gives an empty result.
     *
     * @param  ?Hints  $hints  Reserved; not used yet.
     */
    public function detect(string $number, ?Hints $hints = null): Result
    {
        $normalized = Normalizer::normalize($number);

        /** @var array<string, Hit> $best */
        $best = [];

        foreach ($this->rules->rules() as $rule) {
            $match = RuleMatcher::match($rule, $normalized);

            if ($match === null) {
                continue;
            }

            foreach ($this->hits($match) as $hit) {
                $current = $best[$hit->carrier->id] ?? null;

                if ($current === null || self::strength($hit) > self::strength($current)) {
                    $best[$hit->carrier->id] = $hit;
                }
            }
        }

        return new Result($normalized, Scorer::rank($normalized, array_values($best)));
    }

    /**
     * @return list<Hit>
     */
    private function hits(RuleMatch $match): array
    {
        $rule = $match->rule;

        if ($rule->isS10()) {
            $country = $match->group('CountryCode') ?? '';
            $operator = $match->lookups['Courier']['courier'] ?? null;

            return [new Hit(
                $this->rules->carriers()->forS10Country($country, is_string($operator) ? $operator : $country),
                $match,
            )];
        }

        $carriers = array_map($this->rules->carriers()->get(...), $rule->carrierIds);
        $gs1Number = $match->group('GS1Number');

        if ($gs1Number !== null) {
            $issuers = array_values(array_filter($carriers, static fn (Carrier $carrier) => $carrier->issues(
                $gs1Number,
            )));

            if ($issuers !== []) {
                return array_map(static fn (Carrier $carrier) => new Hit($carrier, $match, ownRange: true), $issuers);
            }
        }

        return array_map(static fn (Carrier $carrier) => new Hit($carrier, $match), $carriers);
    }

    /**
     * Which of two matches for the same carrier to keep.
     */
    private static function strength(Hit $hit): int
    {
        return ($hit->ownRange ? 2 : 0) + ($hit->match->checksumVerified === true ? 1 : 0);
    }
}
