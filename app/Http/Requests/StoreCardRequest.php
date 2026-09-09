<?php

namespace App\Http\Requests;

use App\Enums\CardType;
use App\Enums\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCardRequest extends FormRequest
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
            'bank' => ['required', 'string', 'max:255'],
            'alias' => ['nullable', 'string', 'max:255'],
            'last_four' => ['required', 'digits:4'],
            'type' => ['required', Rule::enum(CardType::class)],
            'closing_day' => ['nullable', 'integer', 'between:1,31'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_limit_currency' => ['required_with:credit_limit', Rule::enum(Currency::class)],
        ];
    }
}
