<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreHokesenJournalLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('text'))) {
            $this->merge(['text' => trim($this->input('text'))]);
        }

        if (is_string($this->input('idempotency_key'))) {
            $this->merge(['idempotency_key' => trim($this->input('idempotency_key'))]);
        }
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:10000', 'regex:/^[^\r\n]+$/'],
            'entry_date' => ['nullable', 'date_format:Y-m-d'],
            'idempotency_key' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('text') === '') {
                $validator->errors()->add('text', 'The text field cannot be empty.');
            }
        });
    }
}
