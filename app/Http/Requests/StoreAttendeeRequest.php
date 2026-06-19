<?php

namespace App\Http\Requests;

use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $event = $this->route('event');
        $eventId = $event instanceof Event ? $event->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                // One registration per email per event.
                Rule::unique('attendees', 'email')->where('event_id', $eventId),
            ],
            'status' => ['nullable', Rule::in(['interested', 'attending'])],
        ];
    }

    /**
     * The validated attendee payload, typed for the service.
     *
     * @return array{name: string, email: string, status: ?string}
     */
    public function attendeeData(): array
    {
        $status = $this->input('status');

        return [
            'name' => $this->string('name')->toString(),
            'email' => $this->string('email')->toString(),
            'status' => is_string($status) ? $status : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => "You're already on the list for this event.",
        ];
    }
}
