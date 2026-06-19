<x-mail::message>
# You're on the list! 🎉

Hi {{ $attendee->name }},

Thanks for registering as **{{ $attendee->status === 'attending' ? 'attending' : 'interested' }}** for **{{ $event->title }}**. We've added you to the attendee list and you'll receive reminders as the event approaches.

<x-mail::panel>
**{{ $event->title }}**<br>
🗓 {{ $startsAt->format('l, j F Y · g:i A T') }}<br>
📍 {{ $location->label() }}
</x-mail::panel>

<x-mail::button :url="route('events.show', $event)">
View event details
</x-mail::button>

See you there,<br>
{{ config('app.name') }}
</x-mail::message>
