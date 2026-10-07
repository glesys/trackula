<?php

declare(strict_types=1);

use Glesys\Trackula\Normalizer;
use Glesys\Trackula\Rules\Rule;
use Glesys\Trackula\Rules\RuleMatcher;

// The test numbers ship with the rule data: upstream's from
// jkeen/tracking_number_data, ours from carrier specifications.

it('matches every valid test number', function (Rule $rule, string $number) {
    expect(RuleMatcher::match($rule, Normalizer::normalize($number)))->not->toBeNull();
})->with(rule_test_numbers('valid'));

it('rejects every invalid test number', function (Rule $rule, string $number) {
    expect(RuleMatcher::match($rule, Normalizer::normalize($number)))->toBeNull();
})->with(rule_test_numbers('invalid'));
