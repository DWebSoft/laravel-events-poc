<?php

namespace App\Mail;

use App\Models\Attendee;
use App\Services\LocationResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class AttendanceConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Attendee $attendee) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "You're on the list: {$this->attendee->event->title}",
        );
    }

    public function content(): Content
    {
        $event = $this->attendee->event;
        $location = app(LocationResolver::class)->resolve($event->latitude, $event->longitude);

        return new Content(
            markdown: 'emails.attendance-confirmation',
            with: [
                'attendee' => $this->attendee,
                'event' => $event,
                'location' => $location,
                'startsAt' => Carbon::createFromTimestamp($event->created_time, $location->timezone),
            ],
        );
    }
}
