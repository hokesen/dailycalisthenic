<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJournalEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $today = $this->user()?->now()->toDateString() ?? now()->toDateString();

        return [
            'entry_date' => [
                'nullable',
                'date_format:Y-m-d',
                function (string $attribute, mixed $value, \Closure $fail) use ($today): void {
                    if ($value !== null && $value > $today) {
                        $fail('Journal entries cannot be dated in the future.');
                    }
                },
            ],
            'notes' => 'nullable|string|max:10000',
        ];
    }
}
