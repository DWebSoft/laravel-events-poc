<?php

namespace App\Console\Commands;

use App\Mail\EventReminderMail;
use App\Models\Attendee;
use Carbon\CarbonInterface;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

#[Signature('events:send-reminders')]
#[Description('Queue reminder emails for attendees of upcoming events (3 days and 24 hours before).')]
class SendEventReminders extends Command
{
    public function handle(): int
    {
        $now = now();
        $tomorrow = $now->addDay();

        // Non-overlapping windows so an event never triggers both at once.
        $threeDay = $this->remind('reminded_3d_at', $tomorrow, $now->addDays(3), 'in 3 days');
        $oneDay = $this->remind('reminded_24h_at', $now, $tomorrow, 'in 24 hours');

        $this->info("Queued {$threeDay} 3-day and {$oneDay} 24-hour reminders.");

        return self::SUCCESS;
    }

    /**
     * Queue reminders for attendees whose event starts in (`$fromExclusive`, `$toInclusive`]
     * and that haven't been reminded for this window yet. Idempotent via `$flag`.
     */
    private function remind(string $flag, CarbonInterface $fromExclusive, CarbonInterface $toInclusive, string $timeframe): int
    {
        $count = 0;

        Attendee::query()
            ->whereNull($flag)
            // `created_time` is a UTC unix timestamp, so compare against epoch seconds.
            ->whereHas('event', fn (Builder $q) => $q
                ->where('status', '!=', 'cancelled')
                ->where('created_time', '>', $fromExclusive->timestamp)
                ->where('created_time', '<=', $toInclusive->timestamp))
            ->with('event')
            ->chunkById(500, function (Collection $attendees) use ($flag, $timeframe, &$count) {
                foreach ($attendees as $attendee) {
                    Mail::to($attendee->email)->queue(new EventReminderMail($attendee, $timeframe));
                    $attendee->update([$flag => now()]);
                    $count++;
                }
            });

        return $count;
    }
}
