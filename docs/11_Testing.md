# Testing

Phase 11 coverage is in `tests/phase11.php` and is included in `composer check`. It covers public metadata and H1 behavior, filter noindex rules, overrides and inactive entities, Open Graph and conservative JSON-LD, dynamic sitemap/robots responses, hostile Host isolation, exact safe 301 mappings, and noindex 404 responses.

Phase 12 coverage is in `tests/phase12.php`, with related session and upload regressions retained in the existing phase tests. It covers production configuration fail-closed behavior, conditional HSTS, frame/CSP policy, redirect safety, wrong-method write protection, path handling, upload resource bounds, inactive-session revocation, and the complete prior regression suite.

## 1. Overview

Testing ensures that the Frigo Sistem website is reliable, secure, usable, SEO-safe, and ready to replace the current production website.

Testing combines:

- manual functional testing
- focused automated testing
- security testing
- responsive/browser testing
- performance testing
- SEO/migration testing
- user acceptance testing

The project does not require an oversized enterprise QA process.

---

# 2. Test Environments

Tests should run primarily against:

- local development environment
- production-like configuration before deployment

Never run destructive tests against live production data.

---

# 3. Automated Testing

Use PHPUnit for small, high-value tests where practical.

Priority automated targets:

- validators
- slug generation
- CSRF helpers
- authentication helpers
- repository/query behavior
- product visibility rules
- redirect mapping helpers
- image-validation service where testable

Automated tests are support for, not a replacement for, full workflow testing.

---

# 4. Public Website Functional Tests

## Homepage

Verify:

- design sections render correctly
- navigation works
- CTA links work
- layout is responsive
- images do not cause major layout shift

## Product Catalog

Verify:

- only active products display
- cards show correct product data
- pagination works if present
- empty catalog state is safe

## Search

Verify:

- name search
- brand search
- code/model search
- no-result state
- special characters
- injection-like input does not break the query

## Filters

Verify:

- brand filter
- category filter
- combined filters
- invalid filter values
- active products only

## Product Details

Verify:

- correct product
- correct gallery
- main image
- specifications
- description
- CTA
- related products
- hidden product behavior
- canonical URL
- metadata

## Contact

Verify:

- valid submission
- missing/invalid fields
- CSRF failure
- spam/rate-limit behavior
- product context attachment
- SMTP failure produces safe user error
- no mail credentials are exposed

---

# 5. Admin Functional Tests

## Authentication

Verify:

- valid login
- invalid login
- generic error
- logout
- direct access to protected routes
- session regeneration
- session expiration behavior

## Product Management

Verify:

- create valid product
- reject missing required data
- unique slug behavior
- edit product
- hide/activate product
- delete confirmation
- delete cleanup
- price optional behavior
- SEO fallback behavior

## Brands / Categories

Verify:

- create/edit
- slug uniqueness
- cannot delete referenced taxonomy without explicit resolution
- active/hidden behavior

## Specifications

Verify:

- add
- edit
- remove
- reorder
- long/invalid values

## Images

Verify:

- valid JPG/PNG/WebP
- invalid extension/content mismatch
- fake image
- oversized image
- huge dimensions
- multiple upload
- main image selection
- reorder
- delete
- orphan cleanup
- executable upload rejected

## Analytics

Verify:

- product view is recorded
- hidden/admin page views do not pollute analytics
- top product calculation
- top category calculation
- date-range behavior

---

# 6. Database Tests

Verify:

- schema import/migrations succeed
- foreign keys
- unique indexes
- cascade rules
- restrict rules
- SET NULL rule only if optional contact table exists
- transaction rollback on failed product data write
- queries use expected indexes for common catalog operations

---

# 7. Security Tests

Required before production:

- SQL injection probes
- reflected XSS probes
- stored XSS probes through admin fields
- CSRF token rejection
- session fixation check
- session cookie flags under HTTPS
- unauthenticated admin POST requests
- login throttling
- path traversal attempts
- malicious upload attempts
- MIME spoofing
- direct access to upload scripts/files
- production error leakage
- sensitive file access (`.env`, logs, migrations)

Run `composer audit` before release.

---

# 8. Responsive and Browser Testing

Test representative sizes:

- mobile
- tablet
- laptop
- desktop

Browsers:

- Chrome
- Edge
- Firefox
- Safari / iOS Safari when available

Focus on:

- navigation
- catalog filters
- galleries
- specification tables
- forms
- admin product-entry usability

---

# 9. Accessibility Checks

Verify:

- keyboard navigation
- visible focus
- labels
- form errors
- heading order
- alt text
- contrast
- modal/lightbox keyboard behavior if used

Automated accessibility tools may be used, but manual keyboard checks are still required.

---

# 10. Performance Testing

Measure production-like pages, especially:

- homepage
- catalog
- image-heavy product page

Check:

- image size/dimensions
- number of requests
- render-blocking assets
- server response time
- database query count
- layout stability
- mobile performance

Performance problems should be fixed based on measured bottlenecks.

---

# 11. SEO and Migration Tests

Before deployment:

- crawl current known URLs
- verify redirect map
- validate 301 targets
- ensure no redirect chains/loops
- validate canonical tags
- validate sitemap
- validate robots
- ensure hidden/admin/search pages are not indexable
- validate structured data
- verify unique titles/H1s on important pages
- check broken internal links

After deployment:

- recrawl major old URLs
- verify response codes
- inspect key pages in Search Console
- monitor new 404s

---

# 12. User Acceptance Testing

A Frigo Sistem representative should verify that they can:

- understand dashboard
- add a new product
- upload images
- edit specifications
- hide/publish product
- edit brand/category
- find the product publicly
- use the contact flow

Feedback should prioritize real usability problems over decorative changes.

---

# 13. Regression Checklist

Before each release verify at minimum:

- homepage
- catalog
- search
- filters
- product page
- contact
- admin login
- product CRUD
- image upload
- specification management
- analytics
- redirects
- sitemap
- HTTPS

---

# 14. Release Acceptance Criteria

Release is approved when:

- critical functional flows pass
- no known critical/high security issue remains
- no broken production-critical migration exists
- admin product workflows pass
- contact delivery passes
- responsive checks pass
- major SEO migration checks pass
- backups and rollback are ready
