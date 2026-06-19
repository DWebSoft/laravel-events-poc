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

class EventReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $timeframe  Human phrase for when the event starts, e.g. "in 3 days".
     */
    public function __construct(public Attendee $attendee, public string $timeframe) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reminder: {$this->attendee->event->title} starts {$this->timeframe}",
        );
    }

    public function content(): Content
    {
        $event = $this->attendee->event;
        $location = app(LocationResolver::class)->resolve($event->latitude, $event->longitude);

        return new Content(
            markdown: 'emails.event-reminder',
            with: [
                'attendee' => $this->attendee,
                'event' => $event,
                'location' => $location,
                'timeframe' => $this->timeframe,
                'startsAt' => Carbon::createFromTimestamp($event->created_time, $location->timezone),
            ],
        );
    }
}
