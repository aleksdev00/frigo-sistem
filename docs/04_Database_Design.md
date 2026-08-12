# Database Design

## 1. Overview

The Frigo Sistem application uses MySQL as its persistent database.

The database supports:

- administrator authentication
- brands
- categories
- products
- product images
- flexible product specifications
- basic product-view analytics

The first version intentionally avoids e-commerce tables and unnecessary CMS tables.

---

# 2. Database Technology

- MySQL
- character set: `utf8mb4`
- collation: use a modern `utf8mb4` collation supported by the production MySQL version
- access: PDO prepared statements
- schema changes: versioned SQL files stored in Git

No ORM is required.

Database scripts should live under:

```text
database/
├── migrations/
└── seeds/
```

Example:

```text
001_initial_schema.sql
002_add_product_seo_fields.sql
```

The production database may be created through Hostinger hPanel / phpMyAdmin, but the authoritative schema history remains in the Git repository.

---

# 3. Core Tables

Initial database scope:

- `admins`
- `brands`
- `categories`
- `products`
- `product_images`
- `product_specifications`
- `product_views`

Optional later table:

- `contact_requests` only if Frigo Sistem explicitly wants inquiries stored inside the application

No orders, carts, customers, payments, inventory, reviews, or wishlists are required.

---

# 4. Table: admins

Purpose: stores the private administrator account.

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK, auto increment |
| username | VARCHAR(100) | unique, not null |
| password_hash | VARCHAR(255) | not null |
| is_active | TINYINT(1) | default 1 |
| last_login_at | DATETIME | nullable |
| created_at | DATETIME | not null |
| updated_at | DATETIME | not null |

Notes:

- never store a plaintext password
- one account is sufficient for the initial version
- username uniqueness must be enforced by the database

---

# 5. Table: brands

Purpose: manufacturers represented in the catalog.

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK, auto increment |
| name | VARCHAR(150) | unique, not null |
| slug | VARCHAR(180) | unique, not null |
| logo_path | VARCHAR(255) | nullable |
| seo_title | VARCHAR(255) | nullable |
| seo_description | VARCHAR(320) | nullable |
| is_active | TINYINT(1) | default 1 |
| created_at | DATETIME | not null |
| updated_at | DATETIME | not null |

---

# 6. Table: categories

Purpose: product type / catalog grouping.

Examples:

- inverter klima
- mobilna klima
- multi-split sistem

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK, auto increment |
| name | VARCHAR(150) | unique, not null |
| slug | VARCHAR(180) | unique, not null |
| description | TEXT | nullable |
| seo_title | VARCHAR(255) | nullable |
| seo_description | VARCHAR(320) | nullable |
| is_active | TINYINT(1) | default 1 |
| created_at | DATETIME | not null |
| updated_at | DATETIME | not null |

---

# 7. Table: products

Purpose: main air-conditioning product entity.

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK, auto increment |
| brand_id | BIGINT UNSIGNED | FK, not null |
| category_id | BIGINT UNSIGNED | FK, not null |
| name | VARCHAR(255) | not null |
| slug | VARCHAR(255) | unique, not null |
| code | VARCHAR(150) | nullable, indexed |
| price | DECIMAL(12,2) | nullable |
| short_description | TEXT | nullable |
| description | LONGTEXT | nullable |
| seo_title | VARCHAR(255) | nullable |
| seo_description | VARCHAR(320) | nullable |
| is_featured | TINYINT(1) | default 0 |
| is_active | TINYINT(1) | default 1 |
| created_at | DATETIME | not null |
| updated_at | DATETIME | not null |

Notes:

- main image is not duplicated in this table; it is selected through `product_images.is_main`
- hidden products remain in the database but are not exposed publicly
- `price` is optional because the site is not an online store

---

# 8. Table: product_images

Purpose: image gallery and main-image selection.

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK, auto increment |
| product_id | BIGINT UNSIGNED | FK, not null |
| image_path | VARCHAR(255) | not null |
| alt_text | VARCHAR(255) | nullable |
| is_main | TINYINT(1) | default 0 |
| sort_order | INT UNSIGNED | default 0 |
| width | INT UNSIGNED | nullable |
| height | INT UNSIGNED | nullable |
| created_at | DATETIME | not null |

