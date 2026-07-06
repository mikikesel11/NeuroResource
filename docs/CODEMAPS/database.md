# Database Codemap

**Last Updated:** 2026-07-06

## Schema Overview

```
Authentication & Users
├─ users                       (id, name, email, email_verified_at, password, ...)
├─ email_verifications         (id, email, token, created_at)  [Laravel Fortify]
└─ password_reset_tokens       (email, token, created_at)      [Laravel]

Content
├─ posts                       (id, slug, title, excerpt, body, ..., published_at)
├─ post_tags                   (post_id, tag_id)
└─ tags                        (id, name, slug, created_at)

Resources (Email-Gate)
├─ resources                   (id, slug, title, summary, type, file_path, ...)
│                              [access: 'free'|'email', guarded in model #33]
│                              [download_count: guarded, updated async]
├─ resource_unlocks            (id, resource_id, user_id|null, email, confirmed_at, ...)
└─ resource_tags               (resource_id, tag_id)

Profile
├─ profiles                    (id, name, headline, bio, avatar_path, ...)
└─ certifications              (id, profile_id, name, issuer, issued_on, expires_on,
                               credential_url, badge_path, sort_order)
                               [credential_url validated #31: http(s) only or null]

Game
├─ cards                       (id, title, description, image_path, timer_minutes, xp_earned, ...)
├─ user_card_pulls             (id, user_id, card_id, pulled_at)
│                              [unique index on (user_id, card_id) for idempotency]
├─ game_progress               (id, user_id, story_id, scene_id, state_json, updated_at)
└─ xp_events                   (id, user_id|null, source, amount, awarded_at)
                               [append-only: never update/delete]

Preferences
└─ preferences                 (id, user_id|null, cookie_id|null, theme, text_scale,
                               line_height, font, reduce_motion, locale, ...)

Catalog Cache (Optional)
└─ shop_products_cache         (handle, payload_json, fetched_at)  [or Redis-only]
                               [invalidated by Shopify webhooks]
```

## Models & Relationships

### User (app/Models/User.php)
```php
class User extends Authenticatable {
    // Relations
    hasMany(GameProgress::class)
    hasMany(UserCardPull::class)
    hasMany(XpEvent::class)
    hasMany(ResourceUnlock::class)
    hasOne(Preference::class)
    
    // Key attributes
    email_verified_at  // email gate check requires this
    
    // Methods
    hasVerifiedEmail() // used in ResourceGate
}
```

### Post (app/Domains/Content/Models/Post.php)
```php
class Post extends Model {
    // Relations
    belongsToMany(Tag::class, 'post_tags')
    
    // Scopes
    published()
    byTag($tagSlug)
    
    // Attributes
    body               // Markdown, rendered via safe_markdown() helper
    published_at       // nullable
    reading_minutes    // estimated read time
}
```

### Tag (app/Domains/Content/Models/Tag.php)
```php
class Tag extends Model {
    // Relations
    belongsToMany(Post::class, 'post_tags')
    belongsToMany(Resource::class, 'resource_tags')
    
    // Scope
    bySlug($slug)
}
```

### Resource (app/Domains/Resources/Models/Resource.php)
```php
class Resource extends Model {
    protected $guarded = ['access', 'download_count']; // #33 mass-assignment protection
    
    // Relations
    belongsToMany(Tag::class, 'resource_tags')
    hasMany(ResourceUnlock::class)
    
    // Methods
    isEmailGated()   // access === 'email'
    isFree()         // access === 'free'
    
    // Accessors/Mutators
    file_path        // object storage path
}
```

### ResourceUnlock (app/Domains/Resources/Models/ResourceUnlock.php)
```php
class ResourceUnlock extends Model {
    // Relations
    belongsTo(Resource::class)
    belongsTo(User::class, 'user_id')->nullable()
    
    // Attributes
    email              // email address (for anonymous)
    user_id            // logged-in user (if any)
    confirmed_at       // timestamp when confirmation link clicked
    token              // for double opt-in link (also verified via URL signature #33)
    
    // Scope
    confirmed()        // ->whereNotNull('confirmed_at')
}
```

### Card (app/Domains/Game/Models/Card.php)
```php
class Card extends Model {
    // Attributes
    title, description, image_path
    timer_minutes       // optional: if set, offer "Start N-minute timer" button
    xp_earned          // awarded on completion, not on draw
    
    // Relations
    hasMany(UserCardPull::class)
}
```

### UserCardPull (app/Domains/Game/Models/UserCardPull.php)
```php
class UserCardPull extends Model {
    // Attributes
    user_id, card_id, pulled_at
    
    // Unique Index
    unique: (user_id, card_id)  // prevent duplicate pulls
    
    // Relations
    belongsTo(User::class)
    belongsTo(Card::class)
}
```

### GameProgress (app/Domains/Game/Models/GameProgress.php)
```php
class GameProgress extends Model {
    // Attributes
    user_id, story_id, scene_id, state_json
    
    // Relations
    belongsTo(User::class)
}
```

### XpEvent (app/Domains/Game/Models/XpEvent.php)
```php
class XpEvent extends Model {
    const UPDATED_AT = null;  // append-only: never update
    
    // Attributes
    user_id|null       // anonymous events possible
    source             // 'login_bonus', 'card_completion', etc.
    amount             // points awarded
    awarded_at         // timestamp (frozen)
    
    // Never update or delete rows
    // XpService reads all rows, calculates totals on demand
}
```

