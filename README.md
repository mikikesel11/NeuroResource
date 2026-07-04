# NeuroResource

A website built **by and for NeuroDivergent people** — a Shop, a Blog, a
Resource Library, an About section for the featured person, and a gentle
click-through Adventure game. Accessibility and a calm, low-stimulation
experience are first-class requirements, not a polish pass.

---

## Features

- **Accessible by design** — four user themes (Light, Dark, High Contrast,
  **Low Stimulation**), adjustable text size and spacing, reduced-motion support
  (honors OS preference), skip links, focus management, and keyboard-complete
  UI. Preferences persist with no flash of the wrong theme.
- **Shop** — a headless **Shopify** storefront. Products and cart render natively
  on-site; checkout hands off to Shopify (no PCI scope here). Builds and runs
  **without a Shopify account** via a fixture catalog, then flips to live with a
  token — no code change.
- **Resource Library** — free and **email-gated** downloads with a proper
  **double opt-in** flow; downloads are counted asynchronously. Paid goods live
  in Shopify, never gated here.
- **Blog** — posts with tag filtering, an accessible reading view, and an **RSS
  feed**.
- **About** — biography and a list of certifications with verifiable credential
  links.
- **The Adventure** — a branching, no-pressure story driven by a simple JSON
  scene graph, with an accessible engine (focus management, live-region
  announcements, Go Back / Start Over, no timers). Progress saves locally and,
  for signed-in players, **syncs across devices**. Ready to run on its own
  `play.` subdomain.
- **Accounts** — Laravel Breeze auth. Logging in returns you to the page you came
  from (never a dashboard), with a brief, dismissible signed-in banner.
- **Localization-ready** — locale middleware + translatable content (English
  first).
- **Privacy-respecting analytics** — Plausible (cookieless, no consent banner).

All user-facing prose follows a documented **Capitalize Key Terms** style — see
[docs/writing-style.md](docs/writing-style.md).

---

## Tech stack

- **Laravel 13** (PHP **8.4+**)
- **Livewire 3** + **Volt**, Blade, **Tailwind CSS**, **Vite**
- **Laravel Breeze** (authentication)
- **spatie/laravel-translatable** (i18n content), **spatie/laravel-feed** (RSS)
- **Shopify Storefront API** (headless shop), **Plausible** (analytics)
- MySQL/PostgreSQL in production; SQLite for local/CI

---

## Architecture

A **modular monolith**. Domain logic lives under `app/Domains/<Module>`, keeping
clear internal boundaries so a module can be extracted later without a rewrite.

```
app/
  Domains/
    Content/      Blog posts + tags
    Game/         The Adventure (engine controller, story loader, progress API)
    Preferences/  Accessibility preferences
    Profile/      About page (bio + certifications)
    Resources/    Resource Library (downloads, email-gate, jobs)
    Shop/         Headless Shopify (ProductCatalog seam, Storefront client)
  Livewire/       Full-page Livewire components (Shop, Blog, Resources)
resources/
  js/adventure.js         Framework-agnostic story engine
  adventure/story.json    Adventure content (author here / export from Twine)
  css/accessibility.css   Theme tokens + accessible component styles
  views/                  Blade (public layout, pages, components, emails)
docs/                     Design, writing style, authoring guide
DEPLOYMENT.md             Operational / deployment guide
```

See [docs/system-design.md](docs/system-design.md) for the full design and
trade-offs.

---

## Getting started

Requires PHP 8.4+, Composer, and Node 22+.

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database + sample content (SQLite by default)
php artisan migrate --seed

# 4. Build assets and serve
npm run build          # or `npm run dev` for hot reload
php artisan serve
```

Visit `http://localhost:8000`. A seeded test account is available:
`test@example.com` / `password`.

> The seeders create **sample/placeholder content** (profile, resources, blog
> posts, products) so the site is explorable out of the box. Replace it with real
> content before launch — and do not run the seeders in production.

---

## Key concepts

- **Shop without Shopify** — `ProductCatalog` has two implementations behind a
  config-driven binding: `FakeCatalog` (local fixtures, the default) and
  `ShopifyCatalog` (live Storefront API, used when `SHOPIFY_STOREFRONT_TOKEN` is
  set). A free Shopify **development store** provides real test data + test-mode
  checkout when you're ready.
- **Email needs nothing locally** — the resource opt-in uses the `log` mailer in
  dev (the confirmation link appears in `storage/logs/laravel.log`) and
  `Mail::fake()` in tests; production plugs in Postmark/Resend/SES.
- **The Adventure is content-first** — add or edit scenes in
  `resources/adventure/story.json`; a validator (run in CI) catches broken links.
  See [docs/adventure-authoring.md](docs/adventure-authoring.md).
- **Game subdomain** — set `PLAY_DOMAIN` to serve the game on `play.<domain>`;
  leave it blank to run everything on one host at `/play`. See
  [DEPLOYMENT.md](DEPLOYMENT.md).

---

## Testing & CI

```bash
php artisan test          # 77 tests
./vendor/bin/pint --test  # code style
npm run build             # asset build
```

GitHub Actions runs the test suite (PHP 8.4 + 8.5), Pint, and the asset build on
every pull request and on pushes to `main`.

---

## Documentation

- [docs/system-design.md](docs/system-design.md) — architecture, data model,
  trade-offs
- [docs/writing-style.md](docs/writing-style.md) — the Capitalize Key Terms voice
- [docs/adventure-authoring.md](docs/adventure-authoring.md) — authoring the game
- [DEPLOYMENT.md](DEPLOYMENT.md) — deployment & operations

---

## Status & roadmap

Built and working: accessible public site, Shop catalogue, Resource Library
(double opt-in), Blog + RSS, About, the Adventure (with cross-device save and
subdomain support), accounts, and CI.

Planned next:

- Cart + Shopify hosted checkout
- Mailing-list provider sync for confirmed opt-ins
- Cross-subdomain login return-to-origin; optional fully-static `play.` build

---

This is a private project. Content is © its authors; built for the
NeuroDivergent community.
