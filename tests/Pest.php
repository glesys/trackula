<?php

declare(strict_types=1);

use Glesys\Trackula\Rules\Rule;
use Glesys\Trackula\Rules\RuleRepository;

function repository(): RuleRepository
{
    static $repository = null;

    return $repository ??= RuleRepository::fromDirectory(dirname(__DIR__).'/data');
}

/**
 * Every rule's test numbers as a dataset: "<rule key>: <number>" => [rule, number].
 *
 * @return array<string, array{Rule, string}>
 */
function rule_test_numbers(string $kind): array
{
    $dataset = [];

    foreach (repository()->rules() as $rule) {
        foreach ($rule->testNumbers[$kind] as $number) {
            $dataset["{$rule->key}: {$number}"] = [$rule, $number];
        }
    }

    return $dataset;
}
