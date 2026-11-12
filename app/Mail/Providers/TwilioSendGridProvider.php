<?php

namespace App\Mail\Providers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Sends e-mails through Twilio SendGrid (Twilio's e-mail API).
 */
class TwilioSendGridProvider implements EmailProvider
{
    private const string BASE_URL = 'https://api.sendgrid.com/v3';

    /**
     * @param  array<string, string>  $credentials
     */
    public function __construct(private readonly array $credentials) {}

    public static function key(): string
    {
        return 'twilio';
    }

    public static function label(): string
    {
        return 'Twilio SendGrid';
    }

    public static function credentialFields(): array
    {
        return [
            'api_key' => [
                'label' => 'API Key',
                'type' => 'password',
                'secret' => true,
                'placeholder' => 'SG.xxxxxxxx',
                'help' => 'Crie em SendGrid → Settings → API Keys, com a permissão "Mail Send".',
                'rules' => ['required', 'string', 'starts_with:SG.', 'max:255'],
            ],
            'from_email' => [
                'label' => 'E-mail do remetente',
                'type' => 'email',
                'secret' => false,
                'placeholder' => 'avisos@bet368.games',
                'help' => 'Precisa ser um remetente ou domínio verificado no SendGrid.',
                'rules' => ['required', 'email', 'max:255'],
            ],
            'from_name' => [
                'label' => 'Nome do remetente',
                'type' => 'text',
                'secret' => false,
                'placeholder' => 'bet368',
                'help' => null,
                'rules' => ['required', 'string', 'max:100'],
            ],
        ];
    }

    public function verifyCredentials(): void
    {
        $response = $this->request(fn (PendingRequest $request) => $request->get(self::BASE_URL.'/scopes'));

        if ($response->unauthorized() || $response->forbidden()) {
            throw new EmailDeliveryException('A API Key foi recusada pelo SendGrid.');
        }

        if ($response->failed()) {
            throw new EmailDeliveryException($this->errorMessage($response));
        }

        if (! in_array('mail.send', (array) $response->json('scopes', []), true)) {
            throw new EmailDeliveryException('A API Key não tem a permissão "Mail Send".');
        }
    }

    public function send(OutgoingEmail $email): ?string
    {
        $response = $this->request(fn (PendingRequest $request) => $request->post(self::BASE_URL.'/mail/send', [
            'personalizations' => [
                ['to' => [['email' => $email->to]]],
            ],
            'from' => [
                'email' => $this->credentials['from_email'],
                'name' => $this->credentials['from_name'],
            ],
            'subject' => $email->subject,
            'content' => [
                ['type' => 'text/html', 'value' => $email->html],
            ],
        ]));

        if ($response->failed()) {
            throw new EmailDeliveryException($this->errorMessage($response));
        }

        return $response->header('X-Message-Id') ?: null;
    }

    /**
     * Send an authenticated request, turning connection problems into delivery errors.
     *
     * @param  callable(PendingRequest): Response  $send
     */
    private function request(callable $send): Response
    {
        try {
            return $send(
                Http::withToken($this->credentials['api_key'])
                    ->acceptJson()
                    ->timeout(15)
            );
        } catch (ConnectionException $exception) {
            throw new EmailDeliveryException('Não foi possível conectar ao SendGrid.', previous: $exception);
        }
    }

    /**
     * Build a readable error message from a failed SendGrid response.
     */
    private function errorMessage(Response $response): string
    {
        $message = $response->json('errors.0.message');

        return is_string($message) && $message !== ''
            ? "SendGrid ({$response->status()}): {$message}"
            : "SendGrid respondeu com o status {$response->status()}.";
    }
}
