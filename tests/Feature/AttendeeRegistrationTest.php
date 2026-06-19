<?php

use App\Mail\AttendanceConfirmationMail;
use App\Models\Attendee;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('registers an attendee and queues a confirmation email', function () {
    Mail::fake();
    $event = Event::factory()->create();

    $this->post(route('events.attendees.store', $event), ['name' => 'Ada Lovelace', 'email' => 'ada@example.com'])
        ->assertRedirect();

    $this->assertDatabaseHas('attendees', [
        'event_id' => $event->id,
        'email' => 'ada@example.com',
        'name' => 'Ada Lovelace',
        'status' => 'interested',
    ]);

    Mail::assertSent(AttendanceConfirmationMail::class, fn (AttendanceConfirmationMail $mail) => $mail->hasTo('ada@example.com'));
});

it('validates name and email', function () {
    $event = Event::factory()->create();

    $this->post(route('events.attendees.store', $event), ['name' => '', 'email' => 'nope'])
        ->assertSessionHasErrors(['name', 'email']);
});

it('records whether the attendee is interested or attending', function () {
    Mail::fake();
    $event = Event::factory()->create();

    $this->post(route('events.attendees.store', $event), ['name' => 'Bob', 'email' => 'bob@example.com', 'status' => 'attending'])
        ->assertRedirect();

    $this->assertDatabaseHas('attendees', ['email' => 'bob@example.com', 'status' => 'attending']);

    $this->post(route('events.attendees.store', $event), ['name' => 'Eve', 'email' => 'eve@example.com', 'status' => 'maybe'])
        ->assertSessionHasErrors('status');
});

it('prevents duplicate registration for the same event', function () {
    Mail::fake();
    $event = Event::factory()->create();
    Attendee::factory()->for($event)->create(['email' => 'dup@example.com']);

    $this->post(route('events.attendees.store', $event), ['name' => 'Dup', 'email' => 'dup@example.com'])
        ->assertSessionHasErrors('email');

    expect(Attendee::where('email', 'dup@example.com')->count())->toBe(1);
});

it('allows the same email to register for different events', function () {
    Mail::fake();
    $first = Event::factory()->create();
    $second = Event::factory()->create();
    Attendee::factory()->for($first)->create(['email' => 'multi@example.com']);

    $this->post(route('events.attendees.store', $second), ['name' => 'Multi', 'email' => 'multi@example.com'])
        ->assertSessionDoesntHaveErrors();

    expect(Attendee::where('email', 'multi@example.com')->count())->toBe(2);
});

it('renders the confirmation email with the event details', function () {
    $event = Event::factory()->create(['payload' => ['name' => 'Global Tech Summit']]);
    $attendee = Attendee::factory()->for($event)->create(['name' => 'Grace']);

    $rendered = (new AttendanceConfirmationMail($attendee))->render();

    expect($rendered)->toContain('Global Tech Summit')->toContain('Grace');
});
