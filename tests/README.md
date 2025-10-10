# Tests

Die PHPUnit-Suite wird mit den Admin-Workflows (Backlog-Items B8/B9) erweitert.
Aktuell existiert ein Skelett-Namespace (`GameItemsList\Tests`) sowie Composer-
Scripts für die Ausführung.

## Lokale Kommandos

- `composer test` – startet PHPUnit (Konfiguration folgt, sobald erste Module
  integriert sind).
- `composer phpstan` – statische Analyse auf Level 8, deckt Tests & Src ab.
- `composer phpcs` – Stilprüfung (PSR-12). Für Auto-Fixes `composer fix`.

## TODOs für die nächste Iteration

- Auth- und Listen-Integrationstests inkl. JWT-Flow abdecken.
- Fixtures/Fakes für `ListChange`-Review erstellen, sobald Admin-Endpunkte
  implementiert sind.
- Coverage- und Mutation-Targets im CI hinterlegen (siehe ADR-0004).
