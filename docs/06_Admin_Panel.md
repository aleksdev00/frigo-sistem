# Admin Panel

## 1. Overview

The Frigo Sistem admin panel is a private catalog-management interface.

Its purpose is to allow the company to maintain air-conditioning products without editing source code.

The admin panel should remain intentionally small.

Primary modules:

- Login
- Dashboard
- Products
- Brands
- Categories
- Analytics

Product images and specifications are managed from the product workflow rather than as unrelated standalone CMS systems.

---

# 2. Access

Recommended route structure:

```text
/admin/login
/admin
/admin/products
/admin/brands
/admin/categories
/admin/analytics
```

The exact admin prefix may be changed before production, but its secrecy is not a security control.

Every admin route except login must enforce authentication server-side.

---

# 3. Authentication

Login fields:

- username
- password

Requirements:

- CSRF token
- login rate limiting
- generic invalid-login error
- password hash verification
- session regeneration after success
- Secure + HttpOnly + SameSite session cookie in production
- logout destroys the authenticated session

No:

- registration
- public password reset
- roles
- permissions
- multiple-user management in v1

---

# 4. Dashboard

The dashboard should answer useful operational questions quickly.

Recommended cards/data:

- total products
- active products
- hidden products
- total views in selected period
- most viewed products
- most viewed categories / climate types
- recently created or updated products

Charts should be simple and based on `product_views`.

Do not build enterprise analytics.

---

# 5. Product List

The list should display:

- main image
- product name
- brand
- category
- status
- updated date
- views
- actions

Actions:

- view public page
- edit
- activate/hide
- delete

Search/filter inside admin may include:

- product name/code
- brand
- category
- status

---

# 6. Create Product

The create form is the central admin workflow.

## 6.1 Basic Data

Required:

- product name
- brand
- category

Optional:

- product code/model
- price
- short description
- full description
- featured flag

## 6.2 URL and SEO

Fields:

- slug
- SEO title
- SEO description

Slug may be auto-generated from the name but must remain editable and unique.

SEO fields may fall back to sensible generated values when empty, but manually entered values take precedence.

## 6.3 Images

Admin can:

- upload multiple images
- choose main image
- reorder images
- delete image

The UI should clearly show upload/processing errors.

## 6.4 Specifications

Admin can dynamically add rows:

```text
[ Specifikacija ] [ Vrednost ] [ Remove ]
```

Rows can be reordered.

Example:

```text
Kapacitet hlađenja | 12.000 BTU
Energetska klasa   | A++
Rashladni gas      | R32
```

## 6.5 Status

States:

- Active
- Hidden

New products may default to Hidden until the admin intentionally publishes them. This reduces accidental publication of incomplete product pages.

---

# 7. Edit Product

Editing supports all create-product fields.

Changes to slug require special care:

- if an indexed/public slug changes, preserve the old URL in the redirect strategy
- do not silently break existing external links

Image changes must not leave orphan files when deletion succeeds.

---

# 8. Delete Product

Deletion must require explicit confirmation.

When a product is deleted:

- product row is removed
- image metadata is removed
- specification rows are removed
- product-view rows are removed
- physical image files are cleaned up

Before permanent deletion, the interface should recommend hiding a product when the device may need to remain reachable for SEO/history.

---

# 9. Brand Management

Brand list:

- name
- slug
- active status
- number of products

Admin can:

- create
- edit
- activate/hide

Deleting a brand with assigned products must be blocked unless products are reassigned.

Optional:

- logo
- SEO title
- SEO description

---

# 10. Category Management

Category list:

- name
- slug
- active status
- number of products

Admin can:

- create
- edit
- activate/hide

Deleting a category with assigned products must be blocked unless products are reassigned.

Optional:

- description
- SEO title
- SEO description

---

# 11. Image Upload UX

The UI should:

- state allowed image formats
- state maximum upload size
- show preview
- show upload validation errors
- preserve existing valid product data if an image upload fails
- avoid confusing partial saves

Recommended accepted user uploads:

- JPG/JPEG
- PNG
- WebP

Server processing may normalize images to WebP.

SVG is not required.

---

# 12. Analytics

Analytics is internal and simple.

Required:

- top viewed products
- top viewed categories / types
- product views over time

Time ranges may include:

- last 7 days
- last 30 days
- all time

The dashboard/chart should not require third-party visitor tracking.

Phase 6 uses a small dependency-free canvas chart loaded only by the existing admin JavaScript. The 7-day and 30-day views include zero-view dates; all-time results group recorded dates and are capped at 3,660 daily points to keep the response bounded. Invalid range values safely fall back to 30 days.

---

# 13. Validation

Every admin form must validate server-side.

Examples:

Product:

- name required
- brand/category valid and active/allowed
- slug valid and unique
- price numeric and non-negative if present
- descriptions length-bounded
- SEO fields length-bounded

Image:

- request upload succeeded
- MIME allowed
- image decodes successfully
- size within configured limit
- dimensions within configured limit

Client-side validation is only a usability enhancement.

---

# 14. Admin UX Principles

- simple navigation
- clear success/error messages
- confirmation before destructive action
- no hidden side effects
- preserve entered data after validation errors
- mobile usability is desirable, but desktop administration is the primary use case
- no decorative complexity that slows product entry

---

# 15. Admin Acceptance Criteria

The admin panel is ready when one administrator can safely:

- log in
- create a complete product
- upload/reorder/delete images
- add/reorder/edit specifications
- publish/hide products
- edit products
- manage brands/categories
- delete products safely
- view basic analytics
- log out

without editing application files or database rows manually.

---

# 16. Phase 5 Owner Workflow

Product creation remains intentionally two-step: the owner first saves valid basic product data, then is redirected to the edit page where images and specifications can be added. This guarantees that a product ID exists before filesystem storage is used and prevents an image failure from discarding otherwise valid product data.

The edit page provides multiple-image upload, a neutral no-image placeholder, main-image selection, simple up/down ordering, deletion, and dynamic specification rows. Specification rows are saved separately from basic data so validation errors in one section do not overwrite another section.
