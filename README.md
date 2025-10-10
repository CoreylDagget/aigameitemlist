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

