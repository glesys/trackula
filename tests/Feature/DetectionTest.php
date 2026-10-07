<?php

declare(strict_types=1);

use Glesys\Trackula\Candidate;
use Glesys\Trackula\Confidence;
use Glesys\Trackula\Region;
use Glesys\Trackula\Trackula;

/**
 * @return list<string> "carrier:confidence", best first.
 */
function ranking(string $number): array
{
    return array_map(
        static fn (Candidate $c) => "{$c->carrier->id}:{$c->confidence->value}",
        Trackula::create()->detect($number)->candidates,
    );
}

function best(string $number): Candidate
{
    return Trackula::create()->detect($number)->best() ?? throw new RuntimeException("No candidate for {$number}.");
}

it('ranks candidates for known shapes', function (string $number, array $expected) {
    expect(ranking($number))->toBe($expected);
})->with([
    'UPS 1Z' => ['1Z5R89390357567127', ['ups:certain']],
    'S10 from Sweden is PostNord' => ['RR123456785SE', ['postnord:certain']],
    'S10 from Norway is Bring' => ['CD656914836NO', ['bring:certain']],
    'PostNord legacy numeric' => ['84971563697SE', ['postnord:certain']],
    'SSCC in Bring\'s own range' => ['370438101268058536', ['bring:certain']],
    'GSIN in Bring\'s own range' => ['70438101268018539', ['bring:certain']],
    'SSCC in a sender\'s range, Nordic carriers in configured order' => [
        '373999991234567899',
        ['postnord:likely', 'bring:likely', 'dhl-freight-se:likely', 'dsv-road:likely', 'posti:likely'],
    ],
    'SSCC with AI 00 also passes USPS 20' => [
        '(00)370438101268058536',
        ['bring:likely', 'usps:likely'],
    ],
    'Swedish waybill' => ['9500486734', ['dhl-freight-se:likely', 'dsv-road:likely']],
    'Swedish waybill that also passes DHL Express mod 7' => [
        '1806273700',
        ['dhl-freight-se:likely', 'dsv-road:likely', 'dhl-express:likely'],
    ],
    'GLS' => ['641012345674', ['gls:likely']],
    'Bring label-free code' => ['BRING-1234-5678', ['bring:likely']],
    'Posti JJFI' => ['JJFI64574900000202361', ['posti:likely']],
]);

it('returns an empty result for unknown input', function (string $number) {
    $result = Trackula::create()->detect($number);

    expect($result->isEmpty())->toBeTrue()->and($result->best())->toBeNull();
})->with(['', 'hello', '1234', '9500486735', 'RR123456786SE']);

it('describes the best candidate', function () {
    $best = best(' 1z5r8 9390 3575 67127 ');

    expect($best->carrier->name)
        ->toBe('UPS')
        ->and($best->carrier->region)
        ->toBe(Region::International)
        ->and($best->format)
        ->toBe('UPS')
        ->and($best->checksumVerified)
        ->toBeTrue()
        ->and($best->confidence)
        ->toBe(Confidence::Certain)
        ->and($best->trackingUrl)
        ->toBe('https://wwwapps.ups.com/WebTracking/track?track=yes&trackNums=1Z5R89390357567127');
});

it('reports the service when the rule names one', function () {
    expect(best('1ZXX3150YW44070023')->service)->toBe('UPS SurePost - Delivered by the USPS');
});

it('leaves checksumVerified null for formats without a check digit', function () {
    expect(best('BRING-1234-5678')->checksumVerified)->toBeNull();
});

it('keeps upstream operator names for unmapped S10 countries', function () {
    $best = best('RB123456785DE');

    expect($best->carrier->id)
        ->toBe('s10-de')
        ->and($best->carrier->name)
        ->toBe('Deutsche Post')
        ->and($best->carrier->region)
        ->toBe(Region::International)
        ->and($best->trackingUrl)
        ->toBeNull();
});

it('puts the normalized number in the tracking URL', function () {
    expect(best('(00) 3704 3810 1268 0585 36')->trackingUrl)
        ->toBe('https://tracking.bring.com/tracking/00370438101268058536');
});
