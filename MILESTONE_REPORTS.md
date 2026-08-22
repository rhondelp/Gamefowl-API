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

## Milestone 4 — Disease & Symptom Knowledge Base API

**Status:** Complete & verified locally
**Date:** 2026-08-22
**Commit:** `feat: add admin-managed knowledge base API for diseases and symptoms`

This is the data layer the expert system runs on. No diagnostic/inference logic — that is the next milestone.

### Endpoints

**General reads** (any authenticated user; active entries only; no rule weights or `is_active` exposed):
| Method | Endpoint |
|---|---|
| GET | `/api/v1/symptoms` (`?grouped=1` returns symptoms grouped by category) |
| GET | `/api/v1/diseases` |
| GET | `/api/v1/diseases/{id}` (includes active symptom names/categories, still no weights) |

**Admin-only** (`auth:sanctum` + `admin` middleware alias → owners get `403 {success:false, message:"Forbidden."}`):
| Method | Endpoint | Notes |
|---|---|---|
| GET/POST/PUT/DELETE | `/api/v1/admin/symptoms[/{id}]` | DELETE = deactivate |
| GET/POST/PUT/DELETE | `/api/v1/admin/diseases[/{id}]` | show/list include rules **with weights** + recommendations + `is_active` |
| POST/PUT/DELETE | `/api/v1/admin/rules[/{id}]` | attach `(disease, symptom, weight)` / update weight / detach |
| GET/POST/PUT/DELETE | `/api/v1/admin/recommendations[/{id}]` | DELETE = deactivate |
| POST / DELETE | `/api/v1/admin/diseases/{id}/recommendations[/{recommendationId}]` | attach / detach recommendation to disease |

All deletes are deactivations (`is_active = false`) — no hard-delete endpoint exists.

