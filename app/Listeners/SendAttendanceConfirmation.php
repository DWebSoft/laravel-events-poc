<?php

namespace App\Listeners;

use App\Events\AttendeeRegistered;
use App\Mail\AttendanceConfirmationMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendAttendanceConfirmation implements ShouldQueue
{
    public function handle(AttendeeRegistered $event): void
    {
        Mail::to($event->attendee->email)
            ->send(new AttendanceConfirmationMail($event->attendee));
    }
}
