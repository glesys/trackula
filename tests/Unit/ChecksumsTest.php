<?php

declare(strict_types=1);

use Glesys\Trackula\Rules\Checksums;

$gs1 = ['name' => 'mod10', 'evens_multiplier' => 3, 'odds_multiplier' => 1, 'reverse' => true];

it('validates GS1 mod 10 with jkeen\'s mod10 parameters', function (string $serial, string $check, bool $valid) use (
    $gs1,
) {
    expect(Checksums::validate($serial, $check, $gs1))->toBe($valid);
})->with([
    'Bring SSCC, Appendix F' => ['37610425002123456', '9', true],
    'DHL Freight SE SSCC' => ['37399999123456789', '9', true],
    'Bring GSIN' => ['7043810126801853', '9', true],
    'Bring placeholder GSIN' => ['7043810126379741', '0', false],
]);

it('validates mod10 with alphanumeric characters like UPS', function () {
    $ups = ['name' => 'mod10', 'evens_multiplier' => 1, 'odds_multiplier' => 2, 'reverse' => false];

    expect(Checksums::validate('5R8939035756712', '7', $ups))
        ->toBeTrue()
        ->and(Checksums::validate('5R8939035756712', '8', $ups))
        ->toBeFalse();
});

it('validates S10 with the default UPU weights', function (string $serial, string $check, bool $valid) {
    $s10 = ['name' => 's10', 'weightings' => [8, 6, 4, 2, 3, 5, 9, 7], 'modulo' => 11];

    expect(Checksums::validate($serial, $check, $s10))->toBe($valid);
})->with([
    'remainder maps to 11 - r' => ['12345678', '5', true],
    'PostNord letter spec example' => ['47312482', '9', true],
    'Bring test fixture, remainder 0 gives 5' => ['00000000', '5', true],
    'PostNord doc example with wrong digit' => ['12345678', '6', false],
]);

it('honours S10 weightings for PostNord\'s legacy numeric format', function (
    string $serial,
    string $check,
    bool $valid,
) {
    $legacy = ['name' => 's10', 'weightings' => [9, 7, 8, 6, 4, 2, 3, 5, 9, 7], 'modulo' => 11];

    expect(Checksums::validate($serial, $check, $legacy))->toBe($valid);
})->with([
    ['8497156369', '7', true],
    ['7702188688', '4', true],
    ['9693200755', '5', true],
    ['9693200755', '6', false],
]);

it('validates Luhn as used by Swedish 10-digit waybills', function (string $serial, string $check, bool $valid) {
    expect(Checksums::validate($serial, $check, ['name' => 'luhn']))->toBe($valid);
})->with([
    'DHL Freight SE manual' => ['950048673', '4', true],
    'DSV Road' => ['180620323', '6', true],
    'wrong digit' => ['950048673', '5', false],
]);

it('validates GLS mod 10 plus one', function (string $serial, string $check, bool $valid) {
    expect(Checksums::validate($serial, $check, ['name' => 'gls_mod10']))->toBe($valid);
})->with([
    'Uni-Box example' => ['64101234567', '4', true],
    'wrong digit' => ['64101234567', '5', false],
]);

it('validates mod 7 like DHL Express', function () {
    expect(Checksums::validate('309734892', '5', ['name' => 'mod7']))
        ->toBeTrue()
        ->and(Checksums::validate('309734892', '4', ['name' => 'mod7']))
        ->toBeFalse();
});

// FedEx's weighted sum product and DPD's mod 37/36 are covered by the upstream test numbers.

it('rejects unknown checksum names', function () {
    Checksums::validate('123', '4', ['name' => 'nope']);
})->throws(InvalidArgumentException::class);
