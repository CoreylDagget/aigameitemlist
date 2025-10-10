# Next Steps Overview

This summary distills the immediate expectations from `AGENTS.md` and the planning backlog and turns them into an actionable starter plan for the next engineering iteration.

## Immediate Focus (Iteration 0)

| Priority | Backlog Ref | Outcome | Entry Criteria | Exit Validation | Notes |
|----------|-------------|---------|----------------|-----------------|-------|
| 🚀 | B1 – Development Environment Baseline | Slim 4 skeleton, Docker compose stack (PHP-FPM, NGINX, Postgres, Redis) with `/health` endpoint. | AGENTS.md deliverables reviewed; environment prerequisites confirmed with DevOps. | `docker compose up` boots all services; `/health` returns 200; README documents run steps. | Capture follow-up bugs or infra gaps in backlog as soon as discovered. |
| 🚀 | B2 – Authoritative OpenAPI Contract | `openapi.yaml` that enumerates all v1 endpoints (auth, lists, tags, items, entries, admin) and error models. | B1 merged; endpoint scope validated with product + backend leads. | Spec lint passes; Swagger UI available at `/docs`; version tagged in repo. | Use the contract to drive early stub controllers once approved. |
| 🚀 | B3/B4 Foundations – Auth & Lists | JWT-backed register/login plus `/v1/lists` CRUD and publish workflow aligned to the contract. | B2 approved; database schema for accounts/lists finalized. | PHPUnit integration tests pass; PHPStan level 8 clean; publish toggles `is_published` with cache invalidation hooks stubbed. | Begin with service/repository scaffolding to keep controllers thin. |

## Progress Update (Iteration 0)
- ✅ **B1** infrastructure scaffolded: Docker Compose stack (PHP-FPM, NGINX, Postgres, Redis), Slim 4 bootstrap, health endpoint, and README onboarding instructions are now committed. Follow-up work should validate the stack via CI once additional tooling is configured.

## Execution Guardrails

- **Approval Workflow**: Any structural list change (items, tags, metadata) must create a pending `ListChange` record requiring admin approval before materializing. Stub the persistence model during B1/B2 to keep later work unblocked.
- **Architecture & Style**: Controllers should delegate to services and repositories, enforce strict types, and follow PSR-12 conventions. Prefer DTOs for request/response payloads.
- **Caching Plan**: Redis caches read-heavy list detail responses per account for ~60 seconds. Remember to invalidate caches on approvals and personal `ItemEntry` updates.
- **Quality Gates**: Budget time for PHPUnit (≥85% lines / 75% branches), PHPStan level 8, PHPCS, PHP CS Fixer, and `composer audit`. Document which checks run for each milestone as tooling comes online.

## Open Questions to Resolve Early

1. Confirm whether MySQL needs first-class support in addition to Postgres or if Postgres-only is acceptable for the initial milestone.
2. Decide the scope of JWT refresh tokens for the first release (stub vs. full refresh flow) so auth contracts remain stable.
3. Determine whether Swagger UI will be exposed in production or limited to development environments to set deployment expectations.

Record decisions as ADRs and update both this summary and the planning backlog once resolved.
