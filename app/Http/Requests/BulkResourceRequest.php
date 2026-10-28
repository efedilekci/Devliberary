<?php

namespace App\Http\Requests;

use App\Models\Collection;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkResourceRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['uuid', 'distinct'],
            'action' => ['required', Rule::in(['favorite', 'unfavorite', 'archive', 'restore', 'delete', 'collect'])],
            'collection_id' => [
                'required_if:action,collect',
                'uuid',
                Rule::exists(Collection::class, 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Selecteer minstens één link.',
            'ids.min' => 'Selecteer minstens één link.',
        ];
    }
}