Rules:

- each product may have multiple images
- application logic must enforce at most one main image per product
- deleting a product cascades to its image rows
- deleting an image row must also delete the corresponding file through the application service

---

# 9. Table: product_specifications

Purpose: flexible technical specifications.

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK, auto increment |
| product_id | BIGINT UNSIGNED | FK, not null |
| name | VARCHAR(190) | not null |
| value | VARCHAR(500) | not null |
| sort_order | INT UNSIGNED | default 0 |
| created_at | DATETIME | not null |
| updated_at | DATETIME | not null |

Example:

```text
Kapacitet hlađenja → 12.000 BTU
Rashladni gas       → R32
Wi-Fi               → Da
```

A flexible key-value model is intentional because specifications vary between manufacturers.

---

# 10. Table: product_views

Purpose: lightweight internal catalog analytics.

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK, auto increment |
| product_id | BIGINT UNSIGNED | FK, not null |
| viewed_at | DATETIME | not null |
| visitor_token_hash | CHAR(64) | nullable |

`visitor_token_hash` is optional and may be used only to reduce obvious duplicate counting. It must not contain a raw IP address.

Indexes:

- `(product_id, viewed_at)`
- `viewed_at`

Analytics can aggregate by joining products to categories and brands.

---

# 11. Optional Table: contact_requests

Do not create this table unless the company wants inquiries stored in the admin system.

If enabled:

| Column | Type | Rules |
|---|---|---|
| id | BIGINT UNSIGNED | PK, auto increment |
| product_id | BIGINT UNSIGNED | nullable FK |
| name | VARCHAR(150) | not null |
| phone | VARCHAR(50) | nullable |
| email | VARCHAR(255) | nullable |
| message | TEXT | not null |
| status | VARCHAR(30) | default `new` |
| created_at | DATETIME | not null |

Deletion rule:

- deleting a product should set `contact_requests.product_id` to NULL, not delete the historical inquiry

Personal-data retention must be defined before enabling this feature.

---

# 12. Relationships

```text
brands 1 ─── N products
categories 1 ─── N products

products 1 ─── N product_images
products 1 ─── N product_specifications
products 1 ─── N product_views
```

Optional:

```text
products 1 ─── N contact_requests
```

---

# 13. Foreign Key Behavior

Recommended:

- `products.brand_id` → `brands.id` : RESTRICT
- `products.category_id` → `categories.id` : RESTRICT
- `product_images.product_id` → `products.id` : CASCADE
- `product_specifications.product_id` → `products.id` : CASCADE
- `product_views.product_id` → `products.id` : CASCADE
- optional `contact_requests.product_id` → `products.id` : SET NULL

Brands/categories should not be silently deleted while products still reference them.

---

# 14. Required Indexes

At minimum:

- `brands.slug` UNIQUE
- `categories.slug` UNIQUE
- `products.slug` UNIQUE
- `products.code`
- `products.brand_id`
- `products.category_id`
- `products.is_active`
- `product_images.product_id`
- `product_specifications.product_id`
- `product_views(product_id, viewed_at)`
- `product_views.viewed_at`

Search requirements should be tested before introducing FULLTEXT indexes. Do not add complexity without measured need.

---

# 15. Transactions

Product creation/update involving multiple tables should use a transaction where practical.

Example:

```text
BEGIN
  update product
  replace specifications
  update image metadata
COMMIT
```

Filesystem operations cannot be rolled back by MySQL automatically, so image services must include cleanup/error handling.

---

# 16. Slugs

Slugs must:

- be lowercase
- use stable ASCII-friendly characters
- be unique
- avoid changing unnecessarily after publication

If an already-indexed product slug changes, the old URL should be preserved in a redirect map instead of simply returning 404.

---

# 17. Database Security

- production credentials must not be committed to Git
- use a dedicated database user
- grant only required database privileges
- use PDO prepared statements
- use `utf8mb4`
- never expose database errors to visitors
- production backups must include the database

---

# 18. Deliberately Excluded Tables

Do not create tables for:

- orders
- cart
- customers
- payments
- shipping
- coupons
- reviews
- wishlist
- inventory

unless the business scope explicitly changes.
