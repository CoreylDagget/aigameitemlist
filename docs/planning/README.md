# Planning & Delivery Backlog

This document consolidates everything currently known about the **gameitemslist**
product and tracks the backlog in a single, high-visibility place. It is based
on the mission and standards captured in `AGENTS.md` and the public README.

## Vision Check

- **Product mission**: Build a web app + API that lets accounts manage
  per-game item lists, propose structural changes that require admin approval,
  and track personal ownership or quantities.
- **Success definition**: All endpoints described in the spec exist, enforce
  approval workflows, are documented in OpenAPI, and run inside the prescribed
  Docker stack with quality gates (tests, static analysis, linting) at green.
- **Key architectural drivers**: Slim 4 + PHP 8.3, MySQL/MariaDB (primary) with Postgres compatibility, Redis, clear
  separation of controllers/services/repositories, PSR-12 style compliance,
  caching for read-heavy list views, JWT-secured access, and admin review of
  structural changes.

## Milestone Roadmap (high-level)

1. **Bootstrap & Infrastructure** – Slim skeleton, Docker compose, healthcheck.
2. **API Contract** – Authoritative `openapi.yaml` + Swagger UI.
3. **Auth & Core Lists** – Registration/login, list CRUD, publishing stub.
4. **Tags & Item Definitions** – Pending change flow for structural edits.
5. **Personal Item Entries** – Ownership tracking per account.
6. **Filters & Caching** – Tag/search filters, Redis-backed caching + busting.
7. **Admin Approval Materialization** – Approve/reject flows, transactions.
8. **Quality Gates & DX** – Automated tests, static analysis, CI, Xdebug.

Each milestone below is broken into backlog items with explicit entry/exit
criteria, owners, and reference links.

## Backlog

| ID | Item | Status | Description | Entry Criteria | Exit Validation | Owner / Escalation | References |
|----|------|--------|-------------|----------------|-----------------|--------------------|------------|
| B1 | Development Environment Baseline | ✅ Done | Stand up Slim 4 skeleton with Docker (PHP-FPM, NGINX, MySQL/MariaDB default with Postgres-compatible option, Redis) and health endpoint. | Specs in `AGENTS.md` reviewed; Docker + PHP versions confirmed with DevOps. | `docker compose up` succeeds; `/health` returns 200; README updated with run instructions. | Primary: Backend Lead; Escalation: DevOps Lead via `#dev-env`. | AGENTS.md Deliverables §1. |
| B2 | Authoritative OpenAPI Contract | ✅ Done | Draft `openapi.yaml` covering all v1 endpoints including admin actions and error models. | Milestone B1 available; product + backend sign off on endpoint list. | Spec lint passes; hosted at `/docs` via Swagger UI; version tagged in repo. | Primary: API Architect; Escalation: Product Manager via weekly sync. | AGENTS.md API Requirements; README features. |
| B3 | Auth & Account Lifecycle | ✅ Done | Implement register/login endpoints issuing JWT, persistence in MySQL/MariaDB (with Postgres compatibility). | B1 complete; OpenAPI endpoints for auth finalized. | PHPUnit integration tests for auth pass; JWT secrets configurable; PHPStan level 8 clean. | Primary: Backend Lead; Escalation: Security Champion (`@sec-lead`). | AGENTS.md Mission; Security; ADR-0003. |
| B4 | List CRUD & Publishing | ✅ Done | Implement `/v1/lists` CRUD and publish flow aligned with spec. | B2 approved; auth flow available. | GET/POST/PATCH endpoints match OpenAPI; publishing toggles `is_published`; cache invalidation hooks stubbed. | Primary: Backend Lead; Escalation: Product Manager. | AGENTS.md Endpoints; Deliverables 3. |
| B5 | Tags & Item Definition Changes | ✅ Done | Enable creating/editing tags and item definitions with pending `ListChange` records. | B4 deployed to dev; change review UX defined. | Pending changes recorded; admin queues reflect new entries; unit tests cover service layer. | Primary: Feature Team A; Escalation: Admin SME. | AGENTS.md Items & Tags; Approval log; ADR-0003. |
| B6 | Item Entries & Ownership | ✅ Done | Provide `/entries` endpoints storing per-account ownership/quantity/text. | Data model for lists/items stable; caching strategy drafted. | Personal entries mutate immediately; responses cached per account; PHPUnit coverage ≥ 85% for module. | Primary: Feature Team B; Escalation: Data Steward. | AGENTS.md ItemEntry; Performance notes. |
| B7 | Filters & Caching Strategy | 🚧 In Arbeit | Implement tag/owned/search filters and Redis caching + busting rules. | Redis infra validated in staging; instrumentation plan ready. | Filter queries return expected results; cache TTL 60s; invalidation tested in integration suite. | Primary: Performance Squad; Escalation: Infra Guild. | AGENTS.md Filters; Performance & Caching. |
| B8 | Admin Approval Materialization | ⏳ Offen | Build approve/reject endpoints applying pending changes transactionally. | Admin UX mockups signed off; data migration scripts ready. | Approved changes update canonical data; rejected changes audit trail intact; transactional tests pass. | Primary: Feature Team A; Escalation: Backend Lead. | AGENTS.md Admin Endpoints; Transactions. |
| B9 | Quality Gates & CI | ⏳ Offen | Configure PHPUnit, PHPStan lvl 8, PHPCS, PHP CS Fixer, composer audit, CI workflows, and coverage thresholds. | Core features merged; tooling requirements reviewed. | CI green; coverage ≥ 85% lines / 75% branches; audit clean or waivers documented. | Primary: QA Lead; Escalation: Engineering Manager. | AGENTS.md Tests & Quality Gates; ADR-0004. |
| B10 | Developer Experience Enhancements | ⏳ Offen | Enable Xdebug in dev, document debugging workflow, add DX scripts. | Base tooling stable; developer feedback collected. | Xdebug toggled via env; docs updated; onboarding feedback cycle complete. | Primary: DevEx Advocate; Escalation: Engineering Manager. | AGENTS.md Tech Stack; Working Agreements. |
| B11 | Refresh Token Rotation & Hardening | ⏳ Offen | Implement refresh token issuance/storage with 14-day TTL (within 30-day sliding window), rotation on use, reuse detection, and revocation plumbing. | Policy ratified in planning docs; security review availability confirmed. | Integration tests cover rotation/revocation; hashed storage enforced; reuse triggers invalidation + audit log. | Primary: Backend Lead; Escalation: Security Champion (`@sec-lead`). | AGENTS.md Security; README Accounts & Auth. |

