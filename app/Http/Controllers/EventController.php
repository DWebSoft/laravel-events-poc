<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Services\LocationResolver;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    /** Event categories stored in the `type` column. */
    private const TYPES = ['concert', 'conference', 'meetup', 'workshop', 'festival', 'sports', 'networking', 'exhibition'];

    private const STATUSES = ['published', 'sold_out', 'cancelled', 'draft'];

    /** Bounding-box half-width (degrees) for the city filter; covers the seeder's ±0.5° jitter. */
    private const CITY_BOX = 0.5;

    private const PER_PAGE = 24;

    public function __construct(private readonly LocationResolver $locations) {}

    /** Original lightweight listing (table + lazy load). */
    public function index(Request $request): Response
    {
        return Inertia::render('Events/Index', [
            'filters' => [
                'status' => $request->status,
                'from' => $request->input('from', '2023-01-01'),
            ],
            'statuses' => ['draft', 'published', 'cancelled', 'sold_out'],
        ]);
    }

    /** JSON feed backing the original listing's infinite scroll. */
    public function data(Request $request): JsonResponse
    {
        [$events, $stats] = $this->loadListing($request);

        return response()->json([
            'data' => $events->items(),
            'current_page' => $events->currentPage(),
            'last_page' => $events->lastPage(),
            'total' => $events->total(),
            'stats' => $stats,
        ]);
    }

    /** Events Grid — card grid. */
    public function grid(Request $request): Response
    {
        return $this->renderFeed('Events/Grid', $request);
    }

    /** Events Timeline — chronological agenda. */
    public function timeline(Request $request): Response
    {
        return $this->renderFeed('Events/Timeline', $request);
    }

    public function show(Event $event): Response
    {
        $event->load('images');

        return Inertia::render('Events/Show', [
            'event' => (new EventResource($event))->resolve(),
        ]);
    }

    private function renderFeed(string $component, Request $request): Response
    {
        $events = $this->feedQuery($request)
            ->cursorPaginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render($component, [
            // One cursor-page per request; the client accumulates pages for
            // infinite scroll and resets the list whenever filters change.
            'events' => EventResource::collection($events->items())->resolve(),
            'nextCursor' => $events->nextCursor()?->encode(),
            'filters' => $this->filters($request),
            'cities' => $this->locations->cities(),
            'types' => self::TYPES,
            'statuses' => self::STATUSES,
        ]);
    }

    /**
     * Build the filtered feed query. Every filter targets an indexed column —
     * never the JSON payload — so cost stays bounded by page size, not row count.
     *
     * @return Builder<Event>
     */
    private function feedQuery(Request $request): Builder
    {
        $filters = $this->filters($request);
        $tz = $this->timezone($request);

        return Event::query()
            ->with('images')
            ->when($filters['status'] !== 'all', fn (Builder $q) => $q->where('status', $filters['status']))
            ->when($filters['type'], fn (Builder $q, string $type) => $q->where('type', $type))
            // Dates are interpreted in the viewer's timezone so the day boundary
            // matches what they see; `created_time` comparison stays absolute (UTC epoch).
            ->when($filters['from'], fn (Builder $q, string $from) => $q->where('created_time', '>=', Carbon::parse($from, $tz)->startOfDay()->timestamp))
            ->when($filters['to'], fn (Builder $q, string $to) => $q->where('created_time', '<=', Carbon::parse($to, $tz)->endOfDay()->timestamp))
            ->when($this->cityBox($filters['city']), function (Builder $q, array $box) {
                $q->whereBetween('latitude', [$box['lat'] - self::CITY_BOX, $box['lat'] + self::CITY_BOX])
                    ->whereBetween('longitude', [$box['lng'] - self::CITY_BOX, $box['lng'] + self::CITY_BOX]);
            })
            // Soonest-first; (created_time, id) is a stable, indexed cursor key.
            ->orderBy('created_time')
            ->orderBy('id');
    }

    /**
     * Normalised filter state, echoed back to the page so the UI stays in sync.
     * Defaults to upcoming, published events.
     *
     * @return array{status: string, type: ?string, from: string, to: ?string, city: ?string}
     */
    private function filters(Request $request): array
    {
        return [
            'status' => $request->input('status') ?: 'published',
            'type' => $request->input('type') ?: null,
            'from' => $request->input('from') ?: Carbon::now('UTC')->toDateString(),
            'to' => $request->input('to') ?: null,
            'city' => $request->input('city') ?: null,
        ];
    }

    /**
     * The viewer's timezone for date filtering; falls back to UTC if absent/invalid.
     * Validates via DateTimeZone so legacy aliases (e.g. "Asia/Calcutta") that browsers
     * still report — but `timezone_identifiers_list()` omits — are accepted.
     */
    private function timezone(Request $request): string
    {
        $tz = $request->input('tz');

        if (! is_string($tz) || $tz === '') {
            return 'UTC';
        }

        try {
            new \DateTimeZone($tz);

            return $tz;
        } catch (\Exception) {
            return 'UTC';
        }
    }

    /**
     * Resolve a city label to its anchor coordinates for the bounding-box filter.
     *
     * @return array{lat: float, lng: float}|null
     */
    private function cityBox(?string $cityLabel): ?array
    {
        if (! $cityLabel) {
            return null;
        }

        foreach ($this->locations->cities() as $city) {
            if ($city['label'] === $cityLabel) {
                return ['lat' => $city['lat'], 'lng' => $city['lng']];
            }
        }

        return null;
    }

    /**
     * @return array{0: LengthAwarePaginator, 1: array{ms: int, bytes: int}}
     */
    private function loadListing(Request $request): array
    {
        $start = microtime(true);

        $events = Event::with('user')
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_time')
            ->paginate(50)
            ->withQueryString();

        $stats = [
            'ms' => (int) round((microtime(true) - $start) * 1000),
            'bytes' => strlen((string) json_encode($events->items())),
        ];

        return [$events, $stats];
    }
}
