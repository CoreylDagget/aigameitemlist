# Next Steps Overview

This summary distills the immediate expectations from `AGENTS.md` and the planning backlog and turns them into an actionable starter plan for the next engineering iteration.

## Immediate Focus (Iteration 1)

| Priority | Backlog Ref | Outcome | Entry Criteria | Exit Validation | Notes |
|----------|-------------|---------|----------------|-----------------|-------|
| 🚀 | B7 – Filters & Caching Strategy | Deliver tag/owned/search filters with Redis-backed caching and observable busting rules. | B1–B6 deployed; Redis infrastructure smoke-tested; filter requirements validated with product. | Integration tests cover tag + ownership filters; cache TTL fixed at 60s with instrumentation; invalidation triggered on approvals and `ItemEntry` mutations. | Pair with QA to expand cache-invalidation regression suite; document metrics/alerts alongside [Backlog B7](README.md#backlog-b7). |
| 🚀 | B8 – Admin Approval Materialization | Ship approve/reject endpoints that materialize pending `ListChange` records transactionally and keep caches coherent. | B7 cache hooks available; admin UX copy/mocks finalized; data migrations rehearsed. | Approvals apply changes atomically; rejections persist audit trail; caches bust on approval; end-to-end tests cover happy/sad paths. | Coordinate with security on reviewer permissions; capture rollback steps in runbook referencing [Backlog B8](README.md#backlog-b8). |

## Progress Update (Iteration 1)
- ✅ **Platform foundations (B1–B6) complete**: Environment bootstrap, OpenAPI contract, auth flows, list CRUD/publishing, tag + item definition workflows, and personal entry tracking are merged and validated. These building blocks unlocked cached list reads and the approval ledger required for downstream work.
- 🚧 **Filters & caching (B7) active**: Engineering is implementing combined tag/owned/search filters while wiring Redis TTLs, cache busting hooks, and observability needed for fast diagnosis. Integration scenarios for invalidation are being authored alongside QA.
- ⏳ **Admin approval materialization (B8) queued**: Pending change application flows are prepped, with UX and migration assets ready to go once B7 validates the cache hooks that approvals must call into.

## Execution Guardrails

- **Approval Workflow (B8 dependency)**: All structural list edits must continue to land as pending `ListChange` records that only materialize after approval. As we wire the approval endpoints, require transactional tests that confirm audit logging, reviewer permissions, and cache busting occur together.
- **Architecture & Style**: Controllers should delegate to services and repositories, enforce strict types, and follow PSR-12 conventions. Prefer DTOs for request/response payloads.
- **Caching Plan (B7 scope)**: Redis caches read-heavy list detail responses per account for ~60 seconds. Build automated tests that invalidate caches on approvals and personal `ItemEntry` updates, and record metrics/alerts before cutting release candidates.
- **Auth Session Policy**: Enforce the 14-day refresh token TTL (max 30-day sliding window), rotate tokens on every refresh, persist only hashed values, and treat reuse as a security event that invalidates the token family.
- **Quality Gates**: Budget time for PHPUnit (≥85% lines / 75% branches), PHPStan level 8, PHPCS, PHP CS Fixer, and `composer audit`. Document which checks run for each milestone as tooling comes online.

## Open Questions to Resolve Early

1. ✅ **Database baseline decided**: MySQL/MariaDB is the default datastore for new work; Postgres remains a compatible fallback for teams already running it. Adjust iteration plans, Docker defaults, and onboarding docs to match.
2. ✅ **Refresh token policy locked**: 14-day TTL, rotation on use, hashed persistence, and reuse detection with automatic family revocation + alerting.
3. Determine whether Swagger UI will be exposed in production or limited to development environments to set deployment expectations.

Record decisions as ADRs and update both this summary and the planning backlog once resolved.
