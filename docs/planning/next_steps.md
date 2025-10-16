# Next Steps Overview

This summary distills the immediate expectations from `AGENTS.md` and the planning backlog and turns them into an actionable starter plan for the next engineering iteration.

## Immediate Focus (Iteration 1)

| Priority | Backlog Ref | Outcome | Entry Criteria | Exit Validation | Notes |
|----------|-------------|---------|----------------|-----------------|-------|
| ✅ | B7 – Filters & Caching Strategy | Deliver tag/owned/search filters with Redis-backed caching and observable busting rules. | B1–B6 deployed; Redis infrastructure smoke-tested; filter requirements validated with product. | Integration tests cover tag + ownership filters; cache TTL fixed at 60s with instrumentation; invalidation triggered on approvals and `ItemEntry` mutations. | Cache observer metrics committed with unit coverage; align with QA on regression coverage referencing [Backlog B7](README.md#backlog-b7). |
| ✅ | B8 – Admin Approval Materialization | Ship approve/reject endpoints that materialize pending `ListChange` records transactionally and keep caches coherent. | B7 cache hooks available; admin UX copy/mocks finalized; data migrations rehearsed. | Approvals apply changes atomically; rejections persist audit trail; caches bust on approval; end-to-end tests cover happy/sad paths. | Coordinated reviewer permissions and captured rollback steps in runbook referencing [Backlog B8](README.md#backlog-b8). |
| 🚀 | B9 – Quality Gates & CI | Establish automated PHPUnit, PHPStan, PHPCS, PHP CS Fixer, and composer audit runs wired into CI with coverage enforcement. | Admin workflows stabilized (B8) and tooling requirements reviewed with QA. | CI pipeline green with ≥80% line / 75% branch coverage, static analysis & linters passing, audit clean or waivers filed. | GitHub Actions workflow `CI` provisioniert, läuft mit PHP 8.3 + pcov; Abstimmung mit QA bzgl. fehlender lokaler Composer-Installation offen. Referenz: [Backlog B9](README.md#backlog-b9). |

## Progress Update (Iteration 1)
- ✅ **Platform foundations (B1–B6) complete**: Environment bootstrap, OpenAPI contract, auth flows, list CRUD/publishing, tag + item definition workflows, and personal entry tracking are merged and validated. These building blocks unlocked cached list reads and the approval ledger required for downstream work.
- ✅ **Filters & caching (B7) delivered**: Combined tag/owned/search filters now ship with Redis-backed caching, observer instrumentation, and integration coverage for cache busting on personal entry changes.
- ✅ **Admin approval materialization (B8) delivered**: Approve/reject endpoints now apply pending changes transactionally, enforce reviewer separation, and bust list detail caches for affected owners.
- 🚧 **Quality gates & CI (B9) underway**: CI-Workflow erstellt (`.github/workflows/ci.yml`) und bindet PHPUnit (mit Coverage-Limits), PHPStan, `composer audit` sowie wieder aktivierte PHPCS- und PHP-CS-Fixer-Dry-Run-Schritte ein. Lokale Verifikation bleibt blockiert, bis Composer-Abhängigkeiten installiert werden können. Coverage-Grenzen (80 % Lines / 75 % Branches) werden weiterhin per `tools/coverage-guard.php` automatisiert geprüft.

## Execution Guardrails

- **Approval Workflow (B8)**: Keep structural edits flowing through pending `ListChange` records. Maintain transactional tests covering audit logging, reviewer separation, and cache invalidation on approval.
- **Architecture & Style**: Controllers should delegate to services and repositories, enforce strict types, and follow PSR-12 conventions. Prefer DTOs for request/response payloads.
- **Caching Plan (B7 scope)**: Redis caches read-heavy list detail responses per account for ~60 seconds. Build automated tests that invalidate caches on approvals and personal `ItemEntry` updates, and record metrics/alerts before cutting release candidates.
- **Auth Session Policy**: Enforce the 14-day refresh token TTL (max 30-day sliding window), rotate tokens on every refresh, persist only hashed values, and treat reuse as a security event that invalidates the token family.
- **Quality Gates**: Budget time for PHPUnit (≥80% lines / 75% branches), PHPStan level 8, PHPCS, PHP CS Fixer, and `composer audit`. Document which checks run for each milestone as tooling comes online.

## Open Questions to Resolve Early

1. ✅ **Database baseline decided**: MySQL/MariaDB is the default datastore for new work; Postgres remains a compatible fallback for teams already running it. Adjust iteration plans, Docker defaults, and onboarding docs to match.
2. ✅ **Refresh token policy locked**: 14-day TTL, rotation on use, hashed persistence, and reuse detection with automatic family revocation + alerting.
3. Determine whether Swagger UI will be exposed in production or limited to development environments to set deployment expectations.

Record decisions as ADRs and update both this summary and the planning backlog once resolved.
