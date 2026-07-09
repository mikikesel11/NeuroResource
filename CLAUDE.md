# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Git workflow

**Never commit directly to `main`.** Always:

1. Create a feature branch first (`git checkout -b feat/...`)
2. Commit there
3. Ask about creating a PR
4. Ask about merging the PR
5. Ask what to do with the branch after merge

## Commands

```bash
# Dev server (runs Laravel + Vite concurrently with queue and log tail)
composer dev

# Tests
php artisan test                        # full suite
php artisan test --filter=ClassName     # single test class
php artisan test --filter=test_name     # single test method

# Code style (Laravel Pint)
./vendor/bin/pint                       # fix
./vendor/bin/pint --test                # check only (used in CI)

# Asset build
npm run dev        # hot reload (Vite on :5173)
npm run build      # production bundle

# Database
php artisan migrate
php artisan migrate --seed              # seeds sample content for local exploration
```

Tests run against SQLite in-memory (configured in `phpunit.xml`). Use `Carbon::setTestNow()` for time-sensitive tests and reset it in `tearDown`.

## Architecture

**Modular monolith.** All domain logic lives under `app/Domains/<Module>/` with clear internal boundaries. Modules: `Content` (blog), `Game` (adventure + XP), `Preferences` (accessibility), `Profile` (about/bio), `Resources` (library/downloads), `Shop` (headless Shopify).

**Recent Security Hardening (v1.6, Jul 6):**
- HTTP headers: X-Content-Type-Options, Referrer-Policy (protects email-gate tokens), Permissions-Policy
- Email-gate: rate limiting per IP+email, signed+expiring links, mass-assignment protection, session de-dup
- HTML sanitization: `safe_markdown()` helper (CommonMark + symfony/html-sanitizer)
- URL validation: credential_url rejects non-http(s) schemes
- Type safety: `declare(strict_types=1)` across codebase
- Removed dead auth scaffolding (GuestLayout, layouts/guest.blade.php, auth-session-status component, @tailwindcss/vite)

**Livewire + Volt** for full-page interactive components (shop catalog, blog, resource library). Blade for everything else. The Adventure game is a standalone vanilla-JS engine (`resources/js/adventure.js`) with no framework dependency — it reads a JSON data island and renders into `#adventure`.

**Key patterns:**

- **Shop seam** — `ProductCatalog` is a bound interface. `FakeCatalog` (local fixture data in `app/Domains/Shop/Catalog/products.php`) is the default; `ShopifyCatalog` activates when `SHOPIFY_STOREFRONT_TOKEN` is set. No code change required to switch.
- **Resource gate** — `ResourceGate` in `app/Domains/Resources/Support/` controls free vs. email-gated downloads. Paid goods live in Shopify, never here.
- **XP event log** — `xp_events` is an append-only table (`source`, `amount`, `awarded_at`). New XP sources add rows; the schema never changes. `XpService` owns all metric calculations. Login bonus is wired via `Illuminate\Auth\Events\Login` → `AwardDailyLoginXp` listener in `AppServiceProvider::boot()`.
- **Adventure routing** — when `PLAY_DOMAIN` env var is set, the game is served on a subdomain via `Route::domain()`; otherwise it runs at `/play` on the primary host. The `$adventureRoutes` closure in `routes/web.php` handles both cases — always add game routes inside it.

## Config

App-specific config lives in `config/neuroresource.php`: supported locales, accessibility themes (`light`, `dark`, `high-contrast`, `low-stimulation`), resource access tiers, and domain config. Access via `config('neuroresource.*')`.

## Accessibility requirements

Accessibility is a first-class requirement for this NeuroDivergent audience, not a polish pass. When touching any UI:

- All interactive elements must be keyboard-operable (`<button>`, not `<div onclick>`).
- Focus must be managed explicitly on dynamic content changes (see `heading.focus()` in `adventure.js`).
- Screen reader announcements via `aria-live="polite"` for dynamic updates; never `assertive` unless urgent.
- No *imposed* timers, autoplay, or time pressure. The one exception is the Card game's opt-in timer: it is player-started, optional, silent, and carries no penalty — a gentle self-care reminder, never a countdown to failure. Any future timer must meet that same bar.
- Theme tokens come from CSS custom properties in `resources/css/accessibility.css` — use `var(--ns-*)` rather than hardcoded colours.
- The four themes (`low-stimulation` is the calm default) must all remain coherent after any CSS change.

## Content style

All user-facing prose follows **Capitalize Key Terms** — product names, feature names, and section titles are capitalized (e.g. "The Adventure", "Resource Library", "Low Stimulation"). See `docs/writing-style.md` for the full voice guide.

## Story / game content

The Adventure story lives in `resources/adventure/story.json`. Run `php artisan tinker` → `(new App\Domains\Game\Support\Story)->validate()` to catch broken scene links before committing. See `docs/adventure-authoring.md` for the scene schema.

Card game cards (`cards` table, seeded by `CardSeeder`) may set an optional `timer_minutes`. When present, the drawn card offers a player-started "Start a N-minute timer" button; leave it `null` for cards with no time element. XP (`xp_earned`) is granted on **completion** (marking the card done or checking its last subtask), not on draw.

## Architecture Codemaps

For detailed implementation guides, module structure, data flows, and security details, see:
- `docs/CODEMAPS/INDEX.md` — overview of all areas and recent changes
- `docs/CODEMAPS/frontend.md` — Livewire, Blade, themes, accessibility
- `docs/CODEMAPS/backend.md` — controllers, services, models, routes
- `docs/CODEMAPS/database.md` — complete schema and relationships
- `docs/CODEMAPS/security.md` — headers, email-gate, validation, sanitization
