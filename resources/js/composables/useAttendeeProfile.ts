import { useStorage } from '@vueuse/core';

export type AttendeeStatus = 'interested' | 'attending';

export interface AttendeeProfile {
    name: string;
    email: string;
}

/** Remembers the registrant's details so they don't retype them per event. */
const profile = useStorage<AttendeeProfile>('events:attendee-profile', { name: '', email: '' });

/** Map of event ID → chosen status for events registered from this browser
 *  (UX only; the unique (event_id, email) constraint is the real safeguard). */
const registrations = useStorage<Record<string, AttendeeStatus>>('events:registrations', {});

export function useAttendeeProfile() {
    function remember(name: string, email: string): void {
        profile.value = { name, email };
    }

    function markRegistered(eventId: string, status: AttendeeStatus): void {
        registrations.value = { ...registrations.value, [eventId]: status };
    }

    function isRegistered(eventId: string): boolean {
        return eventId in registrations.value;
    }

    function registeredStatus(eventId: string): AttendeeStatus | null {
        return registrations.value[eventId] ?? null;
    }

    return { profile, remember, markRegistered, isRegistered, registeredStatus };
}
