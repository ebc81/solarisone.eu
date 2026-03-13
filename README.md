# solarisone.eu

Landingpage für SolarisOne (DE/EN) mit Light/Dark-Theme und einfacher selbst gehosteter Wartelisten-Anmeldung.

## Theme

- Standard-Theme ist **Light**.
- Theme-Auswahl (☀️/🌙) wird in `localStorage` gespeichert.

## Waitlist (PHP mit Double-Opt-In, ohne JSON-Speicherung)

Die Anmeldung läuft über `signup.php` auf demselben Server.

### Ablauf

1. Nutzer sendet E-Mail + Einwilligung über das Formular.
2. `signup.php` erzeugt ein Token und sendet einen Bestätigungslink per E-Mail.
3. Nutzer klickt den Link (`confirm.php`).
4. Erst dann wird `info@ebctech.eu` informiert.

Es werden dabei keine `waitlist_*.json` Dateien gespeichert.

### Voraussetzungen auf IONOS/Plesk

- PHP aktiviert.
- Mailversand per `mail()` funktional (über Server-MTA/Relay).
- Environment Variable `WAITLIST_SIGNING_KEY` gesetzt (starkes Secret für den Bestätigungslink), z.B. in Plesk unter **PHP-Einstellungen** oder **Apache & nginx Settings**.
- SPF/DKIM/DMARC für die Domain sauber eingerichtet.

### DSGVO-Hinweis

- Die Form enthält eine verpflichtende Einwilligung.
- Datenschutzerklärung sollte den Prozess transparent beschreiben.
- Diese Implementierung nutzt Double-Opt-In.
- Es erfolgt keine JSON-Persistenz von Wartelistendaten auf dem Server.