<?php

namespace App\Http\Requests;

use App\Concerns\ProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    use ProfileValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * The email address is displayed but cannot be changed from this form.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => $this->nameRules(),
        ];
    }
}
