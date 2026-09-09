<?php

namespace App\Http\Requests;

use App\Enums\Currency;
use App\Models\Purchase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Purchase::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'card_id' => ['required', 'integer', Rule::exists('cards', 'id')->where('user_id', $this->user()?->id)],
            'merchant' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'installment_count' => ['required', 'integer', 'min:1', 'max:48'],
            'currency' => ['required', Rule::enum(Currency::class)],
            'first_due_date' => ['required', 'date'],
        ];
    }
}
