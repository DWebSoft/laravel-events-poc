<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CalendarClock, Database, Gauge, Image, LayoutGrid, MailCheck, MapPin } from '@lucide/vue';
import { grid, timeline } from '@/actions/App/Http/Controllers/EventController';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const scaleTactics = [
    'Cursor pagination keyed on (created_time, id) — flat cost at any scroll depth (no OFFSET).',
    'No COUNT(*) on the feed — cursor pagination needs no total.',
    'Indexes matched to the filters: (created_time), (status, created_time), (latitude, longitude).',
    'Filters hit indexed columns only — never the JSON payload.',
    'EventResource trims ~1.5 KB payloads; ~12 cached local placeholder images for the whole set.',
    'Reverse-geocoding runs only on the ~24 visible rows, never across the table.',
];

const perf = [
    { label: 'Default feed', value: '~13 ms' },
    { label: 'City filter', value: '~39 ms' },
    { label: 'Date range', value: '~1 ms' },
];

const features = [
    {
        icon: MapPin,
        title: 'Locations & timezones',
        body: 'Offline reverse-geocoding: coordinates snap to the nearest of ~75 city anchors for a "City, Country" label and IANA timezone. Toggle between venue-local and your local time.',
    },
    {
        icon: Image,
        title: 'Images, served locally',
        body: 'A real event_images table supports uploads; the seeded set derives a stable 2–3 images per event from a local placeholder pool via crc32(id) — no hotlinking, no seed bloat.',
    },
    {
        icon: MailCheck,
        title: 'Attendees & emails',
        body: 'Register as interested or attending. A queued confirmation email is sent on signup; reminders go out 3 days and 24 hours before, idempotently, via a scheduled command.',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 p-4 md:p-6">
        <header class="flex flex-col gap-1">
            <h1 class="text-2xl font-bold tracking-tight">Event Visuals — POC overview</h1>
            <p class="text-sm text-muted-foreground">
                Browse a fully-seeded dataset of 1.25M events across two distinct experiences.
            </p>
        </header>

        <!-- Quick links -->
        <div class="grid gap-4 sm:grid-cols-2">
            <Link
                :href="grid().url"
                class="group flex items-center gap-3 rounded-xl border bg-card p-4 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
            >
                <LayoutGrid class="size-6 text-primary" />
                <div>
                    <p class="font-semibold group-hover:text-primary">Events Grid</p>
                    <p class="text-sm text-muted-foreground">Responsive image-card browse</p>
                </div>
            </Link>
            <Link
                :href="timeline().url"
                class="group flex items-center gap-3 rounded-xl border bg-card p-4 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
            >
                <CalendarClock class="size-6 text-primary" />
                <div>
                    <p class="font-semibold group-hover:text-primary">Events Timeline</p>
                    <p class="text-sm text-muted-foreground">Chronological agenda by day</p>
                </div>
            </Link>
        </div>

        <!-- Headline: handling the large dataset -->
        <section class="rounded-xl border bg-card p-5 shadow-sm">
            <div class="mb-3 flex items-center gap-2">
                <Database class="size-5 text-primary" />
                <h2 class="text-lg font-semibold">Handling 1.25M events efficiently</h2>
            </div>
            <p class="mb-4 text-sm text-muted-foreground">
                The guiding rule: <strong>the cost of any screen is independent of the total row count.</strong>
                Every page query is bounded to ~24 rows and stays index-backed.
            </p>

            <div class="grid gap-5 md:grid-cols-[1.6fr_1fr]">
                <ul class="flex flex-col gap-2">
                    <li v-for="(tactic, i) in scaleTactics" :key="i" class="flex gap-2 text-sm">
                        <Gauge class="mt-0.5 size-4 shrink-0 text-primary" />
                        <span>{{ tactic }}</span>
                    </li>
                </ul>

                <div class="flex flex-col gap-2 rounded-lg border bg-muted/40 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                        Measured on the live 1.25M-row table
                    </p>
                    <div v-for="metric in perf" :key="metric.label" class="flex items-baseline justify-between">
                        <span class="text-sm text-muted-foreground">{{ metric.label }}</span>
                        <span class="font-mono text-sm font-semibold">{{ metric.value }}</span>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">Each returns a single 24-row page.</p>
                </div>
            </div>
        </section>

        <!-- Feature cards -->
        <div class="grid gap-4 md:grid-cols-3">
            <div
                v-for="feature in features"
                :key="feature.title"
                class="flex flex-col gap-2 rounded-xl border bg-card p-4 shadow-sm"
            >
                <component :is="feature.icon" class="size-5 text-primary" />
                <h3 class="font-semibold">{{ feature.title }}</h3>
                <p class="text-sm text-muted-foreground">{{ feature.body }}</p>
            </div>
        </div>

        <p class="text-xs text-muted-foreground">
            Full write-up in <span class="font-mono">README.md</span>. Note: emails are queued
            (database driver) — run <span class="font-mono">php artisan queue:work</span> to deliver them (Mailpit on :8025).
        </p>
    </div>
</template>
