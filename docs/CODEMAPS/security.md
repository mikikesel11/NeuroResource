# Security Codemap

**Last Updated:** 2026-07-06

## Recent Hardening (v1.6)

This codemap documents all security features added in recent commits (#30–#34).

### Overview

```
Security Layers
├─ HTTP Headers (#30)
│  ├─ X-Content-Type-Options: nosniff
│  ├─ Referrer-Policy: strict-origin-when-cross-origin
│  └─ Permissions-Policy: camera=(), microphone=(), geolocation=()
│
├─ Email-Gate Hardening (#33)
│  ├─ Rate Limiting (per IP + email)
│  ├─ Signed + Expiring Links (URL::temporarySignedRoute)
│  ├─ Mass-Assignment Protection (guarded columns)
│  └─ Session De-Duplication (first unlock only)
│
├─ URL & Content Validation (#31)
│  ├─ Certification credential_url: http(s) only
│  └─ SafeMarkdown: CommonMark + tgalopin/html-sanitizer
│
└─ Type Safety (#32)
   └─ declare(strict_types=1) across app/
```

---

## 1. HTTP Security Headers (#30)

**File:** `app/Http/Middleware/SecurityHeaders.php`  
**Registered:** Global on web route group in `bootstrap/app.php`

### Implementation

```php
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Prevent MIME-sniffing attacks (browser won't guess content type)
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // Limit Referer leakage; CRITICAL for email-gate tokens in query strings
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Opt out of browser APIs not used
        $response->headers->set('Permissions-Policy', 
            'camera=(), microphone=(), geolocation=()'
        );
        
        return $response;
    }
}
```

### Why Each Header?

| Header | Purpose | Impact |
|--------|---------|--------|
| **X-Content-Type-Options: nosniff** | Prevent browser MIME-sniffing (e.g., serving JS as HTML) | Blocks a class of XSS exploits in older browsers |
| **Referrer-Policy: strict-origin-when-cross-origin** | Prevent Referer header from leaking sensitive data (email-gate tokens) to third-party sites | **CRITICAL** — resource unlock links have `?signature=...&expires=...` in query string; must not leak to external scripts |
| **Permissions-Policy: camera=(), microphone=(), geolocation=()** | Opt out of browser APIs not used | Reduces attack surface; NeuroScouts doesn't need these sensors |

### Testing

See `tests/Feature/SecurityHeadersTest.php` — verifies all three headers present on every web response.

---

## 2. Email-Gate Hardening (#33)

**Files:**
- `app/Livewire/Resources/ResourcePage.php` — unlock form
- `app/Domains/Resources/Http/Controllers/ConfirmUnlockController.php` — confirmation endpoint
- `app/Domains/Resources/Models/Resource.php` — mass-assignment protection
- `app/Domains/Resources/Mail/ConfirmResourceUnlock.php` — signed link generation
- Config: `config/neuroresource.php` — throttle settings + TTL

### Problem Statement

The email-gate flow had several risks:

1. **Mail-bombing:** attacker could spam unlock requests (e.g., 1000 emails to victim)
2. **Tampered links:** unsigned confirmation links could be forged with arbitrary tokens
3. **Mass-assignment:** `access` and `download_count` could be set from request input
4. **Session bloat:** revisiting a confirmation link could add duplicate entries to session

### Solution: Four-Part Hardening

#### A. Rate Limiting (C3)

**Endpoint:** `POST /resources/{slug}/unlock`

```php
// In ConfirmUnlockController::store()
RateLimiter::hit(
    'resource-unlock:' . request()->ip() . ':' . $email,
    $maxAttempts = config('neuroresource.email_gate_max_attempts', 3),  // default 3
    $decaySeconds = config('neuroresource.email_gate_decay_minutes', 60) * 60  // default 1 hour
);

if (RateLimiter::tooManyAttempts(...)) {
    // Return friendly notice, send NO email
    return response()->json([
        'message' => 'Too many requests. Please try again later.',
    ], 429);
}
```

**Result:** After 3 attempts within 1 hour (per IP + email), throttle kicks in. Friendly notice, no mail sent, rate limit key tracked.

**Config:** Set in `config/neuroresource.php`:
```php
'email_gate_max_attempts' => env('RESOURCE_GATE_MAX_ATTEMPTS', 3),
'email_gate_decay_minutes' => env('RESOURCE_GATE_DECAY_MINUTES', 60),
```

#### B. Signed + Expiring Links (H3)

**Mail:** `app/Domains/Resources/Mail/ConfirmResourceUnlock.php`

```php
// Generate confirmation URL
$url = URL::temporarySignedRoute(
    'resources.confirm',
    now()->addHours(config('neuroresource.unlock_link_ttl_hours', 24)),
    ['slug' => $resource->slug, 'token' => $unlock->token]
);

// Link format: /resources/{slug}/confirm?expires=1625097600&signature=abc123...
```

**Validation:** `app/Domains/Resources/Http/Controllers/ConfirmUnlockController.php`

```php
Route::get('/resources/{slug}/confirm', [ConfirmUnlockController::class, 'confirm'])
    ->middleware(['signed', 'throttle:resource-confirm'])  // Verify HMAC + expiry
    ->name('resources.confirm');

public function confirm(Request $request, $slug)
{
    // The `signed` middleware already verified:
    // - HMAC signature is valid
    // - `expires` timestamp is not in the past
    
    // Double-check: verify token in DB (defense in depth)
    $unlock = ResourceUnlock::where('token', $request->token)
        ->whereNull('confirmed_at')
        ->firstOrFail();
    
    // Mark as confirmed
    $unlock->update(['confirmed_at' => now()]);
    
    // De-dup on first confirm only (see C below)
    session(['resource_unlocks' => array_unique([..., $slug])]);
}
```

**Result:** Unsigned, expired, or tampered links return 403 Forbidden. Only the resource creator can generate valid links.

**Config:** Set in `config/neuroresource.php`:
```php
'unlock_link_ttl_hours' => env('RESOURCE_UNLOCK_LINK_TTL_HOURS', 24),
```

#### C. Mass-Assignment Protection (H4)

**Model:** `app/Domains/Resources/Models/Resource.php`

```php
class Resource extends Model
{
    // BEFORE: $fillable = ['title', 'summary', 'access', 'download_count', ...];
    // PROBLEM: attacker could POST access=free or download_count=999
    
    // AFTER: $guarded prevents mass-assignment
    protected $guarded = ['access', 'download_count'];  // Can only set via forceFill()
}
```

**Testing:** `ResourceFactory` sets guarded columns explicitly:

```php
// In tests/Factories/ResourceFactory.php
class ResourceFactory {
    public function definition(): array
    {
        return [
            'title' => 'Sample Resource',
            // ... other fields
        ];
    }
    
    public function create(): Resource {
        $resource = Resource::factory()->make();
        return $resource->forceFill([
            'access' => 'email',          // Set guarded column
            'download_count' => 0,        // Set guarded column
        ])->save();
    }
}
```

**Result:** POST `/resources` with `access=free` is silently ignored; only admin can set via forceFill().

#### D. Session De-Duplication (MEDIUM)

**Controller:** `ConfirmUnlockController::confirm()`

```php
public function confirm(Request $request, $slug)
{
    $unlock = ResourceUnlock::where('token', $request->token)->firstOrFail();
    
    // On first confirmation only:
    if (! $unlock->confirmed_at) {
        $unlock->update(['confirmed_at' => now()]);
        
        // De-dup: only add slug once
        session(['resource_unlocks' => array_unique([
            ...session('resource_unlocks', []),
            $slug,
        ])]);
    }
    
    // Revisiting link does nothing (no duplicate session entry)
    // and doesn't send another mail
}
```

**Result:** Revisiting a confirmation link doesn't spam the session or re-send mail. Session stays clean.

### Test Coverage

All hardening tested:
- `tests/Feature/Resources/EmailGateThrottleTest.php` — rate limit behavior
- `tests/Feature/Resources/SignedLinkTest.php` — signature validation, expiry checks
- `tests/Feature/Resources/SessionDedupTest.php` — first-confirm-only logic
- `tests/Unit/Resources/ResourceMassAssignmentTest.php` — guarded columns

---

## 3. URL & Content Validation (#31)

### A. Certification Credential URL Validation

**File:** `app/Domains/Profile/Models/Certification.php`

**Problem:** A malicious credential_url like `javascript:alert('xss')` or `data:text/html,...` could execute in a browser.

**Solution: Attribute Mutator**

```php
class Certification extends Model
{
    // Mutator: called on assignment (Cert::create(['credential_url' => $url]))
    protected function credentialUrl(): Attribute
    {
        return Attribute::make(
            set: fn (string|null $value) => 
                ($value && str_starts_with($value, ['http://', 'https://']))
                    ? $value
                    : null,  // Reject non-http(s), store null
        );
    }
}
```

**Blade Defense-in-Depth:** `resources/views/livewire/profile/about.blade.php`

```blade
@forelse($profile->certifications as $cert)
    @if($cert->credential_url && str_starts_with($cert->credential_url, ['http://', 'https://']))
        <a href="{{ $cert->credential_url }}" rel="noopener noreferrer">
            View {{ $cert->issuer }} {{ $cert->name }} Credential
        </a>
    @else
        <span>{{ $cert->name }} ({{ $cert->issuer }})</span>
    @endif
@empty
    <p>No certifications listed.</p>
@endforelse
```

**Result:** 
- Mutator blocks bad URLs at the model layer (immutable)
- Blade guard ensures even legacy rows with bad schemes never reach an href
- Defense-in-depth: both layers protect against each other's failure

### B. Markdown HTML Sanitization

**File:** `app/Support/SafeMarkdown.php`

**Problem:** CommonMark can render HTML if the Markdown contains it, and HTML edge cases could emit XSS-capable constructs.

**Solution: Two-Pass Approach**

```php
function safe_markdown(string $content): string
{
    // Pass 1: CommonMark → convert Markdown to HTML, stripping raw HTML
    $html = Str::markdown($content, [
        'html_input' => 'strip',           // Remove raw HTML blocks
        'allow_unsafe_links' => false,     // No javascript: or data: URLs
    ]);

    // Pass 2: Sanitize via tgalopin/html-sanitizer (strict whitelist)
    $sanitizer = Sanitizer::create([
        'extensions' => ['basic', 'code', 'image', 'list', 'table', 'extra'],
    ]);

    return $sanitizer->sanitize($html);  // Remove any XSS-capable constructs
}
```

**Extensions Allowed:**
- `basic` — `<strong>`, `<em>`, `<u>`, `<a>` (href validated)
- `code` — `<code>`, `<pre>`
- `image` — `<img>` (src validated, no onerror)
- `list` — `<ul>`, `<ol>`, `<li>`
- `table` — `<table>`, `<tr>`, `<td>`, `<th>`
- `extra` — `<hr>`, `<blockquote>`, `<figure>`

**Blocks:**
- `<script>`, `<style>`, `<iframe>`, `<object>`, `<embed>` — removed
- Event handlers (`onclick`, `onerror`, etc.) — removed
- Non-http(s) URLs — sanitized

**Usage:** Replace `{!! Str::markdown($text) !!}` with `{!! safe_markdown($text) !!}`

Applied in:
- `resources/views/livewire/blog/show.blade.php` — post body
- `resources/views/livewire/profile/about.blade.php` — profile bio

**Test:** `tests/Unit/Support/SafeMarkdownTest.php` — verifies XSS attempts are sanitized.

---

## 4. Type Safety (#32)

**Scope:** `declare(strict_types=1)` added to all PHP files under `app/` except:
- `app/Domains/Game/Services/{CardService.php, XpService.php}` — owned by sibling PR
- `app/Domains/Resources/` — owned by sibling PR #33

**Impact:** Strict type checking prevents silent type coercions (e.g., `"10" == 10` is now a hard error).

**Example:**

```php
// BEFORE (lenient)
function awardXp(string $source, $amount) {
    // If $amount is "10" (string), it silently coerces
    XpEvent::create(['amount' => $amount]);  // Stores as integer 10 (but risky)
}

// AFTER (strict)
declare(strict_types=1);

function awardXp(string $source, int $amount): void {
    // If $amount is "10" (string), TypeError thrown immediately
    XpEvent::create(['amount' => $amount]);  // Guaranteed to be int
}
```

**Adoption:** All 111 tests passing with strict types enabled (scope 1).

---

## 5. Authentication & Authorization

### Email Verification (Required)

**For Email-Gated Resources:**
```php
// ResourceGate::unlocked()
if (auth()->check() && auth()->user()?->hasVerifiedEmail()) {
    return true;  // Allow download
}
```

Logged-in users with unverified emails are treated as anonymous (must go through email-gate unlock flow).

**For Comments/Submissions:** Consider adding email verification requirement to prevent spam.

### CSRF Protection

Provided by Laravel (global middleware in `app/Http/Middleware/VerifyCsrfToken.php`):
- All POST/PUT/PATCH/DELETE requests require `X-CSRF-TOKEN` or `_token` form field
- Livewire handles automatically (`@csrf` in forms)

### XSS Prevention

Layers:
1. **Blade escaping:** `{{ }}` escapes HTML by default; use `{!! !!}` only with `safe_markdown()`
2. **Safe helpers:** `safe_markdown()` uses sanitizer
3. **URL validation:** credential_url accepts http(s) only
4. **CSP planning:** (not implemented yet — inline `<script>` blocks in layout block this)

### CORS

Not used (same-origin SPA). If API is exposed to cross-origin clients, add:
```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api([\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class]);
})
```

---

## 6. Sensitive Data Protection

### Passwords
- Hashed via bcrypt (Laravel Breeze default)
- Never logged or displayed
- Reset tokens are secure, time-limited

### API Tokens
- Shopify Storefront token: env var only, never in repo
- Plausible analytics ID: env var only

### Email Addresses
- Resource unlock: email captured only for opt-in confirmation
- Mailing list: managed separately (not in app yet)

### Referrer Leakage
- **Referrer-Policy: strict-origin-when-cross-origin** — prevents email-gate tokens from leaking to third-party scripts

---

## 7. Input Validation

All user input validated at system boundaries:

| Source | Validation | Location |
|--------|-----------|----------|
| HTTP request body | Form requests (Laravel validation rules) | Controllers, Livewire |
| Query parameters | Signed routes + middleware | Confirmation links, pagination |
| File uploads | MIME type, size, malware scan (later) | Resources, images |
| Database queries | Prepared statements (Eloquent ORM) | All models |
| Markdown content | safe_markdown() helper | Blog, profile |

### Rate Limiting
- Email-gate unlock: per IP + email
- API endpoints: per user/IP (Laravel throttle middleware)
- Download: no limit (but async job prevents abuse)

---

## 8. Logging & Monitoring

### Sensitive Data in Logs
- NO passwords in logs (Laravel hashes by default)
- NO API keys in logs (use env vars, log only "set"/"unset")
- Email-gate tokens logged (in URLs), but Referrer-Policy prevents leakage

### Error Messages
- User-facing: generic (e.g., "Invalid request")
- Server logs: detailed (stack trace, SQL, etc.)
- Exception handler: `app/Exceptions/Handler.php`

### Security Event Logging
Consider adding:
- Failed unlock attempts (throttled, not logged yet)
- Suspicious certificate URLs (rejected silently)
- Signed link failures (403 returned, could log)

---

## 9. External Dependencies (Security-Relevant)

| Package | Purpose | Security Notes |
|---------|---------|-----------------|
| laravel/breeze | Auth (registration, login, email verify) | Battle-tested; keep updated |
| tgalopin/html-sanitizer | XSS defense | Strict whitelist; no bypass known |
| shopify/storefront-api-client | Shopify integration | Token-based; never store webhook data |
| plausible/analytics | Privacy-first analytics | Cookieless; no consent banner needed |

---

## 10. Security Checklist

Before any deploy:

- [x] All tests green (156 tests)
- [x] No hardcoded secrets (API keys, tokens)
- [x] User input validated + sanitized
- [x] CSRF protection enabled (Laravel)
- [x] Email verification required (auth)
- [x] SQL injection prevented (ORM)
- [x] XSS prevented (Blade escaping + SafeMarkdown)
- [x] Rate limiting on unlock endpoint
- [x] Signed + expiring email-gate links
- [x] Security headers (X-Content-Type-Options, Referrer-Policy, Permissions-Policy)
- [x] Type safety (declare strict_types)
- [x] Error messages don't leak data
- [x] Sensitive data not in logs
- [x] HTTPS enforced in production (`APP_DEBUG=false`, secure cookies)
- [x] No dead auth scaffolding (removed #34)

---

## 11. Future Hardening

**Not yet implemented:**
- CSP (Content Security Policy) nonce headers
- HSTS (HTTP Strict-Transport-Security)
- Mailing list provider integration (check terms)
- File upload virus scanning (ClamAV)
- DDoS protection (Cloudflare WAF)
- Security audit of Adventure game (cross-device save)

---

## Related Areas

- [backend.md](backend.md) — Controllers implementing security checks
- [database.md](database.md) — Model-layer validation (mutators, guarded columns)
- [frontend.md](frontend.md) — Blade escaping, client-side validation
