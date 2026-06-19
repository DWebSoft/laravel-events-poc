<?php

use App\Mail\EventReminderMail;
use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/** An attendee of an event starting in $hours hours. */
function attendeeForEventIn(int $hours, array $eventAttributes = []): Attendee
{
    $event = Event::factory()->create(array_merge([
        'status' => 'published',
        'created_time' => now()->addHours($hours)->timestamp,
    ], $eventAttributes));

    return Attendee::factory()->for($event)->create([
        'reminded_3d_at' => null,
        'reminded_24h_at' => null,
    ]);
}

it('queues a 3-day reminder for an event ~2 days out', function () {
    Mail::fake();
    $attendee = attendeeForEventIn(48);

    $this->artisan('events:send-reminders')->assertSuccessful();

    Mail::assertQueued(EventReminderMail::class, 1);
    Mail::assertQueued(EventReminderMail::class, fn (EventReminderMail $m) => $m->timeframe === 'in 3 days' && $m->hasTo($attendee->email));
    expect($attendee->fresh()->reminded_3d_at)->not->toBeNull()
        ->and($attendee->fresh()->reminded_24h_at)->toBeNull();
});

it('queues only the 24-hour reminder for an event ~12 hours out', function () {
    Mail::fake();
    $attendee = attendeeForEventIn(12);

    $this->artisan('events:send-reminders');

    Mail::assertQueued(EventReminderMail::class, 1);
    Mail::assertQueued(EventReminderMail::class, fn (EventReminderMail $m) => $m->timeframe === 'in 24 hours');
    expect($attendee->fresh()->reminded_24h_at)->not->toBeNull()
        ->and($attendee->fresh()->reminded_3d_at)->toBeNull();
});

it('does not remind for events more than 3 days out', function () {
    Mail::fake();
    attendeeForEventIn(24 * 5);

    $this->artisan('events:send-reminders');

    Mail::assertNothingQueued();
});

it('does not remind for cancelled events', function () {
    Mail::fake();
    attendeeForEventIn(48, ['status' => 'cancelled']);

    $this->artisan('events:send-reminders');

    Mail::assertNothingQueued();
});

it('is idempotent and does not resend the same reminder', function () {
    Mail::fake();
    attendeeForEventIn(48);

    $this->artisan('events:send-reminders');
    $this->artisan('events:send-reminders');

    Mail::assertQueued(EventReminderMail::class, 1);
});
