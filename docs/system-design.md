# NeuroResource — System Design

Status: Draft v1 · Owner: mikikesel11 · Date: 2026-06-26

A site for **NeuroDivergent** people: a **Shop**, a **Blog**, a **Resource
Library** for distribution, an **About** section for the featured person, and a
fun click-through **Adventure** game on its own subdomain. Accessibility is a
first-class requirement, not a polish pass.

Prose style for all user-facing copy is defined in [writing-style.md](writing-style.md).

---

## 1. Requirements

### Functional
- **Shop** — products, collections, cart rendered natively on `neuroresource.org`,
  backed by **Shopify** via the Storefront API. Checkout hands off to Shopify.
- **Blog** — authored posts, categories/tags, RSS, accessible reading view.
- **Accounts** — first-party user accounts in v1. Needed for game cross-device
  save and for access to account/email-gated Resources.
- **Resource Library** — downloadable/distributable resources (PDFs, printables,
  links). Access is **`free`** or **`email`** (email-gated, self-hosted). Tagged
  and searchable. **Paid products live entirely in Shopify** — no payment gating
  on our servers.
- **About** — biography, list of Certifications, and links to each cert's
  verification page/credential.
- **Adventure game** — branching click-through story on `play.neuroresource.org`.
  Authored in a **visual editor** (Twine) for async collaboration with the
  content author. Save/resume progress. Fully keyboard- and screen-reader-navigable.
- **Localization** — folder structure and locale routing set up in v1; ships
  English first, translations added later without re-architecting.
- **Accessibility prefs** — theme (light / dark / high-contrast / low-stimulation),
  text size, line spacing, dyslexia-friendly font, reduced motion. Persisted.

### Non-functional
- **Accessibility:** WCAG 2.2 **AA** baseline; target **AAA** for contrast and
  text where feasible. NeuroDivergent-specific affordances (below).
- **Performance:** fast first paint, low motion by default, lightweight pages.
  Budget ~≤150KB JS on content pages.
- **Privacy:** minimal/consented analytics, no dark patterns, no behavioral ad
  tracking. Sensitive audience.
- **Availability:** start modest; design so no rewrite is needed to scale.
- **PCI:** **out of scope on our servers** — payment/checkout lives in Shopify.

### Constraints
- Stack: **Laravel (PHP)**. Repo already initialized.
- Hosting: **start on a small managed host, evolve to scalable cloud.** The
  design is a **modular monolith** so modules can be extracted later without a
  rewrite.

---

## 2. High-Level Architecture

```
                         neuroresource.org (Laravel modular monolith)
   ┌──────────────────────────────────────────────────────────────────┐
   │  Web (Blade + light JS islands)                                    │
   │  ┌────────┐ ┌────────┐ ┌──────────────┐ ┌────────┐ ┌────────────┐  │
   │  │  Home  │ │  Blog  │ │ Resource Lib │ │ About  │ │   Shop     │  │
   │  └────────┘ └────────┘ └──────────────┘ └────────┘ └─────┬──────┘  │
   │  ┌───────────────────────────────────────────────────────┼──────┐  │
   │  │ App services: Content · Resources · Profile · Shop ◄───┘      │  │
   │  └───────┬───────────────┬───────────────┬──────────────┬───────┘  │
   └──────────┼───────────────┼───────────────┼──────────────┼──────────┘
              │               │               │              │
         ┌────▼────┐    ┌─────▼─────┐    ┌─────▼─────┐   ┌────▼─────────────┐
         │ DB      │    │  Redis    │    │  Object   │   │ Shopify          │
         │ (MySQL/ │    │ cache +   │    │ Storage + │   │ Storefront API   │
         │  PG)    │    │ queue     │    │ CDN       │   │ + Webhooks       │
         └─────────┘    └───────────┘    └───────────┘   │ (checkout/pay)   │
                                                          └──────────────────┘

         play.neuroresource.org  (Adventure — static SPA on CDN)
         ┌───────────────────────────────────────────────┐
         │ Story engine (JSON/Ink scene graph) + a11y UI  │
         │ progress in localStorage (+ optional API sync) │
         └───────────────────────────────────────────────┘
```

