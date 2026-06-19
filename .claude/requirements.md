# Event Visuals — Requirements & Implementation Plan

> Architectural plan for the LHP Laravel coding test. Authored as Principal Architect:
> decisions are explained, trade-offs flagged, and scope is deliberately kept tight.
> **No feature code is written until the architecture below is approved.**

---

## 1. What we're building (from `CODING_TEST.md`)

Two **distinct** event-browsing pages plus the supporting backend:

1. **Event Visuals 1** and **Event Visuals 2** — two modern, visually different layouts.
2. Each event shows: **title, description, location, date/time, image**.
3. **Images** — end-to-end, **2+ per event**, served **locally**.
4. **Addresses** — turn lat/lng into a **human-readable location**.
5. **Date/time** — events are global; handle timezones sensibly.
6. **Filtering** — at minimum **by date** and **by location**.
7. **Tailwind** styling + tasteful **animations**.
8. **Attendees** — register interest/attendance; **confirmation email** on signup;
   **reminder emails 3 days before and 24 hours before**.
9. Clean, readable code + a short note on decisions.

---

## 2. What already exists (the starting point)

| Area | State |
|------|-------|
| Stack | Laravel 13, PHP 8.4, Vue 3 + Inertia 3, Tailwind v4, Fortify auth, Pest |
| DB | **MySQL** (`lender411`), `QUEUE_CONNECTION=database`, `MAIL_MAILER=log` |
| `events` table | `uuid id`, `user_id`, `type`, `status`, `created_time` (unix), `latitude`, `longitude`, `payload` (JSON longText), timestamps. Index on `status` only. |
| Payload JSON | `name`, `category`, `description`, `organizer`, `venue{name,capacity}`, `location{lat,lng}`, `schedule{starts_at,ends_at}` (unix), `pricing`, `tags`, `notes` |
| Seeder | `EventSeeder` bulk-inserts up to **1.25M** events (env `SEED_ROWS`) jittered around ~75 known **city anchors**. `created_time` == event **start time**. |
| Controller | `EventController@index/data/show` — `data` returns paginated JSON; `Index.vue` is a debug table with infinite scroll (has a typo bug: `aplyFilters`). |
| Pages | `Events/VisualOne.vue`, `Events/VisualTwo.vue` are **empty placeholders**. `Show.vue` dumps raw payload. |
| Data state | **Not migrated / not seeded yet** (0 rows). |

### Key insight that shapes the whole design
The event's **title/description live inside the JSON payload**, but the **start time
(`created_time`) and coordinates (`latitude`/`longitude`) are real indexed-able columns.**
Our two required filters (date + location) therefore map cleanly onto columns — we never
have to filter on JSON at scale. Good.

The seeder jitters events **±0.5° around ~75 named city anchors**. We exploit this: an
event's nearest anchor *is* its city. This gives us **offline, instant, zero-API
reverse-geocoding and timezone resolution** that works at 1.25M rows. This is the single
most important architectural decision (see §5).

---

## 3. Scope decisions (challenging the requirements)

Keeping it focused — "quality over quantity". Explicit calls:

- **No new packages beyond Laravel Boost.** Reverse geocoding, timezones, images, and
  reminders are all solved with the framework + a small static dataset. No Spatie, no
  geocoding SaaS, no spatial-index gymnastics.
- **Images are NOT stored as 1.25M+ rows.** Storing 2–3 image rows per seeded event = 2.5M+
  rows of pure placeholder noise. Instead we add a real, working images subsystem (model +
  relation + local storage + upload-capable) **and** seed images **deterministically** from
  a small local placeholder pool keyed off the event UUID (see §6). End-to-end support with
  zero seed bloat. *(Decision flagged — alternative is a fully-populated `event_images`
  table; recommend the deterministic approach for this dataset.)*
- **Attendees are email-based, not auth-gated.** "Let people register interest" — we don't
  force login. Name + email is enough; dedupe per event by email.
- **Location filter = pick a city** (from the anchor list) → bounding-box query on
  lat/lng. Far more usable than a raw radius box and trivially fast with indexes.
- **Reminders run off the scheduler**, idempotently, keyed by per-attendee flags — no
  external cron assumptions beyond `schedule:work` / a single cron entry.

---

## 4. Data model & migrations

### 4.1 New: `attendees`
```
id              bigint pk
event_id        uuid  fk -> events.id  cascadeOnDelete, indexed
name            string
email           string
status          string  default 'interested'   // interested | attending
confirmed_at    timestamp nullable
reminded_3d_at  timestamp nullable             // idempotency for 3-day reminder
reminded_24h_at timestamp nullable             // idempotency for 24-hour reminder
timestamps
unique (event_id, email)
```

