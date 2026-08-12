# Product Module

## 1. Overview

The Product Module is the core application module.

It manages air-conditioning products displayed on the public website and edited in the admin panel.

The module must remain flexible enough to support different manufacturers and models for several years without changing the database for every new specification.

---

# 2. Product Entity

A product represents one air-conditioning model.

Example names:

- Midea Xtreme Save Pro 12
- Daikin Sensira 12
- Gree Amber Nordic

A product belongs to one brand and one category.

A product may have:

- multiple images
- multiple specifications
- multiple view events

---

# 3. Product Data

Required:

- name
- brand
- category
- slug

Optional:

- product code/model
- price
- short description
- detailed description
- featured flag
- SEO title
- SEO description

State:

- active
- hidden

---

# 4. Product Creation Flow

```text
Admin
  ↓
Create Product
  ↓
Validate product fields
  ↓
Start database transaction
  ↓
Insert product
  ↓
Insert specifications
  ↓
Commit database data
  ↓
Process/store images with cleanup on failure
  ↓
Product remains Hidden or is explicitly published
```

Implementation may adjust transaction boundaries around filesystem operations, but partial broken data must be avoided.

---

# 5. Product Editing

Admin can update:

- name
- brand
- category
- code
- price
- descriptions
- SEO fields
- featured flag
- visibility
- images
- specifications

All writes require:

- authentication
- authorization check for admin session
- CSRF validation
- server-side field validation

---

# 6. Product Visibility

## Active

Active products:

- appear in catalog
- appear in search
- may appear in related products
- may be included in sitemap
- can receive product inquiries

## Hidden

Hidden products:

- remain in admin
- are not shown publicly
- are not returned by public search
- are not included in sitemap
- should return 404 or an appropriate non-public response if accessed directly by visitors

---

# 7. Product Deletion

Permanent deletion is allowed but should not be the default choice for discontinued products with useful search history.

Preferred workflow when appropriate:

```text
Active → Hidden → Permanent delete only when safe
```

On delete:

- cascade database-owned product images metadata
- cascade specifications
- cascade view events
- remove physical product image files
- evaluate whether the former public URL requires a redirect

---

# 8. Product Images

Products support:

- one selected main image
- additional gallery images

Image rules:

- upload only known raster image types
- validate real file contents
- decode and re-encode uploaded images
- randomize stored filename
- generate optimized web version
- store dimensions where available
- preserve aspect ratio
- strip unnecessary metadata
- store relative application path in database

The original user-supplied filename is not used as the stored filesystem name.

---

# 9. Main Image

Main image is selected with `product_images.is_main`.

Rules:

- at most one main image per product
- if the main image is deleted, administrator must select another or the system may safely choose the first remaining image
- a product without an image uses a controlled placeholder

---

# 10. Specifications

Specifications use a flexible key-value model.

Fields:

- name
- value
- sort_order

Examples:

```text
Kapacitet hlađenja → 12.000 BTU
Kapacitet grejanja → 3.8 kW
Energetska klasa   → A++
Rashladni gas      → R32
Wi-Fi              → Da
Nivo buke          → 19 dB
```

Do not create separate database columns for every manufacturer-specific specification unless a future filter/business requirement proves that a field must become structured.

---

# 11. Search

Public search should match:

- product name
- brand name
- product code/model

Rules:

- only active products
- parameterized SQL
- limit result count/pagination
- escaped query output
- no search query may alter SQL structure

Start with straightforward indexed queries. Introduce FULLTEXT search only if product volume and real search behavior justify it.

---

# 12. Filtering

Initial filters:

- brand
- category

Future filters can be added if product data is normalized enough to support reliable results.

Do not attempt to filter arbitrary key-value specifications with complex SQL in v1 unless explicitly required.

---

# 13. Related Products

Simple deterministic selection is preferred.

Suggested priority:

1. same category
2. optionally same brand
3. active products only
4. exclude current product
5. limit to a small number

No AI recommendation engine is required.

---

# 14. Product View Analytics

A product-detail request may record a lightweight view event.

The system must avoid recording raw IP addresses.

Optional duplicate reduction can use:

- a short-lived session marker
- a privacy-preserving token hash

Analytics is approximate business insight, not billing-grade measurement.

---

# 15. Product SEO

Each product must support:

- unique slug
- unique or sensible SEO title
- SEO description
- canonical URL
- H1 based on product name
- optimized image alt text
- breadcrumb data
- Product structured data when accurate

Product content should be useful and specific rather than copied mechanically from manufacturer pages.

---

# 16. Product URL Changes

Published URLs should remain stable.

If a slug changes:

- record the old path in the deployment/redirect map
- return 301 from old URL to new canonical URL

A future redirect table may be added only if URL changes become frequent enough to justify it.

---

# 17. Product Module Acceptance Criteria

A product is considered correctly implemented when:

- admin can create/edit/hide/delete it
- its brand/category relationships are valid
- images can be managed safely
- specifications are ordered and displayed correctly
- public page uses correct data
- search/filter behavior is correct
- hidden state is respected everywhere
- SEO metadata is generated correctly
- view analytics do not expose raw personal identifiers
