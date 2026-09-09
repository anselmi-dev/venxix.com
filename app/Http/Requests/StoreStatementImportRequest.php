<?php

namespace App\Http\Requests;

use App\Billing\PlanAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreStatementImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null
            && app(PlanAccess::class)->canImportStatements($this->user());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'card_id' => [
                'required',
                'integer',
                Rule::exists('cards', 'id')->where('user_id', $this->user()?->id),
            ],
            'statement' => [
                'required',
                File::types(['csv', 'txt'])->max('2mb'),
            ],
        ];
    }
}
