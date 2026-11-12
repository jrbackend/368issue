<?php

namespace App\WhatsApp;

use App\Models\DomainSetting;

/**
 * Builds the default WhatsApp warning about the domain change, using the current domain settings.
 */
class DomainChangeWhatsAppMessage
{
    /**
     * Get the default message, formatted with WhatsApp markdown (*bold*).
     */
    public function defaultText(): string
    {
        $domainSetting = DomainSetting::current();

        return implode("\n", [
            '⚠️ *Aviso oficial bet368*',
            '',
            "Mudamos de endereço! O *{$domainSetting->old_domain}* agora é *{$domainSetting->new_domain}*.",
            '',
            '✅ Mesma conta, mesmo saldo e histórico',
            '🔒 Conexão mais rápida e segura',
            '🔖 Salve o novo endereço nos seus favoritos',
            '',
            "👉 Acesse agora: {$domainSetting->new_domain_url}",
            '',
            "Nosso único endereço oficial é *{$domainSetting->new_domain}*. Nunca pedimos sua senha por mensagem.",
        ]);
    }
}
