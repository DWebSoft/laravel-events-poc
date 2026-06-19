<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, CheckCircle2, Clock, ExternalLink, MapPin, Ticket, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import { grid, storeAttendee } from '@/actions/App/Http/Controllers/EventController';
import TimeToggle from '@/components/events/TimeToggle.vue';
import { Badge } from '@/components/ui/badge';
import { useAttendeeProfile } from '@/composables/useAttendeeProfile';
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
const { profile, remember, markRegistered, isRegistered, registeredStatus } = useAttendeeProfile();

const activeImage = ref(props.event.images[0]);

// True on return visits to an event this browser already signed up for.
const registered = computed(() => isRegistered(props.event.id));
const registeredLabel = computed(() => (registeredStatus(props.event.id) === 'attending' ? 'attending' : 'interested in'));

// Prefill from the remembered profile so repeat registrations are one click.
const form = useForm<{ name: string; email: string; status: 'interested' | 'attending' }>({
    name: profile.value.name,
    email: profile.value.email,
    status: 'interested',
});

/** Return to the page we came from (grid/timeline/list); fall back to the grid. */
function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit(grid().url);
    }
}

function register() {
    form.post(storeAttendee.url(props.event.id), {
        preserveScroll: true,
        onSuccess: () => {
            remember(form.name, form.email);
            markRegistered(props.event.id, form.status);
        },
    });
}

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
        <button
            type="button"
            class="flex w-fit items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
            @click="goBack"
        >
            <ArrowLeft class="size-4" /> Back to events
        </button>

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

                <div class="flex flex-col gap-3 rounded-xl border bg-card p-4">
                    <p class="flex items-center gap-2 text-sm font-medium">
                        <Users class="size-4 text-muted-foreground" />
                        {{ (event.attendees_count ?? 0).toLocaleString() }}
                        {{ (event.attendees_count ?? 0) === 1 ? 'person is' : 'people are' }} interested
                    </p>

                    <div
                        v-if="registered"
                        class="flex items-center gap-2 rounded-lg bg-primary/10 p-3 text-sm font-medium text-primary"
                    >
                        <CheckCircle2 class="size-4" /> You're {{ registeredLabel }} this event.
                    </div>

                    <form v-else class="flex flex-col gap-3" @submit.prevent="register">
                        <!-- Interested vs. attending -->
                        <div class="grid grid-cols-2 gap-1 rounded-lg border bg-muted/40 p-1">
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="form.status === 'interested' ? 'bg-background shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                @click="form.status = 'interested'"
                            >
                                Interested
                            </button>
                            <button
                                type="button"
                                class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                                :class="form.status === 'attending' ? 'bg-background shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                                @click="form.status = 'attending'"
                            >
                                Attending
                            </button>
                        </div>

                        <div class="flex flex-col gap-1">
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="Your name"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            />
                            <span v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="you@example.com"
                                class="h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                            />
                            <span v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</span>
                        </div>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 font-medium text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-60"
                        >
                            <Ticket class="size-4" />
                            {{ form.status === 'attending' ? "I'm attending" : "I'm interested" }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div v-if="event.description" class="flex flex-col gap-2">
            <h2 class="text-lg font-semibold">About this event</h2>
            <p class="leading-relaxed text-muted-foreground">{{ event.description }}</p>
        </div>
    </div>
</template>
