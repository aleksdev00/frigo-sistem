# Roadmap

## 1. Overview

This roadmap defines the implementation order for the Frigo Sistem Website Modernization project.

The objective is to reach production efficiently while avoiding both rushed security work and unnecessary architecture.

Development should proceed in small Codex tasks with review after each phase.

---

# 2. Phase 0 — Documentation Audit and Lock

Tasks:

- align all 12 documentation files with the approved stack
- remove obsolete Laravel assumptions
- confirm final project scope
- confirm database entities
- confirm public URL direction
- confirm admin scope
- confirm SEO migration requirements
- create root `AGENTS.md`

Result:

- one consistent source of truth for Codex

---

# 3. Phase 1 — Repository and Local Foundation

Tasks:

- initialize Git repository / GitHub repository
- create project folder structure
- configure Composer autoloading
- create `.gitignore`
- create `.env.example`
- create local environment configuration
- set up PHP 8.4
- set up local MySQL
- create front controller/router skeleton
- create base error handling/logging
- create base HTML layout

Result:

- application boots locally with no business features

---

# 4. Phase 2 — Database

Tasks:

- create `001_initial_schema.sql`
- create tables:
  - admins
  - brands
  - categories
  - products
  - product_images
  - product_specifications
  - product_views
- add foreign keys
- add indexes
- add seed procedure/data for first administrator and initial taxonomy if needed
- verify `utf8mb4`
- test migration on empty local database

Result:

- stable database foundation

---

# 5. Phase 3 — Security and Admin Authentication

Implement before product CRUD:

- secure session bootstrap
- CSRF service/helper
- admin login
- logout
- password hashing/verification
- protected admin routes
- login rate limiting
- production-safe error behavior
- security headers baseline

Result:

- secure empty admin area

---

# 6. Phase 4 — Product Catalog Administration

Implement:

## Brands

- list
- create
- edit
- active/hidden
- safe delete rules

## Categories

- list
- create
- edit
- active/hidden
- safe delete rules

## Products

- list/search
- create
- edit
- activate/hide
- delete
- slug
- price optional
- descriptions
- SEO fields

Result:

- administrator can manage core catalog data

---

# 7. Phase 5 — Images and Specifications

Implement:

- safe image upload validation
- GD/Imagick re-encoding
- optimized WebP output
- main image
- gallery images
- ordering
- delete cleanup
- specification add/edit/delete/reorder

Result:

- complete Gigatron-like product data can be entered through admin

---

# 8. Phase 6 — Analytics Dashboard

Implement:

- product view event recording
- total views
- top viewed products
- top viewed categories/types
- recent products
- simple charts

Result:

- required internal catalog analytics available

---

# 9. Phase 7 — Public Product Catalog

Implement:

- Serbian catalog route
- product listing
- product cards
- search
- brand filter
- category filter
- pagination if needed
- active-only rules
- brand/category landing pages where approved

Result:

- visitors can find products quickly

---

# 10. Phase 8 — Product Details

Implement:

- product header
- image gallery
- descriptions
- specifications
- related products
- product CTA
- product-specific inquiry context
- breadcrumb

Result:

- complete product showcase page

---

# 11. Phase 9 — Public Website Design

Implement approved design:

- homepage
- about
- services
- gallery/work section if approved
- contact
- footer
- responsive states

Avoid adding CMS functionality for content that does not need frequent editing.

Result:

- final Frigo Sistem public visual experience

---

# 12. Phase 10 — Contact Delivery

Implement:

- contact form
- product inquiry form
- SMTP/mail service
- server validation
- CSRF
- honeypot / rate limiting
- safe failure handling

Default:

- send inquiries to company email
- no admin inquiry database unless separately approved

Result:

- working lead/contact flow

---

# 13. Phase 11 — SEO Research and Implementation

Research before final production copy:

- Serbian search intent
- competitors
- current Frigo Sistem indexed URLs
- legacy URL map
- brand/category terminology
- local intent

Implement:

- final URL structure
- titles/descriptions
- canonical
- sitemap
- robots
- structured data
- breadcrumbs
- Open Graph
- index/noindex rules
- 301 redirects
- image SEO
- internal linking

SEO is considered throughout earlier phases, but this phase finalizes and validates it.

Result:

- migration-ready SEO-first website

---

# 14. Phase 12 — Testing and Hardening

Run:

- functional tests
- PHPUnit high-value tests
- security tests
- upload attack tests
- browser/responsive tests
- accessibility checks
- performance checks
- SEO crawl tests
- production-like smoke tests

Fix blockers before deployment.

Result:

- release candidate

---

# 15. Phase 13 — Hostinger Production Preparation

Tasks:

- backup existing website
- export/crawl current important URLs
- prepare production MySQL
- prepare production `.env`
- build/install optimized Composer dependencies
- prepare production file structure
- configure PHP 8.4 for new app at cutover time
- configure HTTPS/canonical hostname
- configure rewrite/redirect rules
- prepare rollback package

Result:

- production environment ready

---

# 16. Phase 14 — Cutover

Tasks:

- deploy app
- apply database schema
- configure first admin
- verify public site
- verify admin
- verify mail
- verify images
- verify redirects
- verify sitemap/robots
- verify analytics
- monitor errors

Result:

- new `prodajaklimauredjaja.com` live

---

# 17. Phase 15 — Post-Launch SEO and Monitoring

Tasks:

- Search Console sitemap submission
- inspect important URLs
- monitor indexing
- monitor 404s
- monitor redirect issues
- monitor contact delivery
- monitor logs
- review real search queries over time

Result:

- stable post-launch operation

---

# 18. Initial Release Scope

Included:

- public business website
- searchable/filterable product catalog
- product details
- product images
- flexible specifications
- brands/categories
- contact forms
- one-admin secure panel
- product CRUD
- analytics
- strong technical/local SEO foundation
- Hostinger deployment

Excluded:

- cart
- checkout
- payments
- customer accounts
- orders
- inventory
- wishlist
- reviews
- complex CMS
- multi-admin permissions
- blog in initial release

---

# 19. Codex Working Rule

Codex should receive one bounded phase/task at a time.

Do not prompt:

> Build the whole Frigo Sistem website.

Prefer:

> Implement Phase 3 admin authentication according to `03_System_Architecture.md`, `08_Security.md`, and `12_Roadmap.md`. Do not implement product CRUD yet.

Every phase should be reviewed and tested before moving to the next major phase.
