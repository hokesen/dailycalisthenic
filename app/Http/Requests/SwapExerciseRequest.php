<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SwapExerciseRequest extends FormRequest
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
            'order' => 'required|integer|min:1',
            'new_exercise_id' => 'required|integer',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // If new_exercise_id is positive, validate it exists in database
        // Negative IDs are default exercises and will be materialized later
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $newExerciseId = $this->input('new_exercise_id');

            // Only validate existence if it's a positive ID (database exercise)
            if ($newExerciseId > 0) {
                $exists = \App\Models\Exercise::where('id', $newExerciseId)->exists();
                if (! $exists) {
                    $validator->errors()->add('new_exercise_id', 'The selected exercise does not exist.');
                }
            }
        });
    }
}
