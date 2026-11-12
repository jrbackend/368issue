<?php

namespace App\Http\Controllers;

use App\Models\EmailCampaignRecipient;
use Illuminate\Http\Response;

class EmailOpenController extends Controller
{
    /**
     * A transparent 1x1 GIF.
     */
    private const string TRANSPARENT_GIF = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    /**
     * Record the first time an e-mail is opened (its tracking pixel is loaded).
     */
    public function __invoke(string $token): Response
    {
        EmailCampaignRecipient::query()
            ->where('tracking_token', $token)
            ->whereNull('opened_at')
            ->update(['opened_at' => now()]);

        return response(base64_decode(self::TRANSPARENT_GIF), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
