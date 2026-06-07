# Shortlist

A job-application screening tool. Applicants submit applications; a heuristic engine
scores them for risk; reviewers triage them behind Auth0-protected routes.

> For the data model, heuristic rules, architecture, and design decisions, see [`DESIGN.md`](./DESIGN.md).

## Stack

| Part | Tech | Port |
|---|---|---|
| `backend` | Laravel + SQLite | 8000 |
| `frontend-applicant` | Vue 3 + TS + Naive UI | 5173 |
| `frontend-reviewer` | Vue 3 + TS + Naive UI | 5174 |
| `shared` | Shared TS types/constants | — |

## How it works

- **Applicant** (`5173`): public form. Loads skills from `GET /api/skills`, submits via
  `POST /api/applications`. Stores skill IDs; the backend resolves them to names for analysis.
- **Heuristic engine** (`HeuristicService`): on submit, flags risky applications and assigns a
  `risk_score` (0–100) — over-claiming skills, weak cover-letter coverage, suspicious experience, etc.
- **Reviewer** (`5174`): Auth0 login. Lists Pending/Shortlisted/Rejected, opens a detail drawer,
  and shortlists/rejects via `PATCH /api/applications/{id}/review`.

## Authentication (Auth0)

- Reviewer routes are protected by `Auth0Middleware`, which validates the `Authorization: Bearer <JWT>`
  using Auth0's public keys (signature, audience, issuer, expiry).
- The frontend logs in via redirect, then sends the access token on every reviewer request.
- `audience` (`https://shortlist.api`) must match across the Auth0 dashboard, frontend, and backend.

## API

| Method | Route | Auth |
|---|---|---|
| `GET` | `/api/skills` | public |
| `POST` | `/api/applications` | public |
| `GET` | `/api/applications?status=` | reviewer |
| `GET` | `/api/applications/{id}` | reviewer |
| `PATCH` | `/api/applications/{id}/review` | reviewer |

## Running the app

Requires Docker. Make sure both `.env.backend` and `.env.frontend` exist at the repo root

```bash
docker compose up --build -d                                          # build + start all services
docker exec shortlist-backend-1 php artisan migrate --seed            # run migrations + seed skills
docker exec shortlist-backend-1 php artisan db:seed --class=ApplicationSeeder   # optional: 20 sample applications
```

Then open:
- Applicant form → http://localhost:5173
- Reviewer dashboard → http://localhost:5174

> **Reviewer login credentials** are in `.env.backend` under `TEST_REVIEWER_EMAIL` and `TEST_REVIEWER_PASSWORD`.

## Tests

```bash
docker exec shortlist-backend-1 php artisan test
```

Covers the heuristic engine, application API, and Auth0 route protection. The valid-token auth
test needs the Password grant enabled on the Auth0 app and `TEST_REVIEWER_EMAIL` /
`TEST_REVIEWER_PASSWORD` in `.env.backend`; otherwise it skips.
