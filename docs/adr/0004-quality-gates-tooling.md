# ADR 0004: Quality Gates & Tooling Setup

- Status: Accepted
- Date: 2024-04-XX

## Context

Die Missionsvorgaben verlangen automatisierte Quality Gates (Tests, PHPStan,
Code Style, Security Audit). Bisher waren lediglich Makefile-Targets und ein
Test-Platzhalter vorhanden. Um die nächsten Features iterieren zu können, muss
klar dokumentiert sein, welche Kommandos verpflichtend sind und wie sie in CI
integriert werden.

## Decision

- Hinterlegen der benötigten Werkzeuge als Composer-Skripte:
  - `composer test` startet PHPUnit (Konfiguration folgt mit den Admin-Flows).
  - `composer phpstan` analysiert den Quellcode auf Level 8.
  - `composer phpcs` prüft PSR-12; `composer fix` nutzt PHP CS Fixer für
    Auto-Korrekturen.
  - `composer audit` wird für Security-Checks verwendet, sobald Abhängigkeiten
    eingefroren sind.
- Dokumentation der Gates im README sowie Fortschritts-Tracking im Planning
  Backlog, inklusive Status-Tabelle für manuelle Reviews.
- Pull Requests müssen die Ausführung (oder begründete Ausnahme) dieser Gates
  dokumentieren; fehlende Checks werden als Follow-up-Tasks erfasst.
- CI-Pipeline integriert die obigen Skripte, sobald Admin-Workflow & Tests
  vorliegen (Backlog Items B8/B9).

## Consequences

- Entwickler haben einheitliche Einstiegspunkte für lokale Checks und können
  Abweichungen früh erkennen.
- Solange PHPUnit-Tests noch aufgebaut werden, dokumentieren wir bewusst den
  offenen Status, damit technische Schulden transparent bleiben.
- Die CI-Konfiguration kann die Composer-Skripte ohne weitere Wrapper
  verwenden, wodurch Wartungskosten sinken.
