# Deployment

Operational guide for deploying NeuroResource. Pairs with the architecture in
[docs/system-design.md](docs/system-design.md).

NeuroResource is a **modular-monolith Laravel app** with three external services
(Shopify for the shop, Plausible for analytics, an email provider for the
resource opt-in) and an **Adventure game that can run on its own subdomain**.

---

## 1. Requirements

| Component | Version / notes |
|---|---|
| PHP | **8.4+** (locked deps require ≥ 8.4.1) with `mbstring, pdo, openssl, tokenizer, xml, ctype, bcmath, curl, fileinfo, gd, zip, intl, sqlite3/pdo_mysql` |
| Composer | 2.x |
| Node | **22+** (build only; not needed at runtime) |
| Database | MySQL 8 / PostgreSQL 15+ (SQLite only for local/CI) |
| Redis | Recommended in production for cache, sessions, and queues |
| Web server | Nginx/Caddy + PHP-FPM, or a managed platform (Laravel Forge, etc.) |

---

## 2. Environment configuration

Copy `.env.example` → `.env` and set values. Key variables:

### Core
- `APP_NAME`, `APP_ENV=production`, `APP_DEBUG=false`
- `APP_KEY` — generate once with `php artisan key:generate`
- `APP_URL=https://neuroresource.org`

### Database
- `DB_CONNECTION=mysql` (or `pgsql`) + `DB_HOST/PORT/DATABASE/USERNAME/PASSWORD`

### Sessions, cache, queue (production: Redis)
- `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`
- `REDIS_HOST/PASSWORD/PORT`
- `SESSION_SECURE_COOKIE=true` (HTTPS), `SESSION_DOMAIN` — **see §5** for the game subdomain.

### Object storage (resource downloads & uploads)
- `FILESYSTEM_DISK=s3` and `AWS_*` (`ACCESS_KEY_ID, SECRET_ACCESS_KEY, DEFAULT_REGION, BUCKET`, optional `AWS_URL`, `AWS_ENDPOINT` for S3-compatible providers).

### Mail (resource double opt-in confirmation)
- `MAIL_MAILER=postmark` (or `resend`/`ses`) + provider key (`POSTMARK_API_KEY`, etc.), `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`. Without a real mailer, confirmation emails won't be delivered and email-gated resources can't be unlocked.

### Shopify (headless storefront)
- `SHOPIFY_STOREFRONT_DOMAIN` (e.g. `neuroscouts.myshopify.com`)
- `SHOPIFY_STOREFRONT_TOKEN` (public Storefront access token)
- `SHOPIFY_API_VERSION` (e.g. `2025-07`), `SHOPIFY_CACHE_TTL`, `SHOPIFY_WEBHOOK_SECRET`
- With no token set, the shop falls back to local fixtures (`FakeCatalog`) — fine for staging previews, not for real sales.

### Analytics
- `PLAUSIBLE_DOMAIN=neuroresource.org` (cookieless; no consent banner). Leave blank to disable. Off automatically in `local`/`testing`.

### Game subdomain (see §5)
- `APP_DOMAIN`, `PLAY_DOMAIN` (leave both blank for single-host), `SESSION_DOMAIN`.

---

## 3. Build & release steps

Run on each deploy (zero-downtime tools like Envoyer/Forge wrap these):

```bash
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build            # compiles app + adventure entries
php artisan migrate --force        # DB migrations (never run sample seeders in prod)
php artisan storage:link           # only if serving files from the public disk
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart          # pick up new code in workers
```

> **Do not run `db:seed` in production.** The seeders (`ProfileSeeder`,
> `ResourceSeeder`, `PostSeeder`) contain **sample/placeholder content**.
> Author real content via the database/admin before launch.

---

## 4. Queues & scheduler

Async work runs on the queue:
- `RecordResourceDownload` — increments download counters off the request path.
- Outbound mail (where queued).

