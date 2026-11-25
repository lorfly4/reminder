<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where(fn ($query) => $query->where('user_id', $this->user()->id)->whereNull('deleted_at'))],
            'start_at' => ['nullable', 'date'],
            'deadline_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'status' => ['sometimes', 'in:todo,in_progress,completed'],
        ];
    }
}
