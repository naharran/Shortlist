# Shortlist — Design Overview

> **Smart Job Application Screening Queue**

Shortlist is a full-stack application that lets candidates submit job applications and lets
reviewers screen them efficiently. On submission, the system runs a **heuristic analysis** that
computes a **Risk Score** and generates intelligent **flags**, helping reviewers make faster,
smarter decisions (Shortlist or Reject).

> For setup and how to run the project, see [`README.md`](./README.md).

---

## Key Design Decisions

**Heuristics model realistic hiring signals.** The rules were derived from practical questions:
how many skills can a person realistically acquire over time, what's the difference between broad
and specific skills, and does a candidate actually explain and support the skills they claim —
directly or indirectly — in their cover letter.

**Two independent micro-frontends.** Applicant and reviewer experiences serve fundamentally
different users and workflows. Splitting them keeps each app focused on a single responsibility,
reduces coupling between public-facing and internal functionality, and lets each evolve independently.

**Auth0 over custom auth.** Reviewers access applicant data and internal evaluation results, so a
managed authentication solution offloads credential handling and token validation — keeping
development focused on the core product.

**Reviewers are Auth0 identities today, not local users.** There is no reviewer `User` model or
table in use — identity comes from Auth0 on the client, and the backend only validates the Bearer
token. In further development, I would introduce a local reviewer model and table to manage who can
review, assign roles, and tie each shortlist/reject decision to a specific reviewer record.

**Two-tier rate limiting on application submit .** Submission is limited to 5 successful
saves per IP per minute, applied *after* validation so form errors (422) do not consume the quota.
That fixes a UX problem — users fixing validation mistakes should not hit "too many attempts" — but
it leaves a gap: invalid requests can still be spammed to keep PHP busy (routing, validation, DB
`exists` checks) without ever saving an application. In production I would add a second, coarser
route-level limit on all `POST /api/applications` attempts (e.g. 30/min per IP) alongside the
strict success limit, plus CAPTCHA or CDN/WAF for volumetric abuse.

---

## Data Model

### Skill

| Field | Type | Notes |
|---|---|---|
| `id` | string | |
| `name` | string | |
| `type` | enum | `broad` \| `specific` |
| `related_keywords` | json array | Used for explanation-coverage detection |

```json
{ "name": "React", "type": "specific", "related_keywords": ["react", "jsx", "hooks", "redux", "next.js", "component", "frontend"] }
{ "name": "Vue",   "type": "specific", "related_keywords": ["vue", "vuex", "pinia", "nuxt", "composition api"] }
{ "name": "Front-end Development", "type": "broad", "related_keywords": [] }
```

### Application

| Field | Type | Notes |
|---|---|---|
| `id` | int | |
| `name` | string | |
| `email` | string | |
| `phone_number` | string | |
| `overall_experience` | integer | Years |
| `top_skills` | json array | |
| `moderate_skills` | json array | |
| `cover_letter` | text | |
| `status` | enum | `pending` \| `shortlisted` \| `rejected` |
| `risk_score` | integer | 0–100 |
| `heuristic_flags` | json | |
| `review_note` | text | nullable |
| `reviewed_at` | timestamp | nullable |
| `created_at` / `updated_at` | timestamp | |

---

## Heuristic Rules

The Risk Score and flags are calculated automatically when a new application is submitted.

### 1. `over_claiming_top_skills`

Evaluates whether the candidate claims an unusually high number of **top skills** relative to
their experience. The expected maximum is:

```
experience_cap = min(8, 2 + overall_experience)
```

The logic assumes a candidate starts with a base capacity of ~3 top skills and can reasonably add
about one more per year of experience, up to a maximum of 8:

| Experience | Cap |
|---|---|
| 1 year | 3 skills |
| 2 years | 4 skills |
| 3 years | 5 skills |
| 6+ years | 8 skills |

An **optimal focus** of 6 top skills is also encouraged.

- If the candidate **exceeds the experience cap**, a stronger penalty applies: `(count - experience_cap) × 10`
- If within the cap but **above the optimal focus** of 6: `(count - 6) × 3`

Only one penalty applies, preventing double penalization for the same behavior.

### 2. `over_claiming_broad_skills`

Same logic as the top-skills rule, but applied only to **broad skills** (high-level domains such
as Frontend, Backend, DevOps, Data Engineering) with stricter thresholds. The cap grows more slowly:

```
experience_cap = 2 + floor(overall_experience / 2)
```

The optimal focus is **4** broad skills. Candidates are penalized for exceeding either the
experience cap or the optimal focus.

### 3. `skill_explanation_coverage`

Checks **specific skills only** (not broad). Includes top and moderate skills, weighted differently:

