# AGENTS.md

## 1. Purpose

This file defines the operating rules for AI coding agents working on the **Frigo Sistem Website Modernization** project.

Treat the repository documentation as the source of truth. Do not redesign the architecture, expand the product scope, or introduce major dependencies unless explicitly instructed.

The goal is to build the **simplest secure and maintainable solution** that satisfies the documented requirements.

---

## 2. Project Summary

Frigo Sistem is a Serbian business website with a searchable air-conditioning product catalog and a private administration panel.

This is **not an e-commerce application**.

Visitors can:

- browse air-conditioning products
- search and filter products
- view detailed product pages
- view images and technical specifications
- learn about Frigo Sistem services
- contact the company
- send product-specific inquiries

The administrator can:

- log in securely
- manage products
- manage brands
- manage categories
- upload/manage product images
- manage flexible product specifications
- manage product SEO fields
- view basic product/category view analytics

---

## 3. Mandatory Documentation

Before implementing a task, read the documents relevant to that task.

Documentation:

```text
docs/
├── 01_Project_Overview.md
├── 02_Requirements.md
├── 03_System_Architecture.md
├── 04_Database_Design.md
├── 05_Public_Website.md
├── 06_Admin_Panel.md
├── 07_Product_Module.md
├── 08_Security.md
├── 09_SEO_Strategy.md
├── 10_Deployment.md
├── 11_Testing.md
└── 12_Roadmap.md
```

For every implementation task:

1. Read `01_Project_Overview.md`.
2. Read `03_System_Architecture.md`.
3. Read `08_Security.md` if the task handles requests, authentication, sessions, forms, uploads, database writes, or admin functionality.
4. Read the feature-specific document.
5. Read the relevant roadmap phase in `12_Roadmap.md`.

Do not rely only on this file when a detailed project document exists.

---

## 4. Documentation Priority

If instructions conflict, use this order:

1. explicit current developer/user instruction
2. `08_Security.md` for security requirements
3. feature-specific documentation
4. `03_System_Architecture.md`
5. `04_Database_Design.md`
6. `02_Requirements.md`
7. `01_Project_Overview.md`
8. `12_Roadmap.md`
9. this `AGENTS.md`

If two project documents genuinely conflict and the intended behavior cannot be determined safely:

**stop and report the conflict instead of silently choosing an architecture.**

---

## 5. Approved Technology Stack

Use:

```text
Backend
- PHP 8.4
- PDO
- MySQL
- Composer where justified

Frontend
- HTML5
- CSS3
- modern JavaScript

Infrastructure
- Git
- GitHub
- Hostinger Premium shared hosting
- Apache / .htaccess
```

Do not introduce:

- Laravel
- Symfony
- WordPress
- Supabase
- Node.js as a production requirement
- Next.js
- React
- Vue
- Angular
- an ORM
- Docker as a production requirement
- Redis
- external database infrastructure

unless explicitly approved.

Small mature Composer or frontend libraries are allowed only when they clearly reduce complexity or security risk.

---

## 6. Architecture Rules

Use the lightweight architecture defined in `03_System_Architecture.md`.

Expected separation:

```text
Request
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
```

Rules:

- controllers coordinate HTTP/application flow
- services contain business logic
- repositories contain database queries
- views render output
- validation/security helpers remain reusable
- templates must not query MySQL directly
- SQL must not be embedded in views
- do not put substantial business logic inside templates
- do not create abstractions without a real use case

Prefer readable explicit code over clever abstractions.

---

## 7. Scope Guard

The initial release does NOT contain:

- shopping cart
- checkout
- payments
- customer accounts
- customer authentication
- orders
- shipping
- coupons
- wishlist
- reviews
- inventory management
- multiple admin roles
- permission matrices
- newsletter system
- complex CMS
- blog
- AI recommendation engine

Do not implement these proactively.

Do not create database tables for hypothetical future features.

Future extensibility should come from clean code and sensible data modeling, not speculative infrastructure.

---

## 8. Database Rules

Use MySQL through PDO.

Mandatory:

- prepared statements for all dynamic values
- `utf8mb4`
- versioned SQL migration files
- foreign keys where documented
- indexes where documented
- transactions for multi-table writes where appropriate
- production credentials outside Git

Do not use an ORM.

Do not dynamically concatenate untrusted input into SQL.

Dynamic SQL identifiers such as sort columns must use explicit whitelists.

The authoritative database design is:

`docs/04_Database_Design.md`

If implementation requires a schema change:

1. explain why
2. create a new migration
3. update affected documentation
4. do not silently alter the established schema

Never edit an already-applied production migration to represent a new schema change.

---

## 9. Security Is Mandatory

Security requirements are defined in:

`docs/08_Security.md`

