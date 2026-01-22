<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExerciseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('modify', $this->route('template'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'exercise_id' => 'required|exists:exercises,id',
            'duration_seconds' => 'nullable|integer|min:0',
            'rest_after_seconds' => 'nullable|integer|min:0',
            'sets' => 'nullable|integer|min:1',
            'reps' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:1000',
            'tempo' => 'nullable|string|in:slow,normal,fast,explosive',
            'intensity' => 'nullable|string|in:recovery,easy,moderate,hard,maximum',
        ];
    }
}
