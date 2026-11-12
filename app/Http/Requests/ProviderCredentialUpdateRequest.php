<?php

namespace App\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates and verifies the credentials of a messaging provider (e-mail, WhatsApp...).
 *
 * The fields and their rules come from the provider strategy, so this request
 * works for any provider. Secret fields may be left blank to keep the stored value.
 */
abstract class ProviderCredentialUpdateRequest extends FormRequest
{
    /**
     * Get the credential fields of the provider being configured.
     *
     * @return array<string, array{label: string, type: 'text'|'email'|'password', secret: bool, placeholder: string, help: string|null, rules: list<string>}>
     */
    abstract protected function credentialFields(): array;

    /**
     * Get the credentials already stored for the provider.
     *
     * @return array<string, string>
     */
    abstract protected function storedCredentials(): array;

    /**
     * Check the credentials with the provider, returning an error message when they don't work.
     *
     * @param  array<string, string>  $credentials
     */
    abstract protected function verificationError(array $credentials): ?string;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $storedCredentials = $this->storedCredentials();

        $rules = [];

        foreach ($this->credentialFields() as $name => $field) {
            $rules[$name] = $field['secret'] && isset($storedCredentials[$name])
                ? ['nullable', ...array_values(array_diff($field['rules'], ['required', 'nullable']))]
                : $field['rules'];
        }

        return $rules;
    }

    /**
     * Get the custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_map(fn (array $field) => $field['label'], $this->credentialFields());
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'Informe :attribute.',
            'email' => 'Informe um e-mail válido em :attribute.',
            'starts_with' => ':attribute deve começar com :values.',
            'alpha_num' => ':attribute deve conter apenas letras e números.',
            'max' => ':attribute deve ter no máximo :max caracteres.',
        ];
    }

    /**
     * Check the credentials with the provider once the fields are valid.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $error = $this->verificationError($this->credentials());

                if ($error !== null) {
                    $validator->errors()->add((string) array_key_first($this->credentialFields()), $error);
                }
            },
        ];
    }

    /**
     * Get the credentials to store, keeping stored secrets that were left blank.
     *
     * @return array<string, string>
     */
    public function credentials(): array
    {
        $storedCredentials = $this->storedCredentials();
        $credentials = [];

        foreach ($this->credentialFields() as $name => $field) {
            $value = trim((string) $this->input($name, ''));

            $credentials[$name] = $value === '' && $field['secret']
                ? ($storedCredentials[$name] ?? '')
                : $value;
        }

        return $credentials;
    }

    /**
     * Get the key of the provider being configured.
     */
    public function providerKey(): string
    {
        return (string) $this->route('provider');
    }
}
