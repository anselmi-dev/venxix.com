<?php

namespace App\Http\Requests;

use App\Enums\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preferred_currency' => ['required', Rule::enum(Currency::class)],
            'show_both_currencies' => ['required', 'boolean'],
            'usd_uyu_rate' => ['nullable', 'numeric', 'min:0.0001'],
        ];
    }
}
