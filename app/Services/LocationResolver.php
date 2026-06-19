<?php

namespace App\Services;

use App\Support\ResolvedLocation;

/**
 * Turns raw latitude/longitude into a human-readable location + IANA timezone
 * by snapping to the nearest known city anchor (config/city_anchors.php).
 *
 * The seeder jitters every event ±0.5° around one of ~75 anchors, so the
 * nearest anchor is the event's true city. This is offline, deterministic and
 * O(anchors) per call — cost is bounded by page size, never by table size.
 */
class LocationResolver
{
    /** @var list<array{city: string, country: string, lat: float, lng: float, tz: string}> */
    private array $anchors;

    /** Memoize resolutions so repeated coordinates on a page cost nothing. */
    /** @var array<string, ResolvedLocation> */
    private array $cache = [];

    public function __construct(?array $anchors = null)
    {
        $this->anchors = $anchors ?? config('city_anchors');
    }

    public function resolve(float $latitude, float $longitude): ResolvedLocation
    {
        $key = round($latitude, 4).','.round($longitude, 4);

        return $this->cache[$key] ??= $this->nearest($latitude, $longitude);
    }

    /**
     * Anchors as plain rows for a location filter dropdown, sorted by label.
     *
     * @return list<array{city: string, country: string, label: string, lat: float, lng: float, tz: string}>
     */
    public function cities(): array
    {
        $cities = array_map(fn (array $a) => [
            'city' => $a['city'],
            'country' => $a['country'],
            'label' => "{$a['city']}, {$a['country']}",
            'lat' => $a['lat'],
            'lng' => $a['lng'],
            'tz' => $a['tz'],
        ], $this->anchors);

        usort($cities, fn ($a, $b) => strcmp($a['label'], $b['label']));

        return $cities;
    }

    private function nearest(float $latitude, float $longitude): ResolvedLocation
    {
        $best = null;
        $bestDistance = INF;

        foreach ($this->anchors as $anchor) {
            $distance = $this->haversineKm($latitude, $longitude, $anchor['lat'], $anchor['lng']);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
                $best = $anchor;
            }
        }

        return new ResolvedLocation(
            city: $best['city'],
            country: $best['country'],
            timezone: $best['tz'],
            latitude: $latitude,
            longitude: $longitude,
            distanceKm: round($bestDistance, 1),
        );
    }

    /** Great-circle distance in kilometres. */
    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * asin(min(1.0, sqrt($a)));
    }
}