### Preference (app/Domains/Preferences/Models/Preference.php)
```php
class Preference extends Model {
    // Attributes
    user_id|null       // logged-in user OR
    cookie_id|null     // anonymous visitor (session cookie)
    
    theme              // 'light'|'dark'|'high-contrast'|'low-stimulation'
    text_scale         // 1.0, 1.2, 1.5, etc.
    line_height        // 1.4, 1.6, 1.8, etc.
    font               // 'system'|'dyslexia-friendly'
    reduce_motion      // boolean
    locale             // 'en', 'es', etc.
    
    // Relations
    belongsTo(User::class)->nullable()
}
```

### Profile (app/Domains/Profile/Models/Profile.php)
```php
class Profile extends Model {
    // Attributes
    name, headline, bio, avatar_path
    
    // Relations
    hasMany(Certification::class)
}
```

### Certification (app/Domains/Profile/Models/Certification.php)
```php
class Certification extends Model {
    // Attributes
    name, issuer, issued_on, expires_on
    badge_path, sort_order
    
    // Mutators
    credential_url  // mutator: rejects non-http(s) URLs, stores null (#31)
    
    // Relations
    belongsTo(Profile::class)
}
```

## Key Indexes

```sql
-- Performance
users(email)                           -- auth lookups
posts(slug)                            -- blog routes
posts(published_at DESC)               -- feed, index
resources(slug)                        -- detail routes
resources(access)                      -- filtering free vs email-gated
certifications(profile_id, sort_order) -- ordered list
cards(id)                              -- deck shuffle
user_card_pulls(user_id, card_id)      -- prevent duplicates

-- Game progress
game_progress(user_id)                 -- load on login
xp_events(user_id)                     -- compute totals
xp_events(awarded_at DESC)             -- leaderboard

-- Email-gate
resource_unlocks(resource_id)          -- find all unlocks of a resource
resource_unlocks(email)                -- detect mail-bombing attempts (rate limit #33)
resource_unlocks(user_id)              -- user's unlock history
```

## Migrations

Located in `database/migrations/`:

```
****_01_00_create_users_table.php
****_01_01_create_email_verifications_table.php
****_01_02_create_password_reset_tokens_table.php

****_02_00_create_posts_table.php
****_02_01_create_tags_table.php
****_02_02_create_post_tags_table.php

****_03_00_create_resources_table.php
****_03_01_create_resource_unlocks_table.php
****_03_02_create_resource_tags_table.php

****_04_00_create_profiles_table.php
****_04_01_create_certifications_table.php

****_05_00_create_preferences_table.php

****_06_00_create_cards_table.php
****_06_01_create_user_card_pulls_table.php
****_06_02_create_game_progress_table.php
****_06_03_create_xp_events_table.php
```

## Data Access Patterns

### Read: Product Catalog
```php
// Via interface (abstraction)
app(ProductCatalog::class)->all()   → ProductData[] (cached)
app(ProductCatalog::class)->find($handle) → ProductData|null
```

### Read: Email-Gate Status
```php
// Direct gate check (no model query)
ResourceGate::unlocked($resource)   → boolean
  checks: resource.access === 'free'
       OR auth().check() && auth().user().hasVerifiedEmail()
       OR session('resource_unlocks')[] contains slug
```

### Write: Confirm Email-Gate Unlock
```php
// POST /resources/{slug}/confirm
ConfirmUnlockController::confirm()
  → verify signed URL + expiry + throttle
  → ResourceUnlock::where('token', $token)->firstOrFail()
  → update confirmed_at (mark as confirmed)
  → session(['resource_unlocks' => $slug])  // de-dup on first confirm only
```

### Write: Award XP
```php
// On login or card completion
XpService::awardXp('login_bonus', 10)
  → XpEvent::create(['user_id' => $userId, 'source' => '...', 'amount' => 10])
  // Schema never changes; calculations happen on read
```

### Read: Calculate XP Totals
```php
// Leaderboard, user stats
XpService::getTodayTotal($userId)     → read all XpEvents for today, sum
XpService::getWeekTotal($userId)      → read all XpEvents for this week, sum
XpService::getLeaderboard()           → aggregate all users, order by sum
  // All sums computed from append-only xp_events table
```

## Capacity & Scale Notes

- **Append-only XP table**: unbounded growth, but queries are simple (filter by user_id + awarded_at range, sum)
- **Shopify cache**: small (products rarely change), TTL 5-15 min, invalidated by webhooks
- **Preferences**: one row per user (logged-in) + one per anonymous session (small)
- **Game progress**: one row per user per story (small, updated on save)

Phase 1: single managed host, MySQL/Postgres. Phase 2: read replicas for leaderboard calcs if needed.

## Security Notes

### Email-Gate (Hardened #33)
- `Resource::$guarded = ['access', 'download_count']` prevents mass-assignment
- `ResourceUnlock` uses signed URL + throttle middleware to prevent brute-force
- `ResourceUnlock::token` verified via HMAC signature (URL::temporarySignedRoute) + expiry check

### Certification URLs (#31)
- Mutator on `Certification::credential_url` rejects non-http(s) schemes
- Blade template guard: `str_starts_with('http', $cert->credential_url)` before rendering href

### XP Integrity
- `XpEvent::UPDATED_AT = null` → immutable (no updates, only creates)
- `XpService` owns all calculations; never trust client-side XP values
- Card completion checks if `xp_earned` was already awarded (idempotent)

## Related Areas

- [backend.md](backend.md) — Controllers & services that query these models
- [security.md](security.md) — Email-gate hardening, signed URLs, validation
