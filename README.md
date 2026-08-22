# Gamefowl Expert System — Backend API

Rule-based expert system backend for early gamefowl disease detection and health monitoring. Built as a Laravel REST API consumed by a companion React Native mobile app.

> ⚠️ **Disclaimer:** This system is an educational/support tool and is **not a replacement for a licensed veterinarian**. All diagnostic output is presented as a *possible condition* based on submitted symptoms — never as a confirmed diagnosis. Severe or critical findings always direct the user to consult a veterinarian.

---

## Table of Contents

- [Overview](#overview)
- [Tech Stack](#tech-stack)
- [Project Type](#project-type)
- [Architecture](#architecture)
- [Requirements](#requirements)
- [Local Setup](#local-setup)
- [Authentication & Roles](#authentication--roles)
- [Response Format](#response-format)
- [API Reference (v1)](#api-reference-v1)
- [The Diagnostic Engine](#the-diagnostic-engine)
- [Health Status Labels](#health-status-labels)
- [Data Model](#data-model)
- [Seeded Knowledge Base](#seeded-knowledge-base)
- [Configuration](#configuration)
- [Testing](#testing)
- [Conventions](#conventions)
- [Development Status](#development-status)
- [Capstone Context](#capstone-context)

---

## Overview

The API provides:

1. **Gamefowl profile management** — owners register and manage their own birds (breed, age, sex, weight, notes).
2. **An admin-managed knowledge base** — diseases, symptoms with weighted rules linking them, and care recommendations.
3. **A weighted rule-based diagnostic engine** — scores possible diseases against user-reported symptoms and persists immutable assessment records.
4. **Health history** — a chronological per-bird timeline merging assessments with manual logbook entries (vet visits, weigh-ins), plus a derived current-status summary.
5. **Admin tooling** — user management with self-lockout prevention, knowledge-base CRUD, and aggregate dashboard statistics.

All expert-system logic lives server-side; the mobile app is a thin client.

## Tech Stack

| Component | Choice | Notes |
|---|---|---|
| Framework | Laravel 12 | PHP ≥ 8.2 |
| Database | PostgreSQL | Eloquent ORM, migrations |
| Auth | Laravel Sanctum 4 | Token-based bearer tokens (not cookie/SPA mode) |
| Validation | Form Requests | No inline controller validation |
| Response shaping | API Resources | Controlled field exposure per audience |
| Testing | PHPUnit | Feature + unit suites, in-memory SQLite for tests |

## Project Type

Rule-based / knowledge-based expert system — **not** machine learning or computer vision.

Diagnostic output comes from a deterministic weighted symptom-matching formula ([documented below](#the-diagnostic-engine)), not a trained model. Knowledge is authored by administrators as `(disease, symptom, weight)` rules; inference is transparent arithmetic over those rules, which makes every score explainable after the fact.

## Architecture

```
[React Native / Expo mobile app]
        │  REST (JSON, HTTPS)
        ▼
[Laravel API — Sanctum auth · business logic · expert-system engine]
        │  Eloquent ORM
        ▼
[PostgreSQL]
```

Related repositories:

- **Backend (this repo):** <https://github.com/rhondelp/Gamefowl-API.git>
- **Mobile app:** <https://github.com/rhondelp/Gamefowl-MobileApp.git>

## Requirements

- PHP ≥ 8.2 with the `pdo_pgsql` and `pgsql` extensions
- Composer
- PostgreSQL 14+
- Git

## Local Setup

```bash
git clone https://github.com/rhondelp/Gamefowl-API.git
cd Gamefowl-API
composer install
cp .env.example .env
php artisan key:generate
```

Configure PostgreSQL credentials in `.env`:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=gamefowl
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

Create the database, then migrate and seed:

```bash
createdb -U postgres gamefowl          # or use any Postgres client
php artisan migrate --seed
```

`php artisan db:seed --class=KnowledgeBaseSeeder` loads/refreshes only the diagnostic knowledge base (idempotent — it wipes and re-seeds that data). Run the dev server:

```bash
php artisan serve                      # http://127.0.0.1:8000
```

### Running Tests

```bash
php artisan test                       # full suite: feature + unit
```

Tests run against in-memory SQLite (configured in `phpunit.xml`) so no running PostgreSQL instance is required; the schema is portable across both engines.

## Authentication & Roles

Token-based via Sanctum. `POST /auth/register` or `POST /auth/login` returns a plain-text token; send it on every authenticated request:

```
Authorization: Bearer <token>
```

| Role | Capabilities |
|---|---|
| `owner` *(default)* | Manage own gamefowl profiles, submit health assessments for own birds, view own health history/status, read active knowledge base entries |
| `admin` | Everything above plus: knowledge-base CRUD, rule management, recommendation management, user management, dashboard |

Roles cannot be chosen at registration — every new account is an `owner`, and role injection via payload is explicitly tested against. Admins are blocked from modifying/deactivating their own account through the admin API (409 Conflict, self-lockout prevention).

Login attempts are rate-limited to 6 requests/minute; unknown-email and wrong-password failures return identical errors to prevent account enumeration.

## Response Format

Every JSON response follows one envelope:

**Success**

```json
{
  "success": true,
  "message": "Gamefowl created successfully.",
  "data": { "...": "..." }
}
```

**Validation error (422)**

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": { "field": ["Human readable message."] }
}
```

**Other errors** follow `{success, message}` with appropriate status codes: `401 Unauthenticated.` · `403 Forbidden.` (admin middleware) · uniform `404 Resource not found.` — ownership violations and missing IDs are deliberately indistinguishable so private records can't be probed for existence. Paginated lists add `data.pagination = {current_page, last_page?, per_page, total}`.

## API Reference (v1)

All routes are prefixed `/api/v1`. Full request contracts live in the code (`routes/api.php` + Form Requests) and milestone-by-milestone examples in [`MILESTONE_REPORTS.md`](MILESTONE_REPORTS.md).

### Public

| Method | Endpoint | Description |
|---|---|---|
| POST | `/auth/register` | Create owner account → returns user + token (201) |
| POST | `/auth/login` | Authenticate → returns user + token (rate limited) |

### Authenticated (any role)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/auth/me` | Current authenticated user |
| POST | `/auth/logout` | Revoke current token only |
| GET | `/gamefowls` | Own birds, paginated; active-only by default, `?include_inactive=1` |
| POST | `/gamefowls` | Create bird (`user_id` forced from token) |
| GET / PUT / DELETE | `/gamefowls/{id}` | Show / update (incl. `is_active` toggle) / soft-delete own bird |
| GET | `/symptoms` | Active symptoms; `?grouped=1` groups by category |
| GET | `/diseases` | Active diseases (no internal rule weights exposed) |
| GET | `/diseases/{id}` | Disease detail incl. symptom names (no weights) |
| POST | `/gamefowls/{id}/health-assessments` | Submit symptoms → scored, persisted, immutable record (201) |
| GET | `/health-assessments/{id}` | Full assessment detail with snapshots |
| POST | `/gamefowls/{id}/health-records` | Manual log entry (vet visit, weight check…); backdating allowed |
| GET | `/gamefowls/{id}/health-records` | Paginated manual records |
| GET | `/gamefowls/{id}/health-history` | Merged timeline of assessments + records, newest first |
| GET | `/gamefowls/{id}/health-status` | Derived status label + context summary |

All gamefowl-scoped routes enforce per-owner isolation: accessing another owner's bird (or its data) by ID returns the same generic 404 as a nonexistent ID.

### Admin only (`role = admin`)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/admin/dashboard` | Aggregate statistics (see below) |
| GET | `/admin/users` | All users; filters `?role=`, `?status=active\|inactive` |
| GET / PATCH / DELETE | `/admin/users/{id}` | Detail (+counts) / update role & active status / deactivate. Self-modification → 409 |
| GET / POST | `/admin/diseases` · `/admin/diseases/{id}` | List (incl. inactive, with rule weights) / create / detail / update |
| PATCH·PUT / DELETE | `/admin/diseases/{id}` | Update fields / **deactivate** (never hard delete) |
| POST / DELETE | `/admin/diseases/{id}/recommendations[/{recId}]` | Attach / detach recommendation |
| GET / POST / PUT / DELETE | `/admin/symptoms[/{id}]` | Symptom CRUD (DELETE = deactivate) |
| GET / POST / PUT / DELETE | `/admin/recommendations[/{id}]` | Recommendation CRUD (DELETE = deactivate) |
| POST | `/admin/rules` | Attach `(disease_id, symptom_id, weight)` — unique pair enforced, weight 1–5 |
| PUT / DELETE | `/admin/rules/{id}` | Adjust weight / detach pair |

## The Diagnostic Engine

Core logic: `app/Services/ExpertSystem/DiagnosticEngine.php` — isolated from HTTP, unit-tested against seeded data.

Given submitted symptom IDs, every **active** disease with effective rules is scored:

```
match_score(D, S) = round( Σ weight(r) for r ∈ rules(D) where r.symptom_id ∈ S
                           ───────────────────────────────────────────────────── × 100 )
                          Σ weight(r) for r ∈ rules(D)
```

Behavioral guarantees (all unit-tested):

- Rules pointing at **inactive symptoms are excluded from numerator *and* denominator**.
- Diseases with no rules, or zero overlap with the input, are never candidates — no divide-by-zero path exists.
- Standard rounding (`round()`, half away from zero); single documented rounding rule.
- Results below `min_match_threshold` are dropped; output capped at `max_results`.
- Ranking: score descending → disease name ascending (deterministic tie-break).
- Input is defensively sanitized here (unknown/inactive/duplicate/non-numeric IDs ignored); *existence validation* belongs to the assessment endpoint's Form Request.
- `vet_warning` surfaces only when disease severity ranks ≥ severe (mild < moderate < severe < critical).
- Results persist as **immutable snapshots** — later renames/deactivations in the knowledge base never alter historical assessments.

Each result carries: possible disease (id/name), match score, matched symptom names, missing symptom names ("why not higher"), severity, and vet warning where applicable.

## Health Status Labels

`GET /gamefowls/{id}/health-status` derives exactly one label, evaluated in order:

1. **`no_data`** — no assessments exist (manual records may exist; symptom screening simply hasn't happened yet).
2. **`stale`** — latest assessment is older than `recent_assessment_days` (config, default 14).
3. **`needs_attention`** — recent assessment whose top result scores ≥ 50.
4. **`healthy`** — anything else, including low scores and assessments with zero qualifying results.

The response includes `based_on` (assessment id, top disease, score), `days_since_last_assessment`, latest health record, and the standard disclaimer.

## Data Model

```
users ──< gamefowls ──< health_assessments ──< health_assessment_results
              │                    │
              │                    └──< health_assessment_symptoms >── symptoms
              └──< health_records
diseases ──< disease_symptom_rules (weight) >── symptoms
diseases ──< disease_recommendations >── recommendations
```

Eleven tables total. Key conventions:

- **Deactivate, don't delete:** `is_active` flags and/or `softDeletes()` everywhere; no hard-delete endpoint exists anywhere in the API. FKs into historical data use `RESTRICT` so database-level deletes can't silently destroy health history.
- **Snapshot denormalization:** assessments copy symptom names, disease names, severity, vet warnings, and matched/missing lists *at submission time*. Admins may rename or deactivate knowledge-base entries later without corrupting what a historical assessment shows — this is intentional redundancy in service of medical-record integrity.
- **Immutability:** health assessments are append-only; there are no update/delete endpoints for them.

## Seeded Knowledge Base

`KnowledgeBaseSeeder` (general public poultry-veterinary knowledge, not invented specifics):

| Item | Count |
|---|---|
| Diseases | 5 — Infectious Coryza, Fowl Pox, Newcastle Disease, Coccidiosis, Fowl Cholera |
| Symptoms | 23 — across respiratory / physical / digestive / neurological / behavioral categories |
| Weighted rules | 32 — weights 1–5 (5 = highly indicative), tuned so diseases produce distinct match profiles |
| Recommendations | 10 — hygiene / isolation / nutrition / monitoring / medication / vaccination / environment |
| Disease ↔ recommendation links | 21 |

Reset anytime with `php artisan db:seed --class=KnowledgeBaseSeeder`.

## Configuration

`config/expertsystem.php` (each value env-overridable):

| Key | Env var | Default | Purpose |
|---|---|---|---|
| `min_match_threshold` | `EXPERTSYSTEM_MIN_MATCH_THRESHOLD` | `20` | Minimum score for a disease to appear in results |
| `max_results` | `EXPERTSYSTEM_MAX_RESULTS` | `5` | Max ranked matches returned per diagnosis |
| `recent_assessment_days` | `EXPERTSYSTEM_RECENT_ASSESSMENT_DAYS` | `14` | Age window before an assessment is flagged stale in health-status |

## Testing

```bash
php artisan test
```

Current state: **79 tests, 639 assertions, all passing**, covering:

- Auth flows, rate limiting, enumeration-safe errors, role-injection resistance
- Gamefowl CRUD with per-owner isolation and spoof-proof `user_id`
- Knowledge-base authorization (23-route admin sweep), rule uniqueness, weight bounds
- The diagnostic engine's formula verified against hand-calculated seeded examples (rounding included)
- Assessment pass-through fidelity (endpoint output asserted equal to a direct engine call)
- Snapshot immutability under post-hoc knowledge-base edits
- Transaction rollback (a mid-persist crash leaves zero partial rows)
- Timeline chronology with interleaved sources, pagination, and table-driven status-label derivation
- Dashboard statistics asserted against exact fixture counts

## Conventions

- Conventional Commits (`feat:`, `fix:`, `refactor:`, `docs:`); work proceeds in small, verifiable milestones.
- Every mutating endpoint requires authentication; admin routes additionally require the `admin` role (middleware alias backed by an explicit middleware class).
- Records with historical significance (users, gamefowl, diseases, symptoms, recommendations, assessments) are deactivated/soft-deleted, never removed.
- Ownership checks live in policies (`GamefowlPolicy` is the single source of truth; resource policies delegate to it).
- Milestone-by-milestone development log with endpoints, decisions, and test evidence: [`MILESTONE_REPORTS.md`](MILESTONE_REPORTS.md).

## Development Status

- [x] Milestone 1 — Laravel + PostgreSQL initialization
- [x] Milestone 2 — Authentication API (Sanctum)
- [x] Milestone 3 — Gamefowl CRUD API
- [x] Milestone 4 — Disease & Symptom knowledge base API + seeder
- [x] Milestone 5 — Diagnostic engine (unit-tested in isolation)
- [x] Milestone 6 — Health Assessment API (submit → score → persist → retrieve)
- [x] Milestone 7 — Health History API (merged timeline + derived status)
- [x] Milestone 8 — Admin API (user management, dashboard) ← backend complete
- [ ] Milestone 9+ — React Native mobile app (separate repository)

## Capstone Context

**GAMEFOWL: Expert System for Early Bird Disease Monitoring and Analysis in Gamefowl.**

This backend implements the diagnostic logic and data layer for a mobile-based expert system positioned academically as a knowledge-based AI approach (rule-based weighted inference with explainable scoring), deliberately not machine learning, per project scope decisions made at the start of development.