### 4.2 New: `event_images` (real subsystem, lightly seeded)
```
id          bigint pk
event_id    uuid fk -> events.id cascadeOnDelete, indexed
disk        string default 'public'
path        string                 // e.g. event-images/placeholder-03.jpg
sort_order  unsigned small int default 0
timestamps
```
> Created so uploads/relations work end-to-end. For the massive seeded set we do **not**
> fill this per row; the `Event::images()` accessor falls back to the deterministic
> placeholder pool when no rows exist (§6). New/real events use the table.

### 4.3 Alter `events` — add indexes for our filters (critical at 1.25M)
```
index (created_time)              // default sort + date range  ← the workhorse
index (status, created_time)      // status filter combined with the default sort
index (latitude, longitude)       // bounding-box (city) location filtering
```
> Without these, every date/location filter table-scans 1.25M rows. The `(status,
> created_time)` composite lets the common "published, newest-first" view use one index for
> both filter and order. See §13 for how queries map onto these.

---

## 5. Location & timezone — `LocationResolver` service

A single service backed by a **static `config/city_anchors.php`** array: the same ~75
anchors the seeder uses, enriched with `name`, `country`, and IANA `timezone`.

```
LocationResolver::resolve(float $lat, float $lng): ResolvedLocation
  -> nearest anchor (squared-distance, in-memory)
  -> { city, country, label "City, Country", timezone, distance_km }
```

- **Reverse geocoding:** nearest-anchor lookup → human-readable "City, Country". Offline,
  deterministic, O(75) per event, cacheable. No API, no rate limits, scales to any dataset.
- **Timezone:** each anchor carries its IANA zone → event-local time with zero extra work.
- **Cities for the filter dropdown:** the distinct anchor list, passed as an Inertia prop.

**Time handling policy:** `created_time`/`schedule.*` are UTC unix timestamps.
`EventResource` sends `starts_at_utc` (ISO‑8601) + the venue `timezone` (from the
resolver). The frontend renders **both** and offers a **toggle: "Venue time" ⇄ "My time"**:
- *Venue time* — formatted in the event's resolved IANA zone (e.g. "Sat 12 Jul, 8:00 PM CEST").
- *My time* — formatted in the viewer's zone via `Intl.DateTimeFormat().resolvedOptions().timeZone`.

The toggle is a client-side preference (persisted in `localStorage`), so flipping it needs
**no server round-trip** — both representations derive from the single UTC instant + the two
zones. Reminder windows are always computed in UTC against `created_time`.

---

## 6. Images subsystem

- Ship **~8–10 local placeholder images** in `storage/app/public/event-images/`
  (served via `php artisan storage:link`). No external/hotlinked URLs.
- `Event::images()` relation → `EventImage[]`. Accessor `getImagesAttribute()`:
  - if rows exist → return them (real/uploaded events);
  - else → derive **2–3 deterministic placeholders** from the pool using
    `crc32($event->id)` so each event has a stable, varied image set.
- `EventResource` exposes `images: string[]` (public URLs) — frontend never knows the
  difference. First image is the card/hero image.

---

## 7. Transport: Inertia (no standalone JSON API)

This app is **Inertia-driven**, so we do **not** build a REST/JSON API. Controllers return
`Inertia::render()` with props; the existing raw-JSON `/events/data` endpoint (a `fetch()`
used only by the debug table for its perf stats) is **dropped**. Inertia 3 gives us
everything the test needs natively:

| Concern | Inertia mechanism |
|---------|-------------------|
| Filtering | `router.get(route, filters, { only: ['events'], preserveState, preserveScroll })` — partial reload |
| Infinite scroll | **merge props** (`->merge()` on the paginator prop) + `<WhenVisible>` to append the next page |
| Cities dropdown | shared/page prop (anchor list) |
| Register attendee | `useForm().post(...)` → controller redirects back with a flash message |
| Detail page | `Inertia::render('Events/Show', ...)` |

**`EventResource`** stays, but as an **Inertia prop-shaping DTO** (not a REST resource) —
it flattens the messy payload into the clean contract the Vue pages consume:
```
{ id, title, description, type, status,
  starts_at_utc, ends_at_utc, timezone, local_time_label,
  location: { lat, lng, city, country, label },
  images: string[], attendees_count }
```

