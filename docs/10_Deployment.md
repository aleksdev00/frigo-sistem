# Deployment

> The concrete Phase 12 Hostinger environment, filesystem, backup, rollback, and safe deployment checklist is maintained in `docs/13_Production_Readiness.md`.

> Phase 11 requirement: production must provide the exact public HTTPS origin in `APP_URL` (for example, `https://example.com`, with no path/query/fragment) and `APP_ENV=production`. A localhost, temporary, or staging URL must never be used for production sitemap or canonical output. Development and test environments intentionally prevent indexing. Populate only verified legacy mappings in `config/redirects.php` after crawling the previous site.

## 1. Overview

This document defines deployment of the Frigo Sistem Website Modernization project to the existing Hostinger shared-hosting environment.

Approved production stack:

- Hostinger Premium shared hosting
- PHP 8.4 for the new application
- MySQL
- HTTPS
- GitHub as source repository

The current production website should not be disrupted until the replacement is tested and ready for cutover.

---

# 2. Current Hosting Context

Known hosting capabilities include:

- PHP
- MySQL
- shared filesystem
- SSL/HTTPS
- phpMyAdmin / database management

The production plan is sufficient for this project provided the application remains lightweight and images are optimized.

The existing website currently uses another PHP version. Do not change the live site's PHP version prematurely if that could affect the existing WordPress installation.

---

# 3. Environments

## Development

Local machine:

- Git repository
- PHP 8.4 development runtime
- local MySQL database
- local `.env`

## Production

Hostinger:

- production domain
- production MySQL database
- production environment variables/config
- optimized production dependencies
- production upload storage

Never use the production database as the normal development database.

---

# 4. Source Control

GitHub stores:

- source code
- documentation
- SQL migrations
- Composer files
- tests
- public static assets

Do not commit:

- `.env`
- production passwords
- production database dumps containing personal data
- runtime logs
- user-uploaded production media unless intentionally backed up elsewhere

---

# 5. Composer

Dependencies should be installed locally before deployment or through supported server tooling.

Recommended production install:

```bash
composer install --no-dev --optimize-autoloader
```

Commit `composer.lock`.

Do not require Laravel Artisan or Node.js production commands.

---

# 6. Database Deployment

## Initial Deployment

1. create production MySQL database in Hostinger
2. create dedicated database user
3. configure credentials securely
4. import/run versioned SQL migration files
5. create initial administrator hash through a controlled setup procedure
6. verify schema and indexes
7. verify PDO connection

Database migrations are plain versioned SQL files tracked in Git.

Example:

```text
database/migrations/001_initial_schema.sql
```

---

# 7. File Layout

Preferred deployment keeps sensitive application files outside direct public access.

If Hostinger document-root constraints require `public_html`, deploy so that only public entry/assets/uploads are reachable by HTTP and application/config/storage files are protected.

Public web root should contain:

- `index.php`
- public assets
- controlled public upload output
- `.htaccess`

Never expose:

- `.env`
- database migrations
- logs
- Composer metadata unnecessarily
- source configuration files

---

# 8. Apache / Rewrite Configuration

Production must support:

- front-controller routing
- canonical HTTPS redirects
- preferred www/non-www policy
- legacy 301 redirect map
- blocking access to sensitive dotfiles/config
- prevention of script execution in upload directories

Rewrite rules must be tested before cutover to prevent redirect loops.

---

# 9. PHP Configuration

Target new application runtime:

- PHP 8.4

Production requirements:

- `display_errors = Off`
- secure session settings applied by the application and/or PHP configuration
- required extensions enabled, including PDO MySQL
- GD or Imagick available for image processing
- suitable upload limits

Application-level image limits should be much lower than the server maximum.

---

# 10. Environment Configuration

Required configuration may include:

```text
APP_ENV
APP_URL
APP_DEBUG

DB_HOST
DB_PORT
DB_DATABASE
DB_USERNAME
DB_PASSWORD

MAIL_HOST
MAIL_PORT
MAIL_USERNAME
MAIL_PASSWORD
MAIL_ENCRYPTION
MAIL_FROM_ADDRESS
MAIL_FROM_NAME
CONTACT_TO_ADDRESS
MAIL_TRANSPORT
```

Exact variable names are implementation details but must remain consistent.

