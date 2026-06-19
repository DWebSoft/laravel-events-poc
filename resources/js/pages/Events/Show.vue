<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, Clock, ExternalLink, MapPin, Ticket } from '@lucide/vue';
import { computed, ref } from 'vue';
import { grid } from '@/actions/App/Http/Controllers/EventController';
import TimeToggle from '@/components/events/TimeToggle.vue';
import { Badge } from '@/components/ui/badge';
import { useTimeMode } from '@/composables/useTimeMode';
import { formatEventTime } from '@/lib/eventTime';
import type { EventItem } from '@/types';

const props = defineProps<{ event: EventItem }>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Events Grid', href: grid().url }],
    },
});

const { mode, localTimezone } = useTimeMode();

const activeImage = ref(props.event.images[0]);

const starts = computed(() => formatEventTime(props.event.starts_at_utc, props.event.timezone, mode.value, localTimezone));
const ends = computed(() => formatEventTime(props.event.ends_at_utc, props.event.timezone, mode.value, localTimezone));

const mapUrl = computed(
    () => `https://www.google.com/maps/search/?api=1&query=${props.event.location.lat},${props.event.location.lng}`,
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
    <Head :title="event.title" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4 md:p-6">
        <Link :href="grid().url" class="flex w-fit items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
            <ArrowLeft class="size-4" /> Back to events
        </Link>

        <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
            <!-- Gallery -->
            <div class="flex flex-col gap-3">
                <div class="aspect-16/10 overflow-hidden rounded-2xl border bg-muted">
                    <img :src="activeImage" :alt="event.title" class="size-full object-cover" />
                </div>
                <div v-if="event.images.length > 1" class="grid grid-cols-4 gap-3">
                    <button
                        v-for="(image, i) in event.images"
                        :key="i"
                        type="button"
                        class="aspect-square overflow-hidden rounded-lg border transition-all"
                        :class="image === activeImage ? 'ring-2 ring-primary' : 'opacity-70 hover:opacity-100'"
                        @click="activeImage = image"
                    >
                        <img :src="image" :alt="`${event.title} image ${i + 1}`" loading="lazy" class="size-full object-cover" />
                    </button>
                </div>
            </div>

            <!-- Details -->
            <div class="flex flex-col gap-5">
                <div class="flex flex-col gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge class="capitalize">{{ event.type }}</Badge>
                        <Badge :variant="statusVariant" class="capitalize">{{ event.status.replace('_', ' ') }}</Badge>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight">{{ event.title }}</h1>
                </div>

                <dl class="flex flex-col gap-4 rounded-xl border bg-card p-4 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="flex items-center gap-2 font-medium"><CalendarDays class="size-4 text-muted-foreground" /> When</dt>
                        <TimeToggle />
                    </div>
                    <dd class="-mt-2 flex flex-col gap-1">
                        <span class="font-medium">{{ starts.date }}</span>
                        <span class="flex items-center gap-1.5 text-muted-foreground">
                            <Clock class="size-3.5" />
                            {{ starts.time }}<template v-if="ends.time"> – {{ ends.time }}</template> {{ starts.zone }}
                        </span>
                    </dd>

                    <div class="border-t pt-3">
                        <dt class="flex items-center gap-2 font-medium"><MapPin class="size-4 text-muted-foreground" /> Where</dt>
                        <dd class="mt-1 flex flex-col gap-1">
                            <span v-if="event.venue">{{ event.venue }}</span>
                            <span class="text-muted-foreground">{{ event.location.label }}</span>
                            <a
                                :href="mapUrl"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex w-fit items-center gap-1 text-primary hover:underline"
                            >
                                View on map <ExternalLink class="size-3.5" />
                            </a>
                        </dd>
                    </div>
                </dl>

                <button
                    type="button"
                    class="flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                >
                    <Ticket class="size-4" /> Register interest
                </button>
            </div>
        </div>

        <div v-if="event.description" class="flex flex-col gap-2">
            <h2 class="text-lg font-semibold">About this event</h2>
            <p class="leading-relaxed text-muted-foreground">{{ event.description }}</p>
        </div>
    </div>
</template>
