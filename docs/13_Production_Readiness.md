# Phase 12 Production Readiness

This checklist prepares the FRIGO SISTEM release candidate for Hostinger. It does not authorize deployment or cutover.

## Required production environment

Set real values outside Git. Do not copy secrets into documentation, tickets, screenshots, or command history.

```text
APP_ENV=production
APP_DEBUG=false
APP_URL=https://<production-domain>
APP_KEY=<cryptographically-random-secret-of-at-least-32-characters>
APP_TIMEZONE=Europe/Belgrade
SESSION_IDLE_TIMEOUT=1800

DB_HOST=<Hostinger-MySQL-host>
DB_PORT=3306
DB_DATABASE=<database-name>
DB_USERNAME=<least-privilege-database-user>
DB_PASSWORD=<database-password>

MAIL_TRANSPORT=smtp
MAIL_HOST=<Hostinger-SMTP-host>
MAIL_PORT=<465-or-587-as-provided-by-Hostinger>
MAIL_USERNAME=<authenticated-mailbox>
MAIL_PASSWORD=<mailbox-password-or-app-password>
MAIL_ENCRYPTION=<ssl-or-tls-matching-the-port>
MAIL_FROM_ADDRESS=<authenticated-Frigo-Sistem-address>
MAIL_FROM_NAME="Frigo Sistem"
CONTACT_TO_ADDRESS=<inquiry-recipient-address>
```

Production startup deliberately fails closed when debug is enabled, the canonical URL is not an HTTPS origin, required database/SMTP values are empty, `APP_KEY` is too short, the session timeout is unreasonable, or a required PHP extension is missing.

## Hostinger PHP and filesystem

- Select PHP 8.4, or the documented compatible 8.4 runtime.
- Enable PDO MySQL, fileinfo, and GD with WebP support. EXIF is optional and only improves JPEG orientation correction.
- Set the domain document root to `public/`. Do not expose the repository root.
- Make `storage/logs/`, `storage/cache/contact-rate-limits/`, `storage/mail/`, and `public/uploads/products/` writable by PHP, with no broader write access than necessary.
- Confirm `public/uploads/.htaccess` is honored and scripts cannot execute from uploads.
- Keep `.env`, `app/`, `bootstrap/`, `config/`, `database/`, `docs/`, `resources/`, `storage/`, `tests/`, Composer metadata, and Git metadata outside the public document root.
- Keep `storage/mail/` empty/unused in production; production must use SMTP.

## HTTPS and headers

- Install and verify a valid TLS certificate for the final canonical hostname.
- Configure one direct HTTP-to-HTTPS redirect and one preferred www/non-www hostname at the hosting/web-server layer.
- Test for redirect loops before cutover.
- The application emits HSTS only on HTTPS production requests. Confirm HTTPS is working before relying on it.
- Verify CSP, frame protection, `nosniff`, Referrer-Policy, Permissions-Policy, admin no-store, and admin noindex headers in the final environment.

## Safe deployment sequence

1. Export/crawl the current production URLs and complete the verified exact mappings in `config/redirects.php`.
2. Back up the current website files, rewrite configuration, production database, and uploaded product images.
3. Verify each backup exists and perform a restore/readability check. A successful command alone is not sufficient evidence.
4. Prepare a maintenance/deployment state or a short controlled cutover window so writes cannot race schema changes.
5. Deploy the reviewed release code without replacing the recoverable backups.
6. Configure the production environment outside the web root.
7. Run `composer install --no-dev --optimize-autoloader` for the locked dependencies.
8. Run the migration runner against the backed-up production database; apply pending migrations only. Never edit already-applied migration files.
9. Verify filesystem ownership and the minimum required writable directories.
10. Verify the document root is `public/`, HTTPS/canonical redirects work, and sensitive paths are unreachable.
11. Smoke test public pages, a real 404, login/logout, all catalog administration workflows, upload processing, analytics, sitemap/robots, and SMTP delivery to a controlled business mailbox.
12. Review application/server logs for errors without exposing their contents publicly.
13. End maintenance state only after the smoke test passes. If a critical check fails, restore the previous files, rewrite rules, database backup, and uploads using the prepared rollback procedure.

## Business-owner decisions and inputs still required

- Final canonical production domain and preferred www/non-www form.
- Verified Hostinger database and SMTP settings, supplied directly in the hosting environment.
- A strong production `APP_KEY` and administrator password, transferred securely.
- Confirmation of the inquiry recipient/sender mailboxes and permission for one controlled production SMTP test during deployment preparation.
- Verified legal company name, address, phone, email, opening hours, service area, and any required legal-page content.
- A crawl/export of the current live site and owner-approved legacy redirect map.
- Approval of final active products, taxonomy visibility, product copy, prices, and image rights/alt text.
- Confirmation that database, file, and upload backups have a named owner, retention policy, and tested restore procedure.

## Release gate

Do not deploy until the full automated suite, production-like smoke tests, browser/responsive checks, backup verification, legacy redirects, SMTP test plan, and owner content review are complete. Phase 12 produces a release candidate only.
