# Tests

Die PHPUnit-Suite deckt bereits die zentralen Domänenobjekte (Accounts, Spiele
und Listen-Aggregate) sowie die zugehörigen Application-Services ab. Admin-
Workflows (Approve/Reject) und die zugehörigen HTTP-Actions werden über Fakes
gegen die Service-Schicht getestet, inklusive JWT-Security und Middleware.
Darüber hinaus verifizieren dedizierte Tests die Cache-Schicht für Listendetail-
Antworten und die Infrastruktur rund um Coverage- und Repository-Utilities.

## Lokale Kommandos

- `composer test` – startet PHPUnit (Konfiguration folgt, sobald erste Module
  integriert sind) und schreibt zusätzlich einen Coverage-Report nach
  `var/phpunit/coverage.txt`.
- `composer coverage-check` – wertet den Coverage-Report aus und erzwingt
  aktuell mindestens 80 % Line-Coverage und 75 % Branch-Coverage (Branches
  können aufgrund von PCOV derzeit optional übersprungen werden).
  Details zum CLI-Tool lassen sich über `php tools/coverage-guard.php --help`
  abrufen.
- `composer phpstan` – statische Analyse auf Level 8, deckt Tests & Src ab.
- `composer phpcs` – Stilprüfung (PSR-12). Für Auto-Fixes `composer fix`.

## Nächste Testlücken

- End-to-End- bzw. Integrationstests für den kompletten Slim-Stack (HTTP →
  Container → DB/Redis) fehlen noch; aktuell wird alles über Service-Fakes
  abgedeckt.
- Persistence-Adapter jenseits des `PdoItemDefinitionRepository` benötigen
  ebenfalls Integrationstests gegen die echte Datenbank.
