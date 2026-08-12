# Project Overview

## 1. Project Name

**Frigo Sistem Website Modernization**

Internal system type: **Product Catalog Management System (PCMS)**

---

## 2. Project Description

Frigo Sistem Website Modernization is a complete redesign and technical modernization of the existing website at `prodajaklimauredjaja.com`.

The system is **not an e-commerce platform**. Visitors cannot create accounts, add products to a cart, complete checkout, or make online payments.

The website is a modern business website with a searchable air-conditioning product catalog. Its purpose is to help visitors:

- discover available air-conditioning units
- search and filter products
- view detailed product pages
- review images and technical specifications
- learn about Frigo Sistem services
- contact the company regarding a product, installation, maintenance, or service

The system also includes a private administration panel used to maintain the product catalog.

---

## 3. Business Context

Frigo Sistem sells, installs, services, and maintains air-conditioning systems.

The existing website is being modernized to improve:

- visual quality
- mobile usability
- product presentation
- search-engine visibility
- website performance
- security
- ease of product maintenance

The website should remain stable and maintainable for approximately **3–4 years** without requiring a major architectural rewrite.

---

## 4. Primary Goals

The project must:

- provide a professional and modern public website
- provide a fast searchable product catalog
- present product details in a structure similar to large retail product pages, without purchasing functionality
- allow the administrator to manage air-conditioning products without developer assistance
- allow multiple images and flexible specifications per product
- provide simple product-view analytics
- generate customer inquiries through clear contact actions
- be built SEO-first for the Serbian market, with strong local relevance for Frigo Sistem
- be secure and compatible with the existing Hostinger shared-hosting environment
- remain intentionally simple and avoid unnecessary platform complexity

---

## 5. Public Website Scope

The public website includes:

- Homepage
- About
- Services
- Product Catalog
- Product Details
- Gallery / project showcase, when required by the approved design
- Contact
- Legal pages required for the production website

Core visitor capabilities:

- browse active products
- search products
- filter products
- open a dedicated product page
- view product images
- view technical specifications
- contact Frigo Sistem
- submit a product-specific inquiry

---

## 6. Administration Scope

The administration panel is intentionally focused on catalog management.

The administrator can:

- log in securely
- log out
- view the dashboard
- create products
- edit products
- hide or activate products
- delete products
- manage brands
- manage categories
- upload and manage product images
- select the main product image
- manage flexible product specifications
- manage product SEO fields
- view basic product and category view analytics

The first version supports **one administrator account**.

---

## 7. Explicitly Excluded Features

The following are not part of the project unless the business scope changes:

- online shopping
- shopping cart
- checkout
- online payment
- customer registration
- customer accounts
- order management
- shipping
- coupons
- wishlist
- product reviews
- inventory / stock management
- newsletter system
- complex role and permission system
- public user-generated content

A blog is not part of the initial scope. It may be considered later only if an SEO/content strategy demonstrates clear business value.

---

## 8. Technology Direction

The approved direction is:

- **PHP 8.4** for server-side application code
- **MySQL** for persistent data
- **PDO** with prepared statements for database access
- HTML5
- CSS3
- modern JavaScript
- Composer for dependency/autoload management where needed
- Git + GitHub for source control
- Hostinger Premium shared hosting for production

The project does not depend on Laravel, Supabase, Node.js, or a JavaScript application framework.

---

## 9. Design Direction

The approved homepage visual concept supplied for Frigo Sistem is the primary visual reference for the public website.

Product-detail pages should follow the information hierarchy of modern retail product pages:

- strong product title area
- image gallery
- concise key information
- clear contact CTA
- detailed description
- structured technical specifications
- related products

The interface must not visually imply that online purchase is available.

---

## 10. SEO Direction

SEO is a first-class project requirement, not a post-development add-on.

The website must be designed for:

- Serbian-language search intent
- local commercial intent
- product and brand discovery
- installation/service intent
- strong technical SEO
- preservation of existing useful URLs through a controlled redirect plan
- fast, mobile-friendly pages
- accurate structured data
- Google Search Console readiness

No search-volume or keyword assumptions should be hardcoded into the project documentation without actual research.

---

## 11. Success Criteria

The project is successful when:

- visitors can quickly find and understand suitable products
- product pages are complete and easy to navigate
- contact actions are obvious and functional
- the administrator can manage the catalog without editing source code
- the admin area is strongly protected
- the website performs well on mobile and desktop
- the new site can replace the existing production site without losing important URLs unnecessarily
- SEO foundations are implemented correctly
- deployment and rollback procedures are documented
- the system remains easy to maintain for the next several years

---

## 12. Guiding Principle

> Build the simplest system that completely satisfies Frigo Sistem's real business needs.

Features should be added only when they provide clear value to visitors, the administrator, SEO, security, or maintainability.
