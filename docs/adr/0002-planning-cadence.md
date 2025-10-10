# ADR 0002: Planning Cadence & Backlog Governance

- Status: Accepted
- Date: 2024-03-XX

## Context

The mission brief emphasizes rigorous backlog hygiene: every backlog item needs
entry criteria, exit validation, and clear ownership. Prior to this ADR the
repository had no documented process describing how frequently the backlog is
reviewed, who updates it, or how to capture follow-up tasks.

## Decision

Adopt the following planning cadence and governance rules:

1. Maintain a single source of truth in `docs/planning/README.md` containing the
   prioritized backlog with required metadata (entry criteria, exit validation,
   owners, references).
2. Review backlog status during a weekly Monday stand-up. Item owners update the
   document before the meeting and surface blockers or reprioritization needs.
3. Record new risks, follow-up tasks, or architectural learnings as GitHub
   issues and, when appropriate, as new ADRs linked from the backlog table.
4. When a milestone completes, append a row to the planning change log noting
   date, outcome, and follow-ups.

## Consequences

- Contributors know exactly where to check the current plan and when it is
  reviewed.
- Risks are documented promptly, reducing rediscovery and churn.
- The backlog stays aligned with architectural decisions because links to ADRs
  and issues are required for significant items.
- Planning overhead increases slightly, but the clarity gained should reduce
  thrash and duplicated effort.