Security requirements are not optional cleanup tasks.

Never postpone required security controls until the end if the feature being implemented depends on them.

Mandatory principles include:

- secure password hashing
- server-side authentication
- protected admin routes
- secure PHP sessions
- session regeneration after login
- CSRF protection
- PDO prepared statements
- contextual output escaping
- server-side validation
- login throttling
- secure file uploads
- HTTPS in production
- environment-based secrets
- safe production error handling
- security headers

Never rely on a secret admin URL as authentication.

Never weaken a security requirement merely to make implementation easier.

---

## 10. Admin Authentication

There is one administrator account in v1.

Do not build:

- registration
- customer login
- roles
- permissions
- OAuth
- JWT authentication
- public password reset

Authentication should use:

```text
username
password_hash()
password_verify()
PHP session
```

On successful login:

- regenerate session ID
- establish authenticated session state
- redirect to admin dashboard

On logout:

- clear authenticated state
- invalidate/destroy the session appropriately

All protected routes must verify authentication server-side.

---

## 11. CSRF

All state-changing requests require CSRF protection.

Examples:

- login
- create product
- edit product
- delete product
- image upload/delete/reorder
- specification changes
- brand/category changes
- public contact forms

Do not create GET endpoints that perform destructive actions.

---

## 12. Output Escaping

Treat database content as untrusted when rendering.

For ordinary HTML text, use safe escaping equivalent to:

```php
htmlspecialchars(
    $value,
    ENT_QUOTES | ENT_SUBSTITUTE,
    'UTF-8'
);
```

Do not render raw administrator-entered HTML unless a deliberate sanitization system has been approved.

Escaping must match the output context.

---

## 13. File Upload Rules

Product image upload is security-sensitive.

Accept only documented raster image formats.

Never trust:

- original filename
- extension
- browser-provided MIME type

Server must:

1. verify upload success
2. enforce application size limits
3. inspect MIME with server-side tooling
4. verify the file decodes as an image
5. enforce reasonable dimensions
6. generate a random storage filename
7. re-encode the image
8. store only the processed output
9. prevent script execution in upload directories
10. save only a relative path in MySQL

Do not accept SVG in v1.

Do not store uploaded PHP or arbitrary files.

---

## 14. Product Rules

Products belong to:

- one brand
- one category

Products may have:

- multiple images
- multiple specifications
- view events

Product states:

```text
Active
Hidden
```

Only Active products may appear in:

- public catalog
- public search
- related products
- XML sitemap

Hidden products must remain manageable from admin.

---

## 15. Product Specifications

Specifications use the documented flexible key-value model.

Do not create database columns such as:

```text
btu
wifi
energy_class
gas_type
noise
```

solely because one product currently uses them.

Only promote a specification to a structured database field if a real future filtering/business requirement justifies the schema change.

---

## 16. Product Images

A product may have multiple images.

Support:

- main image
- gallery images
- ordering
- deletion

At most one image should be treated as the main image for a product.

Physical file cleanup and database cleanup must remain consistent.

Avoid orphaned files.

---

## 17. Public Website

The supplied Frigo Sistem homepage design is the primary visual reference.

Do not independently redesign the visual identity unless instructed.

Product pages should use a modern retail-style information hierarchy similar to the approved Gigatron reference concept, while remaining clearly a **catalog/contact experience rather than online checkout**.

Do not add:

- Add to Cart
- Buy Now
- quantity selectors
- stock checkout UI
- payment UI

Primary conversion actions are:

- call
- contact
- request information
- request installation

---

## 18. SEO Is a Core Requirement

Read:

`docs/09_SEO_Strategy.md`

SEO is part of architecture and implementation.

Do not defer all SEO until the final commit.

Important requirements:

- Serbian SEO-friendly URLs
- server-rendered content
- unique metadata
- canonical URLs
- correct headings
- breadcrumbs
- XML sitemap
- robots.txt
- structured data when accurate
- image alt text
- internal linking
- correct HTTP status codes
- noindex for admin/internal search where documented
- controlled 301 migration from important legacy URLs

Do not invent keyword-volume statistics.

Do not create doorway/location spam pages.

Do not automatically index every filter combination.

---

## 19. URL Stability

Published URLs should remain stable.

Before changing an established public slug or route:

- check whether the old URL is already public/indexed
- preserve it through a 301 redirect when appropriate

Do not casually change product URLs because a different slug looks cleaner.

---

## 20. Analytics

Analytics is intentionally lightweight.

Required business insights:

- most viewed products
- most viewed categories/types
- total product views over useful periods

Do not store raw IP addresses.

Do not add invasive third-party tracking unless explicitly approved.

Do not build real-time analytics infrastructure.

---

## 21. Contact Forms

