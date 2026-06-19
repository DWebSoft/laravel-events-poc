<?php

/*
|--------------------------------------------------------------------------
| City anchors
|--------------------------------------------------------------------------
|
| The EventSeeder jitters every event ±0.5° around one of these ~75 city
| anchors, so an event's nearest anchor *is* its city. We enrich each anchor
| with a human-readable name, country and IANA timezone, which lets
| LocationResolver turn raw lat/lng into a usable location and a correct
| local time — entirely offline, with no geocoding API, at any dataset size.
|
| Each row: ['city', 'country', 'lat', 'lng', 'tz'].
|
*/

return [
    // United States
    ['city' => 'New York', 'country' => 'United States', 'lat' => 40.7128, 'lng' => -74.0060, 'tz' => 'America/New_York'],
    ['city' => 'Los Angeles', 'country' => 'United States', 'lat' => 34.0522, 'lng' => -118.2437, 'tz' => 'America/Los_Angeles'],
    ['city' => 'Chicago', 'country' => 'United States', 'lat' => 41.8781, 'lng' => -87.6298, 'tz' => 'America/Chicago'],
    ['city' => 'Houston', 'country' => 'United States', 'lat' => 29.7604, 'lng' => -95.3698, 'tz' => 'America/Chicago'],
    ['city' => 'Phoenix', 'country' => 'United States', 'lat' => 33.4484, 'lng' => -112.0740, 'tz' => 'America/Phoenix'],
    ['city' => 'Philadelphia', 'country' => 'United States', 'lat' => 39.9526, 'lng' => -75.1652, 'tz' => 'America/New_York'],
    ['city' => 'San Antonio', 'country' => 'United States', 'lat' => 29.4241, 'lng' => -98.4936, 'tz' => 'America/Chicago'],
    ['city' => 'San Diego', 'country' => 'United States', 'lat' => 32.7157, 'lng' => -117.1611, 'tz' => 'America/Los_Angeles'],
    ['city' => 'Dallas', 'country' => 'United States', 'lat' => 32.7767, 'lng' => -96.7970, 'tz' => 'America/Chicago'],
    ['city' => 'San Jose', 'country' => 'United States', 'lat' => 37.3382, 'lng' => -121.8863, 'tz' => 'America/Los_Angeles'],
    ['city' => 'Austin', 'country' => 'United States', 'lat' => 30.2672, 'lng' => -97.7431, 'tz' => 'America/Chicago'],
    ['city' => 'San Francisco', 'country' => 'United States', 'lat' => 37.7749, 'lng' => -122.4194, 'tz' => 'America/Los_Angeles'],
    ['city' => 'Seattle', 'country' => 'United States', 'lat' => 47.6062, 'lng' => -122.3321, 'tz' => 'America/Los_Angeles'],
    ['city' => 'Denver', 'country' => 'United States', 'lat' => 39.7392, 'lng' => -104.9903, 'tz' => 'America/Denver'],
    ['city' => 'Boston', 'country' => 'United States', 'lat' => 42.3601, 'lng' => -71.0589, 'tz' => 'America/New_York'],
    ['city' => 'Las Vegas', 'country' => 'United States', 'lat' => 36.1699, 'lng' => -115.1398, 'tz' => 'America/Los_Angeles'],
    ['city' => 'Miami', 'country' => 'United States', 'lat' => 25.7617, 'lng' => -80.1918, 'tz' => 'America/New_York'],
    ['city' => 'Atlanta', 'country' => 'United States', 'lat' => 33.7490, 'lng' => -84.3880, 'tz' => 'America/New_York'],
    ['city' => 'Washington', 'country' => 'United States', 'lat' => 38.9072, 'lng' => -77.0369, 'tz' => 'America/New_York'],
    ['city' => 'Nashville', 'country' => 'United States', 'lat' => 36.1627, 'lng' => -86.7816, 'tz' => 'America/Chicago'],
    ['city' => 'Portland', 'country' => 'United States', 'lat' => 45.5152, 'lng' => -122.6784, 'tz' => 'America/Los_Angeles'],
    ['city' => 'New Orleans', 'country' => 'United States', 'lat' => 29.9511, 'lng' => -90.0715, 'tz' => 'America/Chicago'],

    // Canada
    ['city' => 'Toronto', 'country' => 'Canada', 'lat' => 43.6532, 'lng' => -79.3832, 'tz' => 'America/Toronto'],
    ['city' => 'Montreal', 'country' => 'Canada', 'lat' => 45.5019, 'lng' => -73.5674, 'tz' => 'America/Toronto'],
    ['city' => 'Vancouver', 'country' => 'Canada', 'lat' => 49.2827, 'lng' => -123.1207, 'tz' => 'America/Vancouver'],
    ['city' => 'Calgary', 'country' => 'Canada', 'lat' => 51.0447, 'lng' => -114.0719, 'tz' => 'America/Edmonton'],
    ['city' => 'Ottawa', 'country' => 'Canada', 'lat' => 45.4215, 'lng' => -75.6972, 'tz' => 'America/Toronto'],
    ['city' => 'Edmonton', 'country' => 'Canada', 'lat' => 53.5461, 'lng' => -113.4938, 'tz' => 'America/Edmonton'],
    ['city' => 'Quebec City', 'country' => 'Canada', 'lat' => 46.8139, 'lng' => -71.2080, 'tz' => 'America/Toronto'],
    ['city' => 'Winnipeg', 'country' => 'Canada', 'lat' => 49.8951, 'lng' => -97.1384, 'tz' => 'America/Winnipeg'],

    // Mexico
    ['city' => 'Mexico City', 'country' => 'Mexico', 'lat' => 19.4326, 'lng' => -99.1332, 'tz' => 'America/Mexico_City'],
    ['city' => 'Guadalajara', 'country' => 'Mexico', 'lat' => 20.6597, 'lng' => -103.3496, 'tz' => 'America/Mexico_City'],
    ['city' => 'Monterrey', 'country' => 'Mexico', 'lat' => 25.6866, 'lng' => -100.3161, 'tz' => 'America/Monterrey'],
    ['city' => 'Puebla', 'country' => 'Mexico', 'lat' => 19.0414, 'lng' => -98.2063, 'tz' => 'America/Mexico_City'],
    ['city' => 'Tijuana', 'country' => 'Mexico', 'lat' => 32.5149, 'lng' => -117.0382, 'tz' => 'America/Tijuana'],
    ['city' => 'Cancún', 'country' => 'Mexico', 'lat' => 21.1619, 'lng' => -86.8515, 'tz' => 'America/Cancun'],
    ['city' => 'Mérida', 'country' => 'Mexico', 'lat' => 20.9674, 'lng' => -89.5926, 'tz' => 'America/Merida'],

    // Europe
    ['city' => 'London', 'country' => 'United Kingdom', 'lat' => 51.5074, 'lng' => -0.1278, 'tz' => 'Europe/London'],
    ['city' => 'Paris', 'country' => 'France', 'lat' => 48.8566, 'lng' => 2.3522, 'tz' => 'Europe/Paris'],
    ['city' => 'Berlin', 'country' => 'Germany', 'lat' => 52.5200, 'lng' => 13.4050, 'tz' => 'Europe/Berlin'],
    ['city' => 'Madrid', 'country' => 'Spain', 'lat' => 40.4168, 'lng' => -3.7038, 'tz' => 'Europe/Madrid'],
    ['city' => 'Rome', 'country' => 'Italy', 'lat' => 41.9028, 'lng' => 12.4964, 'tz' => 'Europe/Rome'],
    ['city' => 'Amsterdam', 'country' => 'Netherlands', 'lat' => 52.3676, 'lng' => 4.9041, 'tz' => 'Europe/Amsterdam'],
    ['city' => 'Barcelona', 'country' => 'Spain', 'lat' => 41.3851, 'lng' => 2.1734, 'tz' => 'Europe/Madrid'],
    ['city' => 'Munich', 'country' => 'Germany', 'lat' => 48.1351, 'lng' => 11.5820, 'tz' => 'Europe/Berlin'],
    ['city' => 'Milan', 'country' => 'Italy', 'lat' => 45.4642, 'lng' => 9.1900, 'tz' => 'Europe/Rome'],
    ['city' => 'Vienna', 'country' => 'Austria', 'lat' => 48.2082, 'lng' => 16.3738, 'tz' => 'Europe/Vienna'],
    ['city' => 'Prague', 'country' => 'Czechia', 'lat' => 50.0755, 'lng' => 14.4378, 'tz' => 'Europe/Prague'],
    ['city' => 'Lisbon', 'country' => 'Portugal', 'lat' => 38.7223, 'lng' => -9.1393, 'tz' => 'Europe/Lisbon'],
    ['city' => 'Dublin', 'country' => 'Ireland', 'lat' => 53.3498, 'lng' => -6.2603, 'tz' => 'Europe/Dublin'],
    ['city' => 'Copenhagen', 'country' => 'Denmark', 'lat' => 55.6761, 'lng' => 12.5683, 'tz' => 'Europe/Copenhagen'],
    ['city' => 'Stockholm', 'country' => 'Sweden', 'lat' => 59.3293, 'lng' => 18.0686, 'tz' => 'Europe/Stockholm'],
    ['city' => 'Oslo', 'country' => 'Norway', 'lat' => 59.9139, 'lng' => 10.7522, 'tz' => 'Europe/Oslo'],
    ['city' => 'Helsinki', 'country' => 'Finland', 'lat' => 60.1699, 'lng' => 24.9384, 'tz' => 'Europe/Helsinki'],
    ['city' => 'Brussels', 'country' => 'Belgium', 'lat' => 50.8503, 'lng' => 4.3517, 'tz' => 'Europe/Brussels'],
    ['city' => 'Zurich', 'country' => 'Switzerland', 'lat' => 47.3769, 'lng' => 8.5417, 'tz' => 'Europe/Zurich'],
    ['city' => 'Warsaw', 'country' => 'Poland', 'lat' => 52.2297, 'lng' => 21.0122, 'tz' => 'Europe/Warsaw'],
    ['city' => 'Budapest', 'country' => 'Hungary', 'lat' => 47.4979, 'lng' => 19.0402, 'tz' => 'Europe/Budapest'],
    ['city' => 'Athens', 'country' => 'Greece', 'lat' => 37.9838, 'lng' => 23.7275, 'tz' => 'Europe/Athens'],
    ['city' => 'Lyon', 'country' => 'France', 'lat' => 45.7640, 'lng' => 4.8357, 'tz' => 'Europe/Paris'],
    ['city' => 'Hamburg', 'country' => 'Germany', 'lat' => 53.5511, 'lng' => 9.9937, 'tz' => 'Europe/Berlin'],
    ['city' => 'Manchester', 'country' => 'United Kingdom', 'lat' => 53.4808, 'lng' => -2.2426, 'tz' => 'Europe/London'],
    ['city' => 'Edinburgh', 'country' => 'United Kingdom', 'lat' => 55.9533, 'lng' => -3.1883, 'tz' => 'Europe/London'],
    ['city' => 'Frankfurt', 'country' => 'Germany', 'lat' => 50.1109, 'lng' => 8.6821, 'tz' => 'Europe/Berlin'],
    ['city' => 'Kraków', 'country' => 'Poland', 'lat' => 50.0647, 'lng' => 19.9450, 'tz' => 'Europe/Warsaw'],
    ['city' => 'Porto', 'country' => 'Portugal', 'lat' => 41.1579, 'lng' => -8.6291, 'tz' => 'Europe/Lisbon'],
    ['city' => 'Naples', 'country' => 'Italy', 'lat' => 40.8518, 'lng' => 14.2681, 'tz' => 'Europe/Rome'],

    // Global hubs
    ['city' => 'Tokyo', 'country' => 'Japan', 'lat' => 35.6762, 'lng' => 139.6503, 'tz' => 'Asia/Tokyo'],
    ['city' => 'Seoul', 'country' => 'South Korea', 'lat' => 37.5665, 'lng' => 126.9780, 'tz' => 'Asia/Seoul'],
    ['city' => 'Singapore', 'country' => 'Singapore', 'lat' => 1.3521, 'lng' => 103.8198, 'tz' => 'Asia/Singapore'],
    ['city' => 'Sydney', 'country' => 'Australia', 'lat' => -33.8688, 'lng' => 151.2093, 'tz' => 'Australia/Sydney'],
    ['city' => 'Melbourne', 'country' => 'Australia', 'lat' => -37.8136, 'lng' => 144.9631, 'tz' => 'Australia/Melbourne'],
    ['city' => 'Dubai', 'country' => 'United Arab Emirates', 'lat' => 25.2048, 'lng' => 55.2708, 'tz' => 'Asia/Dubai'],
    ['city' => 'São Paulo', 'country' => 'Brazil', 'lat' => -23.5505, 'lng' => -46.6333, 'tz' => 'America/Sao_Paulo'],
    ['city' => 'Buenos Aires', 'country' => 'Argentina', 'lat' => -34.6037, 'lng' => -58.3816, 'tz' => 'America/Argentina/Buenos_Aires'],
];
