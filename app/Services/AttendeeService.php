<?php

namespace App\Services;

use App\Events\AttendeeRegistered;
use App\Models\Attendee;
use App\Models\Event;

class AttendeeService
{
    /**
     * Register an attendee for an event and trigger their confirmation email.
     *
     * @param  array{name: string, email: string, status?: ?string}  $data
     */
    public function register(Event $event, array $data): Attendee
    {
        $attendee = $event->attendees()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'status' => $data['status'] ?? 'interested',
            'confirmed_at' => now(),
        ]);

        AttendeeRegistered::dispatch($attendee);

        return $attendee;
    }
}
