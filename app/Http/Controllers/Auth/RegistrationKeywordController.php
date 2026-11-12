<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class RegistrationKeywordController extends Controller
{
    /**
     * The session key that marks a guest as allowed to register.
     */
    public const string VERIFIED_SESSION_KEY = 'registration.keyword_verified';

    /**
     * Show the keyword prompt that guards the registration page.
     */
    public function create(): Response
    {
        return Inertia::render('auth/RegisterKeyword');
    }

    /**
     * Verify the keyword and, when it matches, allow the guest to register.
     */
    public function store(Request $request): RedirectResponse|HttpResponse
    {
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'max:255'],
        ], [
            'keyword.required' => 'Informe a palavra-chave.',
        ]);

        if (! $this->keywordMatches($validated['keyword'])) {
            $request->session()->forget(self::VERIFIED_SESSION_KEY);

            return Inertia::render('auth/RegisterForbidden')
                ->toResponse($request)
                ->setStatusCode(HttpResponse::HTTP_FORBIDDEN);
        }

        $request->session()->put(self::VERIFIED_SESSION_KEY, true);

        return to_route('register');
    }

    /**
     * Determine if the given keyword matches the configured registration keyword.
     */
    private function keywordMatches(string $keyword): bool
    {
        $expectedKeyword = (string) config('fortify.registration_keyword');

        return $expectedKeyword !== '' && hash_equals($expectedKeyword, $keyword);
    }
}
