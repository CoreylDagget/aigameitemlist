# AI Coding Agent Instructions — gameitemslist

## Mission
Build **gameitemslist**: a web app and API to create and manage per-game item lists (“Listen”) with admin-approved changes, per-account ownership/quantities, tags (categories), filtering, and a share/publish flow.

## Tech Stack
- **Language:** PHP 8.3+
- **Framework:** Slim 4
- **HTTP Server:** NGINX
- **Runtime:** Dockerized (PHP-FPM, NGINX, DB, Redis)
- **DB:** MySQL/MariaDB (preferred) with PostgreSQL as a supported fallback
- **Cache:** Redis
- **Docs:** OpenAPI 3.1 (Swagger UI)
- **Testing:** PHPUnit (unit + integration), Pest optional
- **Static Analysis:** PHPStan (level 8)
- **Style:** PHPCS (PSR-12) + PHP CS Fixer
- **Debugging:** Xdebug enabled in dev
- **Package:** Composer

## Core Domain Model (initial)
- **Account**: id, email, password_hash, created_at
- **Game**: id, name
- **List** (per game & account): id, account_id, game_id, name, is_published, created_at
- **Tag** (category): id, list_id, name
- **ItemDefinition** (template within a list): id, list_id, name, description?, image_url?, storage_type(enum: boolean|count|text)
- **ItemEntry** (per account per item on a list): id, list_id, item_definition_id, account_id, value (bool|int|string normalized), updated_at
- **ListChange** (approval log): id, list_id, actor_account_id, type(enum:add_item|edit_item|add_tag|edit_tag|etc), payload(json), status(enum:pending|approved|rejected), reviewed_by?, reviewed_at?

> Note: `ItemDefinition` belongs to a List; users propose changes via POST → pending admin approval. `ItemEntry` stores personal ownership/quantity for that account on that list.

## API Requirements
### General
- All GET endpoints return **JSON**.
- POST/PUT/PATCH endpoints that **change a List’s structure** (e.g., add item/tag) create a **pending ListChange**; the List updates only after **admin approval**.
- Authentication: Bearer JWT (dev stub ok, real signing in prod).
- Caching: Redis for frequently read **account-bound lists** and GET endpoints; invalidate on approval.

### Endpoints (v1)
- `POST /v1/auth/register` — create account
- `POST /v1/auth/login` — issue JWT
- `GET /v1/lists` — lists for current account
- `POST /v1/lists` — create list (immediate; no approval needed)
- `GET /v1/lists/{listId}` — list detail incl. items, tags
- `PATCH /v1/lists/{listId}` — metadata changes → create pending change
- `POST /v1/lists/{listId}/items` — propose new item → pending
- `GET /v1/lists/{listId}/items` — list items (+filter by tag, text, ownership)
- `PATCH /v1/lists/{listId}/items/{itemId}` — propose change → pending
- `POST /v1/lists/{listId}/tags` — propose new tag → pending
- `GET /v1/lists/{listId}/tags` — list tags
- `POST /v1/lists/{listId}/entries/{itemId}` — set ownership/quantity/text for current account (no admin approval; personal data)
- `GET /v1/lists/{listId}/entries` — current account’s entries for that list
- `POST /v1/lists/{listId}/publish` — set `is_published=true` (owner only)
- **Admin**  
  - `GET /v1/admin/changes?status=pending`  
  - `POST /v1/admin/changes/{changeId}/approve`  
  - `POST /v1/admin/changes/{changeId}/reject`

### Filters
- Query params: `?tag=...&owned=true|false&search=...`

### OpenAPI
- Keep `/openapi.yaml` authoritative; generate Swagger UI at `/docs`.

## Coding Standards
- PSR-12, strict types, DTOs for request/response.
- Controllers thin → services + repositories.
- Transactions for approvals that materialize changes.
- Validate input (Respect/Valitron or Slim middleware) + return RFC7807 style errors.
- Avoid N+1 via explicit repository methods.
- Use Redis keys with prefixes (`gil:*`), include versioning for easy busting.

## Tests & Quality Gates
- **Unit tests** for services/repositories; **integration tests** for HTTP flows (Slim + DB container).
- **Coverage target:** 85% lines / 75% branches (enforced in CI).
- **PHPStan level 8** must pass.
- **PHPCS** clean and **PHP CS Fixer** dry-run must pass.
- **composer audit** must pass (no critical vulns).
- Optional: **Infection** mutation score ≥ 60 (later).

## Performance & Caching
- Cache `GET /v1/lists/{id}` for account for 60s; bust on approvals and on personal entries update for that account.
- ETags/Last-Modified for big list payloads.

## Security
- Hash passwords with password_hash (argon2id preferred).
- JWT: short-lived access tokens plus refresh tokens with a 14-day TTL inside a 30-day sliding window; rotate refresh tokens on every use, persist only hashed values, and treat reuse as a revocation + security alert.
- Enforce owner-only modifications except admin endpoints.

## Deliverables Order
1. Slim bootstrap + Docker compose + healthcheck.
2. OpenAPI skeleton covering endpoints above.
3. Auth + Lists minimal CRUD.
4. Tags & Items (definitions) + pending change flow.
5. ItemEntry (ownership/qty).
6. Filters + caching layer.
7. Admin approval materialization.
8. Tests + CI + Swagger UI + Xdebug in dev.

## Done = Accepted When
- All endpoints exist & match OpenAPI; admin approval persists changes.
- CI pipeline green with gates above.
- Swagger UI served and valid.
- Docker compose: PHP-FPM, NGINX, DB, Redis all up; Xdebug works in dev.


Backlog Hygiene Checklist
Before starting any backlog item, ensure the following are explicitly documented:

Entry criteria describing why the work matters and how success will be measured.
Exit validation including automated or manual checks to prove the outcome.
Ownership and escalation path for blockers (who to ping, where to document risks).
Links to relevant ADRs, issues, or design notes to preserve architectural context.
Reprioritize aggressively as new information arrives. When a backlog item is promoted to an iteration, flesh out success criteria before starting the work.

Working Agreements
Always leave the repo better than you found it. Capture follow-up tasks immediately so they are never rediscovered the hard way.
Codify learnings. Every iteration should add to the log above and, when appropriate, spawn ADRs or knowledge base entries.
Validate before shipping. Define concrete tests or checks (linting, unit tests, smoke tests) and record their execution in commit/PR notes.
Optimize for clarity. Prefer small, well-documented changes over large, ambiguous ones.
Retrospective Template
When closing an iteration, append a new row to the log and optionally capture richer context using this checklist:

What was the objective?
What worked well?
What caused friction or risk?
What should we change for the next iteration?
What follow-up tasks did we create?
Use the answers to update the backlog and create actionable issues or TODOs in-code where appropriate.


