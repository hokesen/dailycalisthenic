<?php

namespace App\Http\Requests;

use App\Enums\LiftCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class LogLiftingSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('sets_completed')) {
            $this->merge(['sets_completed' => 1]);
        }

        if (is_string($this->input('lift_category'))) {
            $this->merge([
                'lift_category' => str($this->input('lift_category'))->lower()->trim()->toString(),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'lift_category' => ['required', new Enum(LiftCategory::class)],
            'weight_lbs' => ['required', 'numeric', 'min:0.01'],
            'reps_completed' => ['required', 'integer', 'min:1'],
            'sets_completed' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
