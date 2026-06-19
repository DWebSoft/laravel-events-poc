<?php

namespace App\Support;

/**
 * A human-readable location derived from raw coordinates by LocationResolver.
 */
readonly class ResolvedLocation
{
    public function __construct(
        public string $city,
        public string $country,
        public string $timezone,
        public float $latitude,
        public float $longitude,
        public float $distanceKm,
    ) {}

    /** "City, Country" — the label shown in the UI. */
    public function label(): string
    {
        return "{$this->city}, {$this->country}";
    }

    /** @return array{city: string, country: string, label: string, timezone: string, lat: float, lng: float} */
    public function toArray(): array
    {
        return [
            'city' => $this->city,
            'country' => $this->country,
            'label' => $this->label(),
            'timezone' => $this->timezone,
            'lat' => $this->latitude,
            'lng' => $this->longitude,
        ];
    }
}
