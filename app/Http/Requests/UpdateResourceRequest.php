<?php

namespace App\Http\Requests;

use App\Models\Collection;
use App\Models\Resource;
use App\Support\TagList;
use App\Support\UrlNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateResourceRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $resource = $this->route('resource');

        abort_unless(
            $resource instanceof Resource && $resource->user_id === $this->user()?->id,
            404,
        );

        $this->merge([
            'title' => trim($this->string('title')->toString()),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048'],
            'title' => ['required', 'string', 'max:200'],
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
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required' => 'Vul een geldige link in.',
            'title.required' => 'Vul een titel in.',
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

            $resource = $this->route('resource');
            $hash = UrlNormalizer::hash($normalized);
            $taken = $this->user()->resources()
                ->where('normalized_hash', $hash)
                ->when(
                    $resource instanceof Resource,
                    fn ($query) => $query->whereKeyNot($resource->id),
                )
                ->exists();

            if ($taken) {
                $validator->errors()->add('url', 'Deze link staat al in je bibliotheek.');
            }
        });
    }
}
