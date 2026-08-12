# Functional and Non-Functional Requirements

## 1. Overview

This document defines the requirements for the Frigo Sistem Website Modernization project.

The system contains two primary areas:

- Public Website
- Administration Panel

The product catalog is the central functional feature. The system is not an e-commerce application.

---

# 2. Public Website Requirements

## 2.1 Homepage

The homepage must:

- follow the approved Frigo Sistem visual design
- introduce the company
- present key services
- provide clear navigation to the product catalog
- include prominent contact / installation calls to action
- include the content sections required by the approved design
- be responsive across mobile, tablet, and desktop
- load efficiently

Featured products or brands may be shown when they improve product discovery.

---

## 2.2 Product Catalog

Visitors must be able to:

- browse all active products
- search products
- filter products
- open a product-detail page
- navigate without any cart or checkout flow

Each product card should display, when available:

- main image
- product name
- brand
- category
- short key information
- price, if Frigo Sistem chooses to display prices
- link to product details

Hidden products must never appear in public listings, public search, public sitemap output, or related-product sections.

---

## 2.3 Product Search

Search must support at minimum:

- product name
- brand
- product code / model

Search behavior must:

- ignore irrelevant case differences
- safely escape all user input
- return only active products
- return an understandable empty-state message when no result exists

Search-result URLs should not be indexed as standalone SEO landing pages.

---

## 2.4 Product Filtering

Initial filters:

- brand
- category

The architecture should permit later filters such as:

- cooling capacity / BTU
- energy class
- Wi-Fi support
- price range

Advanced filters are not required until product data is consistent enough to support them reliably.

---

## 2.5 Product Details

Every active product must have a dedicated, crawlable page.

A product page should support:

- product name
- brand
- category
- product code / model
- optional price
- main image
- additional gallery images
- short description
- detailed description
- technical specifications
- clear contact CTA
- product-specific inquiry
- related products
- breadcrumb navigation
- SEO metadata

Optional PDF documentation may be supported later if Frigo Sistem has reliable manufacturer documentation to publish.

---

## 2.6 Contact and Product Inquiry

The public website must provide a general contact form and may provide a product-specific inquiry form on product pages.

Recommended fields:

- name
- phone
- email
- message

At least one of phone or email should be required, rather than forcing both when not necessary.

Product-specific inquiries must automatically include the internal product ID and product name.

The form must:

- validate input server-side
- include CSRF protection
- include anti-spam / rate-limit protection
- never expose mail credentials
- send the inquiry to the configured Frigo Sistem mailbox

The initial version does **not** require a contact-message management module inside the admin panel. Storing inquiries in the database is optional and should be added only if the company explicitly wants an internal inquiry history.

---

# 3. Administration Panel Requirements

## 3.1 Authentication

The system supports one administrator account.

Administrator must be able to:

- log in
- access protected admin pages
- log out

There is no:

- public registration
- customer authentication
- role system
- permission matrix
- password-reset-by-email flow in the initial version

Password changes can be performed through a controlled administrator workflow or maintenance procedure.

---

## 3.2 Dashboard

The dashboard should remain simple and show useful catalog information such as:

- total products
- active products
- hidden products
- recent products
- total recorded product views
- most viewed products
- most viewed categories / air-conditioner types

Analytics must not require invasive visitor tracking.

---

## 3.3 Product Management

Administrator must be able to:

- create a product
- edit a product
- activate / hide a product
- delete a product with confirmation

Product fields:

- name
- slug
- brand
- category
- product code / model
- optional price
- short description
- full description
- active status
- optional featured status
- SEO title
- SEO description

Slug values must be unique.

---

## 3.4 Brand Management

Administrator must be able to:

- create a brand
- edit a brand
- hide / activate a brand when useful
- delete a brand only when no product depends on it, or after explicitly reassigning products

Brand data:

- name
- slug
- optional logo
- optional SEO title
- optional SEO description

---

## 3.5 Category Management

Administrator must be able to:

- create a category
- edit a category
- hide / activate a category when useful
- delete a category only when no product depends on it, or after explicitly reassigning products

Category data:

- name
- slug
- optional description
- optional SEO title
- optional SEO description

---

## 3.6 Product Images

Administrator must be able to:

- upload images
- delete images
- reorder images
- select the main image

Upload processing must:

- validate actual MIME type and image validity
- reject executable or unsupported content
- apply server-side size/dimension limits
- generate safe random filenames
- remove unneeded metadata by re-encoding
- generate optimized web images
- preserve image aspect ratio

SVG uploads are not required.

---

## 3.7 Product Specifications

Specifications must be flexible because different manufacturers expose different technical characteristics.

Administrator must be able to:

- add specification rows
- edit specification rows
- delete specification rows
- reorder specification rows

Each specification contains:

- name
- value
- sort order

Example:

`Kapacitet hlađenja` → `12.000 BTU`

---

# 4. SEO Requirements

The public website must support:

- Serbian SEO-friendly URLs
- unique titles and descriptions
- canonical URLs
- XML sitemap
- robots.txt
- breadcrumb navigation
- structured data where accurate
- noindex rules for admin and internal search/filter states that should not become landing pages
- 301 redirects from important legacy URLs
- image alt text
- Open Graph metadata
- mobile-first performance

SEO requirements are detailed in `09_SEO_Strategy.md`.

---

# 5. Security Requirements

Required controls include:

- strong password hashing
- PDO prepared statements
- CSRF protection
- output escaping
- strict server-side validation
- secure session configuration
- login throttling / rate limiting
- secure upload processing
- HTTPS in production
- security headers
- environment-based secrets
- disabled production error display
- audit-friendly error logging without exposing sensitive data

Detailed controls are defined in `08_Security.md`.

---

# 6. Performance Requirements

The website should:

- avoid unnecessary JavaScript
- use optimized responsive images
- lazy-load below-the-fold media where appropriate
- use appropriate browser caching
- keep database queries indexed and bounded
- avoid N+1-style query patterns
- minimize third-party scripts
- remain usable on average mobile connections

Performance targets should be validated using real production or production-like measurements rather than guaranteed by documentation alone.

---

# 7. Accessibility and Usability

The website should:

- use semantic HTML
- support keyboard navigation for interactive elements
- provide visible focus states
- provide meaningful labels for form controls
- provide alt text for informative images
- maintain readable contrast
- use responsive touch-friendly controls

---

# 8. Browser Support

Support current mainstream versions of:

- Chrome
- Edge
- Firefox
- Safari

The website should also degrade gracefully on slightly older mobile browsers.

---

# 9. Data and Privacy Scope

The public site should collect only data needed to respond to inquiries.

The application should avoid storing unnecessary visitor personal data.

Product-view analytics should not store raw IP addresses.

---

# 10. Out of Scope

Not included:

- cart
- checkout
- payments
- customer accounts
- order management
- inventory management
- wishlist
- product reviews
- complex CMS
- multiple admin roles
- multilingual support in the initial version
