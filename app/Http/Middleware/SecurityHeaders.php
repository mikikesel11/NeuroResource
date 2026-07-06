<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds baseline HTTP security response headers to every web response.
 *
 * Headers applied:
 *   - X-Content-Type-Options: prevents MIME-sniffing attacks.
 *   - Referrer-Policy: limits Referer leakage; important because resource
 *     email-gate confirmation links carry tokens in query strings that must
 *     not be forwarded to third-party scripts via the Referer header.
 *   - Permissions-Policy: opts out of camera, microphone, and geolocation
 *     browser APIs that this app does not use.
 *
 * CSP is intentionally omitted here — the public layout contains inline
 * <script> blocks that require nonce generation, which is a larger change.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}
