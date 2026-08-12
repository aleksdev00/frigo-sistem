# System Architecture

## 1. Overview

This document defines the approved architecture for the Frigo Sistem Website Modernization project.

The architecture prioritizes:

- simplicity
- security
- maintainability
- direct compatibility with Hostinger shared hosting
- good SEO
- low operational overhead

The system is a PHP business website with a product catalog, not an e-commerce application.

---

# 2. Approved Technology Stack

## Backend

- PHP 8.4
- Composer autoloading
- PDO
- MySQL

No Laravel framework is required.

## Frontend

- HTML5
- CSS3
- modern JavaScript

Small, justified client-side libraries may be used for specific needs such as:

- product image gallery / slider
- admin charts

The project should not require a JavaScript framework or Node.js runtime in production.

## Hosting

Production target:

- Hostinger Premium shared hosting
- HTTPS
- PHP 8.4 runtime
- MySQL database
- filesystem storage for optimized product images

---

# 3. Architecture Style

The project uses a lightweight MVC-inspired structure with explicit separation between:

- routing / request entry
- controllers
- services
- repositories / data access
- views
- validation / security helpers

The purpose is separation of responsibilities, not framework imitation.

Business logic must not be embedded in templates.

SQL must not be written directly inside templates.

---

# 4. Recommended Project Structure

```text
frigo-sistem/
├── app/
│   ├── Controllers/
│   ├── Repositories/
│   ├── Services/
│   ├── Validation/
│   └── Support/
├── bootstrap/
│   └── app.php
├── config/
├── database/
│   ├── migrations/
│   └── seeds/
├── docs/
├── public/
│   ├── assets/
│   ├── uploads/
│   ├── index.php
│   └── .htaccess
├── resources/
│   └── views/
├── storage/
│   └── logs/
├── tests/
├── composer.json
├── .env.example
├── .gitignore
├── AGENTS.md
└── README.md
```

If Hostinger requires `public_html` as the public root, deployment must expose only the contents intended for public access. Application source, configuration, logs, and secrets must not be web-accessible.

---

# 5. Request Flow

Typical public request:

```text
Browser
  ↓
public/index.php
  ↓
Router
  ↓
Controller
  ↓
Service
  ↓
Repository
  ↓
PDO / MySQL
  ↓
View
  ↓
HTML Response
```

Static assets and uploaded optimized images may be served directly by the web server.

---

# 6. Routing

The application should use clean URLs through Apache rewrite rules and a front controller.

Recommended public route direction:

```text
/
/o-nama
/usluge
/klima-uredjaji
/klima-uredjaji/{product-slug}
/brend/{brand-slug}
/kategorija/{category-slug}
/kontakt
```

Exact final URLs must also consider the current production URLs so that important legacy pages can be redirected with HTTP 301 rather than silently removed.

Admin routes should use a dedicated prefix, for example:

```text
/admin/login
/admin
/admin/products
/admin/brands
/admin/categories
```

A less obvious admin prefix may be used, but URL obscurity is never considered an authentication control.

---

# 7. Data Access

All database access goes through PDO.

Rules:

- prepared statements are mandatory for dynamic values
- PDO emulated prepares should be disabled when practical
- connection charset must be `utf8mb4`
- credentials come from environment configuration
- repositories contain database queries
- views never query the database directly

No ORM is required.

---

# 8. Authentication Architecture

Only administrator authentication exists.

Authentication uses:

- administrator table in MySQL
- `password_hash()` / `password_verify()`
- server-side PHP sessions
- session ID regeneration after successful login
- protected admin route middleware/helper
- explicit logout and session destruction
- login throttling

No JWT, Supabase Auth, public registration, or role system is required.

---

# 9. File and Image Architecture

Product images are uploaded through the admin panel.

Processing flow:

```text
Upload
  ↓
Validate request
  ↓
Validate real MIME + decode image
  ↓
Enforce file/dimension limits
  ↓
Re-encode / optimize
  ↓
Generate random filename
  ↓
Store optimized image
  ↓
Save relative path in MySQL
```

The application should generate web-optimized images, preferably WebP, while preserving correct dimensions and transparency where needed.

Executable files must never be accepted as images.

---

# 10. Contact Form Architecture

The contact form is public.

Initial design:

```text
Visitor
  ↓
Server-side validation
  ↓
CSRF + anti-spam/rate-limit checks
  ↓
Mail service
  ↓
Frigo Sistem mailbox
```

PHPMailer or another mature SMTP-capable library may be used through Composer.

Database storage of contact messages is not required by default.

---

# 11. Analytics Architecture

Basic internal analytics records product page views.

Required outputs:

- most viewed products
- most viewed categories / air-conditioner types
- total product views over a selected period

Analytics must avoid raw IP storage.

A lightweight event table is sufficient; no real-time analytics platform is required.

---

# 12. SEO Architecture

SEO affects routing, HTML rendering, database fields, migration strategy, and performance.

SEO is therefore part of the architecture, not a final plugin.

Public content must be server-rendered by PHP so that important text and metadata are available in the initial HTML response.

---

# 13. Error Handling and Logging

Production behavior:

- `display_errors` off
- user receives a generic error page
- technical errors are written to application logs
- logs must not expose passwords, session IDs, database credentials, or unnecessary personal data

Development may use more verbose errors locally.

---

# 14. Design Principles

1. Keep the system simple.
2. Prefer native PHP capabilities and small mature libraries over large dependencies.
3. Do not introduce infrastructure the business does not need.
4. Keep security controls explicit.
5. Keep SEO-critical content server-rendered.
6. Keep product data normalized enough to remain maintainable for several years.
7. Keep deployment compatible with shared hosting.
