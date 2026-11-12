<?php

namespace App\Http\Requests;

use App\Models\TrafficRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrafficRuleUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'is_enabled' => ['required', 'boolean'],
            'allowed_devices' => ['present', 'array'],
            'allowed_devices.*' => ['string', Rule::in(config('traffic.devices'))],
            'allowed_languages' => ['present', 'array'],
            'allowed_languages.*' => ['string', Rule::in(config('traffic.languages'))],
            'allowed_countries' => ['present', 'array'],
            'allowed_countries.*' => ['string', Rule::in(config('traffic.countries'))],
            'allow_unknown_country' => ['required', 'boolean'],
            'blocked_action' => ['required', Rule::in([TrafficRule::ACTION_BLOCK, TrafficRule::ACTION_REDIRECT])],
            'blocked_redirect_url' => [
                Rule::requiredIf($this->input('blocked_action') === TrafficRule::ACTION_REDIRECT),
                'nullable',
                'string',
                'max:255',
                'url:https',
            ],
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
            'allowed_devices.*.in' => 'Selecione apenas dispositivos válidos.',
            'allowed_languages.*.in' => 'Selecione apenas idiomas válidos.',
            'allowed_countries.*.in' => 'Selecione apenas países válidos.',
            'blocked_action.in' => 'Escolha o que fazer com os visitantes bloqueados.',
            'blocked_redirect_url.required' => 'Informe a URL para onde os visitantes bloqueados serão enviados.',
            'blocked_redirect_url.url' => 'Informe uma URL válida começando com https://.',
            'blocked_redirect_url.max' => 'A URL deve ter no máximo 255 caracteres.',
        ];
    }
}