| Skill kind | Weight |
|---|---|
| Top skill | 1.0 |
| Moderate skill | 0.5 |

For each specific skill, the system checks whether the cover letter mentions the skill name or one
of its related keywords. Coverage is:

```
coverage = weighted_covered / weighted_total
```

- Coverage **≥ 40%** → no penalty
- Coverage **< 40%** → `poor_skill_explanation_coverage` flag, penalty scaled to how low it is:
  - 0% coverage → 30-point penalty
  - close to 40% → close to 0 penalty

### Additional Flags

| Flag | Trigger | Penalty |
|---|---|---|
| `very_short_cover_letter` | Cover letter has fewer than 50 words | 15 |
| `suspicious_experience` | More than 40 years of experience, **or** 0 years but top skills selected | 20 |

---

## API Endpoints

### Public (Applicant)

| Method | Route | Purpose |
|---|---|---|
| `POST` | `/api/applications` | Submit an application (validates input, runs heuristics, stores) |
| `GET` | `/api/skills` | Retrieve available skills for the form |

### Authentication (Reviewer)

Authentication is handled through **Auth0** on the frontend. After login, the frontend receives an
Auth0 access token and sends it to the API as a Bearer token. The backend does **not** manage
reviewer login sessions directly — protected routes are guarded by an Auth0 middleware that
validates the Bearer token against the configured Auth0 domain, client ID, client secret, and audience.

### Protected (Reviewer)

Require a valid Auth0 Bearer token in the `Authorization` header.

| Method | Route | Purpose |
|---|---|---|
| `GET` | `/api/applications` | List applications (filters: `status`, `search`, experience range) |
| `GET` | `/api/applications/{id}` | Get a single application |
| `PATCH` | `/api/applications/{id}/review` | Review (shortlist / reject + optional note) |

---

## Architecture

- **Backend:** Laravel + SQLite (can switch to PostgreSQL)
- **Frontend:** Two separate Vue 3 micro-frontends in a monorepo
  - `frontend-applicant` — candidate application form
  - `frontend-reviewer` — reviewer dashboard
- **Shared:** common folder for types, API client, UI components, constants
- **Authentication:** Auth0 on the frontend; protected API requests carry an Auth0 Bearer token

### Applicant View (`frontend-applicant`)

- Clean application form
- Multi-select inputs for Top Skills and Moderate Skills
- Rich textarea for the Cover Letter
- Success page after submission

### Reviewer View (`frontend-reviewer`)

- Login page (predefined reviewer account)
- **Queue View:** pending applications with full-text search (SQLite FTS5) + filters
- **Review Drawer:** full application details, Risk Score, flags, suggested action,
  Approve (Shortlist) / Reject buttons
- Separate tabs: **Pending**, **Shortlisted**, **Rejected**

---

## Tests

Tests focus on what must not break: reviewer access, scoring logic, the application API contract,
and the error messages candidates actually see.

### Auth0 protection (`Auth0ApiTest`)

Reviewer data stays behind Auth0 — tests walk the full gate from no token → fake JWT → real token.

- `test_protected_routes_reject_requests_without_token` / `…_invalid_jwt` — always run; expect 401.
- `test_protected_routes_accept_valid_auth0_token` — logs into Auth0 for a real Bearer token and hits a protected route; skipped without `TEST_REVIEWER_*` credentials or Password grant.

### Heuristic engine (`HeuristicServiceTest`)

Unit tests for `HeuristicService` — the product's core value. Grouped around skill over-claiming
(`test_top_skills_over_experience_cap_raises_flag`, `test_too_many_broad_skills_raises_flag`),
cover letter signals (`test_poor_cover_letter_coverage_raises_flag`, `test_very_short_cover_letter_raises_flag`),
and composite outcomes (`test_clean_candidate_has_low_score_and_no_flags` vs `test_risky_candidate_accumulates_high_score`).

### Application API (`ApplicationApiTest`)

Feature tests for the HTTP layer: public submit validates input (`test_store_rejects_invalid_email`),
persists heuristics on success (`test_store_creates_application_with_heuristic_results`),
rate-limits successful saves without punishing form errors (`test_store_returns_429_…`, `test_store_validation_failures_do_not_count_…`),
and reviewer flows — filtered queue, detail vs summary shapes, shortlist/reject, and guarding already-reviewed apps.

### Applicant E2E (`frontend-applicant/e2e`)

Playwright runs against the live form to confirm 422 responses surface in the UI alert — empty submit,
bad email, and missing required fields (`applicant-form.spec.ts`).

### Running tests

```bash
docker exec shortlist-backend-1 php artisan test
cd frontend-applicant && npm run test:e2e   # requires Docker stack running
```
