<?php

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

it('renders the original events listing shell without authentication', function () {
    $this->get(route('events.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Index')
            ->has('statuses', 4)
            ->where('filters.from', '2023-01-01')
        );
});

it('returns a json page of events with load stats for lazy loading', function () {
    $user = User::factory()->create(['name' => 'Ada Lovelace']);
    Event::factory()->for($user)->create([
        'type' => 'concert',
        'status' => 'published',
        'created_time' => 1_700_000_000,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
    ]);

    $this->getJson(route('events.data'))
        ->assertOk()
        ->assertJsonStructure(['data', 'current_page', 'last_page', 'total', 'stats' => ['ms', 'bytes']])
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.type', 'concert')
        ->assertJsonPath('data.0.user.name', 'Ada Lovelace');
});

it('filters the data endpoint by status', function () {
    $user = User::factory()->create();
    Event::factory()->for($user)->create(['status' => 'published']);
    Event::factory()->for($user)->create(['status' => 'cancelled']);

    $this->getJson(route('events.data', ['status' => 'cancelled']))
        ->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.status', 'cancelled');
});

/** Create a published event on a given UTC date at given coordinates. */
function eventOn(string $date, float $lat = 40.7128, float $lng = -74.0060, array $attributes = []): Event
{
    return Event::factory()->create(array_merge([
        'status' => 'published',
        'created_time' => Carbon::parse($date, 'UTC')->timestamp,
        'latitude' => $lat,
        'longitude' => $lng,
    ], $attributes));
}

it('renders the Events Grid with feed props and filter metadata', function () {
    eventOn('+1 week');

    $this->get(route('events.grid'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Grid')
            ->has('events', 1)
            ->has('cities', 75)
            ->has('types', 8)
            ->has('statuses', 4)
            ->where('filters.status', 'published')
            ->where('filters.from', Carbon::now('UTC')->toDateString())
        );
});

it('renders the Events Timeline using the same feed', function () {
    eventOn('+1 week');

    $this->get(route('events.timeline'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Events/Timeline')->has('events', 1));
});

it('defaults to upcoming events and hides past ones', function () {
    eventOn('-1 month'); // past
    eventOn('+1 month'); // upcoming

    $this->get(route('events.grid'))
        ->assertInertia(fn ($page) => $page->has('events', 1));
});

it('filters by an explicit date range', function () {
    eventOn('2027-01-10');
    eventOn('2027-06-10');

    $this->get(route('events.grid', ['from' => '2027-01-01', 'to' => '2027-01-31']))
        ->assertInertia(fn ($page) => $page->has('events', 1)
            ->where('events.0.starts_at_utc', fn ($v) => str_starts_with($v, '2027-01-10')));
});

it('interprets the date filter in the viewer timezone', function () {
    // 21:00 UTC on Jun 19 is 02:30 IST on Jun 20.
    Event::factory()->create([
        'status' => 'published',
        'created_time' => Carbon::parse('2026-06-19 21:00', 'UTC')->timestamp,
        'latitude' => 40.7128,
        'longitude' => -74.0060,
    ]);

    // In IST this event belongs to Jun 20, not Jun 19.
    $this->get(route('events.grid', ['from' => '2026-06-20', 'to' => '2026-06-20', 'tz' => 'Asia/Kolkata']))
        ->assertInertia(fn ($page) => $page->has('events', 1));

    $this->get(route('events.grid', ['from' => '2026-06-19', 'to' => '2026-06-19', 'tz' => 'Asia/Kolkata']))
        ->assertInertia(fn ($page) => $page->has('events', 0));

    // In UTC the same event belongs to Jun 19.
    $this->get(route('events.grid', ['from' => '2026-06-19', 'to' => '2026-06-19', 'tz' => 'UTC']))
        ->assertInertia(fn ($page) => $page->has('events', 1));

    // Browsers may report legacy aliases (e.g. "Asia/Calcutta"); these must still resolve.
    $this->get(route('events.grid', ['from' => '2026-06-19', 'to' => '2026-06-19', 'tz' => 'Asia/Calcutta']))
        ->assertInertia(fn ($page) => $page->has('events', 0));
});

it('filters by city using a coordinate bounding box', function () {
    eventOn('+1 week', 40.7128, -74.0060); // New York
    eventOn('+1 week', 51.5074, -0.1278);  // London

    $this->get(route('events.grid', ['city' => 'New York, United States']))
        ->assertInertia(fn ($page) => $page->has('events', 1)
            ->where('events.0.location.city', 'New York'));
});

it('filters by status', function () {
    eventOn('+1 week', attributes: ['status' => 'published']);
    eventOn('+1 week', attributes: ['status' => 'cancelled']);

    $this->get(route('events.grid', ['status' => 'cancelled']))
        ->assertInertia(fn ($page) => $page->has('events', 1)
            ->where('events.0.status', 'cancelled'));
});

it('cursor-paginates the feed', function () {
    for ($i = 0; $i < 30; $i++) {
        eventOn('+'.($i + 1).' days');
    }

    $this->get(route('events.grid'))
        ->assertInertia(fn ($page) => $page->has('events', 24)->where('nextCursor', fn ($c) => filled($c)));
});

it('shows an event detail page with the shaped resource', function () {
    $event = eventOn('+1 week', attributes: [
        'payload' => ['name' => 'Global Tech Summit', 'schedule' => ['starts_at' => Carbon::parse('+1 week', 'UTC')->timestamp]],
    ]);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Events/Show')
            ->where('event.id', $event->id)
            ->where('event.title', 'Global Tech Summit')
            ->where('event.location.label', 'New York, United States')
            ->has('event.images')
        );
});

it('renders the dashboard without authentication', function () {
    $this->get(route('dashboard'))->assertOk();
});
