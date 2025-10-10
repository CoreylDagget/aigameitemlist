# ADR 0003: Pending Change Workflow für Listen-Struktur

- Status: Accepted
- Date: 2024-04-XX

## Context

Item- und Tag-Änderungen an einer Liste benötigen laut Produktvision eine
Admin-Freigabe, bevor sie in den Kanon übernommen werden. Die ersten
Implementierungen für Listen, Items, Tags und Einträge sind vorhanden, die
Review-Endpunkte jedoch noch nicht. Wir müssen trotzdem sicherstellen, dass
Nutzeränderungen nachvollziehbar gespeichert werden, ohne sofort die
kanonischen Tabellen zu mutieren.

## Decision

- Strukturelle Änderungen erzeugen `ListChange`-Einträge mit Status `pending`
  und JSON-Payload. Die Domain definiert zulässige Typen (`add_item`,
  `edit_item`, `add_tag`, `list_metadata`, …) sowie Statuswerte
  (`pending`, `approved`, `rejected`).
- Die Application-Services (`ListService`, `ItemDefinitionService`,
  `TagService`) kapseln Validierung & Normalisierung und rufen anschließend das
  `ListChangeRepository` auf, das Daten in der Tabelle `list_changes`
  persistiert.
- HTTP-Actions geben `202 Accepted` zurück und liefern die gespeicherten
  Change-Daten, damit Frontends eine Warteschlange/Review-UI darstellen können.
- Solange kein Admin-Flow existiert, ändern diese Endpunkte keine
  produktiven Entities (Listen, Items, Tags). Das reduziert Inkonsistenzen und
  lässt die Admin-Implementierung später deterministisch die Payload anwenden.

## Consequences

- Wir besitzen ein vollständiges Audit-Log für vorgeschlagene Änderungen und
  können Admin-Review nachrüsten, ohne bestehende Endpoints anzupassen.
- Payloads sind bewusst generisch (JSON), wodurch wir künftige Felder ohne
  Schema-Migration erfassen können – mit dem Trade-off, dass spätere Validatoren
  Payload-strukturell prüfen müssen.
- Clients müssen Pending Changes separat pollen/anzeigen, bis der Admin-Flow
  umgesetzt ist. Wir dokumentieren diesen Zwischenstand im README & Backlog.