Run a worker (Supervisor or the platform's process manager). Redis + **Laravel
Horizon** is recommended:

```bash
php artisan horizon          # or: php artisan queue:work redis --tries=3
```

There is currently no scheduled (cron) work; if added, run
`php artisan schedule:run` every minute via cron.

---

## 5. The Adventure game subdomain

The game is designed to live at **play.neuroresource.org**. It's implemented so
the *same app* can serve it on a subdomain, switched entirely by env:

| Mode | Config | Behavior |
|---|---|---|
| Single host (local/CI/staging) | `PLAY_DOMAIN` empty | Game at `/play`, rest of site on the same host. |
| Split | `PLAY_DOMAIN=play.neuroresource.org` + `APP_DOMAIN=neuroresource.org` | Game served at the **root of the play subdomain**; the rest of the site on the primary host. `route()` generates the correct host for each side automatically. |

### To enable the split
1. **DNS** — point both `neuroresource.org` and `play.neuroresource.org` at the app.
2. **TLS** — issue certificates for both hosts (a wildcard `*.neuroresource.org`
   or a SAN cert covering both).
3. **Env** — set `APP_DOMAIN=neuroresource.org` and `PLAY_DOMAIN=play.neuroresource.org`.
4. **Shared login** — set `SESSION_DOMAIN=.neuroresource.org` (leading dot) so the
   session cookie is shared across subdomains. This is what lets a visitor who
   logged in on the main site be recognized on the game subdomain (and is
   required for cross-device game save to sync while signed in).
5. Rebuild the config cache (`php artisan config:cache`) and deploy.

Verify after deploy:
- `https://play.neuroresource.org/` serves the game; `https://neuroresource.org/`
  serves the home page.
- Nav links on the game page point back to the primary host.

### Known considerations
- **Logging in from the game page** lands the visitor on the primary host after
  auth (the redirect is path-based for open-redirect safety). With the shared
  session they are signed in everywhere; they can return to the game. Improving
  cross-subdomain return-to-origin is a future enhancement.
- **Fully static option** — because the engine is framework-agnostic, the game
  can later be deployed as a static SPA on a CDN at `play.neuroresource.org`. That
  would require: hosting the story JSON as a static asset, and exposing the
  progress API under CORS (allow the play origin, `withCredentials`) for
  signed-in save. Not needed for the current same-app split.

---

## 6. External services checklist

- **Shopify** — a free **development store** (Partners program) gives a real
  Storefront API + test-mode checkout with no paid plan. Create a Storefront
  access token; configure a webhook (`products/update`, `collections/update`) to
  the app's webhook endpoint with `SHOPIFY_WEBHOOK_SECRET` so the cached catalog
  invalidates. Checkout is hosted by Shopify (no PCI scope here).
- **Plausible** — add the site in Plausible and set `PLAUSIBLE_DOMAIN`. Start on
  the managed plan; self-host later (point `PLAUSIBLE_SRC` at your instance).
- **Email** — connect Postmark/Resend/SES. The mailing-list provider sync for
  confirmed opt-ins is a documented integration point in `ConfirmUnlockController`.

---

## 7. Caching & invalidation

- App caches: `config:cache`, `route:cache`, `view:cache`, `event:cache` on
  deploy. Clear with `php artisan optimize:clear` if needed.
- Shopify catalog is cached in Redis (`SHOPIFY_CACHE_TTL`) and invalidated by
  webhooks. If catalog looks stale, confirm the webhook is firing.
- Static assets are content-hashed by Vite — safe to cache aggressively / behind
  a CDN.

---

## 8. Security

- `APP_DEBUG=false`, a strong unique `APP_KEY`, HTTPS everywhere.
- `SESSION_SECURE_COOKIE=true`; set `SESSION_DOMAIN` as above for the split.
- Configure `TrustProxies` if behind a load balancer/CDN so HTTPS + client IPs
  are detected correctly.
- Secrets (`APP_KEY`, Shopify/mail keys) come from the environment, never the repo.

---

## 9. Phased infrastructure (from the system design)

- **Phase 1 — start small:** one managed host (e.g. Laravel Forge on a
  DigitalOcean droplet), managed MySQL/Postgres, Redis, S3-compatible object
  storage, Cloudflare CDN. Horizon for queues.
- **Phase 2 — scale out (no rewrite):** containerize; multiple stateless app
  instances behind a load balancer (sessions/cache in Redis, files in object
  storage already); managed cloud DB with a read replica; managed Redis. Extract
  a module to its own service only when a real bottleneck appears.

---

## 10. CI/CD

- **CI** (`.github/workflows/ci.yml`) runs on every PR and on push to `main`:
  tests (PHP 8.4 + 8.5), Pint code style, and the asset build. Keep `main` green.
- **CD** — deploy `main` via Forge/Envoyer (or a platform deploy hook) running
  the steps in §3. Roll back by redeploying the previous release and, only if a
  migration must be undone, running the matching `migrate:rollback`.

---

## 11. Post-deploy smoke checklist

- [ ] `https://neuroresource.org/up` returns healthy.
- [ ] Home, Shop, a product page, Blog + a post, Resource Library, About render.
- [ ] Shop shows real Shopify products (token configured) and "Add to Cart" /
      checkout hands off to Shopify.
- [ ] Email-gated resource: submitting an email sends the confirmation; the link
      confirms and unlocks the download.
- [ ] `https://play.neuroresource.org/` serves the game; progress saves while
      signed in.
- [ ] Plausible is recording; no console errors; reduced-motion + themes work.
