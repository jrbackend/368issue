<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class DomainSettingUpdateRequest extends FormRequest
{
    /**
     * Matches a bare host name such as "bet368.games" (no scheme, path or port).
     */
    private const string DOMAIN_PATTERN = '/^(?!-)(?:[a-z0-9-]{1,63}\.)+[a-z0-9-]{2,63}$/';

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        foreach (['old_domain', 'new_domain', 'new_domain_url'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => Str::lower(trim($this->input($field)))]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'old_domain' => ['required', 'string', 'max:255', 'regex:'.self::DOMAIN_PATTERN, 'different:new_domain'],
            'new_domain' => ['required', 'string', 'max:255', 'regex:'.self::DOMAIN_PATTERN],
            'new_domain_url' => ['required', 'string', 'max:255', 'url:https'],
            'redirect_seconds' => ['required', 'integer', 'between:5,120'],
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'old_domain.required' => 'Informe o domínio antigo.',
            'old_domain.regex' => 'Informe apenas o domínio, sem https:// ou barras (ex.: bet368.cassino).',
            'old_domain.different' => 'O domínio antigo deve ser diferente do novo domínio.',
            'old_domain.max' => 'O domínio antigo deve ter no máximo 255 caracteres.',
            'new_domain.required' => 'Informe o novo domínio.',
            'new_domain.regex' => 'Informe apenas o domínio, sem https:// ou barras (ex.: bet368.games).',
            'new_domain.max' => 'O novo domínio deve ter no máximo 255 caracteres.',
            'new_domain_url.required' => 'Informe a URL de redirecionamento.',
            'new_domain_url.url' => 'Informe uma URL válida começando com https://.',
            'new_domain_url.max' => 'A URL deve ter no máximo 255 caracteres.',
            'redirect_seconds.required' => 'Informe o tempo de redirecionamento.',
            'redirect_seconds.integer' => 'O tempo de redirecionamento deve ser um número inteiro.',
            'redirect_seconds.between' => 'O tempo de redirecionamento deve ficar entre 5 e 120 segundos.',
        ];
    }
}
