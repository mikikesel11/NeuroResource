# Backend Codemap

**Last Updated:** 2026-07-06

## Architecture

```
Backend (Laravel 13 + Modular Monolith)
│
├─ Global HTTP Layer (app/Http/)
│  ├─ Controllers/
│  │  ├─ Controller.php         Base controller
│  │  └─ Auth/VerifyEmailController.php
│  ├─ Middleware/
│  │  ├─ SecurityHeaders.php    (#30) X-Content-Type-Options, Referrer-Policy, Permissions-Policy
│  │  └─ SetLocale.php          Locale routing middleware
│  └─ Requests/ (validation)
│
├─ Service Providers (app/Providers/)
│  ├─ AppServiceProvider.php    Bindings (ProductCatalog, email-gate), listeners
│  ├─ RouteServiceProvider.php
│  ├─ AuthServiceProvider.php
│  └─ BroadcastServiceProvider.php
│
├─ Domain Modules (app/Domains/)
│  ├─ Content/
│  │  ├─ Models/Post.php, Tag.php
│  │  └─ [no controllers: served by Livewire]
│  │
│  ├─ Game/
│  │  ├─ Models/
│  │  │  ├─ Card.php               Card state
│  │  │  ├─ UserCardPull.php       Per-user pull tracking
│  │  │  ├─ GameProgress.php       Adventure save
│  │  │  └─ XpEvent.php            Append-only XP log
│  │  ├─ Services/
│  │  │  ├─ CardService.php        Card draw logic, shuffle, deck state
│  │  │  └─ XpService.php          XP calculation, period totals, leaderboard
│  │  ├─ Http/Controllers/
│  │  │  ├─ AdventureController.php Story + engine (serves JSON data island)
│  │  │  ├─ GameProgressController.php /api/game/progress (cross-device save)
│  │  │  └─ XpMetricsController.php /api/xp/* (stats API)
│  │  ├─ Listeners/
│  │  │  └─ AwardDailyLoginXp.php  Wired to Illuminate\Auth\Events\Login
│  │  └─ Support/Story.php         Scene graph validator
│  │
│  ├─ Preferences/
│  │  └─ Models/Preference.php     Theme, text scale, motion, font, locale
│  │
│  ├─ Profile/
│  │  ├─ Models/
│  │  │  ├─ Profile.php            Bio, headline, avatar
│  │  │  └─ Certification.php      With URL validation (#31)
│  │  └─ Http/Controllers/
│  │     └─ AboutController.php    Renders profile + certs
│  │
│  ├─ Resources/ (Email-Gate Hardening)
│  │  ├─ Models/
│  │  │  ├─ Resource.php           Title, file_path, access ['free'|'email']
│  │  │  │                         $guarded: ['access', 'download_count'] (#33)
│  │  │  └─ ResourceUnlock.php     Unlock captures (user_id | email)
│  │  ├─ Http/Controllers/
│  │  │  ├─ DownloadController.php /resources/{slug}/download (gating check)
│  │  │  └─ ConfirmUnlockController.php /resources/{slug}/confirm (throttled, signed link)
│  │  ├─ Jobs/
│  │  │  └─ RecordResourceDownload.php Async download count increment
│  │  ├─ Mail/
│  │  │  └─ ConfirmResourceUnlock.php Confirmation email with signed link
│  │  └─ Support/
│  │     └─ ResourceGate.php       unlocked() gate logic
│  │
│  └─ Shop/ (Headless Storefront)
│     ├─ Contracts/ProductCatalog.php Interface (abstraction seam)
│     ├─ Catalog/
│     │  ├─ FakeCatalog.php         Local fixture data (default)
│     │  ├─ ShopifyCatalog.php      Storefront API (when token set)
│     │  └─ products.php            Fixture product data
│     ├─ Data/
│     │  ├─ ProductData.php         DTO for product
│     │  ├─ VariantData.php         DTO for variant
│     │  └─ Money.php               Price/currency DTO
│     └─ Services/
│        └─ StorefrontClient.php    Shopify Storefront GraphQL client
│
├─ Global Models (app/Models/)
│  └─ User.php                  Breeze user + verified_email check
│
├─ Livewire Components (app/Livewire/)
│  └─ [See frontend.md — act as mini-controllers for interactive pages]
│
├─ Utilities (app/Support/)
│  └─ SafeMarkdown.php          (#31) CommonMark + tgalopin/html-sanitizer
│
├─ Routes (routes/)
│  ├─ web.php                   Public routes (Blade + Livewire pages)
│  │                             $adventureRoutes closure handles subdomain routing
│  ├─ api.php                   API routes (/api/game/progress, /api/xp/*)
│  └─ channels.php              (Broadcasting — not used yet)
│
└─ Database (database/migrations)
   ├─ users, email_verifications
   ├─ profiles, certifications
   ├─ posts, post_tags, tags
   ├─ resources, resource_unlocks, resource_tags
   ├─ preferences
   ├─ cards, user_card_pulls
   ├─ game_progress
   └─ xp_events
```

## Key Modules

