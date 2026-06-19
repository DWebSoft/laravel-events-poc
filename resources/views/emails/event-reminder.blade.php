<x-mail::message>
# See you {{ $timeframe }}! ⏰

Hi {{ $attendee->name }},

This is a friendly reminder that **{{ $event->title }}** starts **{{ $timeframe }}**.

<x-mail::panel>
**{{ $event->title }}**<br>
🗓 {{ $startsAt->format('l, j F Y · g:i A T') }}<br>
📍 {{ $location->label() }}
</x-mail::panel>

<x-mail::button :url="route('events.show', $event)">
View event details
</x-mail::button>

See you soon,<br>
{{ config('app.name') }}
</x-mail::message>
