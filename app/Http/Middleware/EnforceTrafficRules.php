<?php

namespace App\Http\Middleware;

use App\Models\TrafficRule;
use App\Models\TrafficVisit;
use Closure;
use hisorange\BrowserDetect\Contracts\ResultInterface;
use hisorange\BrowserDetect\Facade as Browser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Stevebauman\Location\Facades\Location;
use Symfony\Component\HttpFoundation\Response;

use function Illuminate\Support\defer;

class EnforceTrafficRules
{
    /**
     * Only let visitors that match the traffic rules through, block or redirect everyone else,
     * and log every visit.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $trafficRule = TrafficRule::current();
        $ip = (string) $request->ip();
        $browser = Browser::parse($request->userAgent() ?? '');
        $device = $this->deviceType($browser);
        $language = $this->language($request);

        $location = $trafficRule->restrictsCountries() ? $this->location($ip) : null;

        $blockedReason = $trafficRule->blockedReason($device, $language, $location['country_code'] ?? null);

        $this->logVisit($request, $ip, $browser, $device, $language, $location, $blockedReason);

        if ($blockedReason === null) {
            $response = $next($request);
        } else {
            $response = $this->blockedResponse($request, $trafficRule);
        }

        if ($trafficRule->is_enabled) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }

    /**
     * Detect the visitor's device type.
     */
    private function deviceType(ResultInterface $browser): string
    {
        return match (true) {
            $browser->isTablet() => 'tablet',
            $browser->isMobile() => 'mobile',
            default => 'desktop',
        };
    }

    /**
     * Get the primary language of the visitor's browser (e.g. "pt" for "pt-BR").
     */
    private function language(Request $request): ?string
    {
        $preferredLanguage = $request->getPreferredLanguage();

        return $preferredLanguage === null
            ? null
            : Str::lower(Str::before($preferredLanguage, '_'));
    }

    /**
     * Resolve the location of an IP address, caching the result.
     *
     * @return array{country_code: string|null, region_name: string|null, city_name: string|null}
     */
    private function location(string $ip): array
    {
        $unknownLocation = ['country_code' => null, 'region_name' => null, 'city_name' => null];

        if (! $this->isPublicIp($ip) && ! config('location.testing.enabled')) {
            return $unknownLocation;
        }

        $cacheKey = "traffic-control:location:{$ip}";
        $cachedLocation = Cache::get($cacheKey);

        if (is_array($cachedLocation)) {
            return $cachedLocation;
        }

        $position = Location::get($ip);

        $location = $position && $position->countryCode
            ? [
                'country_code' => Str::upper($position->countryCode),
                'region_name' => $position->regionName ?: null,
                'city_name' => $position->cityName ?: null,
            ]
            : $unknownLocation;

        Cache::put(
            $cacheKey,
            $location,
            now()->addMinutes($location['country_code'] === null
                ? config('traffic.failed_country_cache_minutes')
                : config('traffic.country_cache_minutes')),
        );

        return $location;
    }

    /**
     * Determine if the IP address is public (private and reserved ranges can't be geolocated).
     */
    private function isPublicIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    /**
     * Record the visit after the response has been sent, so logging never slows the page down.
     *
     * @param  array{country_code: string|null, region_name: string|null, city_name: string|null}|null  $location
     */
    private function logVisit(
        Request $request,
        string $ip,
        ResultInterface $browser,
        string $device,
        ?string $language,
        ?array $location,
        ?string $blockedReason,
    ): void {
        $userAgent = $request->userAgent();

        defer(function () use ($ip, $browser, $device, $language, $location, $blockedReason, $userAgent): void {
            TrafficVisit::create([
                'ip' => $ip,
                ...($location ?? $this->location($ip)),
                'device' => $device,
                'is_bot' => $browser->isBot(),
                'operating_system' => $browser->platformName() ?: null,
                'browser' => $browser->browserFamily() !== 'Unknown' ? $browser->browserFamily() : null,
                'language' => $language,
                'user_agent' => $userAgent === null ? null : Str::limit($userAgent, 509),
                'was_allowed' => $blockedReason === null,
                'blocked_reason' => $blockedReason,
            ]);
        })->always();
    }

    /**
     * Build the response shown to visitors that don't match the traffic rules.
     */
    private function blockedResponse(Request $request, TrafficRule $trafficRule): Response
    {
        if ($trafficRule->blocked_action === TrafficRule::ACTION_REDIRECT && $trafficRule->blocked_redirect_url) {
            return Inertia::location($trafficRule->blocked_redirect_url);
        }

        return Inertia::render('TrafficBlocked')
            ->toResponse($request)
            ->setStatusCode(Response::HTTP_FORBIDDEN);
    }
}
