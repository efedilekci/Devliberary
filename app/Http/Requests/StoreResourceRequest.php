<?php

namespace App\Http\Requests;

use App\Models\Collection;
use App\Support\TagList;
use App\Support\UrlNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreResourceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->exists('collection_ids')) {
            return;
        }

        $raw = $this->input('collection_ids');

        $this->merge([
            'collection_ids' => collect(is_array($raw) ? $raw : [$raw])
                ->filter(fn (mixed $id): bool => is_string($id) && $id !== '')
                ->unique()
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048'],
            'title' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'string', 'max:400', function (string $attribute, mixed $value, Closure $fail): void {
                if (! is_string($value)) {
                    return;
                }

                $problem = TagList::problem($value);

                if ($problem !== null) {
                    $fail($problem);
                }
            }],
            'collection_id' => [
                'nullable',
                'uuid',
                Rule::exists(Collection::class, 'id')->where('user_id', $this->user()->id),
            ],
            'collection_ids' => ['sometimes', 'array'],
            'collection_ids.*' => [
                'uuid',
                Rule::exists(Collection::class, 'id')->where('user_id', $this->user()->id),
            ],
            'show_on_home' => ['sometimes', 'boolean'],
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