Production:

```text
APP_ENV=production
APP_DEBUG=false
```

## Phase 10 mail configuration

Local development should keep `MAIL_TRANSPORT=log`; messages are written to the non-public, Git-ignored `storage/mail/` preview directory and no SMTP connection is made.

Hostinger production must configure an authenticated mailbox and set:

```text
MAIL_TRANSPORT=smtp
MAIL_HOST=<Hostinger SMTP hostname>
MAIL_PORT=<Hostinger SMTP port, commonly 465 or 587>
MAIL_USERNAME=<full authenticated mailbox address>
MAIL_PASSWORD=<mailbox password or app-specific password>
MAIL_ENCRYPTION=<tls or ssl, matching the selected port>
MAIL_FROM_ADDRESS=<authenticated Frigo Sistem sender address>
MAIL_FROM_NAME=Frigo Sistem
CONTACT_TO_ADDRESS=<mailbox that receives website inquiries>
```

The exact hostname, port, encryption mode, mailbox, and password must be copied from the active Hostinger email account rather than guessed. Ensure PHP can write `storage/mail/`, `storage/cache/contact-rate-limits/`, and `storage/logs/`, while none of those directories are web-accessible. Optional throttle settings are `CONTACT_RATE_LIMIT`, `CONTACT_RATE_WINDOW_SECONDS`, and `CONTACT_MIN_FILL_SECONDS`.

---

# 11. Image Storage

Production upload storage must:

- be writable only where necessary
- reject script execution
- contain only processed safe image outputs
- be included in backup strategy

The database stores relative image paths, not machine-specific absolute paths.

---

# 12. Pre-Deployment Checklist

Before cutover:

- all docs aligned with implementation
- production backup of existing website
- production database backup
- new app tested in local/staging-like environment
- admin security tested
- contact form tested
- product CRUD tested
- image processing tested
- SEO metadata reviewed
- legacy URL crawl/export complete
- 301 redirect map complete
- sitemap generated
- robots reviewed
- canonical hostname decided
- legal/contact content verified

---

# 13. Cutover Strategy

Recommended high-level process:

```text
Backup existing production
  ↓
Prepare new application files
  ↓
Prepare production MySQL
  ↓
Deploy new app
  ↓
Apply rewrite + legacy redirects
  ↓
Smoke test
  ↓
Verify HTTPS/canonical URLs
  ↓
Verify admin
  ↓
Verify forms
  ↓
Verify major SEO URLs
```

Schedule cutover when the site can be monitored immediately afterward.

---

# 14. Rollback

A rollback plan is mandatory.

Keep:

- backup of previous public website files
- database backup
- copy of previous rewrite configuration
- clear procedure to restore previous document root/site state

If a severe deployment issue occurs, restore the previous site rather than debugging production under pressure.

---

# 15. Backups

Back up:

- MySQL database
- uploaded product images
- relevant production configuration
- source code separately through GitHub

Hostinger weekly backups are useful but should not be the only recovery strategy for critical data.

Before major releases, create an additional manual backup.

---

# 16. Post-Deployment Verification

Immediately verify:

- homepage
- product catalog
- several product pages
- search/filter
- contact form
- admin login/logout
- create/edit product
- image upload
- analytics recording
- HTTPS
- canonical hostname
- redirects
- sitemap
- robots.txt
- 404 behavior

Check browser/server logs for unexpected errors.

---

# 17. SEO Launch Tasks

After cutover:

- submit/update sitemap in Google Search Console
- inspect key pages
- verify redirects
- monitor 404s
- monitor indexing
- verify structured data
- verify old important URLs are not unexpectedly lost

---

# 18. Production Maintenance

Regular maintenance:

- application backups
- MySQL backups
- dependency security review
- PHP compatibility review before major PHP upgrades
- log review
- disk/upload usage review
- broken-link/404 review
- SEO/indexing review

Do not upgrade PHP or dependencies directly in production without testing.

---

# 19. Deployment Acceptance Criteria

Deployment is complete when:

- site is live over HTTPS
- production database is connected
- public pages work
- admin is protected and functional
- uploads work safely
- contact delivery works
- redirects work
- no sensitive files are public
- production errors are hidden
- backups/rollback are available
- critical SEO pages are crawlable and canonical
