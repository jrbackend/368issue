<?php

namespace App\WhatsApp\Providers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Sends WhatsApp messages through Z-API (https://z-api.io).
 */
class ZApiProvider implements WhatsAppProvider
{
    private const string BASE_URL = 'https://api.z-api.io/instances';

    /**
     * @param  array<string, string>  $credentials
     */
    public function __construct(private readonly array $credentials) {}

    public static function key(): string
    {
        return 'zapi';
    }

    public static function label(): string
    {
        return 'Z-API';
    }

    public static function credentialFields(): array
    {
        return [
            'instance_id' => [
                'label' => 'ID da instância',
                'type' => 'text',
                'secret' => false,
                'placeholder' => '3C2A1B...',
                'help' => 'No painel da Z-API, em Instâncias → sua instância.',
                'rules' => ['required', 'string', 'alpha_num', 'max:64'],
            ],
            'instance_token' => [
                'label' => 'Token da instância',
                'type' => 'password',
                'secret' => true,
                'placeholder' => 'Token da instância',
                'help' => null,
                'rules' => ['required', 'string', 'alpha_num', 'max:128'],
            ],
            'client_token' => [
                'label' => 'Client-Token (token de segurança)',
                'type' => 'password',
                'secret' => true,
                'placeholder' => 'Opcional',
                'help' => 'Obrigatório se o token de segurança da conta estiver ativo (Segurança → Token de segurança da conta).',
                'rules' => ['nullable', 'string', 'alpha_num', 'max:128'],
            ],
        ];
    }

    public function verifyCredentials(): void
    {
        $response = $this->request(fn (PendingRequest $request) => $request->get($this->url('status')));

        if ($response->failed()) {
            throw new WhatsAppDeliveryException(
                in_array($response->status(), [400, 401, 403, 404], true)
                    ? 'ID da instância, token ou Client-Token inválidos.'
                    : $this->errorMessage($response),
            );
        }

        if ($response->json('connected') !== true) {
            throw new WhatsAppDeliveryException(
                'A instância não está conectada ao WhatsApp. Leia o QR Code no painel da Z-API e tente novamente.',
            );
        }
    }

    public function send(OutgoingWhatsAppMessage $message): ?string
    {
        $response = $this->request(fn (PendingRequest $request) => $request->post($this->url('send-text'), [
            'phone' => $message->phone,
            'message' => $message->text,
        ]));

        if ($response->failed()) {
            throw new WhatsAppDeliveryException($this->errorMessage($response));
        }

        $messageId = $response->json('messageId') ?? $response->json('id');

        return is_string($messageId) && $messageId !== '' ? $messageId : null;
    }

    public static function parseStatusWebhook(array $payload): ?WhatsAppStatusUpdate
    {
        if (($payload['type'] ?? null) !== 'MessageStatusCallback' || ! is_array($payload['ids'] ?? null)) {
            return null;
        }

        $status = match ($payload['status'] ?? null) {
            'RECEIVED' => WhatsAppStatusUpdate::DELIVERED,
            'READ', 'PLAYED' => WhatsAppStatusUpdate::READ,
            default => null,
        };

        if ($status === null) {
            return null;
        }

        return new WhatsAppStatusUpdate(
            messageIds: array_values(array_filter($payload['ids'], 'is_string')),
            status: $status,
        );
    }

    /**
     * Build the URL of a Z-API endpoint for the configured instance.
     */
    private function url(string $endpoint): string
    {
        return sprintf(
            '%s/%s/token/%s/%s',
            self::BASE_URL,
            rawurlencode($this->credentials['instance_id']),
            rawurlencode($this->credentials['instance_token']),
            $endpoint,
        );
    }

    /**
     * Send a request, turning connection problems into delivery errors.
     *
     * @param  callable(PendingRequest): Response  $send
     */
    private function request(callable $send): Response
    {
        $request = Http::acceptJson()->timeout(20);

        if (($this->credentials['client_token'] ?? '') !== '') {
            $request->withHeaders(['Client-Token' => $this->credentials['client_token']]);
        }

        try {
            return $send($request);
        } catch (ConnectionException $exception) {
            throw new WhatsAppDeliveryException('Não foi possível conectar à Z-API.', previous: $exception);
        }
    }

    /**
     * Build a readable error message from a failed Z-API response.
     */
    private function errorMessage(Response $response): string
    {
        $message = $response->json('error') ?? $response->json('message');

        return is_string($message) && $message !== ''
            ? "Z-API ({$response->status()}): {$message}"
            : "Z-API respondeu com o status {$response->status()}.";
    }
}
