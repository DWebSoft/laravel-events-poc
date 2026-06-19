<?php

use App\Services\LocationResolver;

/** The real anchor table, loaded without booting the framework. */
function anchorConfig(): array
{
    return require dirname(__DIR__, 2).'/config/city_anchors.php';
}

it('snaps a coordinate to its nearest anchor', function () {
    $resolver = new LocationResolver([
        ['city' => 'London', 'country' => 'United Kingdom', 'lat' => 51.5074, 'lng' => -0.1278, 'tz' => 'Europe/London'],
        ['city' => 'Paris', 'country' => 'France', 'lat' => 48.8566, 'lng' => 2.3522, 'tz' => 'Europe/Paris'],
    ]);

    $near = $resolver->resolve(51.49, -0.10); // just inside London

    expect($near->city)->toBe('London')
        ->and($near->country)->toBe('United Kingdom')
        ->and($near->timezone)->toBe('Europe/London')
        ->and($near->label())->toBe('London, United Kingdom')
        ->and($near->distanceKm)->toBeLessThan(5.0);
});

it('resolves a jittered seeded coordinate to the right city and timezone', function () {
    $resolver = new LocationResolver(anchorConfig());

    // A real seeded row: jittered ±0.5° around the Stockholm anchor.
    $resolved = $resolver->resolve(59.5783, 17.8276);

    expect($resolved->city)->toBe('Stockholm')
        ->and($resolved->timezone)->toBe('Europe/Stockholm')
        ->and($resolved->distanceKm)->toBeLessThan(60.0); // within the jitter radius
});

it('resolves an exact anchor to itself with ~zero distance', function () {
    $resolver = new LocationResolver(anchorConfig());

    $resolved = $resolver->resolve(40.7128, -74.0060); // New York anchor

    expect($resolved->city)->toBe('New York')
        ->and($resolved->timezone)->toBe('America/New_York')
        ->and($resolved->distanceKm)->toBe(0.0);
});

it('exposes the anchor list for a filter dropdown, sorted by label', function () {
    $resolver = new LocationResolver(anchorConfig());

    $cities = $resolver->cities();
    $labels = array_column($cities, 'label');
    $sorted = $labels;
    sort($sorted);

    expect($cities)->toHaveCount(count(anchorConfig()))
        ->and($labels)->toBe($sorted)
        ->and($cities[0])->toHaveKeys(['city', 'country', 'label', 'lat', 'lng', 'tz']);
});

it('has a complete, valid anchor table', function () {
    foreach (anchorConfig() as $anchor) {
        expect($anchor)->toHaveKeys(['city', 'country', 'lat', 'lng', 'tz'])
            ->and(in_array($anchor['tz'], timezone_identifiers_list(), true))->toBeTrue("Invalid tz: {$anchor['tz']}");
    }
});
