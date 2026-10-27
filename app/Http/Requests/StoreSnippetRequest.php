<?php

namespace App\Http\Requests;

use App\Support\SnippetLanguage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSnippetRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->input('language') === 'plaintext') {
            $merge['language'] = 'text';
        }

        if (is_string($this->input('title'))) {
            $merge['title'] = trim($this->input('title'));
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'code' => ['required', 'string', 'max:200000'],
            'language' => ['required', Rule::in(SnippetLanguage::VALUES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Vul een titel in.',
            'code.required' => 'Vul code in.',
            'language.in' => 'Kies een taal.',
        ];
    }
}
