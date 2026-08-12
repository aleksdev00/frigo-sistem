# Frigo Sistem

Lightweight PHP 8.4 application for the Frigo Sistem website modernization.

## Local setup

1. Install PHP 8.4 with `pdo_mysql` and Composer 2.
2. Copy `.env.example` to `.env` and set local values.
3. Run `composer install`.
4. Start the development server with `php -S localhost:8000 -t public`.
5. Open `http://localhost:8000`.

The homepage does not connect to MySQL. Database credentials are prepared for later phases; no schema exists yet.

## Checks

Run `composer check` and `composer validate --strict`.

Production must point its document root at `public/`, use `APP_ENV=production` and `APP_DEBUG=false`, and keep `.env`, source, configuration, and logs outside public access.
