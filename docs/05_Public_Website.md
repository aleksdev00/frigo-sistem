# Public Website

## 1. Overview

The public website is the customer-facing part of the Frigo Sistem Website Modernization project.

Its goals are to:

- present Frigo Sistem professionally
- help visitors find appropriate air-conditioning products
- present detailed and trustworthy product information
- communicate installation, service, and maintenance capabilities
- generate phone calls and contact inquiries
- perform strongly in organic and local search

The website is not an e-commerce platform.

Visitors cannot purchase products online.

---

# 2. Primary Navigation

Recommended primary structure:

```text
Početna
O nama
Usluge
Klima uređaji
Galerija
Kontakt
```

The final navigation must follow the approved design while keeping product discovery and contact actions obvious.

---

# 3. Homepage

The homepage must follow the supplied Frigo Sistem redesign as the primary visual reference.

Expected sections include:

- top contact/header information
- main navigation
- hero area
- company/about preview
- services
- product-discovery CTA and/or selected products when appropriate
- gallery / completed-work section
- testimonials if approved content exists
- contact section
- footer

Do not invent dynamic CMS controls for every homepage section unless the company actually needs them.

Static business content can remain in source-controlled templates if changes are rare.

---

# 4. Product Catalog

Recommended public URL:

```text
/klima-uredjaji
```

The catalog must:

- show only active products
- provide search
- provide filters
- use pagination when product volume requires it
- remain fast on mobile devices
- use crawlable product links
- provide a useful no-results state

Product cards should include:

- image
- name
- brand
- category or important concise detail
- optional price
- details CTA

Do not include cart icons, purchase buttons, stock counters, or checkout language that implies online purchase.

---

# 5. Search

Search fields:

- product name
- brand
- product code/model

Rules:

- server-side validation
- safe parameterized database queries
- active products only
- predictable URL/query parameter behavior
- internal search-result pages should generally be `noindex`

Search should not generate unlimited crawlable URL combinations.

---

# 6. Filters

Initial filters:

- brand
- category

Filter state should use readable query parameters.

Example:

```text
/klima-uredjaji?brand=midea&category=inverter
```

Faceted/filter combinations are user-experience tools by default, not automatically SEO landing pages.

Only intentionally curated category/brand landing pages should be indexable.

---

# 7. Product Details Page

Recommended URL:

```text
/klima-uredjaji/{slug}
```

The page should use a product-information hierarchy inspired by modern retail websites such as Gigatron, but visually adapted to Frigo Sistem and without purchasing functionality.

## 7.1 Product Header

Display:

- H1 product name
- brand
- product code/model when available
- main image
- image thumbnails/gallery
- optional price
- concise summary
- prominent CTA

CTA examples:

- Pozovite nas
- Pošaljite upit
- Zakažite ugradnju

## 7.2 Product Gallery

Requirements:

- optimized responsive images
- thumbnails when multiple images exist
- accessible controls
- no layout shift caused by missing image dimensions
- alt text

Zoom/lightbox behavior may be added with a small justified library.

## 7.3 Product Description

Provide:

- concise introduction
- detailed description
- key benefits written for actual customers

Manufacturer descriptions must not be copied blindly if they create duplicated or low-value content.

## 7.4 Specifications

Specifications must render from the database.

Example:

| Specifikacija | Vrednost |
|---|---|
| Kapacitet hlađenja | 12.000 BTU |
| Energetska klasa | A++ |
| Rashladni gas | R32 |
| Wi-Fi | Da |

The table/list must remain usable on mobile.

## 7.5 Related Products

Related products should be selected using simple rules such as:

1. same category
2. same brand when relevant
3. exclude current product
4. active products only

No complex recommendation engine is required.

## 7.6 Product Inquiry

The product page may contain a compact inquiry form.

The backend must attach the selected product internally rather than trusting a free-text product name submitted by the browser.

---

# 8. About Page

Purpose:

- establish trust
- explain company experience
- communicate what Frigo Sistem does
- provide useful local/business context

Content should be specific and factual, not generic SEO filler.

---

# 9. Services Page

The services page should explain actual services offered by Frigo Sistem, such as:

- sales
- installation
- servicing
- maintenance

Service content should support both users and local commercial search intent.

---

# 10. Gallery / Completed Work

If included in the approved final design, the gallery should display real Frigo Sistem work and/or premises.

Images must be:

- optimized
- descriptive
- relevant
- legally usable by Frigo Sistem

A database-backed gallery is not required unless the company needs frequent gallery updates.

---

# 11. Contact Page

The contact page should include:

- phone
- email
- address
- working hours
- map/location when useful
- contact form
- clear installation/service CTA

Business name, address, and phone information should remain consistent with other public business profiles.

---

# 12. Footer

Footer should include:

- company identity
- important navigation
- contact details
- legal links
- copyright
- social links if active

Do not add empty or abandoned social channels merely for appearance.

---

# 13. Responsive Behavior

The website must be designed for:

- mobile
- tablet
- desktop

Mobile priorities:

- readable typography
- touch-friendly navigation
- easy product filtering
- fast image delivery
- visible contact actions
- product specifications that do not overflow

---

# 14. Accessibility

At minimum:

- semantic headings
- labeled form controls
- keyboard-accessible navigation
- visible focus states
- meaningful alt text
- adequate contrast
- buttons/links with understandable names

---

# 15. Performance

Public pages should:

- server-render important content
- minimize third-party JavaScript
- use responsive optimized images
- lazy-load below-the-fold images
- declare image width/height or aspect ratio
- cache static assets
- avoid unnecessary fonts and font weights
- avoid large blocking scripts

---

# 16. Public Error States

Required:

- custom 404 page
- safe generic 500 error page
- product-not-found behavior
- no-results search state

Deleted or changed legacy URLs should be evaluated for 301 redirects before being allowed to become 404.

---

# 17. Public Website Acceptance Criteria

The public website is ready when:

- all approved design sections are implemented
- catalog/search/filter workflows work
- product pages display complete data
- no e-commerce controls are present
- contact actions work
- mobile behavior is verified
- SEO-critical metadata is correct
- important old URLs are redirected
- performance and accessibility checks have been completed
