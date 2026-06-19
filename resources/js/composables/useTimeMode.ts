import { useStorage } from '@vueuse/core';
import type { TimeMode } from '@/lib/eventTime';

/** Shared, persisted preference for showing venue-local vs. viewer-local time. */
const mode = useStorage<TimeMode>('events:time-mode', 'venue');

export function useTimeMode() {
    const localTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

    function toggle(): void {
        mode.value = mode.value === 'venue' ? 'local' : 'venue';
    }

    return { mode, localTimezone, toggle };
}
