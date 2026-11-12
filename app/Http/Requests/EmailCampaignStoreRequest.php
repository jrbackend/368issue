<?php

namespace App\Http\Requests;

use App\Mail\Providers\EmailProviderFactory;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class EmailCampaignStoreRequest extends FormRequest
{
    /**
     * The maximum number of e-mails per campaign.
     */
    public const int MAX_RECIPIENTS = 1000;

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
                Rule::in(app(EmailProviderFactory::class)->keys()),
                Rule::exists('email_provider_credentials', 'provider'),
            ],
            'subject' => ['required', 'string', 'max:150'],
            'emails' => ['required', 'string', 'max:300000'],
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
            'provider.required' => 'Selecione o serviço de e-mail.',
            'provider.in' => 'Selecione um serviço de e-mail válido.',
            'provider.exists' => 'Configure as credenciais do serviço de e-mail antes de disparar.',
            'subject.required' => 'Informe o assunto do e-mail.',
            'subject.max' => 'O assunto deve ter no máximo 150 caracteres.',
            'emails.required' => 'Informe pelo menos um e-mail.',
            'emails.max' => 'A lista de e-mails é grande demais.',
        ];
    }

    /**
     * Validate the e-mail list: every address must be valid and there may be at most 1,000.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('emails')) {
                    return;
                }

                $addresses = $this->addresses();

                $invalidAddresses = array_values(array_filter(
                    $addresses,
                    fn (string $address) => filter_var($address, FILTER_VALIDATE_EMAIL) === false,
                ));

                if ($invalidAddresses !== []) {
                    $shownAddresses = implode(', ', array_slice($invalidAddresses, 0, 5));
                    $hiddenCount = count($invalidAddresses) - 5;

                    $validator->errors()->add('emails', $hiddenCount > 0
                        ? "E-mails inválidos: {$shownAddresses} e mais {$hiddenCount}."
                        : "E-mails inválidos: {$shownAddresses}.");

                    return;
                }

                if (count($addresses) > self::MAX_RECIPIENTS) {
                    $validator->errors()->add('emails', sprintf(
                        'Máximo de %s e-mails por disparo. A lista tem %s e-mails únicos.',
                        number_format(self::MAX_RECIPIENTS, thousands_separator: '.'),
                        number_format(count($addresses), thousands_separator: '.'),
                    ));
                }
            },
        ];
    }

    /**
     * Get the unique recipient addresses, one per line (commas and semicolons also work).
     *
     * @return list<string>
     */
    public function addresses(): array
    {
        $lines = preg_split('/[\r\n,;]+/', (string) $this->input('emails', '')) ?: [];

        return array_values(array_unique(array_filter(
            array_map(fn (string $line) => Str::lower(trim($line)), $lines),
            fn (string $address) => $address !== '',
        )));
    }
}
