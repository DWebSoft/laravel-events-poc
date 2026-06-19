<?php

namespace App\Models;

use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory, HasUuids;

    /** Number of local placeholder images shipped in storage/app/public/event-images. */
    public const PLACEHOLDER_POOL_SIZE = 12;

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<EventImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(EventImage::class)->orderBy('sort_order');
    }

    /**
     * Public URLs for this event's images. Uploaded/real images win; otherwise
     * we fall back to a stable, varied set from the local placeholder pool so
     * every event has 2+ images without storing rows for the seeded dataset.
     *
     * @return list<string>
     */
    public function getImageUrlsAttribute(): array
    {
        if ($this->images->isNotEmpty()) {
            return array_values($this->images->map(fn (EventImage $image): string => $image->url())->all());
        }

        return $this->placeholderImageUrls();
    }

    /** @return list<string> */
    private function placeholderImageUrls(): array
    {
        $seed = crc32((string) $this->id);
        $count = 2 + ($seed % 2);            // 2 or 3 images, deterministically
        $start = $seed % self::PLACEHOLDER_POOL_SIZE;

        $urls = [];
        for ($i = 0; $i < $count; $i++) {
            $number = (($start + $i) % self::PLACEHOLDER_POOL_SIZE) + 1;
            $file = sprintf('event-images/placeholder-%02d.jpg', $number);
            $urls[] = Storage::disk('public')->url($file);
        }

        return $urls;
    }
}
