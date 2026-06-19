export interface EventLocation {
    city: string;
    country: string;
    label: string;
    timezone: string;
    lat: number;
    lng: number;
}

export interface EventItem {
    id: string;
    title: string;
    description: string | null;
    type: string;
    status: string;
    venue: string | null;
    starts_at_utc: string | null;
    ends_at_utc: string | null;
    timezone: string;
    location: EventLocation;
    images: string[];
    attendees_count?: number;
}

export interface CityOption {
    city: string;
    country: string;
    label: string;
    lat: number;
    lng: number;
    tz: string;
}

export interface EventFilterState {
    status: string;
    type: string | null;
    from: string;
    to: string | null;
    city: string | null;
}

export interface EventFeedProps {
    events: EventItem[];
    nextCursor: string | null;
    filters: EventFilterState;
    cities: CityOption[];
    types: string[];
    statuses: string[];
}
