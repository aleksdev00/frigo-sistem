# Security

## 1. Overview

This document defines mandatory security requirements for the Frigo Sistem Website Modernization project.

The system is intentionally simple, but security is not optional.

Primary assets:

- administrator access
- database integrity
- product data
- uploaded files
- mail credentials
- website availability
- any personal data submitted through contact forms

---

# 2. Security Principles

- deny access by default to admin functionality
- never trust browser input
- validate input server-side
- escape output for the destination context
- use prepared statements
- keep secrets outside Git
- minimize stored personal data
- keep dependencies limited and maintained
- never rely on a hidden admin URL as the main defense

---

# 3. Administrator Authentication

Requirements:

- one administrator account in v1
- password stored only as a secure hash
- use `password_hash()` and `password_verify()`
- prefer Argon2id when available; otherwise use `PASSWORD_DEFAULT`
- generic login failure response
- no username-enumeration message
- login rate limiting
- session regeneration after successful login
- explicit logout
- optional manual credential rotation procedure

No public registration exists.

---

# 4. Session Security

Before starting an authenticated production session, configure:

- `session.use_strict_mode = 1`
- cookies only
- `HttpOnly = true`
- `Secure = true` under HTTPS
- `SameSite = Lax` or stricter when compatible with the application
- short, reasonable admin idle lifetime
- session ID regeneration on login
- session destruction on logout

Do not place authentication state in client-controlled localStorage.

Sensitive admin responses should use appropriate no-cache headers.

---

# 5. Admin Route Protection

All `/admin/*` routes except login must require a valid authenticated session.

Authorization must execute server-side on every protected request.

Directly requesting an admin action URL must not bypass protection.

Admin pages must be excluded from search indexing.

---

# 6. CSRF Protection

All state-changing requests require a valid CSRF token.

Examples:

- login
- logout if implemented as POST
- create/edit/delete product
- image upload/delete/reorder
- specification changes
- brand/category changes

Public contact forms should also use CSRF protection.

CSRF tokens must be generated with cryptographically secure randomness and validated using constant-time comparison.

---

# 7. SQL Injection Protection

Database rules:

- PDO prepared statements for all dynamic values
- never concatenate untrusted input into SQL
- whitelist dynamic sort columns/directions
- use a dedicated production database user
- database credentials only in environment configuration

Raw SQL is allowed when parameterized and reviewed; the project does not rely on an ORM for security.

---

# 8. XSS and Output Encoding

All dynamic output must be encoded according to context.

For normal HTML text use safe HTML escaping such as:

```php
htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
```

Do not allow raw HTML in product descriptions unless a deliberate sanitizer and restricted formatting model are introduced.

JavaScript, URL, and attribute contexts require appropriate handling.

---

# 9. Input Validation

Validate:

- type
- required/optional state
- length
- ranges
- allowed values
- referenced foreign keys
- expected encoding

Do not confuse validation with escaping.

Client-side validation never replaces server-side validation.

---

# 10. File Upload Security

Uploads are one of the highest-risk areas.

Allowed user upload formats in v1:

- JPEG
- PNG
- WebP

Requirements:

- check upload error status
- enforce application file-size limit
- verify MIME using `finfo`
- verify image can actually be decoded
- reject SVG and executable formats
- do not trust file extension
- generate random stored filename
- re-encode image using GD or Imagick
- strip unnecessary metadata by re-encoding
- limit extreme dimensions
- prevent path traversal
- never store user-supplied paths
- do not allow script execution in upload directories

Recommended application upload limit should be substantially below the host's maximum; for example 8–10 MB per source image, subject to final image workflow testing.

---

# 11. Password and Secret Handling

Never commit:

- production `.env`
- database passwords
- mail passwords
- session secrets
- API credentials

Repository includes only:

```text
.env.example
```

Production secrets are configured separately on Hostinger.

---

# 12. Contact Form Security

Public forms require:

- server-side validation
- CSRF
- rate limiting
- anti-spam control such as a honeypot and/or timing checks
- header-injection-safe mail handling
- no direct use of user input in email headers
- bounded field lengths
- safe error messages

If abuse becomes significant, a CAPTCHA-like solution can be added later.

Do not store inquiries in MySQL unless there is a real operational requirement.

---

# 13. HTTP Security Headers

Production should set suitable headers such as:

- `Content-Security-Policy`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy`
- `Permissions-Policy`
- frame-ancestor protection through CSP
- HSTS after HTTPS configuration is confirmed and stable

CSP should be tested with the final design and required third-party resources before enforcement.

---

# 14. HTTPS

Production must use HTTPS.

HTTP should redirect to the canonical HTTPS hostname.

Session cookies marked Secure must only be used after HTTPS is active.

---

# 15. Error Handling

Production:

- `display_errors = Off`
- no stack traces to visitors
- no database error details to visitors
- errors written to controlled logs
- generic public 500 page

Logs must not contain:

- passwords
- database credentials
- raw session IDs
- unnecessary contact-message content

---

# 16. Login Rate Limiting

Login attempts must be throttled.

A simple implementation can track failures using a combination of:

- session/client token
- normalized username
- privacy-conscious server-side attempt records

The system must avoid creating an unbounded database table or easy denial-of-service vector.

After repeated failures, impose increasing delay or temporary lockout.

---

# 17. Database Security

- dedicated database credentials
- least privileges necessary for the application
- no Remote MySQL exposure unless explicitly required
- regular backups
- backup files not stored in public web directories
- PDO exceptions handled safely

---

# 18. Dependency Security

Dependencies should be minimal.

Use Composer packages only when they clearly reduce risk or complexity.

Before releases:

- run `composer audit`
- review outdated security-sensitive dependencies
- commit `composer.lock`

Production should not expose Composer development tools.

---

# 19. Admin URL

A custom/non-obvious admin URL may reduce random automated noise.

However:

> A hidden URL is not authentication.

The application must remain secure even if the admin URL is publicly known.

---

# 20. Security Testing Before Production

Minimum tests:

- failed and successful login
- session fixation resistance
- unauthenticated admin access
- CSRF rejection
- SQL injection payloads
- reflected/stored XSS payloads
- malicious filename/path input
- fake image containing non-image data
- oversized image
- executable upload attempt
- brute-force throttling
- direct access to hidden product/admin actions
- error-message information leakage

---

# 21. Security Acceptance Criteria

Production is not approved until:

- all admin routes are protected
- sessions use secure production settings
- prepared statements are used consistently
- CSRF is active
- output escaping is verified
- upload processing rejects unsafe files
- production secrets are outside Git
- HTTPS is active
- production errors are not displayed
- critical security tests pass
