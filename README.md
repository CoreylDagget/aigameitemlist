# gameitemslist — Item Tracker für Spiele

**Create your list of items for a game.**
Accounts können Listen (pro Spiel) anlegen, Items und Kategorien verwalten (mit Admin-Approval), Besitz/Anzahl pro Account tracken und Listen filtern / veröffentlichen.

## Features
- Listen (pro Spiel) je Account, mehrfach möglich
- Items mit Name, optional Bild & Beschreibung, speicherbar als: **ja/nein**, **Stückzahl**, **Freitext**
- Tags/Kategorien pro Liste
- Besitz/Anzahl pro Account (ItemEntry)
- Filter: Kategorien, vorhanden (owned), Namenssuche
- Änderungen an Listen-Struktur (Items/Tags/Metadaten) benötigen **Admin-Approval**
- Listen können veröffentlicht werden

## Tech
- PHP 8.3, Slim 4, Composer
- NGINX + PHP-FPM (Docker)
- PostgreSQL (oder MySQL), Redis
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
- **gil-postgres**: PostgreSQL 16 mit persistentem Volume `db_data`
- **gil-redis**: Redis 7 mit persistentem Volume `redis_data`

Zum Herunterfahren aller Dienste:
```bash
make down
```

## Projekt-Dokumentation
- **Planung & Backlog**: siehe [`docs/planning/README.md`](docs/planning/README.md)
  für Vision, Meilensteine und priorisierte Aufgaben inklusive Entry-/Exit-
  Kriterien.
- **Architecture Decision Records**: abgelegt unter [`docs/adr/`](docs/adr)
  (z. B. [`0001-documentation-structure.md`](docs/adr/0001-documentation-structure.md),
  [`0002-planning-cadence.md`](docs/adr/0002-planning-cadence.md)).

## Health Endpoint
`GET /health` liefert eine JSON-Antwort mit Service-Status, um Deployments und lokale Setups schnell prüfen zu können. Dieser Endpoint wird von Docker Compose beim lokalen Smoke-Test genutzt und dient später als Basis für Monitoring Checks.

## OpenAPI & Dokumentation
- Die aktuelle API-Spezifikation liegt unter [`/openapi.yaml`](openapi.yaml) und beschreibt alle v1-Endpunkte inkl. Fehlerobjekten.
- Eine Swagger-UI mit der Spezifikation ist unter `http://localhost:8080/docs` (bzw. hinter NGINX unter `https://api.gameitemslist.local/docs`) verfügbar.
- Änderungen an Endpunkten **müssen zuerst** in der OpenAPI-Datei vorgenommen und anschließend implementiert werden, damit das Contract-First-Vorgehen gewährleistet ist.
