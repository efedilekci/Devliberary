<?php

namespace App\Http\Requests;

use App\Models\Collection;
use App\Support\UrlNormalizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreResourceRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048'],
            'title' => ['nullable', 'string', 'max:200'],
            'collection_id' => [
                'nullable',
                'uuid',
                Rule::exists(Collection::class, 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $normalized = UrlNormalizer::normalize($this->string('url')->toString());

            if (! filter_var($normalized, FILTER_VALIDATE_URL)) {
                $validator->errors()->add('url', 'Vul een geldige link in.');

                return;
            }

            $hash = UrlNormalizer::hash($normalized);

            if ($this->user()->resources()->where('normalized_hash', $hash)->exists()) {
                $validator->errors()->add('url', 'Deze link staat al in je bibliotheek.');
            }
        });
    }
}
