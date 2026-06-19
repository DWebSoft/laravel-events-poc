<?php

namespace App\Http\Resources;

use App\Models\Event;
use App\Services\LocationResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * Shapes the raw Event (messy JSON payload + coordinates + unix times) into the
 * clean, lean contract the Vue pages consume as Inertia props. We deliberately
 * omit the heavy payload fields (notes/tags/organizer/pricing) to keep the wire
 * payload small at scale.
 *
 * @mixin Event
 */
class EventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $payload = $this->payload ?? [];
        // `created_time` is the authoritative start time (it's what we index,
        // filter and sort on); the payload only supplies the end time.
        $startsAt = $this->created_time;
        $endsAt = $payload['schedule']['ends_at'] ?? null;

        $location = app(LocationResolver::class)->resolve($this->latitude, $this->longitude);

        return [
            'id' => $this->id,
            'title' => $payload['name'] ?? 'Untitled Event',
            'description' => $payload['description'] ?? null,
            'type' => $this->type,
            'status' => $this->status,
            'venue' => $payload['venue']['name'] ?? null,

            // One UTC instant + the venue zone; the client renders venue-local
            // and viewer-local from these (the timezone toggle needs no round-trip).
            'starts_at_utc' => $startsAt ? Carbon::createFromTimestamp($startsAt, 'UTC')->toIso8601String() : null,
            'ends_at_utc' => $endsAt ? Carbon::createFromTimestamp($endsAt, 'UTC')->toIso8601String() : null,
            'timezone' => $location->timezone,

            'location' => $location->toArray(),

            'images' => $this->image_urls,

            'attendees_count' => $this->whenCounted('attendees'),
        ];
    }
}
