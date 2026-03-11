<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeditationLogRequest extends FormRequest
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
        return [
            'duration_seconds' => 'required|integer|min:1',
            'technique' => 'nullable|string|max:255',
            'breath_cycles_completed' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:10000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'duration_seconds.required' => 'A practice duration is required.',
            'duration_seconds.min' => 'The practice duration must be at least 1 second.',
        ];
    }
}
