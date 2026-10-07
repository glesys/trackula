<?php

declare(strict_types=1);

namespace Glesys\Trackula;

/**
 * Turns per-carrier matches into ranked candidates.
 *
 * The score orders candidates; the confidence is derived by rule so that
 * callers never have to interpret score thresholds.
 */
final class Scorer
{
    private const int FORMAT = 20;
    private const int CHECKSUM = 40;
    private const int DISTINCTIVE = 25;
    private const int ONLY_CARRIER = 15;

    /**
     * @param  list<Hit>  $hits  At most one per carrier.
     * @return list<Candidate>
     */
    public static function rank(string $normalized, array $hits): array
    {
        $verifiedCarriers = count(array_filter($hits, static fn (Hit $hit) => $hit->match->checksumVerified === true));

        $candidates = array_map(static fn (Hit $hit) => self::candidate(
            $normalized,
            $hit,
            count($hits),
            $verifiedCarriers,
        ), $hits);

        usort(
            $candidates,
            static fn (Candidate $a, Candidate $b) => (
                [
                    $b->score,
                    $b->carrier->region->rank(),
                    $a->carrier->priority,
                    $a->carrier->id,
                ] <=> [
                    $a->score,
                    $a->carrier->region->rank(),
                    $b->carrier->priority,
                    $b->carrier->id,
                ]
            ),
        );

        return $candidates;
    }

    private static function candidate(string $normalized, Hit $hit, int $carriers, int $verifiedCarriers): Candidate
    {
        $verified = $hit->match->checksumVerified === true;
        $distinctive = $hit->ownRange || preg_match('/[A-Z]/', $normalized) === 1;

        $score =
            self::FORMAT
            + ($verified ? self::CHECKSUM : 0)
            + ($distinctive ? self::DISTINCTIVE : 0)
            + ($carriers === 1 ? self::ONLY_CARRIER : 0)
            + $hit->carrier->region->rank();

        $confidence = match (true) {
            $distinctive && $verified && $verifiedCarriers === 1 => Confidence::Certain,
            $distinctive || $verified => Confidence::Likely,
            default => Confidence::Possible,
        };

        $template = $hit->carrier->trackingUrl ?? $hit->match->rule->trackingUrl;

        return new Candidate(
            carrier: $hit->carrier,
            format: $hit->match->rule->name,
            service: $hit->match->service(),
            confidence: $confidence,
            score: $score,
            checksumVerified: $hit->match->checksumVerified,
            trackingUrl: $template === null ? null : str_replace('%s', rawurlencode($normalized), $template),
        );
    }
}
