<?php

declare(strict_types=1);

use Glesys\Trackula\Region;
use Glesys\Trackula\Rules\Rule;

/**
 * carriers.json as written, before the repository fills in defaults.
 *
 * @return array{
 *     carriers: array<string, array{name?: string, region?: string, tracking_url?: string}>,
 *     upstream: array<string, string>,
 * }
 */
function carriers_config(): array
{
    /** @var array{carriers: array<string, array{name?: string, region?: string, tracking_url?: string}>, upstream: array<string, string>} */
    return json_decode(
        (string) file_get_contents(dirname(__DIR__, levels: 2).'/data/carriers.json'),
        associative: true,
        flags: JSON_THROW_ON_ERROR,
    );
}

it('gives every configured carrier a name, region and tracking URL', function () {
    foreach (carriers_config()['carriers'] as $id => $carrier) {
        expect($carrier['name'] ?? null)
            ->toBeString("{$id} has no name")
            ->and(Region::tryFrom($carrier['region'] ?? ''))
            ->not
            ->toBeNull("{$id} has no valid region")
            ->and($carrier['tracking_url'] ?? '')
            ->toContain('%s');
    }
});

it('maps upstream rules to configured carriers only', function () {
    $config = carriers_config();

    foreach ($config['upstream'] as $carrierId) {
        expect($config['carriers'])->toHaveKey($carrierId);
    }
});

it('gives every rule a unique key and at least one valid test number', function () {
    $keys = array_map(static fn (Rule $rule) => $rule->key, repository()->rules());

    expect($keys)->toBe(array_unique($keys));

    foreach (repository()->rules() as $rule) {
        expect($rule->testNumbers['valid'])->not->toBeEmpty("{$rule->key} has no valid test numbers");
    }
});

it('pins the upstream data to a release tag', function () {
    [$tag, $sha] = explode(
        "\n",
        trim((string) file_get_contents(dirname(__DIR__, levels: 2).'/data/upstream/VERSION')),
    );

    expect($tag)->toMatch('/^v\d+\.\d+\.\d+$/')->and($sha)->toMatch('/^[0-9a-f]{40}$/');
});
