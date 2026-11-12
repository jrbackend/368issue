<?php

namespace App\Mail;

use App\Models\DomainSetting;

/**
 * Renders the domain change notice e-mail using the current domain settings.
 */
class DomainChangeEmail
{
    /**
     * Get the default subject of the e-mail.
     */
    public function defaultSubject(): string
    {
        $domainSetting = DomainSetting::current();

        return "Atenção: {$domainSetting->old_domain} agora é {$domainSetting->new_domain}";
    }

    /**
     * Render the e-mail HTML, optionally with a pixel that records when it is opened.
     */
    public function render(?string $trackingPixelUrl = null): string
    {
        $domainSetting = DomainSetting::current();

        return view('emails.domain-change', [
            'oldDomain' => $domainSetting->old_domain,
            'newDomain' => $domainSetting->new_domain,
            'newDomainUrl' => $domainSetting->new_domain_url,
            'trackingPixelUrl' => $trackingPixelUrl,
        ])->render();
    }
}
