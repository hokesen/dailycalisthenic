<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('session'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => 'required|string|in:planned,in_progress,completed,cancelled',
            'total_duration_seconds' => 'nullable|integer|min:0',
            'exercise_completion' => 'nullable|array',
            'exercise_completion.*.exercise_id' => 'required|integer|exists:exercises,id',
            'exercise_completion.*.order' => 'required|integer|min:1',
            'exercise_completion.*.status' => 'required|string|in:completed,skipped,marked_completed,incomplete',
        ];
    }
}
