# solarisone.eu

Einfach gehaltene Landingpage mit Light/Dark-Theme und einer selbst gehosteten Wartelisten-Anmeldung (Double-Opt-In).

## Inhalt

- `index.html` – Hauptseite (DE/EN) mit Theme-Schalter.
- `signup.php` – verarbeitet Wartelisten-Anmeldungen und versendet Bestätigungs-E-Mails.
- `confirm.php` – validiert Tokens aus der Bestätigungs-Mail.
- `datenschutz.html` / `impressum.html` – rechtliche Seiten.
- `styles.css`, `fonts.css` – Styling.

## Installation

1. Auf einem Webserver mit PHP (z. B. IONOS/Plesk) deployen.
2. Sicherstellen, dass `mail()` funktioniert (Server-MTA/Relay).
3. In der Server-Umgebung die Variable `WAITLIST_SIGNING_KEY` setzen (starkes Secret).

## Wartelisten-Workflow (Double-Opt-In)

1. Nutzer meldet sich über `index.html` an.
2. `signup.php` generiert ein Token und verschickt einen Bestätigungslink per E-Mail.
3. Nutzer bestätigt über `confirm.php`.
4. Es wird erst nach Bestätigung eine Benachrichtigung versendet.

> ⚠️ Es werden **keine** `waitlist_*.json` Dateien auf dem Server gespeichert.

## Datenschutz

- Die Anmeldung basiert auf einer freiwilligen Einwilligung.
- Die Datenschutzerklärung (`datenschutz.html`) beschreibt den Prozess.

## Lokale Entwicklung

Für lokale Tests kann ein SMTP-Relay (z. B. `smtp4dev` oder `MailHog`) verwendet werden.

---

© SolarisOne