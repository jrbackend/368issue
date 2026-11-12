<?php

use App\Http\Controllers\Auth\RegistrationKeywordController;
use App\Http\Controllers\DomainSettingController;
use App\Http\Controllers\EmailCampaignController;
use App\Http\Controllers\EmailOpenController;
use App\Http\Controllers\EmailProviderCredentialController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TrafficRuleController;
use App\Http\Controllers\TrafficVisitController;
use App\Http\Controllers\WhatsAppCampaignController;
use App\Http\Controllers\WhatsAppProviderCredentialController;
use App\Http\Controllers\WhatsAppWebhookController;
use App\Http\Middleware\EnforceTrafficRules;
use App\Mail\Providers\EmailProviderFactory;
use App\WhatsApp\Providers\WhatsAppProviderFactory;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)
    ->middleware(EnforceTrafficRules::class)
    ->name('home');

Route::inertia('blog', 'Blog')->name('blog');

Route::get('email/open/{token}', EmailOpenController::class)
    ->whereUuid('token')
    ->name('email.open');

Route::post('whatsapp/webhooks/{provider}/{secret}', WhatsAppWebhookController::class)
    ->whereIn('provider', app(WhatsAppProviderFactory::class)->keys())
    ->name('whatsapp.webhooks.status');

Route::middleware('guest')->group(function () {
    Route::get('register/keyword', [RegistrationKeywordController::class, 'create'])
        ->name('register.keyword');

    Route::post('register/keyword', [RegistrationKeywordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.keyword.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DomainSettingController::class, 'edit'])->name('dashboard');
    Route::put('dashboard/domain-settings', [DomainSettingController::class, 'update'])->name('domain-settings.update');

    Route::get('traffic', [TrafficRuleController::class, 'edit'])->name('traffic.edit');
    Route::put('traffic', [TrafficRuleController::class, 'update'])->name('traffic.update');
    Route::get('traffic/logs', [TrafficVisitController::class, 'index'])->name('traffic.logs');

    Route::get('email', [EmailCampaignController::class, 'index'])->name('email.index');
    Route::post('email/campaigns', [EmailCampaignController::class, 'store'])->name('email.campaigns.store');
    Route::put('email/providers/{provider}', [EmailProviderCredentialController::class, 'update'])
        ->whereIn('provider', app(EmailProviderFactory::class)->keys())
        ->name('email.providers.update');

    Route::get('whatsapp', [WhatsAppCampaignController::class, 'index'])->name('whatsapp.index');
    Route::post('whatsapp/campaigns', [WhatsAppCampaignController::class, 'store'])->name('whatsapp.campaigns.store');
    Route::put('whatsapp/providers/{provider}', [WhatsAppProviderCredentialController::class, 'update'])
        ->whereIn('provider', app(WhatsAppProviderFactory::class)->keys())
        ->name('whatsapp.providers.update');
});

require __DIR__.'/settings.php';
