<?php

namespace App\Http\Requests;

use App\Priority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gate is the auth middleware; ownership is enforced in the controller
    }

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', Rule::in(array_map(fn (Priority $priority) => $priority->value, Priority::cases()))],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