**Why a modular monolith:** one Laravel app with clear internal module
boundaries (`Content`, `Resources`, `Profile`, `Shop`, `Preferences`). Cheap to
run on a single host now; each module talks through service interfaces, so any of
them (most likely `Shop` or the game backend) can later become its own service.

**Why the game is separate:** different runtime shape (interactive SPA, heavy
client logic, independent deploy cadence) and a separate subdomain. Hosting it as
a static build on a CDN makes it effectively free to scale and isolates its
failures from the main site.

---

## 3. Component Deep-Dive

### 3.1 Shop (Headless Shopify)
- **Catalog read path:** server-side calls to the **Storefront API (GraphQL)**
  for products/collections. Results **cached in Redis** (e.g. 5–15 min TTL) and
  invalidated by **Shopify webhooks** (`products/update`, `collections/update`)
  hitting a Laravel webhook endpoint (HMAC-verified).
- **Cart:** create a Shopify cart via Storefront API; store the returned
  `cartId`/checkout URL in the session. Line-item add/remove call the Storefront
  API. **Checkout redirects to Shopify's hosted, PCI-compliant checkout.**
- **We never store payment data.** Order history, fulfillment, taxes = Shopify.
- **Failure mode:** if Storefront API is down, serve last-cached catalog
  (read-only) and disable add-to-cart with a clear, non-alarming message.

```
Browser → Laravel Shop service → (cache hit?) → Redis
                                  (miss) → Shopify Storefront API → cache → render
Add to cart → Laravel → Storefront API (cart mutation) → store cartId
Checkout   → redirect → checkout.shopify.com (hosted)
Shopify webhook → Laravel /webhooks/shopify (verify HMAC) → bust cache / queue sync
```

### 3.2 Blog & Resource Library (Content)
- Posts and Resources are **first-party data** in our DB (full control over
  markup = full control over accessibility). Authored in Markdown/rich text via a
  simple admin (Filament/Nova or custom Laravel admin).
- Resources have: title, summary, type (PDF/printable/link), file in object
  storage, tags, and an `access` enum: **`free`** or **`email`**.
- **Gating logic:** `free` → open download. `email` → a logged-in user (we
  already have their email) gets it instantly; an anonymous visitor submits an
  email to unlock the download and is added to the list (double opt-in). This is
  **lead capture, not payment** — anything that costs money is a Shopify product.
- **Downloads** served via signed, expiring URLs from object storage + CDN. Track
  counts asynchronously (queue) to keep the request fast.
