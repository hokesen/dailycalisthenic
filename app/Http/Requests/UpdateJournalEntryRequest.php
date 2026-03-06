<?php

namespace App\Http\Requests;

use App\Models\JournalEntry;
use Illuminate\Foundation\Http\FormRequest;

class UpdateJournalEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $entry = $this->route('entry');

        return $entry && $entry->user_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $entry = $this->route('entry');
        $user = $this->user();
        $today = $user?->now()->toDateString() ?? now()->toDateString();

        return [
            'notes' => 'nullable|string|max:10000',
            'entry_date' => [
                'sometimes',
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, \Closure $fail) use ($entry, $user): void {
                    if (! $user) {
                        return;
                    }

                    $dateAlreadyExists = JournalEntry::query()
                        ->where('user_id', $user->id)
                        ->whereKeyNot($entry?->id)
                        ->whereDate('entry_date', $value)
                        ->exists();

                    if ($dateAlreadyExists) {
                        $fail('You already have a journal entry for that date.');
                    }
                },
                function (string $attribute, mixed $value, \Closure $fail) use ($today): void {
                    if ($value > $today) {
                        $fail('Journal entries cannot be dated in the future.');
                    }
                },
            ],
        ];
    }
}
