# ADR 0001: Documentation Structure Baseline

- Status: Accepted
- Date: 2024-03-XX

## Context

The project currently contains only a top-level README and the mission/standards
in `AGENTS.md`. We need a durable place to collect planning artifacts, backlog
state, and future architectural decisions while we bootstrap the application.
Without a documented structure, new contributors will struggle to locate the
latest plans or understand how documentation should evolve.

## Decision

Establish a `docs/` directory with sub-folders for:

- `docs/planning` — Living backlog and iteration planning notes.
- `docs/adr` — Architecture Decision Records using a sequential numbering
  scheme (`NNNN-title.md`).

All future documentation (plans, ADRs, design notes) should live under this
hierarchy to keep the repository organized and make it clear where updates
belong. Each ADR will follow the "Status / Context / Decision / Consequences"
format for consistency.

## Consequences

- Contributors have an obvious location to add planning updates or new ADRs.
- We can grow documentation incrementally without cluttering the root of the
  repository.
- Reviewers can quickly locate the latest decisions when evaluating changes.
- We must keep the ADR numbering sequential; add new records as `NNNN` with
  zero-padding to maintain lexical order.

