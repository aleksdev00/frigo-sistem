# Frigo Sistem

Lightweight PHP 8.4 application for the Frigo Sistem website modernization.

## Local setup

1. Install PHP 8.4 with `pdo_mysql` and Composer 2.
2. Copy `.env.example` to `.env` and set local values.
3. Run `composer install`.
4. Start the development server with `php -S localhost:8000 -t public`.
5. Apply the local database schema explicitly with `composer db:migrate`.
6. Verify the database schema and constraints with `composer db:check`.
7. Open `http://localhost:8000`.

Set `APP_KEY` to a random value of at least 32 characters (for example, generated with `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`). Create or intentionally replace the initial administrator with `php database/admin.php create [username]`; the command prompts for and confirms a password without writing plaintext to disk or output.

The homepage does not connect to MySQL during normal application boot. Database migrations run only through the explicit CLI command above and use the configured `.env` credentials.

For a database whose application schema predates migration tracking, first verify the schema and then run `php database/migrate.php --baseline` once. Baseline mode is explicit and records discovered migrations without executing their SQL.

## Checks

Run `composer check` and `composer validate --strict`.

Production must point its document root at `public/`, use `APP_ENV=production` and `APP_DEBUG=false`, and keep `.env`, source, configuration, and logs outside public access.

## Contact mail

Development defaults to `MAIL_TRANSPORT=log`, which writes non-public previews to `storage/mail/` without sending email. Production must use `MAIL_TRANSPORT=smtp` and configure the Hostinger SMTP variables documented in `.env.example` and `docs/10_Deployment.md`. Contact inquiries are mailed only; they are not stored in MySQL.
