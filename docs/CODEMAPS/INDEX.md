# NeuroScouts Codemaps

**Last Updated:** 2026-07-06

This directory contains architectural maps of the NeuroScouts codebase. Use these to understand module responsibilities, data flows, and key design patterns.

## Overview

NeuroScouts is a **modular monolith** Laravel application serving NeuroDivergent people with a Shop, Blog, Resource Library, About section, and an Adventure game.

```
app/
  Domains/          Module-specific business logic (clear internal boundaries)
    Content/        Blog posts + tags
    Game/           Adventure game, cards, XP system
    Preferences/    Accessibility preferences (themes, text size, etc.)
    Profile/        About page (bio + certifications with credential links)
    Resources/      Resource Library (downloads, email-gate, throttling)
    Shop/           Headless Shopify storefront (ProductCatalog interface)
  Livewire/         Full-page interactive components
  Http/             Global controllers, middleware, authentication
  Providers/        Service providers and bindings
  Support/          Shared utilities (SafeMarkdown, etc.)
  View/             View components (deprecated layouts removed in #34)
```

## Codemaps

| Area | Purpose | Key Files |
|------|---------|-----------|
| [frontend.md](frontend.md) | Livewire + Volt components, Blade templates, theme engine | `app/Livewire/`, `resources/views/` |
| [backend.md](backend.md) | Controllers, services, models, API routes | `app/Domains/`, `app/Http/` |
| [database.md](database.md) | Schema, models, relationships | Migrations, `app/Models/` |
| [security.md](security.md) | Headers, validation, email-gate hardening, strict types | Middleware, controllers, helpers |

## Recent Changes

### v1.6 — Security Hardening & Code Quality (Jul 6, 2026)

**Added:**
- **SecurityHeaders middleware** (#30): X-Content-Type-Options, Referrer-Policy (protects email-gate tokens), Permissions-Policy
- **SafeMarkdown helper** (#31): CommonMark + tgalopin/html-sanitizer (two-pass XSS defense)
- **Credential URL validation** (#31): Attribute mutator rejects non-http(s) URLs, Blade guard ensures safety
- **Email-gate hardening** (#33):
  - Rate limiting per IP + email on unlock endpoint (config-driven, friendly throttle notices)
  - Signed + expiring confirmation links (URL::temporarySignedRoute)
  - Mass-assignment protection: `access` and `download_count` removed from Resource::$fillable
  - Session de-duplication on first unlock

**Refactored:**
- **declare(strict_types=1)** (#32): added across `app/` (except Game/{CardService,XpService}, Resources tree)

**Removed:**
- Dead Laravel auth scaffolding (#34): GuestLayout component, layouts/guest.blade.php, auth-session-status component
- @tailwindcss/vite (unused v4 plugin conflicting with Tailwind v3 + PostCSS)

All 156 tests passing.

## Key Patterns

### 1. Product Catalog (Shop Seam)
```php
ProductCatalog interface (abstract)
├── FakeCatalog     (default: local fixture data)
└── ShopifyCatalog  (live: Storefront API when token set)
```
No code change needed to switch implementations.

### 2. Resource Email-Gate
```
Free → always open
Email-gated → authenticated user OR session unlock
  └── unlock() endpoint: rate-limited, signed link, de-duped session
Paid → Shopify only (never gated here)
```

### 3. XP Event Log
```
xp_events table (append-only: source, amount, awarded_at)
XpService owns all metric calculations
Login bonus wired via Illuminate\Auth\Events\Login → AwardDailyLoginXp
```

### 4. Adventure Routing
```
PLAY_DOMAIN env set → game on play.<domain> subdomain
PLAY_DOMAIN blank → game at /play on primary host
Both routes in $adventureRoutes closure (routes/web.php)
```

### 5. Accessibility Engine
Four themes via CSS custom properties: light, dark, high-contrast, low-stimulation.
Preferences persisted in session + user account (if logged in).
Respects OS `prefers-reduced-motion` and `prefers-color-scheme`.

## Testing

- **Test suite:** 156 tests (php artisan test)
- **Coverage:** 80%+ on new code; append-only XP, signed URLs, rate limiting all covered
- **Code style:** Pint (Laravel ./vendor/bin/pint --test)
- **CI:** GitHub Actions runs tests (PHP 8.4 + 8.5), Pint, asset build on every PR + push to main

## Configuration

App-specific config in `config/neuroresource.php`:
- Supported locales
- Accessibility themes (CSS tokens)
- Resource access tiers
- Domain config (primary + play subdomain)
- Email-gate throttle settings (max attempts, decay time)
- Unlock link TTL (hours)

See `.env.example` for required secrets (Shopify token, Plausible ID, etc.).

## Cross-Reference

- [system-design.md](../system-design.md) — full architecture & trade-offs
- [CLAUDE.md](../../CLAUDE.md) — developer workflow & key concepts
- [DEPLOYMENT.md](../../DEPLOYMENT.md) — operations & hosting
