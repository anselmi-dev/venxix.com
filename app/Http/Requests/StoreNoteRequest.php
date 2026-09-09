<?php

namespace App\Http\Requests;

use App\Models\Note;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Note::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:'.Note::BODY_MAX_LENGTH],
            'card_id' => ['nullable', 'integer', Rule::exists('cards', 'id')->where('user_id', $this->user()?->id)],
            'is_flagged' => ['sometimes', 'boolean'],
        ];
    }
}
