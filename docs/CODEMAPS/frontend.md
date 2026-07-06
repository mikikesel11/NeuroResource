# Frontend Codemap

**Last Updated:** 2026-07-06

## Architecture

```
Web Frontend (Livewire 3 + Volt + Blade + Tailwind CSS)
│
├─ Livewire Full-Page Components (app/Livewire/)
│  ├─ Shop::Catalog          Searchable product grid, add-to-cart
│  ├─ Shop::ProductPage      Single product detail, variant selection
│  ├─ Blog::Index            Post list with tag filtering, search
│  ├─ Blog::Show             Single post with responsive reading view
│  ├─ Resources::Library     Filterable downloads (free + email-gated)
│  ├─ Resources::ResourcePage Single resource + access control flow
│  ├─ Game::CardDraw         Card reveal UI with shuffle animation
│  └─ Forms::LoginForm       Auth form (Volt)
│
├─ Blade Templates (resources/views/)
│  ├─ layouts/app.blade.php   Public layout (header, nav, footer)
│  ├─ pages/*.blade.php       Home, About, etc.
│  ├─ components/
│  │  ├─ public-layout.php    Replaced dead GuestLayout (#34)
│  │  ├─ theme-switcher.php
│  │  ├─ skip-link.php
│  │  └─ ... other components
│  └─ emails/*.blade.php      Transactional emails
│
├─ CSS & Themes (resources/css/)
│  ├─ accessibility.css       Design tokens (var(--ns-*))
│  ├─ app.css                 Tailwind + custom styles
│  └─ themes/                 Low-stimulation, high-contrast, etc.
│
├─ JavaScript (resources/js/)
│  ├─ adventure.js            Vanilla-JS story engine (NO framework)
│  ├─ components/             Reusable JS modules
│  └─ ... other JS
│
└─ Assets (resources/)
   └─ adventure/story.json    Scene graph (JSON data island)
```

## Key Modules

| Module | Purpose | Exports | Dependencies |
|--------|---------|---------|--------------|
| **Livewire/Shop/Catalog** | Product grid, search, add-to-cart | Component | ProductCatalog, StorefrontClient, Redis cache |
| **Livewire/Shop/ProductPage** | Single product view, variant select | Component | ProductCatalog |
| **Livewire/Blog/Index** | Post list + tag filter + search | Component | Post model, pagination |
| **Livewire/Blog/Show** | Single post + safe_markdown() helper | Component | Post, safe_markdown (#31) |
| **Livewire/Resources/Library** | Filter + search downloadables | Component | Resource, ResourceGate |
| **Livewire/Resources/ResourcePage** | Single resource + email-gate unlock | Component | Resource, ResourceGate, RateLimiter |
| **Livewire/Game/CardDraw** | Card reveal + shuffle animation | Component | Card, XpService, UserCardPull |
| **Livewire/Forms/LoginForm** | Volt login form | Form | User, Laravel Breeze |

## Data Flow

### Shop Product Display
```
User requests /shop
  → Livewire Shop::Catalog
    → ProductCatalog interface
      → (FakeCatalog OR ShopifyCatalog depending on env)
        → Redis cache (5-15 min TTL)
        → Shopify Storefront API if cache miss
    → render grid
```

### Resource Download (Email-Gate)
```
User requests resource (email-gated)
  → Livewire Resources::ResourcePage
    → ResourceGate::unlocked() check
      → if free: allow download
      → if email-gated + auth + verified: allow
      → else: show opt-in form
    → submit email
      → ConfirmResourceUnlock controller
        → RateLimiter::hit() per IP + email (throttle #33)
        → send confirmation link (signed + expiring #33)
        → send ConfirmResourceUnlock mail
    → user clicks signed link
      → ConfirmUnlockController
        → verify HMAC + expiry
        → de-dupe session (first unlock only #33)
        → store slug in session[resource_unlocks]
        → redirect to download
```

### Adventure Game Display
```
Browser requests /play
  → AdventureController
    → return story.json data island + vanilla-JS engine
    → adventure.js reads JSON, renders scenes
    → player clicks choices (real <button> elements)
    → focus moves to new scene heading (a11y)
    → aria-live region announces scene text
    → if logged in: POST /api/game/progress for sync
```

## Accessibility Features

- **Themes via CSS tokens** (`var(--ns-*)`): light, dark, high-contrast, low-stimulation
- **Preference persistence**: localStorage (anon) + user account (logged-in)
- **Reduced motion** by default when OS `prefers-reduced-motion` set
- **Keyboard navigation**: all interactive elements are real `<button>`, `<a>`, or form controls
- **Focus management**: explicit focus() calls on route/scene transitions
- **Screen reader announcements**: `aria-live="polite"` for dynamic updates (never `assertive`)
- **Skip links**: "Skip to main content" in header (public-layout)
- **Text scaling**: adjustable font size + line height controls
- **Semantic HTML**: proper heading hierarchy, landmark regions

## Theme Engine

Four themes in `resources/css/accessibility.css`:
- **light** — default, ample contrast
- **dark** — reduced eye strain
- **high-contrast** — WCAG AAA contrast (3:1+)
- **low-stimulation** — muted palette, no large color blocks, minimal imagery (calm default)

User preference → `<html data-theme="low-stimulation">` → CSS picks `var(--ns-bg-*)` tokens.

## Recent Changes

### #34 — Dead Scaffolding Removal
**Removed:**
- `app/View/Components/GuestLayout.php` — unused, replaced by public-layout
- `resources/views/layouts/guest.blade.php` — unused, replaced by public-layout
- `resources/views/components/auth-session-status.blade.php` — unused
- `@tailwindcss/vite` from package.json — conflicted with Tailwind v3 + PostCSS, never imported

All Livewire & Blade components verified to use public-layout only.

### #31 — HTML Sanitization in Markdown
**Added:**
- `app/Support/SafeMarkdown.php` helper (two-pass: CommonMark + tgalopin/html-sanitizer)
- Replaces bare `Str::markdown()` calls in:
  - `livewire/blog/show.blade.php`
  - `livewire/profile/about.blade.php`

### #30 — Security Headers
**Added:**
- `app/Http/Middleware/SecurityHeaders` (registered globally on web group)
- X-Content-Type-Options: nosniff (MIME-sniffing defense)
- Referrer-Policy: strict-origin-when-cross-origin (protects email-gate tokens in query strings)
- Permissions-Policy: camera=(), microphone=(), geolocation=() (APIs not used)

## Build & Dev

```bash
# Hot reload (Vite on :5173)
npm run dev

# Production bundle
npm run build

# Code style check
./vendor/bin/pint --test
```

Vite config: `vite.config.js`  
Tailwind config: `tailwind.config.js`  
PostCSS config: `postcss.config.js`

## External Dependencies

| Package | Version | Purpose |
|---------|---------|---------|
| livewire/livewire | 3.x | Full-page reactive components |
| livewire/volt | 1.x | Single-file PHP components |
| tailwindcss | 3.x | Utility CSS framework |
| @headlessui/tailwindcss | — | Accessible UI primitives |
| tgalopin/html-sanitizer | Latest | XSS defense (two-pass with CommonMark) |

## Related Areas

- [backend.md](backend.md) — Controllers feeding Livewire components
- [database.md](database.md) — Models & schema behind frontend views
- [security.md](security.md) — Middleware, headers, SafeMarkdown, email-gate hardening
