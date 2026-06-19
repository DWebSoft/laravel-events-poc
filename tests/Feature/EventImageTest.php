<?php

use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\EventImage;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('falls back to a stable set of 2+ placeholder images when none are stored', function () {
    $event = Event::factory()->create();

    $first = $event->image_urls;

    expect($first)->toBeArray()
        ->and(count($first))->toBeGreaterThanOrEqual(2)
        ->and($first[0])->toContain('/storage/event-images/placeholder-');

    // Deterministic: a freshly loaded instance yields the same set.
    expect(Event::find($event->id)->image_urls)->toBe($first);
});

it('uses stored images when present, ordered by sort_order', function () {
    $event = Event::factory()->create();

    EventImage::create(['event_id' => $event->id, 'path' => 'event-images/a.jpg', 'sort_order' => 1]);
    EventImage::create(['event_id' => $event->id, 'path' => 'event-images/b.jpg', 'sort_order' => 0]);

    $urls = $event->fresh()->image_urls;

    expect($urls)->toHaveCount(2)
        ->and($urls[0])->toContain('b.jpg')   // sort_order 0 first
        ->and($urls[1])->toContain('a.jpg');
});

it('shapes an event into the lean prop contract with resolved location and UTC times', function () {
    $event = Event::factory()->create([
        'latitude' => 40.7128,
        'longitude' => -74.0060,            // New York anchor
        'created_time' => 1_700_000_000,
        'payload' => [
            'name' => 'Global Tech Summit',
            'description' => 'A great event',
            'venue' => ['name' => 'The Grand Hall'],
            'schedule' => ['starts_at' => 1_700_000_000, 'ends_at' => 1_700_007_200],
        ],
    ]);

    $data = (new EventResource($event))->toArray(request());

    expect($data['title'])->toBe('Global Tech Summit')
        ->and($data['description'])->toBe('A great event')
        ->and($data['venue'])->toBe('The Grand Hall')
        ->and($data['timezone'])->toBe('America/New_York')
        ->and($data['location']['label'])->toBe('New York, United States')
        ->and($data['starts_at_utc'])->toStartWith('2023-11-14T')
        ->and($data['images'])->toBeArray()
        ->and($data)->not->toHaveKey('payload'); // heavy raw payload never leaks
});