Contact forms must:

- validate server-side
- use CSRF protection
- use anti-spam/rate limiting
- bound input lengths
- safely send mail
- avoid mail-header injection
- show safe error messages

Product-specific inquiries must identify the product using trusted server-side product data.

Do not trust a submitted free-text product name as authoritative.

Database storage of inquiries is not required unless explicitly approved.

---

## 22. Performance

Prefer:

- server-rendered HTML
- minimal JavaScript
- optimized images
- responsive images where useful
- browser caching
- efficient indexed queries
- limited third-party scripts

Do not optimize based on speculation.

Measure first when making non-obvious performance changes.

Do not add Redis, queues, workers, or caching infrastructure without a demonstrated requirement.

---

## 23. Accessibility

New UI must consider:

- semantic HTML
- keyboard navigation
- visible focus
- labels
- alt text
- readable contrast
- touch targets
- responsive layout

Do not knowingly introduce inaccessible custom controls when native HTML controls solve the problem.

---

## 24. Error Handling

Development may expose detailed local errors.

Production must not expose:

- stack traces
- SQL errors
- filesystem paths
- credentials
- configuration values

Production should log technical details and show safe generic error pages.

Never log passwords or raw session IDs.

---

## 25. Environment Configuration

Secrets belong in environment configuration.

Repository must include:

```text
.env.example
```

Repository must not include:

```text
.env
```

Never hardcode:

- database credentials
- mail credentials
- production secrets

---

## 26. Testing Expectations

Read:

`docs/11_Testing.md`

When implementing a feature:

1. test the happy path
2. test validation failures
3. test unauthorized access where relevant
4. test security-sensitive edge cases
5. test mobile/responsive behavior for UI work
6. run relevant automated tests

Do not claim a feature is complete if known tests fail.

Before marking a task complete, report:

- what was implemented
- what was tested
- what remains
- any assumptions or risks

---

## 27. Working With Codex

Work incrementally.

Do not implement multiple roadmap phases unless explicitly requested.

Before changing code:

1. inspect existing repository structure
2. read relevant documentation
3. inspect existing implementation
4. identify the smallest coherent change
5. implement it
6. run relevant tests/checks
7. summarize results

Do not overwrite working code unnecessarily.

Do not refactor unrelated modules during a focused feature task.

Do not create large architectural changes without explaining them first.

---

## 28. Dependency Rule

Before adding a dependency, ask:

1. Can native PHP/browser functionality solve this cleanly?
2. Is the dependency actively maintained?
3. Does it reduce security or implementation risk?
4. Is its size/complexity justified?
5. Will it work on Hostinger shared hosting?

Good example:

- PHPMailer for reliable SMTP handling

Potentially unnecessary examples:

- full PHP framework
- SPA framework
- large admin template framework
- complex image pipeline package when GD already satisfies the requirement

---

## 29. Git Rules

Prefer small coherent commits.

Suggested commit style:

```text
feat: add admin authentication
feat: implement product CRUD
feat: add secure product image uploads
fix: prevent duplicate product slugs
security: harden admin sessions
seo: add canonical product URLs
test: add product validation tests
docs: update database design
```

Never commit secrets.

Do not commit temporary debugging files.

Do not commit production database dumps.

---

## 30. Documentation Maintenance

Documentation must evolve with deliberate architectural decisions.

When implementation intentionally changes documented behavior:

- update the relevant `.md` document in the same task/commit
- explain why the change was necessary

Do not allow code and documentation to silently diverge.

Minor implementation details that do not change architecture or behavior do not require documentation churn.

---

## 31. Hostinger Compatibility

Production is Hostinger shared hosting.

Do not assume:

- root access
- persistent background processes
- Docker
- Redis
- systemd
- Node.js server runtime
- custom server packages

The application must function using normal PHP/MySQL/shared-hosting capabilities.

Do not change production PHP configuration until the new application is ready for cutover and the existing site has been backed up.

---

## 32. Definition of Done

A task is complete only when:

- requested functionality works
- architecture rules are followed
- validation is implemented
- security requirements are satisfied
- relevant tests/checks pass
- no unrelated functionality is broken
- documentation is updated when behavior/architecture changed
- no secrets were committed
- remaining limitations are reported

---

## 33. Decision Rule

When there are multiple valid solutions, prefer in this order:

1. secure
2. simple
3. maintainable
4. compatible with Hostinger
5. performant enough for real requirements
6. easy for another developer to understand

Avoid novelty for novelty's sake.

---

## 34. Final Project Principle

> Frigo Sistem does not need the most sophisticated architecture. It needs a secure, fast, SEO-first, easy-to-maintain product catalog that solves the company's real business problem reliably for the next several years.

Build exactly that.
