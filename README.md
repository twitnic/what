# StadtNews

Mandantenfähige Nachrichtenplattform für Vereine, Geschäfte und Stadtverwaltung. Laravel 13, PHP 8.4+, PostgreSQL; Blade-Oberfläche ohne Node-Build.

Die Oberfläche verwendet lokal ausgelieferte variable Open-Sans-Schriften (normal und kursiv, Gewicht 300–800) aus dem [offiziellen Open-Sans-Repository](https://github.com/googlefonts/opensans). Schriftdateien und SIL-OFL-Lizenz liegen unter `public/fonts/open-sans`; beim Seitenaufruf werden keine externen Font-Dienste kontaktiert.

Die Docker-Infrastruktur liegt in einem unabhängigen Repository außerhalb dieses Checkouts. Dieses Repository enthält ausschließlich Anwendung, Konfiguration, Tests und CI. Remote: `git@github.com:twitnic/what.git`.

## Installation ohne Docker

```sh
composer install
cp .env.example .env
# Datenbank, Mail, Redis und APP_URL in .env auf die Umgebung einstellen.
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Für eine reine lokale SQLite-Installation `DB_CONNECTION=sqlite`, `DB_DATABASE` auf eine vorhandene SQLite-Datei setzen und `CACHE_STORE=database`, `QUEUE_CONNECTION=database` verwenden. Die Standard-`.env.example` passt zur getrennten Docker-Umgebung.

`php artisan db:seed --class=DemoSeeder` legt lokal Beispielinhalte an und zeigt ein zufällig erzeugtes Demo-Passwort. Der Seeder läuft nur in `local`/`testing`. `php artisan stadtnews:admin person@example.com` vergibt Plattformmoderation; `--revoke` entfernt sie.

`CITY_NAME` setzt den Ortsnamen (Standard Musterstadt). Daten werden in UTC gespeichert; Redaktionsformulare verwenden Europe/Berlin, API-Zeitangaben enthalten einen expliziten Offset.

## Rechte

| Aktion | Owner | Admin | Editor | Plattformmoderator |
| --- | --- | --- | --- | --- |
| Beiträge der Organisation lesen/erstellen | ja | ja | ja | nur als Mitglied |
| Eigene Beiträge ändern/löschen | ja | ja | ja | nur als Mitglied |
| Fremde Beiträge ändern/löschen | ja | ja | nein | nur als Mitglied |
| Profil ändern / Beitrag anheften | ja | ja | nein | nur als Mitglied |
| Mitglieder hinzufügen, Rollen ändern, entfernen | ja | nein | nein | nur als Owner |
| Organisation verifizieren / Meldungen moderieren | nein | nein | nein | ja |

Eine Person kann verschiedenen Organisationen in unterschiedlichen Rollen angehören. Der Owner kann sich nicht versehentlich entfernen oder herabstufen. Moderation blendet Beiträge aus; normale Bearbeitung hebt diese Sperre nicht auf.

## API v1

Öffentliche GET-Endpunkte:

- `/api/v1/posts`: paginierter sichtbarer Feed; Parameter `q`, `type`, `category`, `page`.
- `/api/v1/posts/{id}`: einzelner sichtbarer Beitrag.
- `/api/v1/organizations`: paginierte Organisationsliste.
- `/api/v1/categories`: Kategorien.
- `/api/v1/organizations/{slug}/posts`: sichtbare Beiträge einer Organisation.

Schreiben mit `Authorization: Bearer TOKEN`, `Accept: application/json` und der Fähigkeit `posts:write`:

- `POST /api/v1/organizations/{slug}/posts`: Beitrag erstellen (201).
- `PUT /api/v1/organizations/{slug}/posts/{id}`: vollständige Beitragsdaten ersetzen (200).
- `DELETE /api/v1/organizations/{slug}/posts/{id}`: Beitrag löschen (204).

Tokens in der Redaktion erstellen oder widerrufen. Sie gelten 90 Tage; Mitgliedschaften und Rollen werden bei jeder Schreiboperation erneut geprüft. Öffentliche Leser benötigen keinen Token. API-Limit: 120 Anfragen/Minute je Konto bzw. IP. Die Schreib-API ist für Tokens vorgesehen; keine Token-Anmeldung per öffentlichem Login-Endpunkt.

Beispielpayload:

```json
{
  "type": "event",
  "title": "Musik im Bahnhof",
  "text": "Ein Abend mit lokalen Bands.",
  "published_at": "2026-10-07T10:00:00+02:00",
  "expires_at": "2026-10-11T00:00:00+02:00",
  "starts_at": "2026-10-10T19:00:00+02:00",
  "ends_at": "2026-10-10T23:00:00+02:00",
  "location": "Alter Bahnhof",
  "categories": [1],
  "pinned": false
}
```

Pflicht: `type` und `text` (max. 1.000 Zeichen). Bei `event` außerdem `title`, `starts_at`, `location`. Typen: `news`, `event`, `warning`, `traffic`, `city`, `sport`, `offer`. `published_at: null` speichert einen Entwurf. `expires_at` muss nach `published_at`, `ends_at` nach `starts_at` liegen. Bilder per Multipart-Feld `image` (JPG/PNG/WebP, max. 4 MB); bei PHP-Multipart-Updates `POST` mit `_method=PUT`. Linkkarten: `link` (HTTP/HTTPS), `link_title`, `link_description`. `pinned` dürfen Owner/Admin setzen; ohne Feld bleibt der bestehende Wert erhalten. Ohne `categories` wird bei PUT die Kategorieauswahl geleert.

API-Antworten enthalten Bild-URL, Linkkarte, Kategorien, Organisationsname/-slug/-verifizierung und strukturierte Veranstaltungsdaten. Keine Nutzerdaten oder Tokens in öffentlichen Antworten. Entwürfe, geplante, abgelaufene und moderierte Beiträge fehlen auch in Detail- und RSS-Antworten.

RSS: `/feed.xml` mit denselben Filterparametern wie der Feed, maximal 50 Einträge. Organisationen: `/organisationen/{slug}`. Veranstaltungskalender: `/veranstaltungen`.

## Qualität

```sh
composer format
composer quality
php artisan test
```

Psalm 6 Level 1 mit Laravel-Plugin und separatem Taint-Lauf. PHPStan/Larastan `max` mit Strict Rules. Analyse umfasst `app`, `routes`, `bootstrap/app.php`, `database` und `tests`; keine Baselines. Eine inkompatible Zusatzregel für statische Aufrufsyntax ist in `phpstan.neon` begründet. `declare(strict_types=1)` wird durch Pint erzwungen. CI nutzt PHP 8.4 und SQLite-In-Memory.

Die Funktionsplanung und die Startanleitung für Docker stehen im separaten Infrastruktur-Repository.
