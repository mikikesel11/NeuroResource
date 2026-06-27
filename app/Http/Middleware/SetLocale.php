<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active locale for each request.
 *
 * Resolution order: explicit ?lang query → "locale" cookie → authenticated
 * user preference → Accept-Language header → configured fallback. Only locales
 * listed in config('neuroscouts.locales.supported') are honored.
 *
 * NOTE: v1 resolves locale without URL prefixing so it can ship alongside the
 * Breeze auth routes untouched. URL-prefixed locales (/en, /es) are the planned
 * next increment — see docs/system-design.md §7a.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = config('neuroscouts.locales.supported', ['en']);
        $fallback = config('neuroscouts.locales.fallback', 'en');

        $locale = $this->fromCandidates([
            $request->query('lang'),
            $request->cookie('locale'),
            $request->user()?->preference?->locale,
            $request->getPreferredLanguage($supported),
        ], $supported) ?? $fallback;

        App::setLocale($locale);

        return $next($request);
    }

    private function fromCandidates(array $candidates, array $supported): ?string
    {
        foreach ($candidates as $candidate) {
            if ($candidate && in_array($candidate, $supported, true)) {
                return $candidate;
            }
        }

        return null;
    }
}