**Routes / controller actions (all Inertia):**
- `GET /events` → `index` — renders a visual with paginated, filtered, merge-prop events.
  Filters: `from`/`to` (range on `created_time`), `city` (bounding box around its anchor),
  `type`, `status`. Cities passed as a prop.
- `GET /events/{event}` → `show` — real `Show.vue` (hero, gallery, map link, attendee form),
  replacing the JSON dump.
- `POST /events/{event}/attendees` → `store` — `StoreAttendeeRequest` validates name+email,
  dedupes, creates attendee, fires confirmation, redirects back with flash.

> The two visuals are their own Inertia pages (`Events/VisualOne`, `Events/VisualTwo`),
> each rendered by a controller action that supplies the same shaped/filterable event props.

---

## 8. Attendees, emails, events & queues

**Flow (all mail queued — `QUEUE_CONNECTION=database` is ready):**

1. `POST /events/{event}/attendees` (Inertia form) → `AttendeeService::register()` creates the row.
2. Fire `AttendeeRegistered` event → listener queues
   `AttendanceConfirmationMail` (Markdown mailable) → sets `confirmed_at`.
3. **Reminders** — `php artisan events:send-reminders` (scheduled **hourly**):
   - Query **attendees joined to events** where `created_time` falls in the
     **3-day window** and `reminded_3d_at IS NULL` → queue `EventReminderMail('3-day')`,
     stamp `reminded_3d_at`. Same for the **24-hour** window / `reminded_24h_at`.
   - Idempotent via the flag columns; safe to re-run; only touches events that actually
     have attendees (cheap even against 1.25M events).
4. Register the schedule in `routes/console.php` (`Schedule::command(...)->hourly()`).

**Mailables:** `AttendanceConfirmationMail`, `EventReminderMail` (Markdown templates,
implement `ShouldQueue`). Dev verification via `MAIL_MAILER=log` (already set).

---

## 9. Frontend — two distinct visuals (Tailwind + light animation)

Both consume `EventResource` + shared filter bar (date range + city select). Distinct UX:

- **Visual 1 — Card Grid / "Discover".** Responsive masonry-ish cards: hero image,
  title, city, local date/time badge, type chip. Hover lift + image zoom; staggered
  fade-in on load; skeletons while fetching. Infinite scroll (reuse existing pattern,
  fixed).
- **Visual 2 — Timeline / Agenda.** Events grouped by **date** down a vertical timeline
  (sticky date headers, alternating sides on desktop), thumbnail + time + location per
  entry. Animated draw-in of the timeline line / entry slide-in. Same filters, different
  mental model — clearly not "the same page twice".

Shared pieces: `EventCard.vue`, `EventFilters.vue`, `useEventFilters.ts` composable
(wraps `router.get` partial reloads + filter state in the URL query), attendee form on
`Show.vue` via `useForm`. Infinite scroll via Inertia merge props + `<WhenVisible>`.
Animations via Tailwind utilities / `tw-animate-css` + IntersectionObserver — restrained,
purposeful.

---

## 10. Testing (Pest) & quality

- `LocationResolver` unit test (nearest-anchor correctness, timezone mapping).
- Attendee registration: validation, dedupe, confirmation mail queued (`Mail::fake`).
- Reminder command: window selection + idempotency (`Mail::fake`, time travel).
- `EventResource` shape + image fallback.
- Run `composer lint` (Pint), `phpstan`, `npm run types:check` before done.
- Lean on installed **Boost skills** (laravel-best-practices, inertia-vue, pest, tailwind).

---

## 11. Implementation order (phased — approve per phase)

1. **Foundation:** migrate base tables → **seed the full 1.25M** (`db:seed`) → **then** run
   the index migration. *(Adding secondary indexes after the bulk load is far faster than
   maintaining them across 1.25M inserts — InnoDB builds each index in one pass.)* Then
   `storage:link` + add placeholder images.
2. **Location/time:** `config/city_anchors.php` + `LocationResolver` + tests.
3. **Images:** `event_images` migration/model/relation + deterministic accessor + `EventResource`.
4. **Controller/props:** `index`/`show` actions, filter query handling, merge-prop
   pagination, `EventResource` + cities prop wiring. (Drop the old JSON `/events/data`.)
5. **Visual 1** (card grid) + shared filters/composable.
6. **Visual 2** (timeline) + real `Show.vue`.
7. **Attendees + email:** model, request, service, event/listener, mailables.
8. **Reminders:** command + schedule + tests.
9. **Polish:** animations, fix `Index.vue` typo, lint/types/tests, write `DECISIONS.md` note.

