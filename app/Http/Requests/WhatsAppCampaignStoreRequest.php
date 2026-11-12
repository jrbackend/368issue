<?php

namespace App\Http\Requests;

use App\WhatsApp\Providers\WhatsAppProviderFactory;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class WhatsAppCampaignStoreRequest extends FormRequest
{
    /**
     * The maximum number of phone numbers per campaign.
     */
    public const int MAX_RECIPIENTS = 500;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'provider' => [
                'required',
                'string',
                Rule::in(app(WhatsAppProviderFactory::class)->keys()),
                Rule::exists('whats_app_provider_credentials', 'provider'),
            ],
            'message' => ['required', 'string', 'max:4000'],
            'phones' => ['required', 'string', 'max:20000'],
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
            'provider.required' => 'Selecione o serviço de WhatsApp.',
            'provider.in' => 'Selecione um serviço de WhatsApp válido.',
            'provider.exists' => 'Configure as credenciais do serviço de WhatsApp antes de disparar.',
            'message.required' => 'Escreva a mensagem.',
            'message.max' => 'A mensagem deve ter no máximo 4.000 caracteres.',
            'phones.required' => 'Informe pelo menos um número.',
            'phones.max' => 'A lista de números é grande demais.',
        ];
    }

    /**
     * Validate the phone list: every number must be valid and there may be at most 500.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('phones')) {
                    return;
                }

                ['valid' => $phoneNumbers, 'invalid' => $invalidLines] = $this->parsedPhoneNumbers();

                if ($invalidLines !== []) {
                    $shownLines = implode(', ', array_slice($invalidLines, 0, 5));
                    $hiddenCount = count($invalidLines) - 5;

                    $validator->errors()->add('phones', $hiddenCount > 0
                        ? "Números inválidos: {$shownLines} e mais {$hiddenCount}."
                        : "Números inválidos: {$shownLines}.");

                    return;
                }

                if (count($phoneNumbers) > self::MAX_RECIPIENTS) {
                    $validator->errors()->add('phones', sprintf(
                        'Máximo de %d números por disparo. A lista tem %s números únicos.',
                        self::MAX_RECIPIENTS,
                        number_format(count($phoneNumbers), thousands_separator: '.'),
                    ));
                }
            },
        ];
    }

    /**
     * Get the unique phone numbers (digits with country code) to send to.
     *
     * @return list<string>
     */
    public function phoneNumbers(): array
    {
        return $this->parsedPhoneNumbers()['valid'];
    }

    /**
     * Parse the phone list: one number per line, any formatting (spaces, dashes, parentheses).
     *
     * Numbers starting with "+" or "00" keep their country code; numbers with 10 or 11
     * digits are considered local and get the default country code (Brazil, 55).
     *
     * @return array{valid: list<string>, invalid: list<string>}
     */
    private function parsedPhoneNumbers(): array
    {
        $lines = preg_split('/[\r\n,;]+/', (string) $this->input('phones', '')) ?: [];

        $valid = [];
        $invalid = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $digits = (string) preg_replace('/\D/', '', $line);
            $hasCountryCode = Str::startsWith($line, ['+', '00']);

            if (Str::startsWith($line, '00')) {
                $digits = substr($digits, 2);
            }

            if (! $hasCountryCode && in_array(strlen($digits), [10, 11], true)) {
                $digits = config('whatsapp.default_country_code').$digits;
            }

            if (preg_match('/^[1-9]\d{9,14}$/', $digits) === 1 && preg_match('/^[\d\s().+\-]+$/', $line) === 1) {
                $valid[] = $digits;
            } else {
                $invalid[] = $line;
            }
        }

        return [
            'valid' => array_values(array_unique($valid)),
            'invalid' => $invalid,
        ];
    }
}
