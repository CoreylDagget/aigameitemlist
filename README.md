# gameitemslist — Item Tracker für Spiele

**Create your list of items for a game.**
Accounts können Listen (pro Spiel) anlegen, Items und Kategorien verwalten (mit Admin-Approval), Besitz/Anzahl pro Account tracken und Listen filtern / veröffentlichen.

## Features (Stand: v0.2)
- **Accounts & Auth** – Registrierung & Login mit Argon2id gehashten Passwörtern, kurzlebigen Access Tokens und Refresh Tokens (14 Tage TTL, maximal 30 Tage Sliding Window) die bei jeder Verwendung rotiert werden. Tokens enthalten Account-ID & E-Mail als Claims für Downstream-Services; Refresh Tokens werden nur gehasht gespeichert und Reuse führt zur Session-Invalidierung inkl. Security-Alert.
- **Listen-Verwaltung** – Accounts können pro Spiel beliebig viele Listen anlegen, Details abrufen, Metadaten-Änderungen als Pending Change einreichen und Listen veröffentlichen.
- **Item-Definitionen** – Items unterstützen Namen, Beschreibung, Bild-URL sowie die Speichertypen **boolean**, **count** und **text**. Strukturänderungen erzeugen `ListChange`-Einträge.
- **Tags/Kategorien** – Tags je Liste inkl. optionaler HEX-Farbe. Neue Tags werden ebenfalls als Pending Change modelliert.
- **Persönliche Einträge** – Accounts erfassen Besitz/Anzahl/Freitext über `/entries`, Werte werden je nach Item-Speichertyp validiert.
- **Filter & Suche** – Item-Listen unterstützen Filter nach Tag, Besitzstatus und Textsuche (Name/Beschreibung).
- **Admin-Review & Caching** – Pending Changes werden über `/v1/admin/changes` (inkl. `.../approve` & `.../reject`) geprüft und materialisiert. Der account-spezifische Listendetail-Cache für `/v1/lists/{id}` (bereitgestellt vom `CachedListDetailService`) wird nach Admin-Entscheidungen sowie Änderungen an persönlichen Einträgen automatisch invalidiert.

## Tech
- PHP 8.3, Slim 4, Composer
- NGINX + PHP-FPM (Docker)
- MariaDB 11 (primärer Datenspeicher) und Redis
- OpenAPI 3.1 + Swagger UI
- PHPUnit, PHPStan, PHPCS/PHP CS Fixer
- Xdebug (dev)

## Getting Started

### Prerequisites
- Docker (Desktop oder Engine) + Docker Compose v2
- Make (optional, vereinfacht Befehle)

### Initial Setup
1. Kopiere die Beispiel-Umgebungsvariablen:
   ```bash
   cp .env.example .env
   ```
   > Hinweis: Aktualisiere in der neuen `.env` mindestens `JWT_SECRET` auf einen sicheren Wert, bevor du Tokens ausstellst.
2. Installiere PHP-Abhängigkeiten (lokal oder im Container):
   ```bash
   make install
   ```
3. Starte die Docker-Entwicklungsumgebung:
   ```bash
   make up
   ```

Der API-Einstiegspunkt wird anschließend unter [http://localhost:8080](http://localhost:8080) bereitgestellt. Das Health-Endpoint erreicht man via:
```bash
make health
```

### Dienste im Stack
- **gil-php**: PHP-FPM Container (Slim App + Composer)
- **gil-nginx**: NGINX Reverse Proxy vor PHP-FPM
- **gil-mariadb**: MariaDB 11.4 mit persistentem Volume `db_data` und automatischem Schema-Bootstrap über `docker/mariadb/initdb.d`
- **gil-redis**: Redis 7 mit persistentem Volume `redis_data`

Zum Herunterfahren aller Dienste:
```bash
make down
```

## Projekt-Dokumentation
- **Planung & Backlog**: siehe [`docs/planning/README.md`](docs/planning/README.md)
  für Vision, Meilensteine, Status je Backlog-Item und Quality-Gate-Nachweise.
- **Architecture Decision Records**: abgelegt unter [`docs/adr/`](docs/adr)
  (z. B. [`0001-documentation-structure.md`](docs/adr/0001-documentation-structure.md),
  [`0002-planning-cadence.md`](docs/adr/0002-planning-cadence.md),
  [`0003-pending-change-workflow.md`](docs/adr/0003-pending-change-workflow.md),
  [`0004-quality-gates-tooling.md`](docs/adr/0004-quality-gates-tooling.md)).

## API-Überblick
- **OpenAPI**: `/openapi.yaml` definiert alle öffentlichen Endpunkte (Health, Auth, Lists, Items, Tags, Entries) inklusive Filter-Query-Parameter und Fehlerobjekten.
- **Swagger UI**: verfügbar unter `http://localhost:8080/docs` (oder via Reverse Proxy `https://api.gameitemslist.local/docs`).
- **Pending Changes**: Strukturelle Änderungen (`ListChange`) werden über `/lists/{id}`- und `/items`-/`/tags`-Endpoints als `202 Accepted` quittiert und warten auf spätere Admin-Approval-Endpunkte.

## Quality Gates & lokale Checks
- **PHPUnit**: `composer test` – Integrationstests folgen mit den Admin-Workflows, Test-Skelett liegt unter [`tests/`](tests/).
- **PHPStan (Level 8)**: `composer phpstan`
- **Coding Standards**: `composer phpcs` (Prüfung) & `composer fix` (PHP CS Fixer für Auto-Fixes).
  _CI-Hinweis_: Der GitHub-Workflow führt `composer phpcs` sowie den PHP CS Fixer Dry-Run wieder regulär aus. Bitte Verstöße lokal bereinigen,
  bevor Änderungen gemergt werden.
- **Security Audit**: `composer audit` sobald Abhängigkeiten vor dem Release eingefroren sind.
- **Checkliste**: Vor jedem Merge alle oben genannten Kommandos ausführen und Ergebnisse im PR festhalten; Abweichungen (z. B. fehlende Tests) müssen als Follow-up dokumentiert werden.

> Hinweis: Eine frühere PHPCS-Warnung („file should declare new symbols and cause no side effects“) im `CoverageGuardCommandTest` entstand durch ein `require_once` auf Datei-Ebene. Seit das Coverage-Helper-Skript im `setUpBeforeClass()` geladen wird, laufen PHPCS und der PHP CS Fixer in CI wieder sauber.

## Health Endpoint
`GET /health` liefert eine JSON-Antwort mit Service-Status, um Deployments und lokale Setups schnell prüfen zu können. Dieser Endpoint wird von Docker Compose beim lokalen Smoke-Test genutzt und dient später als Basis für Monitoring Checks.

## OpenAPI & Dokumentation
- Die aktuelle API-Spezifikation liegt unter [`/openapi.yaml`](openapi.yaml) und beschreibt alle v1-Endpunkte inkl. Fehlerobjekten.
- Eine Swagger-UI mit der Spezifikation ist unter `http://localhost:8080/docs` (bzw. hinter NGINX unter `https://api.gameitemslist.local/docs`) verfügbar.
- Änderungen an Endpunkten **müssen zuerst** in der OpenAPI-Datei vorgenommen und anschließend implementiert werden, damit das Contract-First-Vorgehen gewährleistet ist.