---

## 12. Confirmed decisions (signed off)

1. **Images:** ✅ deterministic placeholder pool (small local pool, keyed off event UUID).
2. **Timezone display:** ✅ **toggle** between venue-local and viewer-local (see §5).
3. **Visuals:** ✅ Card Grid (Visual 1) + Timeline (Visual 2).
4. **Dev seed size:** ✅ **seed the full 1.25M** — performance against the real dataset is
   an explicit goal. See **§13 Performance & scale**, which becomes a first-class concern.

---

## 13. Performance & scale at 1.25M rows

Performance is now a primary deliverable, not an afterthought. The rules below keep every
page query bounded to ~50 rows regardless of table size, and keep the payload over the wire
small. **Golden rule: the cost of any screen must be independent of total row count.**

### 13.1 Query layer
- **Cursor pagination, never offset.** `Event::...->cursorPaginate(50)` keyed on
  `(created_time, id)`. Offset pagination (`LIMIT 50 OFFSET 1_000_000`) degrades linearly and
  is fatal here; a keyset cursor is O(log n) on the index and stays flat at any depth. The
  current `paginate(50)` is replaced.
- **No `COUNT(*)` on the feed.** Cursor pagination needs no total, so we drop the 1.25M-row
  `COUNT` the old paginator ran on every request. (If a count is ever desired, show an
  *approximate* via `information_schema`/`EXPLAIN`, never `COUNT(*)`.)
- **Indexes do the work** (§4.3):
  - default view → `ORDER BY created_time DESC LIMIT 50` uses `(created_time)` directly.
  - `+ status` → `(status, created_time)` covers filter **and** order in one index.
  - `+ city` → bounding box on `(latitude, longitude)`; ~16k rows/city, narrowed further by
    the date range, then a tiny filesort. Fast.
- **Select only what's needed.** Don't `SELECT *` for aggregates. The feed needs `payload`
  (title/description live there) so we select it for the page's 50 rows only — never for
  counts/joins. **Drop the `with('user')` eager load** in the feed (the visuals show event
  data, not the organizer) — saves a query and bytes.
- **Filter only on real columns** (`created_time`, `latitude`/`longitude`, `status`, `type`).
  Never `WHERE`/`ORDER BY` on JSON `payload` — that can't use an index.

### 13.2 Payload / transport
- `EventResource` trims each row from the ~1500-byte raw payload to the handful of fields the
  UI needs, with the **description truncated** for cards. Big win on the "bytes loaded"
  metric the existing UI already surfaces.
- **Deterministic placeholder images = ~8–10 unique files** for the *entire* dataset → the
  browser/CDN caches them after first paint; image bytes don't scale with row count.
- Keep the small **"loaded X KB in Y ms" perf indicator** from the current UI as a visible
  signal that load cost stays flat as you scroll/filter.

### 13.3 Frontend rendering
- Infinite scroll via Inertia **merge props** + `<WhenVisible>` — append 50 at a time, only
  on demand.
- `loading="lazy"` + `decoding="async"` on all images; fixed aspect-ratio boxes to avoid
  layout shift.
- DOM growth guard: if a session scrolls into the thousands of cards, cap retained
  pages / consider list virtualization. *(Noted as a fallback; not built unless it bites —
  staying focused.)*

### 13.4 Reverse-geocoding & timezone at scale
- `LocationResolver` runs **only on the 50 displayed rows** (O(50×75), in-memory anchor
  list, request-cached) — never across the table. Resolution cost is bounded by page size,
  not row count.
- **No `city` denormalization column.** Tempting for speed, but the test explicitly wants us
  to *derive* a human-readable location from lat/lng — storing it at seed time would sidestep
  the actual requirement. The location **filter** still uses pure lat/lng (bounding box), so
  we honor "events only carry lat/lng" while keeping the filter index-fast.

### 13.5 Background work
- Reminder query drives **from the small `attendees` table** joined to `events` on the
  indexed `created_time` window — it touches only events that have attendees, never the full
  1.25M. Per-attendee `reminded_*_at` flags make it idempotent and cheap to re-run hourly.
- All mail is queued (`database` driver) so registration/reminders never block a request.

### 13.6 Verification
- `EXPLAIN` the feed query under each filter combo to confirm index usage (no full scans,
  no filesort on large sets) — capture in the decisions note.
- Sanity-check first-paint + scroll load times against the live 1.25M set; the perf badge
  should stay roughly constant per page.
