<?php

namespace App\Http\Requests;

use App\Enums\GitProviderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGitProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:255'],
            'provider_type' => ['required', 'string', Rule::in(array_column(GitProviderType::cases(), 'value'))],
            'base_url'      => ['nullable', 'url', 'max:500'],
            'credentials'   => ['nullable', 'string'],
            'is_active'     => ['nullable', 'boolean'],
        ];
    }
}
