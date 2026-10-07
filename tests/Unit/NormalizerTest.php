<?php

declare(strict_types=1);

use Glesys\Trackula\Normalizer;

it('normalizes tracking numbers', function (string $input, string $expected) {
    expect(Normalizer::normalize($input))->toBe($expected);
})->with([
    'spaces and case' => [' 1z999aa1 0123456784 ', '1Z999AA10123456784'],
    'GS1 application identifier' => ['(00) 3704 3810 1268 0585 36', '00370438101268058536'],
    'tabs and newlines' => ["RR123456785SE\n", 'RR123456785SE'],
    'dashes are kept' => ['BRING-1234-5678', 'BRING-1234-5678'],
]);
