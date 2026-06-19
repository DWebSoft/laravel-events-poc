import { router } from '@inertiajs/vue3';
import { nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import type { EventFeedProps, EventFilterState, EventItem } from '@/types';

interface FeedSnapshot {
    items: EventItem[];
    nextCursor: string | null;
    scrollY: number;
}

/**
 * Per-feed cache of accumulated pages + scroll, so navigating into an event and
 * back restores the feed instead of resetting to page one. Keyed by the stable
 * page path (not the live URL, which Inertia mutates during navigation), and
 * lives for the SPA session.
 */
const feedCache: Record<string, FeedSnapshot> = {};

/**
 * Drives an event feed: holds filter state, runs partial Inertia visits that
 * keep the URL shareable, and accumulates cursor pages client-side for
 * infinite scroll. Each request returns just one page (24 events), so load
 * cost stays flat regardless of dataset size.
 */
export function useEventFeed(pageUrl: string, initial: EventFeedProps) {
    const restored = feedCache[pageUrl] ?? null;

    const items = ref<EventItem[]>(restored ? restored.items : [...initial.events]);
    const nextCursor = ref<string | null>(restored ? restored.nextCursor : initial.nextCursor);
    const filters = reactive<EventFilterState>({ ...initial.filters });
    const loading = ref(false);
    const loadingMore = ref(false);

    // Track scroll continuously so the snapshot is accurate regardless of when
    // Inertia resets scroll during navigation.
    let lastScrollY = restored ? restored.scrollY : 0;
    const onScroll = () => {
        lastScrollY = window.scrollY;
    };

    onMounted(() => {
        window.addEventListener('scroll', onScroll, { passive: true });

        if (restored) {
            nextTick(() => requestAnimationFrame(() => window.scrollTo(0, restored.scrollY)));
        }
    });

    onBeforeUnmount(() => {
        window.removeEventListener('scroll', onScroll);
        feedCache[pageUrl] = { items: items.value, nextCursor: nextCursor.value, scrollY: lastScrollY };
    });

    /** The viewer's IANA timezone, so date filters mean "this day where I am". */
    const viewerTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

    /** Strip empty filters so the URL stays clean. */
    function activeFilters(): Record<string, string> {
        const params: Record<string, string> = {};

        for (const [key, value] of Object.entries(filters)) {
            if (value !== null && value !== '') {
                params[key] = String(value);
            }
        }

        // Interpret the from/to dates in the viewer's timezone server-side.
        if (params.from || params.to) {
            params.tz = viewerTimezone;
        }

        return params;
    }

    /** Re-query from page one — used whenever a filter changes. */
    function applyFilters(): void {
        loading.value = true;
        router.get(pageUrl, activeFilters(), {
            only: ['events', 'nextCursor', 'filters'],
            preserveState: true,
            preserveScroll: false,
            onSuccess: (page) => {
                const props = page.props as unknown as EventFeedProps;
                items.value = [...props.events];
                nextCursor.value = props.nextCursor;
                lastScrollY = 0;
            },
            onFinish: () => {
                loading.value = false;
            },
        });
    }

    /** Append the next cursor page (keeps the URL unchanged). */
    function loadMore(): void {
        if (!nextCursor.value || loading.value || loadingMore.value) {
            return;
        }

        loadingMore.value = true;
        router.get(
            pageUrl,
            { ...activeFilters(), cursor: nextCursor.value },
            {
                only: ['events', 'nextCursor'],
                preserveState: true,
                preserveScroll: true,
                preserveUrl: true, // cursor stays out of the address bar / history
                onSuccess: (page) => {
                    const props = page.props as unknown as EventFeedProps;
                    items.value.push(...props.events);
                    nextCursor.value = props.nextCursor;
                },
                onFinish: () => {
                    loadingMore.value = false;
                },
            },
        );
    }

    function resetFilters(): void {
        filters.status = 'published';
        filters.type = null;
        filters.from = initial.filters.from;
        filters.to = null;
        filters.city = null;
        applyFilters();
    }

    return { items, nextCursor, filters, loading, loadingMore, applyFilters, loadMore, resetFilters };
}
