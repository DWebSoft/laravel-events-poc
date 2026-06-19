# Event Visuals — Coding Test

A Laravel + Inertia (Vue 3) app for browsing a **fully-seeded dataset of 1.25M events**, with two
distinct browse experiences, locally-served images, human-readable locations, timezone-aware
date/time, attendee registration, and confirmation + reminder emails.

---

## Highlight: handling 1.25M events efficiently

The headline constraint was a realistic, fully-seeded dataset (1.25M `events`). The guiding rule
was: **the cost of any screen must be independent of the total row count.** That shaped every
decision below.

1. **Cursor pagination, never offset.** The feed uses `cursorPaginate()` keyed on
   `(created_time, id)`. A keyset cursor stays flat at any scroll depth, whereas
   `LIMIT … OFFSET 1_000_000` degrades linearly and would be fatal here.
2. **No `COUNT(*)` on the feed.** Cursor pagination needs no total, so we never run a 1.25M-row
   count on a request.
3. **Indexes matched to the queries** (added *after* the bulk seed, which builds each index in one
   pass instead of maintaining it across 1.25M inserts):
   - `(created_time)` — default sort + date range (the workhorse)
   - `(status, created_time)` — status filter + sort covered by one index
   - `(latitude, longitude)` — bounding-box (city) filtering
4. **Filter only on real columns**, never on the JSON `payload`. The two required filters (date,
   location) map to indexed columns; JSON is never used in `WHERE`/`ORDER BY`.
5. **Lean payloads.** `EventResource` trims each ~1.5 KB raw payload to the few fields the UI needs.
   Placeholder images are a small fixed pool (~12 files) served locally, so image bytes don't scale
   with row count and the browser caches them after first paint.
6. **Reverse-geocoding bounded by page size.** Location/timezone are resolved only for the ~24
   visible rows per page (in-memory, O(rows × anchors)), never across the table.
7. **Background work never scans the table.** The reminder query drives from the small `attendees`
   table joined to `events` on the indexed `created_time` window — it only touches events that have
   attendees.

**Measured on the live 1.25M-row table:** default feed ~13 ms, city-filtered ~39 ms, date-range
~1 ms — all returning a single 24-row page.

The client adds **infinite scroll with state preservation**: pages accumulate client-side, and a
per-feed cache restores the loaded events + scroll position when you return from an event detail
page (so going back never refetches or loses your place).

---

## Key decisions & trade-offs

- **Locations & timezones — offline, no API.** The seeder jitters every event ±0.5° around ~75
  named city anchors, so an event's nearest anchor *is* its city. `LocationResolver` snaps
  coordinates to the nearest anchor (haversine) to produce a "City, Country" label and IANA
  timezone — deterministic, offline, and scalable to any dataset size. No geocoding service, no
  rate limits. (`config/city_anchors.php`, `app/Services/LocationResolver.php`)
- **Timezones — venue ⇄ viewer toggle.** Times are stored as UTC; the API sends the UTC instant +
  the venue timezone, and the client renders **both** venue-local and viewer-local with a toggle
  (persisted in `localStorage`) — no server round-trip. Date filters are interpreted in the
  viewer's timezone so the day boundary matches what they see (validated via `DateTimeZone`, which
  also accepts legacy aliases like `Asia/Calcutta`).
- **Images — end-to-end, deterministically assigned.** A real `event_images` table + relationship
  supports uploads, but for the seeded dataset images are derived deterministically from a small
  local placeholder pool via `crc32(event_id)` — every event gets a stable 2–3 images with **zero**
  extra seed rows. All served locally (`storage/app/public/event-images`), no hotlinking.
- **Transport — pure Inertia, no REST API.** Filtering uses partial Inertia visits (URL stays
  shareable); load-more uses `preserveUrl` so the cursor never pollutes the address bar.
  `EventResource` is a prop-shaping DTO, not a REST resource.
- **Attendees & email.** Email-based registration (no auth), `interested` vs `attending`, with a
  `unique(event_id, email)` safeguard. Registration fires `AttendeeRegistered` → a **queued**
  listener sends the confirmation. Reminders run via the scheduled `events:send-reminders` command
  with **non-overlapping windows** (3-day: 24–72 h out; 24-hour: 0–24 h out) and per-attendee
  idempotency flags (`reminded_3d_at`, `reminded_24h_at`) so the hourly run is safe to repeat.

---

## Two browse experiences

- **Events Grid** (`/events-grid`) — responsive image-card grid with staggered fade-in and
  hover zoom.
- **Events Timeline** (`/events-timeline`) — chronological agenda grouped by day with a vertical
  spine and slide-in entries.

Both share the same filter bar (location + date range + category + status), infinite scroll, and the
time toggle. (The original `/events` debug table is kept as-is.)

---

## Getting started

Requires PHP 8.4, Node, and a MySQL database (config in `.env`).

```bash
composer install
npm install

php artisan migrate          # base tables
php artisan db:seed          # seed the full 1.25M events (a few minutes)
php artisan migrate           # build the filter indexes (after the seed)
php artisan storage:link     # serve local event images

composer run dev             # serves app + vite + queue worker + logs
```

> `composer run dev` runs the queue worker too. If you run the app another way, start
> **`php artisan queue:work`** — the queue connection is `database`, so confirmation/reminder
> emails only send when a worker is processing jobs.

**Mail:** configured for **Mailpit** (`MAIL_PORT=1025`, UI at <http://localhost:8025>).

**Reminders:** scheduled hourly. Run on demand with `php artisan events:send-reminders`, or via
`php artisan schedule:work` to exercise the schedule.

## Tests

```bash
php artisan test
```

Covers location resolution, image fallback/resource shaping, listing filters (incl. timezone-aware
dates and city bounding box), cursor pagination, attendee registration + confirmation email, and
the reminder windows + idempotency.