### Backlog Maintenance

- **Ownership cadence**: Owners review status weekly; escalate blockers during
  Monday stand-up.
- **Risk tracking**: Document blockers as GitHub issues linked from the table.
- **Reprioritization**: If a downstream item becomes critical, annotate the
  table with `🚨` and move it higher, recording rationale in commit messages.

## Known Open Questions

1. ✅ **Database baseline decided**: MySQL/MariaDB is now the primary datastore
   for new work, with PostgreSQL remaining a supported/compatible option for
   teams that already provisioned it. Update iteration plans, docker compose
   defaults, and onboarding docs accordingly.
2. ✅ **Refresh token policy locked**: Refresh tokens live for 14 days within a 30-day sliding session window, rotate on every use, and must be stored hashed with reuse detection that revokes the token family and alerts the security channel.
3. Determine hosting strategy for Swagger UI in production vs. dev-only.

Owners should track answers in new ADRs or inline updates here.

## Quality Gate Status (lokal)

| Gate | Command | Status | Notizen |
|------|---------|--------|---------|
| Tests | `composer test` | 🚧 Geplant | Test-Skelett angelegt, Szenarien folgen nach Admin-Workflow (siehe B8/B9). |
| PHPStan | `composer phpstan` | ✅ Konfiguriert | Läuft lokal gegen `src/` & `tests/` sobald Use-Cases ergänzt werden. |
| PHPCS | `composer phpcs` | ✅ Konfiguriert | PSR-12 Konfiguration aktiv; Fixes via `composer fix`. |
| Composer Audit | `composer audit` | ⏳ Offen | Wird vor Release in CI integriert, momentan manuell bei Dependency-Updates. |

## Change Log

| Date | Change | Author |
|------|--------|--------|
| 2024-03-XX | Initial backlog + planning baseline. | AI Agent |
| 2024-04-XX | Backlog-Status aktualisiert, Quality-Gate-Tabelle ergänzt. | AI Agent |

