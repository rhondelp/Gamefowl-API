# GAMEFOWL Backend — Milestone Reports

Ongoing log of completed milestones for the GAMEFOWL Expert System backend.
Repo: https://github.com/rhondelp/Gamefowl-API.git

---

## Milestone 1 — Laravel + PostgreSQL API Initialization

**Status:** Complete & pushed (`a1a71f9`)
**Date:** 2026-08-22

### What was set up
- Fresh Laravel 12 project connected to PostgreSQL (`DB_CONNECTION=pgsql`)
- Database `gamefowl` created locally; connectivity verified via `php artisan migrate:status`
- Sanctum installed with published config (`config/sanctum.php`) — no auth logic yet at this stage
- API versioning: empty `v1` prefix group in `routes/api.php`
- `.gitignore` verified to exclude `.env`, `vendor/`, and generated artifacts

### How to run locally
```bash
composer install
cp .env.example .env   # configure DB_* credentials
php artisan key:generate
php artisan serve      # http://127.0.0.1:8000
```

---

## Milestone 2 — Authentication API (Sanctum)

**Status:** Complete & pushed (`4de2859`)
**Date:** 2026-08-22

### Endpoints
| Method | Endpoint | Auth | Notes |
|---|---|---|---|
| POST | `/api/v1/auth/register` | none | Always creates `owner`; returns user + token (201) |
| POST | `/api/v1/auth/login` | none | Throttled 6 req/min; identical error for unknown email vs wrong password |
| POST | `/api/v1/auth/logout` | sanctum | Revokes only the current token |
| GET | `/api/v1/auth/me` | sanctum | Returns authenticated user |

### Key decisions
- `role` column: `string(20)` default `owner` (not native pg enum — easier to extend); app-layer validation + `isAdmin()` helper
- Role cannot be injected via registration payload (hardcoded server-side)
- Response envelope enforced globally in `bootstrap/app.php`: `{success, message, data}` / `{success:false, message, errors}`
  - `ValidationException` → 422 envelope, `AuthenticationException` → 401 envelope on `api/*`
- `admin` middleware alias registered (`EnsureUserIsAdmin`) — unused until admin routes arrive

### Notable findings during testing
- `email:rfc,dns` does live DNS lookups (flaky/offline-hostile) → relaxed to `email:rfc`
- Feature tests share one app container across requests; framework's `RequestGuard` caches its resolved user, so token revocation must be tested after `Auth::forgetGuards()`. Production unaffected (fresh container per request).

### Tests
29 tests / 130 assertions passing at this milestone's completion (auth + example suites).

---

## Milestone 3 — Gamefowl CRUD API

**Status:** Complete & verified locally
**Date:** 2026-08-22
**Commit:** `feat: add gamefowl CRUD API with owner-scoped policies`

### Endpoints (all require `auth:sanctum`)
| Method | Endpoint | Behavior |
|---|---|---|
| GET | `/api/v1/gamefowls` | Paginated (15/page), active-only by default; `?include_inactive=1` includes retired birds |
| POST | `/api/v1/gamefowls` | Creates for authenticated owner; payload `user_id` ignored |
| GET | `/api/v1/gamefowls/{id}` | Owner-only |
| PUT/PATCH | `/api/v1/gamefowls/{id}` | Partial update incl. `is_active` toggle |
| DELETE | `/api/v1/gamefowls/{id}` | Soft-delete; row survives for health-history integrity |

### Schema decisions
- `user_id` FK uses **RESTRICT** on delete — bird history can never be silently destroyed by deleting a user
- `date_of_birth` nullable date; computed `age: {years, months}` accessor (never goes stale)
- Both `is_active` (owner-facing status toggle) and `softDeletes()` (removal safeguard) kept — different purposes
- `sex`: string(10), validated `male/female/unknown`, DB default `unknown` (no native pg enums)

### Access control
- `GamefowlPolicy` (`viewAny/view/create/update/delete`) wired via `$this->authorize()`; defense-in-depth via owner-scoped lookups (`$user->gamefowls()->findOrFail($id)`)
- Cross-owner access returns uniform **404** `{success:false, message:"Resource not found."}` whether or not the ID exists (anti-enumeration). Rendered globally for `AuthorizationException`/`NotFoundHttpException` on `api/*`.

### Fixes made en route
- Added `AuthorizesRequests` trait to base `Controller` (Laravel 12 ships it bare)
- Renamed relation to conventional `user()` so factory `->for($user)` FK guessing works

### Tests
**29 passed (130 assertions)** — covers create/list/show/update/delete success paths, spoofed `user_id`, cross-owner 404s on all three ID-based actions, validation failures (missing name, invalid sex, future DOB), inactive filtering, soft-delete survival.
Live smoke test against PostgreSQL passed end-to-end.

---

## Pending

- Milestone 4 — Disease and Symptom API (knowledge base management)
