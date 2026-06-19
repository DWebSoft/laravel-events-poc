<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays, MapPin } from '@lucide/vue';
import { computed } from 'vue';
import { show } from '@/actions/App/Http/Controllers/EventController';
import { Badge } from '@/components/ui/badge';
import { useTimeMode } from '@/composables/useTimeMode';
import { formatEventTime } from '@/lib/eventTime';
import type { EventItem } from '@/types';

const props = defineProps<{ event: EventItem }>();

const { mode, localTimezone } = useTimeMode();

const when = computed(() =>
    formatEventTime(
        props.event.starts_at_utc,
        props.event.timezone,
        mode.value,
        localTimezone,
    ),
);

const statusVariant = computed(() => {
    switch (props.event.status) {
        case 'published':
            return 'default';
        case 'cancelled':
            return 'destructive';
        case 'sold_out':
            return 'secondary';
        default:
            return 'outline';
    }
});
</script>

<template>
    <Link
        :href="show.url(event.id)"
        class="group flex flex-col overflow-hidden rounded-xl border bg-card shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
    >
        <div class="relative aspect-16/10 overflow-hidden bg-muted">
            <img
                :src="event.images[0]"
                :alt="event.title"
                loading="lazy"
                decoding="async"
                class="size-full object-cover transition-transform duration-500 group-hover:scale-105"
            />
            <div class="absolute top-3 left-3 flex gap-1.5">
                <Badge class="capitalize backdrop-blur">{{ event.type }}</Badge>
                <Badge
                    v-if="event.status !== 'published'"
                    :variant="statusVariant"
                    class="capitalize"
                >
                    {{ event.status.replace('_', ' ') }}
                </Badge>
            </div>
        </div>

        <div class="flex flex-1 flex-col gap-2 p-4">
            <h3
                class="line-clamp-2 leading-tight font-semibold group-hover:text-primary"
            >
                {{ event.title }}
            </h3>
            <p
                v-if="event.description"
                class="line-clamp-2 text-sm text-muted-foreground"
            >
                {{ event.description }}
            </p>

            <div class="mt-auto flex flex-col gap-1.5 pt-2 text-sm">
                <span class="flex items-center gap-1.5 text-muted-foreground">
                    <MapPin class="size-3.5 shrink-0" />
                    {{ event.location.label }}
                </span>
                <span class="flex items-center gap-1.5 font-medium">
                    <CalendarDays
                        class="size-3.5 shrink-0 text-muted-foreground"
                    />
                    {{ when.date }} · {{ when.time }}
                    <span class="text-xs font-normal text-muted-foreground">{{
                        when.zone
                    }}</span>
                </span>
            </div>
        </div>
    </Link>
</template>
