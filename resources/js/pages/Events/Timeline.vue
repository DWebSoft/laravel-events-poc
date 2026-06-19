<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useIntersectionObserver } from '@vueuse/core';
import { CalendarX2, Clock, Loader2, MapPin } from '@lucide/vue';
import { computed, ref } from 'vue';
import { show, timeline } from '@/actions/App/Http/Controllers/EventController';
import EventFilters from '@/components/events/EventFilters.vue';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { useEventFeed } from '@/composables/useEventFeed';
import { useTimeMode } from '@/composables/useTimeMode';
import { formatEventTime } from '@/lib/eventTime';
import type { EventFeedProps, EventItem } from '@/types';

const props = defineProps<EventFeedProps>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Events Timeline', href: timeline().url }],
    },
});

const { items, nextCursor, filters, loading, loadingMore, applyFilters, loadMore, resetFilters } = useEventFeed(
    timeline().url,
    props,
);

const { mode, localTimezone } = useTimeMode();

/** Group the chronological feed by its (timezone-aware) calendar day. */
const groups = computed(() => {
    const map = new Map<string, EventItem[]>();
    for (const event of items.value) {
        const key = formatEventTime(event.starts_at_utc, event.timezone, mode.value, localTimezone).date;
        const bucket = map.get(key);
        if (bucket) {
            bucket.push(event);
        } else {
            map.set(key, [event]);
        }
    }
    return Array.from(map, ([date, events]) => ({ date, events }));
});

const sentinel = ref<HTMLElement | null>(null);
useIntersectionObserver(
    sentinel,
    ([entry]) => {
        if (entry?.isIntersecting) {
            loadMore();
        }
    },
    { rootMargin: '600px' },
);

const typedTime = (event: EventItem) => formatEventTime(event.starts_at_utc, event.timezone, mode.value, localTimezone);
</script>

<template>
    <Head title="Events Timeline" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4 md:p-6">
        <header class="flex flex-col gap-1">
            <h1 class="text-2xl font-bold tracking-tight">Events Timeline</h1>
            <p class="text-sm text-muted-foreground">A chronological agenda of what's coming up, grouped by day.</p>
        </header>

        <EventFilters
            :filters="filters"
            :cities="cities"
            :types="types"
            :statuses="statuses"
            @change="applyFilters"
            @reset="resetFilters"
        />

        <!-- Loading skeleton -->
        <div v-if="loading && items.length === 0" class="flex flex-col gap-4 pl-6">
            <div v-for="n in 6" :key="n" class="flex gap-4">
                <Skeleton class="size-16 shrink-0 rounded-lg" />
                <div class="flex flex-1 flex-col gap-2 py-1">
                    <Skeleton class="h-4 w-1/3" />
                    <Skeleton class="h-3 w-2/3" />
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-else-if="items.length === 0"
            class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed py-20 text-center"
        >
            <CalendarX2 class="size-10 text-muted-foreground" />
            <p class="font-medium">No events match your filters</p>
            <p class="text-sm text-muted-foreground">Try widening the date range or choosing a different location.</p>
        </div>

        <!-- Timeline -->
        <div v-else class="relative" :class="{ 'opacity-60 transition-opacity': loading }">
            <!-- vertical spine -->
            <div class="absolute bottom-0 left-1.75 top-2 w-px bg-border" aria-hidden="true" />

            <section v-for="group in groups" :key="group.date" class="relative">
                <h2
                    class="sticky top-2 z-10 mb-3 ml-6 inline-block rounded-full border bg-background/90 px-3 py-1 text-sm font-semibold shadow-sm backdrop-blur"
                >
                    {{ group.date }}
                </h2>

                <ul class="mb-4 flex flex-col gap-3">
                    <li
                        v-for="event in group.events"
                        :key="event.id"
                        class="animate-in fade-in slide-in-from-left-2 fill-mode-both relative pl-6"
                    >
                        <!-- dot -->
                        <span
                            class="absolute left-0 top-5 size-3.5 rounded-full border-2 border-background bg-primary ring-1 ring-border"
                        />

                        <Link
                            :href="show.url(event.id)"
                            class="group flex gap-4 rounded-xl border bg-card p-3 shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md"
                        >
                            <img
                                :src="event.images[0]"
                                :alt="event.title"
                                loading="lazy"
                                decoding="async"
                                class="size-20 shrink-0 rounded-lg object-cover"
                            />
                            <div class="flex min-w-0 flex-1 flex-col gap-1">
                                <div class="flex items-center gap-2">
                                    <Badge class="capitalize">{{ event.type }}</Badge>
                                    <Badge v-if="event.status !== 'published'" variant="secondary" class="capitalize">
                                        {{ event.status.replace('_', ' ') }}
                                    </Badge>
                                </div>
                                <h3 class="truncate font-semibold group-hover:text-primary">{{ event.title }}</h3>
                                <p v-if="event.description" class="line-clamp-2 text-sm text-muted-foreground">
                                    {{ event.description }}
                                </p>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                                    <span class="flex items-center gap-1.5">
                                        <Clock class="size-3.5" /> {{ typedTime(event).time }} {{ typedTime(event).zone }}
                                    </span>
                                    <span class="flex items-center gap-1.5">
                                        <MapPin class="size-3.5" /> {{ event.location.label }}
                                    </span>
                                </div>
                            </div>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>

        <!-- Infinite-scroll sentinel -->
        <div ref="sentinel" class="flex justify-center py-2 text-sm text-muted-foreground">
            <span v-if="loadingMore" class="flex items-center gap-2">
                <Loader2 class="size-4 animate-spin" /> Loading more…
            </span>
            <span v-else-if="!nextCursor && items.length > 0">You've reached the end.</span>
        </div>
    </div>
</template>