| Module | Purpose | Key Files | Dependencies |
|--------|---------|-----------|--------------|
| **Shop** | Headless Shopify integration | ProductCatalog interface, FakeCatalog, ShopifyCatalog | Redis (cache), Shopify Storefront API |
| **Resources** | Email-gated downloads | ResourceGate, ConfirmUnlockController, RateLimiter | Signed links, throttle middleware, queue jobs |
| **Game** | Adventure + XP system | CardService, XpService, AdventureController, Story | Game models, localStorage + API sync |
| **Content** | Blog posts + tags | Post, Tag models | Livewire, safe_markdown helper |
| **Profile** | About + certifications | Certification (with URL validation #31) | — |
| **Preferences** | Theme + accessibility | Preference model | CSS token engine |

## Data Flow

### Product Catalog Fetch
```
ProductCatalog (interface)
├─ Bound to FakeCatalog (default) or ShopifyCatalog (if token set)
├─ Shopify case:
│  └─ StorefrontClient (GraphQL)
│     → Shopify Storefront API
│     → cache result in Redis (5-15 min TTL)
│     → on 404/timeout: return last cached + "offline" notice
└─ Fake case:
   └─ Return fixture data from products.php
```

### Email-Gate Unlock Flow (Hardened #33)
```
1. User requests email-gated resource
   → ResourceGate::unlocked() → false
   → show opt-in form

2. Submit email
   → ResourcePage form submit
   → ConfirmUnlockController::store()
   → RateLimiter::hit('resource-unlock:' . $ip . ':' . $email)
      → if throttled: return friendly notice, send NO mail
      → else: create ResourceUnlock record
   → queue mail job

3. Send email
   → ConfirmResourceUnlock mail
   → signed link: URL::temporarySignedRoute('resources.confirm', ['hours' => config('neuroresource.unlock_link_ttl_hours')])
   → link format: /resources/{slug}/confirm?expires=...&signature=...

4. User clicks link
   → ConfirmUnlockController::confirm()
   → verify: middleware(['signed', 'throttle:resource-confirm'])
   → verify: HMAC check (signature)
   → verify: expiry check (expires param)
   → verify: de-dup (check first confirmation only, session[resource_unlocks])
   → store: session['resource_unlocks'][] = $slug
   → redirect to download

5. Download
   → DownloadController::download()
   → ResourceGate::unlocked() → true (session check)
   → return signed URL from object storage (expiring URL)
   → RecordResourceDownload job (async count increment)
```

### XP Event Log (Append-Only)
```
User action (login, card completion, etc.)
  → AwardDailyLoginXp listener (fires on Login event)
    → XpService::awardXp($source, $amount)
    → XpEvent::create(['source' => $source, 'amount' => $amount])  // append-only
    → calculate totals on read (XpService::getTodayTotal(), getWeekTotal(), etc.)
    → never update or delete rows; schema never changes
```

### Adventure Save (Cross-Device)
```
Player in JavaScript engine (adventure.js)
  → saves to localStorage (always)
  → if logged in: POST /api/game/progress
    → GameProgressController::store()
    → update or create GameProgress record
    → next login/visit: load from API, restore to localStorage
```

## Security Features

### Headers (#30)
**SecurityHeaders middleware** (global on web group):
- X-Content-Type-Options: nosniff → prevents MIME-sniffing attacks
- Referrer-Policy: strict-origin-when-cross-origin → prevents referer leakage (important for email-gate tokens in query strings)
- Permissions-Policy: camera=(), microphone=(), geolocation=() → opt out of browser APIs not used

### Email-Gate Hardening (#33)
- **Rate limiting** per IP + email on unlock endpoint (config-driven)
- **Signed + expiring links** (URL::temporarySignedRoute)
- **Mass-assignment protection**: access, download_count removed from Resource::$fillable
- **Session de-duplication**: first unlock only

### URL Validation (#31)
- **Certification::credential_url** mutator: rejects non-http(s) URLs, stores null
- **Blade guard** in about.blade.php: str_starts_with check before href attribute

### HTML Sanitization (#31)
- **SafeMarkdown helper**: CommonMark (html_input: strip) + tgalopin/html-sanitizer
- Used in blog/show and profile/about to prevent XSS on Markdown-rendered content

### Type Safety (#32)
- **declare(strict_types=1)** added across app/ (except Game/{CardService,XpService}, Resources tree in separate PR)

## Configuration

Global config: `config/neuroresource.php`
- Supported locales
- Accessibility themes
- Resource access tiers
- Domain config (primary + PLAY_DOMAIN)
- Email-gate max attempts + decay time (e.g., 3 attempts / 1 hour)
- Unlock link TTL (hours)

Laravel config: `config/app.php`, `config/database.php`, `config/queue.php`, etc.

## Testing

- **Test directory**: `tests/`
  - Feature/ — HTTP requests, email sending, API endpoints
  - Unit/ — service logic, models, helpers

- **Test setup** (phpunit.xml):
  - SQLite in-memory database
  - Mail: fake() in tests, log mailer in dev
  - Time: Carbon::setTestNow() for time-sensitive tests

- **Coverage**: 80%+ on new code
  - Email-gate throttle, signed links, de-dup all tested
  - XP service calculations (period totals, bounds) tested
  - Card pull concurrency (atomic) tested

```bash
php artisan test                      # all tests
php artisan test --filter=TestClass   # single test class
./vendor/bin/pint --test              # code style (CI)
```

## Related Areas

- [frontend.md](frontend.md) — Livewire components, Blade templates, JavaScript engine
- [database.md](database.md) — Complete schema & model relationships
- [security.md](security.md) — Deep dive into all security features
