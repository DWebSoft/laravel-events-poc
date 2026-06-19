<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { CalendarX2, Loader2 } from '@lucide/vue';
import { useIntersectionObserver } from '@vueuse/core';
import { ref } from 'vue';
import { grid } from '@/actions/App/Http/Controllers/EventController';
import EventCard from '@/components/events/EventCard.vue';
import EventFilters from '@/components/events/EventFilters.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { useEventFeed } from '@/composables/useEventFeed';
import type { EventFeedProps } from '@/types';

const props = defineProps<EventFeedProps>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Events Grid', href: grid().url }],
    },
});

const {
    items,
    nextCursor,
    filters,
    loading,
    loadingMore,
    applyFilters,
    loadMore,
    resetFilters,
} = useEventFeed(grid().url, props);

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
</script>

<template>
    <Head title="Events Grid" />

    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 md:p-6">
        <header class="flex flex-col gap-1">
            <h1 class="text-2xl font-bold tracking-tight">Events Grid</h1>
            <p class="text-sm text-muted-foreground">
                Concerts, conferences and more — happening around the world.
            </p>
        </header>

        <EventFilters
            :filters="filters"
            :cities="cities"
            :types="types"
            :statuses="statuses"
            @change="applyFilters"
            @reset="resetFilters"
        />

        <!-- Skeletons while a filter change is loading the first page -->
        <div
            v-if="loading && items.length === 0"
            class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <div
                v-for="n in 8"
                :key="n"
                class="flex flex-col gap-3 rounded-xl border p-0"
            >
                <Skeleton class="aspect-16/10 w-full rounded-t-xl" />
                <div class="flex flex-col gap-2 p-4 pt-0">
                    <Skeleton class="h-4 w-3/4" />
                    <Skeleton class="h-3 w-full" />
                    <Skeleton class="h-3 w-1/2" />
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
            <p class="text-sm text-muted-foreground">
                Try widening the date range or choosing a different location.
            </p>
        </div>

        <!-- Card grid -->
        <div
            v-else
            class="grid grid-cols-1 gap-5 transition-opacity sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            :class="{ 'opacity-60': loading }"
        >
            <EventCard
                v-for="(event, index) in items"
                :key="event.id"
                :event="event"
                class="animate-in fill-mode-both fade-in slide-in-from-bottom-3"
                :style="{ animationDelay: `${(index % 24) * 35}ms` }"
            />
        </div>

        <!-- Infinite-scroll sentinel + status -->
        <div
            ref="sentinel"
            class="flex justify-center py-6 text-sm text-muted-foreground"
        >
            <span v-if="loadingMore" class="flex items-center gap-2">
                <Loader2 class="size-4 animate-spin" /> Loading more…
            </span>
            <span v-else-if="!nextCursor && items.length > 0"
                >You've reached the end.</span
            >
        </div>
    </div>
</template>
