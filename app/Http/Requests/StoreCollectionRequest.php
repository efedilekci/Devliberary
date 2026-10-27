<?php

namespace App\Http\Requests;

use App\Support\CollectionAppearance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollectionRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'icon' => ['sometimes', 'string', Rule::in(CollectionAppearance::ICONS)],
            'accent_color' => ['sometimes', 'string', Rule::in(CollectionAppearance::COLORS)],
            'show_on_home' => ['sometimes', 'boolean'],
        ];
    }
}