### Schema
- `symptoms`: name (unique), description (nullable), category (free string), severity (mild/moderate/severe), is_active
- `diseases`: name (unique), description, severity (mild/moderate/severe/**critical**), general_info (nullable), recommended_action, prevention_info (nullable), vet_warning (nullable), is_active
- `disease_symptom_rules`: disease_id FK, symptom_id FK, weight (unsigned tinyint), **UNIQUE(disease_id, symptom_id)** — this table *is* the knowledge base
- `recommendations`: title, content, category (constrained extensible list), is_active
- `disease_recommendations`: UNIQUE(disease_id, recommendation_id)
- Pivot FKs cascade; weight range fixed 1–5 via constants on the pivot model

---

## Seeded Knowledge Base (KnowledgeBaseSeeder)

Counts: **5 diseases · 23 symptoms · 32 weighted rules · 10 recommendations · 21 disease↔recommendation links**

### Diseases
| # | Disease | Severity | Rules | Recs linked |
|---|---|---|---|---|
| 1 | Infectious Coryza | moderate | 6 | 4 |
| 2 | Fowl Pox | moderate | 5 | 4 |
| 3 | Newcastle Disease | critical | 7 | 5 |
| 4 | Coccidiosis | severe | 7 | 4 |
| 5 | Fowl Cholera | severe | 7 | 4 |

Content is based on general public poultry-veterinary knowledge (causative agents, transmission, typical signs, supportive actions). Newcastle carries a `vet_warning` (notifiable disease — report to vet/Bureau of Animal Industry); Fowl Cholera has one about carrier birds before restocking.

### Symptoms (23)
| Symptom | Category | Severity |
|---|---|---|
| Nasal discharge (runny nose) | respiratory | moderate |
| Sneezing | respiratory | mild |
| Coughing | respiratory | moderate |
| Gasping or labored breathing | respiratory | severe |
| Wet rales (rattling breath sounds) | respiratory | moderate |
| Foamy eye discharge | respiratory | moderate |
| Swelling of the face or wattles | physical | moderate |
| Wart-like scabs on comb or wattles | physical | moderate |
| Yellow patches inside the mouth | physical | severe |
| Pale comb | physical | moderate |
| Ruffled feathers | physical | mild |
| Weight loss despite feeding | physical | moderate |
| Lameness or swollen joints | physical | moderate |
| Greenish watery droppings | digestive | severe |
| Bloody droppings | digestive | severe |
| Watery white droppings | digestive | moderate |
| Loss of appetite | digestive | moderate |
| Twisted neck (torticollis) | neurological | severe |
| Paralysis of legs or wings | neurological | severe |
| Circling or stargazing | neurological | severe |
| Lethargy or depression | behavioral | moderate |
| Sudden death without prior signs | behavioral | severe |
| Huddling together | behavioral | mild |

### Rules — disease → symptom weights (32)
Weight meaning: **5** = highly indicative / near pathognomonic · **3–4** = strongly associated · **1–2** = weak/general support.

| Disease | Symptom (weight) |
|---|---|
| Infectious Coryza | Nasal discharge (5) · Swelling of face/wattles (5) · Foamy eye discharge (4) · Sneezing (3) · Wet rales (2) · Loss of appetite (2) |
| Fowl Pox | Wart-like scabs on comb/wattles (5) · Yellow patches inside mouth (4) · Gasping/labored breathing (3) · Weight loss despite feeding (2) · Lethargy/depression (2) |
| Newcastle Disease | Greenish watery droppings (5) · Twisted neck/torticollis (5) · Paralysis of legs/wings (4) · Gasping/labored breathing (4) · Circling/stargazing (4) · Loss of appetite (3) · Sudden death without prior signs (3) |
| Coccidiosis | Bloody droppings (5) · Pale comb (4) · Ruffled feathers (3) · Huddling together (3) · Watery white droppings (3) · Weight loss despite feeding (3) · Lethargy/depression (3) |
| Fowl Cholera | Greenish watery droppings (4) · Sudden death without prior signs (4) · Lameness/swollen joints (4) · Swelling of face/wattles (4) · Lethargy/depression (3) · Loss of appetite (3) · Twisted neck/torticollis (2) |

The profiles deliberately overlap on general signs (loss of appetite, lethargy) but diverge on hallmark signs so the engine produces meaningfully different scores per disease.

### Recommendations (10)
| Recommendation | Category |
|---|---|
| Isolate affected birds immediately | isolation |
| Provide clean water with electrolytes | nutrition |
| Improve coop ventilation and reduce ammonia | environment |
| Disinfect housing, feeders, and drinkers | hygiene |
| Keep litter dry and replace soiled bedding | hygiene |
| Follow the recommended vaccination schedule | vaccination |
| Consult a licensed veterinarian before medicating | medication |
| Apply antiseptic (povidone-iodine) to visible lesions | medication |
| Monitor the flock twice daily and record new cases | monitoring |
| Reduce mosquito breeding sites around pens | environment |

**Links:** Coryza → isolate, electrolytes, ventilation, monitoring · Fowl Pox → antiseptic lesions, mosquito control, isolate, monitoring · Newcastle → isolate, consult vet, vaccination, disinfect, monitoring · Coccidiosis → dry litter, electrolytes, consult vet, monitoring · Fowl Cholera → isolate, disinfect, consult vet, monitoring

### Tests
**37 passed (372 assertions)** total suite — non-admin blocked from all 11 admin write attempts (403), full admin CRUD on all four resources, duplicate rule pair rejected (422), weight validation rejects 0/6/'abc', deactivated entries hidden from public reads but visible to admins with weights, unauthenticated 401s, seeder integrity (counts, ≥5 symptoms per disease, ≥4 recs per disease, weights within 1–5, no duplicate pairs).

Live smoke test vs PostgreSQL passed (public reads, admin symptom + rule creation). Local DB seeded; reset anytime with `php artisan db:seed --class=KnowledgeBaseSeeder`.

---

## Milestone 5 — Expert-System Diagnostic Engine

**Status:** Complete & verified locally
**Date:** 2026-08-22
**Commit:** `feat: add weighted symptom-matching diagnostic engine`

Pure logic milestone: no endpoint, no migrations, no controller wiring. The engine is a read-only consumer of the knowledge base, unit-tested in isolation.

### Classes
- `app/Services/ExpertSystem/DiagnosticEngine.php` — inference service; `diagnose(array $symptomIds): Collection<DiagnosisMatch>`
- `app/Services/ExpertSystem/DiagnosisMatch.php` — readonly result DTO (disease id/name, matchScore 0–100, matchedSymptoms, missingSymptoms, severity, vetWarning) with an API-facing `toArray()` for the assessment endpoint in a later milestone
- `config/expertsystem.php` — engine tunables

### Scoring formula (implemented exactly)
```
match_score(D, S) = round( Σ weight(r) where r.symptom_id ∈ S / Σ weight(r) over rules(D) × 100 )
```
- Only active diseases are candidates; rules pointing to inactive symptoms are dropped from **both** numerator and denominator
- Diseases with no effective rules or zero overlap with the input are never candidates (no divide-by-zero by construction)
- Single documented rounding rule: PHP `round()` (half away from zero)

### Configuration
| Key | Default | Env override |
|---|---|---|
| `expertsystem.min_match_threshold` | 20 | `EXPERTSYSTEM_MIN_MATCH_THRESHOLD` |
| `expertsystem.max_results` | 5 | `EXPERTSYSTEM_MAX_RESULTS` |

Ranking: score DESC → disease name ASC (deterministic tie-break). `vet_warning` surfaces only when severity rank ≥ severe (mild < moderate < severe < critical). `unmatched_selected_symptoms` deliberately deferred to the API layer — it is input-dependent, identical across all candidate results, so it is computed once per request there.

**Division of responsibility:** the engine is defensive only (ignores unknown/inactive/duplicate/non-numeric IDs); validating submitted symptoms exists belongs to the assessment endpoint's FormRequest in Milestone 6/7.

### Hand-verified examples (against real seeded data)
1. Coccidiosis (rule total 24): input `{Bloody droppings(5), Pale comb(4), Lethargy(3)}` → `(5+4+3)/24 × 100` = **50**, matched 3 / missing 4 ✓
2. Newcastle Disease (rule total 28): input `{Greenish droppings(5), Twisted neck(5), Loss of appetite(3)}` → `13/28 × 100 = round(46.43)` = **46** ✓
3. Six-symptom overlap input ranks `[Coccidiosis 50, Fowl Cholera 50, Newcastle 46]` — Fowl Cholera ties at 50 because it also carries Twisted neck(w2), and the alphabetical tie-break resolves the tie ✓ (this case initially caught a hand-calc error in the test expectations themselves — the engine math was correct)

### Tests
**50 passed (408 assertions)** full suite; new engine suite adds 13 tests covering: empty/invalid input, both hand-calculated seeded examples, multi-disease ranking, perfect-match 100, inactive symptom exclusion (numerator/denominator/missing lists), inactive disease exclusion, rule-less disease safety, duplicate-ID deduplication, defensive unknown-ID handling, threshold config exclusion, max-results limit, alphabetical tie-break, and vet-warning severity gating.

---

## Milestone 6 — Health Assessment API

**Status:** Complete & verified locally
**Date:** 2026-08-22
**Commit:** `feat: add immutable health assessment API consuming diagnostic engine`

The engine from Milestone 5 is consumed unmodified — this milestone validates input, persists an immutable record, and returns ranked results with explanations.

### Endpoints
| Method | Endpoint | Notes |
|---|---|---|
| POST | `/api/v1/gamefowls/{gamefowlId}/health-assessments` | Owner-only; one transaction wraps all writes; 201 |
| GET | `/api/v1/health-assessments/{id}` | Full detail with snapshots; owner-of-parent-bird only |

No update/delete endpoints — assessments are append-only immutable historical records.

### Schema
- `health_assessments`: gamefowl_id (FK **RESTRICT**), age/sex snapshots at assessment time (auto-filled from the live bird when not supplied by the client), optional context fields (`duration_of_symptoms`, `appetite`, `activity_level` enums; `additional_notes`)
- `health_assessment_symptoms`: pivot with **denormalized `symptom_name` snapshot**
- `health_assessment_results`: rank, match_score, `matched_symptoms`/`missing_symptoms` JSON name snapshots, `disease_name` snapshot, severity + vet-warning snapshots
- FKs into knowledge-base tables use RESTRICT so DB-level hard deletes can never orphan historical data

### Cautious-wording contract
Results expose `possible_disease` (never "diagnosis") and every response carries a static disclaimer: *"This assessment is generated from reported symptoms and is not a confirmed veterinary diagnosis. Always consult a licensed veterinarian..."*

### Division of responsibility (as planned in M5)
Input validation lives here: `symptom_ids` required (1–30), each must exist **and be active** — inactive/nonexistent IDs are rejected 422 here rather than silently ignored by the engine.

### Fixes made en route
- Migration ordering again (child tables sorted before parent alphabetically — renamed timestamps)
- Discovered Laravel converts policy denials (`AuthorizationException`) into `AccessDeniedHttpException` **before** custom render callbacks run — bootstrap updated so cross-owner access keeps its uniform 404 anti-enumeration envelope; the admin middleware's explicit 403 is unaffected

### Tests
**59 passed (453 assertions)** full suite. Health-assessment suite covers: faithful pass-through (endpoint output asserted field-by-field against a direct `DiagnosticEngine::diagnose()` call on identical input), cross-owner bird → 404, empty/inactive/nonexistent symptom rejection, view own vs another owner's assessment, snapshot survival after KB renames/deactivations, and transaction rollback simulation (`HealthAssessmentResult::creating` throws mid-persist → zero rows left behind).

Live cycle verified vs PostgreSQL: submit `{Bloody droppings, Pale comb}` → Coccidiosis **38** = same value pinned by M5 unit tests.

---

## Pending

- Health History API — timeline view combining assessments and general health records over time for each bird

