<?php

namespace App\Http\Requests;

use App\Models\StatementImport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmStatementImportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $statementImport = $this->route('statementImport');

        return $statementImport instanceof StatementImport
            && $this->user()?->id === $statementImport->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'row_ids' => ['required', 'array', 'min:1'],
            'row_ids.*' => ['required', 'integer', 'distinct'],
        ];
    }

    /**
     * @return list<int>
     */
    public function rowIds(): array
    {
        $validated = $this->validated();
        $rowIds = $validated['row_ids'] ?? [];

        if (! is_array($rowIds)) {
            return [];
        }

        return array_values(array_map(
            static fn (mixed $id): int => (int) $id,
            $rowIds,
        ));
    }
}
