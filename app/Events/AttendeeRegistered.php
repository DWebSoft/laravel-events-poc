<?php

namespace App\Events;

use App\Models\Attendee;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendeeRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(public Attendee $attendee) {}
}
