# StadtNews development

- This checkout is an independent Laravel repository. Its origin is `git@github.com:twitnic/what.git`.
- Keep all Docker files outside this repository. The parent infrastructure mounts this checkout; use its Makefile or Docker Compose CLI service for PHP and Composer. Do not require a host PHP installation.
- Keep Laravel on the latest stable 13.x release. Commit composer.lock alongside dependency changes.
- Use strict PHP types. Run Pint, PHPStan/Larastan at max, Psalm at level 1, the separate Psalm taint pass, and PHPUnit. Do not introduce baselines or suppress application errors.
- Authorize every organization-scoped write through policies. Never accept organization_id, user_id, verified, hidden_at or platform admin rights from user input.
- Public feed, detail, calendar and RSS queries must preserve publication, expiration and moderation restrictions.
- Store timestamps in UTC. Web editorial forms use Europe/Berlin; API payloads use explicit offsets.
- Blade and public/stadtnews.css provide the UI; no Node build is required.
- Use demo data only in local/testing. Never commit credentials, .env, generated tokens or storage contents.
