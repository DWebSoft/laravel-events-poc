export type TimeMode = 'venue' | 'local';

export interface FormattedEventTime {
    date: string;
    time: string;
    zone: string;
}

/**
 * Format a UTC instant in either the event's venue timezone or the viewer's
 * local timezone. Both representations derive from the same instant, so the
 * venue/local toggle never needs a server round-trip.
 */
export function formatEventTime(
    iso: string | null,
    venueTimezone: string,
    mode: TimeMode,
    localTimezone: string,
): FormattedEventTime {
    if (!iso) {
        return { date: 'Date TBA', time: '', zone: '' };
    }

    const timeZone = mode === 'venue' ? venueTimezone : localTimezone;
    const date = new Date(iso);

    const datePart = new Intl.DateTimeFormat('en-US', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        timeZone,
    }).format(date);

    const timePart = new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        timeZone,
    }).format(date);

    const zonePart =
        new Intl.DateTimeFormat('en-US', { timeZoneName: 'short', timeZone })
            .formatToParts(date)
            .find((part) => part.type === 'timeZoneName')?.value ?? '';

    return { date: datePart, time: timePart, zone: zonePart };
}