- **Search/filter:** start with DB queries + tag facets; add **Meilisearch**
  later if needed (it's accessible-friendly and cheap to bolt on).

### 3.3 About / Profile
- Single featured-person profile: `bio` (rich text), ordered list of
  `certifications` (name, issuer, issued/expiry dates, credential URL, optional
  badge image).
- Render certs as a semantic list with descriptive links ("View the {Issuer}
  {Credential} verification") — not bare "click here".

### 3.4 Adventure Game
- **Story model:** a directed graph of Scenes. Each Scene has narrative text and
  a set of Choices → next Scene.
- **Authoring (visual, async):** content is written in **Twine** — a free,
  web/desktop **visual node-graph editor** that non-developers find approachable.
  Stories export to **Twee/JSON**, which we convert (e.g. `extwee`) and render in
  **our own accessible runtime** (Twine's built-in player is *not* fully
  accessible, so we don't ship it). The exported story file is committed to the
  repo / shared store so you and the content author can work async and review
  changes as diffs.
- **Runtime:** small Vite-built SPA (vanilla or Preact/React) deployed static to
  CDN. No server needed to play.
- **Progress:** saved in `localStorage`; optional `POST /api/game/progress` to the
  Laravel API if the user has an account and wants cross-device save.
- **Accessibility (critical for a game):** every Choice is a real `<button>`/link,
  full keyboard nav, focus moves to the new Scene heading on transition, screen
  reader announces scene changes via a polite live region, **reduced-motion path**
  with no required animation, and no time pressure.

### 3.5 Preferences / Accessibility Engine
- Design-token theming (CSS custom properties) with switchable themes:
  **light, dark, high-contrast, low-stimulation** (muted palette, no large color
  blocks, minimal imagery).
- User controls: text size, line height, letter spacing, font (incl. a
  dyslexia-friendly option), motion on/off.
- Persisted in a cookie (anon) and to the account if logged in. Respects
  `prefers-reduced-motion` and `prefers-color-scheme` on first visit.

---

## 4. Data Model (first-party)

```
profile        (id, name, headline, bio, avatar_path, updated_at)
certifications (id, profile_id, name, issuer, issued_on, expires_on,
                credential_url, badge_path, sort_order)
posts          (id, slug, title, excerpt, body_md, status, published_at,
                author_id, reading_minutes)
post_tags      (post_id, tag_id)         tags (id, name, slug)
resources      (id, slug, title, summary, type, file_path, external_url,
                access['free'|'email'], download_count, published_at)
resource_tags  (resource_id, tag_id)
users          (id, name, email, email_verified_at, ...)   // v1 accounts
resource_unlocks (id, resource_id, user_id|null, email, created_at)  // email-gate captures
preferences    (id, user_id|null, cookie_id|null, theme, text_scale,
                line_height, font, reduce_motion, locale)
game_progress  (id, user_id, story_id, scene_id, state_json, updated_at)

// i18n: translatable text columns live in a translations table (or
// spatie/laravel-translatable JSON columns), so adding a locale never alters
// the core schema. Start with English rows only.
translations   (id, translatable_type, translatable_id, locale, field, value)

// Shop: NOT a source of truth. Optional thin cache table only:
shop_products_cache (handle, payload_json, fetched_at)   // or Redis-only
```

Shopify owns product/price/inventory/order truth; we cache for performance and
hold only references (handles/IDs).

---

## 5. API Surface (selected)

First-party (Laravel):
```
GET  /                         home
GET  /blog, /blog/{slug}       posts (+ /feed RSS)
GET  /resources                filterable library
GET  /resources/{slug}/download → signed URL (auth/gating checked)
GET  /about                    profile + certifications
GET  /shop, /shop/{handle}     catalog (server-rendered from cache/Storefront)
POST /cart/items, DELETE /cart/items/{id}   cart mutations → Storefront API
GET  /cart  → checkout redirect to Shopify
POST /webhooks/shopify         HMAC-verified, queues cache invalidation
POST /api/preferences          persist a11y prefs
POST /api/game/progress        optional cross-device save
```

External (Shopify): Storefront API (GraphQL) for catalog + cart; Admin webhooks
for sync. No Admin API write access needed for the storefront use case.

---

## 6. Scale & Reliability Path

**Phase 1 — Start small (now):**
- Single managed host (e.g. Laravel Forge on a DigitalOcean droplet), managed
  MySQL/Postgres, Redis on the same or managed, object storage (S3/Spaces) +
  CDN (Cloudflare) in front of everything.
- Queue via **Laravel Horizon** (Redis) for downloads counting, webhook
  processing, email.
- Game + main site both behind the CDN.

**Phase 2 — Scale out (later, no rewrite):**
- Containerize (Docker). Run web behind a load balancer (multiple stateless app
  instances — sessions/cache already in Redis, files already in object storage,
  so the app is horizontally scalable from day one if we keep it stateless).
- Move to managed cloud DB (RDS/Cloud SQL) with read replica, managed Redis
  (ElastiCache/Memorystore), CDN already in place.
- Extract a module to its own service only if a real bottleneck appears (most
  likely the Shop catalog sync or the game progress API).

**Reliability:**
- Stateless app tier; Redis + DB are the stateful pieces (managed, backed up).
- Shopify is an external dependency → cache aggressively, degrade gracefully,
  verify webhooks, and make catalog read-only-survivable.
- Monitoring: uptime checks, error tracking (Sentry), Horizon queue dashboard,
  Core Web Vitals + accessibility regression checks in CI.

---

## 7. Accessibility (cross-cutting, non-negotiable)

- **Standard:** WCAG 2.2 AA min; AAA contrast/text where feasible.
- **Semantics:** real HTML elements, landmark regions, skip links, logical
  heading order, visible focus, managed focus on route/scene changes.
- **NeuroDivergent-specific:** low-stimulation theme, reduced motion by default
  where `prefers-reduced-motion` is set, no autoplay, no flashing, predictable
  navigation, plain-language summaries, generous spacing, user-controllable text.
- **Media:** captions + transcripts for any audio/video.
- **Testing in CI:** axe-core automated checks on key pages, plus a manual
  keyboard + screen-reader pass per release. Accessibility bugs are release
  blockers.
- **Content:** the [Capitalize Key Terms](writing-style.md) style + plain
  language are part of the accessibility surface.

---

## 7a. Localization (set up now, translate later)

- **Routing:** locale URL prefix (`/en/...`, `/es/...`) with a default-locale
  redirect; a `SetLocale` middleware resolves locale from URL → user pref →
  `Accept-Language` → default (`en`).
- **UI strings:** Laravel `lang/{locale}/*.php` files. Create `lang/en/` now; new
  locales are new folders, no code changes.
- **Content (posts/resources/profile):** translatable via a `translations` table
  (or `spatie/laravel-translatable`). English rows ship first; a locale falls
  back to English when a translation is missing.
- **Game:** Twine stories are authored per locale (one story file per language);
  the runtime loads the file matching the active locale, falling back to English.
- **v1 scope:** wire the structure and middleware, ship English only. No
  translation work required until content is ready.

## 8. Subdomains & Environments

- `neuroresource.org` — main Laravel app.
- `play.neuroresource.org` — static Adventure SPA (separate deploy, CDN).
- `admin.neuroresource.org` (or `/admin`) — authoring (Filament/Nova), restricted.
- Environments: local → staging → production. Shopify has dev/prod stores; keep
  Storefront tokens in env/secrets, never in the repo.

---

## 9. Resolved Decisions (v1)

1. **Accounts:** ✅ In v1 (Laravel Fortify/Breeze). Drive game cross-device save
   and account/email-gated Resources.
2. **Resource gating:** ✅ `free` + `email` (self-hosted lead capture). All paid
   goods live in Shopify — no payment gating on our side.
3. **Game authoring:** ✅ Twine visual editor → exported story rendered in our
   accessible runtime; story file in the repo for async collaboration.
4. **Analytics:** ✅ **Plausible** — cookieless, privacy-first, **no cookie
   banner required**. Start managed (~$9/mo), self-host later to fit the scale-up
   path. See §11.
5. **Localization:** ✅ Structure + locale routing in v1, English-only to start;
   see §7a.

## 11. Analytics & Consent (decision pending)

**Goal:** know what content helps people, without surveilling a sensitive
audience or bolting on a creepy cookie banner.

**Consent models, plainly:**
- *No-consent-needed (cookieless privacy analytics):* the tool stores **no
  cookies and no personal data**, so under GDPR/ePrivacy you generally **don't
  need a consent banner**. Best fit for this audience.
- *Opt-in (required for GA-style tracking):* tools that set cookies / profile
  users need an explicit "Accept" before they run — a banner, and a measurable
  share of users decline (skewing data).
- *Opt-out:* user can leave after the fact — weakest privacy stance; avoid.

**Decision:** ✅ **Plausible.** Cookieless and aggregate-only, so **no consent
banner**. Begin on the managed plan (~$9/mo) for zero ops; **self-host later**
(it's open-source) to align with the Phase 2 scale-up. A single lightweight
script tag, no PII, no cross-site tracking — fully on-brand for this audience.

---

## 10. Key Trade-offs Made

| Decision | Chosen | Trade-off |
|---|---|---|
| App shape | Modular monolith (Laravel) | Simpler/cheaper now; relies on discipline to keep module boundaries clean for later extraction. |
| Shop | Headless Storefront API | Full control over accessibility & markup; more integration code than Buy Buttons, and we depend on Shopify uptime (mitigated by caching). |
| Payments | Shopify hosted checkout | Zero PCI scope; less checkout-UI control. |
| Content | First-party DB (not WP) | Full markup/a11y control; we build the (small) admin. |
| Game | Separate static SPA | Cheap scaling + isolation; second codebase/deploy to maintain. |
| Infra | Stateless app from day one | A little more setup now; horizontal scaling needs no rewrite later. |
