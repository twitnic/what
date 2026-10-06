<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\PostType;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

final class PostRequest extends FormRequest
{
    #[\Override]
    protected function prepareForValidation(): void
    {
        if ($this->is('api/*')) {
            return;
        }
        foreach (['published_at', 'expires_at', 'starts_at', 'ends_at'] as $field) {
            /** @var mixed $value */
            $value = $this->input($field);
            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value) === 1) {
                try {
                    $this->merge([$field => Carbon::parse($value, 'Europe/Berlin')->utc()->toDateTimeString()]);
                } catch (InvalidFormatException) { /* The date validation reports invalid input. */
                }
            }
        }
    }

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(PostType::class)], 'text' => ['required', 'string', 'max:1000'],
            'title' => ['nullable', 'required_if:type,event', 'string', 'max:160'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:4096'],
            'link' => ['nullable', 'url:http,https', 'max:2048'], 'link_title' => ['nullable', 'string', 'max:160'], 'link_description' => ['nullable', 'string', 'max:500'],
            'published_at' => ['nullable', 'date'], 'expires_at' => ['nullable', 'date', 'after:published_at'],
            'starts_at' => ['nullable', 'required_if:type,event', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'location' => ['nullable', 'required_if:type,event', 'string', 'max:160'], 'pinned' => ['sometimes', 'boolean'],
            'categories' => ['sometimes', 'array', 'max:10'], 'categories.*' => ['integer', 'distinct', 'exists:categories,id'],
        ];
    }
}
