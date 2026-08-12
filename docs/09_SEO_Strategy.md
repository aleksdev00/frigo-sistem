# SEO Strategy

## 1. Overview

SEO is a primary requirement of the Frigo Sistem Website Modernization project.

The goal is not to add generic SEO tags after development. The site architecture, URLs, content hierarchy, product data, internal linking, image handling, migration plan, and performance must all support organic search from the start.

Primary market:

- Serbia
- Serbian-language users
- local and regional users searching for air-conditioning products and related services

Frigo Sistem's real service area must be reflected accurately. Do not create location pages for places the business does not actually serve.

---

# 2. SEO Objectives

The website should increase qualified organic visibility for search intent around:

- klima uređaji
- specific brands and models
- inverter and other actual product categories
- buying/product-research intent
- installation / ugradnja
- service / servicing
- maintenance
- local commercial searches relevant to Frigo Sistem

Exact keyword priorities must be based on real research before final copy is written.

Do not invent keyword-volume numbers in documentation.

---

# 3. SEO Research Phase

Before final production content, perform a Serbian-market SEO research pass covering:

- current Google results for major commercial queries
- competing local and national websites
- recurring category terminology
- actual manufacturer/model naming
- local intent
- search-result features
- content gaps
- current Frigo Sistem indexed URLs
- backlinks/legacy URLs worth preserving where visible

Outputs:

- keyword / intent map
- page-to-query mapping
- legacy URL redirect map
- title/meta templates
- content priorities

Research findings should be stored separately from this architecture document because search results and competition change over time.

---

# 4. Information Architecture

Recommended Serbian public URL structure:

```text
/
/o-nama
/usluge
/klima-uredjaji
/klima-uredjaji/{product-slug}
/brend/{brand-slug}
/kategorija/{category-slug}
/kontakt
```

URLs should:

- be lowercase
- be short and readable
- use stable slugs
- avoid unnecessary IDs
- remain consistent after publication

Do not create duplicate English `/products/...` and Serbian `/klima-uredjaji/...` routes unless one permanently redirects to the canonical version.

---

# 5. Legacy URL Migration

This is a redesign of an existing domain.

Before production cutover:

1. crawl/export known existing URLs
2. identify URLs with equivalent new content
3. map each important old URL to the best new URL
4. implement HTTP 301 redirects
5. test redirect chains and loops
6. keep the same canonical hostname policy
7. submit/update sitemap after launch

Do not redirect every missing page blindly to the homepage.

Pages with no replacement and no value may correctly return 404/410 after review.

---

# 6. On-Page SEO

Every indexable page requires:

- one clear H1
- logical heading hierarchy
- unique title
- useful meta description
- canonical URL
- descriptive content
- internal links
- correct language (`lang="sr"` or the chosen Serbian locale representation)
- Open Graph metadata

Product pages should include relevant technical information in HTML, not only inside images or client-side scripts.

---

# 7. Product SEO

Each product page should contain:

- exact product/model name
- brand
- category
- useful unique description
- technical specifications
- optimized images
- breadcrumb
- contact / installation CTA
- related internal links

Do not produce thin pages consisting only of a model name and copied manufacturer specifications.

SEO title fallback example:

```text
{Product Name} | Frigo Sistem
```

A manually written title may override the fallback.

Meta descriptions should be natural Serbian copy, not keyword lists.

---

# 8. Brand and Category Pages

Brand/category pages may be indexable when they provide genuine landing-page value.

Examples:

```text
/brend/midea
/kategorija/inverter-klime
```

Useful indexable pages should contain:

- clear title/H1
- relevant introductory copy
- product listing
- internal links
- unique metadata

Do not index empty, near-empty, or duplicate taxonomy pages.

---

# 9. Search and Filter Indexing

Internal search pages should generally be:

```text
noindex, follow
```

Arbitrary filter combinations should not create an unlimited indexable URL space.

Default rule:

- user filters = UX feature
- curated brand/category landing page = potential SEO page

Canonical/noindex behavior must be tested so filtered states do not compete with primary landing pages.

---

# 10. Local SEO

The website should consistently display accurate business information:

- Frigo Sistem name
- address
- phone
- working hours
- service area
- contact details

The same core business information should be consistent with the company's Google Business Profile and other authoritative listings.

Local content should be factual.

Avoid doorway-style pages that repeat the same text for many neighborhoods/cities.

---

# 11. Structured Data

Implement only schema that accurately matches visible content.

Recommended:

- `LocalBusiness` or the most accurate applicable business subtype
- `BreadcrumbList`
- `Product` on product pages
- `Service` only where the page genuinely represents a service

Structured data must:

- match visible page content
- use canonical URLs
- use actual business details
- not invent ratings/reviews/prices/availability

Structured data does not guarantee rich results.

---

# 12. Product Structured Data and Price

Because the website is not a checkout system:

- do not invent Offer data
- if a real public price is displayed and schema requirements are accurately met, structured data may include it
- otherwise describe the Product without pretending that online purchase is available

---

# 13. Image SEO

Images should use:

- descriptive alt text
- appropriate dimensions
- WebP/optimized delivery
- responsive `srcset` where implemented
- explicit width/height or aspect ratio
- human-readable context around the image

Stored filesystem filenames may be randomized for security. SEO does **not** require the physical filename to contain keywords if alt text, surrounding content, and URLs are correct.

Do not sacrifice upload security merely to create keyword filenames.

---

# 14. Technical SEO

Required:

- canonical HTTPS hostname
- 301 HTTP→HTTPS redirect
- one preferred www/non-www version
- XML sitemap
- robots.txt
- correct status codes
- custom 404
- noindex admin
- noindex internal search
- clean internal linking
- crawlable server-rendered product content
- pagination handled consistently
- no broken canonical URLs
- no redirect chains when avoidable

---

# 15. XML Sitemap

Sitemap should include only canonical, indexable URLs such as:

- homepage
- about
- services
- contact
- active products
- active indexable brand/category pages

Do not include:

- admin
- hidden products
- internal search URLs
- arbitrary filter combinations
- redirected URLs
- error pages

The sitemap should update when product visibility/content changes.

---

# 16. robots.txt

`robots.txt` should:

- point to sitemap
- block obvious private/crawl-irrelevant application paths where useful
- not be used as a substitute for authentication
- not be relied upon to remove sensitive URLs from search results

Admin pages must be protected by authentication regardless of robots directives.

---

# 17. Internal Linking

Important internal paths:

```text
Homepage → Catalog
Homepage → Services
Catalog → Product
Brand → Product
Category → Product
Product → Related Product
Product → Contact / Service
Service → Relevant Products or Contact
```

Anchor text should be natural and descriptive.

---

# 18. Content Strategy

Priority content is commercial and useful, not filler.

High-value content types may include:

- strong category introductions
- product descriptions
- installation/service explanations
- selection guidance incorporated into existing pages
- FAQ sections based on real customer questions

A blog should be added only if Frigo Sistem can maintain useful content and SEO research shows it is worthwhile.

AI-generated content must be reviewed for factual accuracy, originality, and usefulness before publishing.

---

# 19. Performance / Core Web Vitals

SEO implementation should support good user performance through:

- optimized images
- limited JavaScript
- limited third-party tags
- efficient PHP/database queries
- browser caching
- stable image/layout dimensions
- sensible font loading
- fast initial server-rendered HTML

Do not chase a perfect synthetic score at the cost of real usability; fix measurable bottlenecks.

---

# 20. Mobile SEO

The mobile version must contain the same important content and metadata as desktop.

Avoid:

- hidden key specifications
- unusable filters
- tiny contact controls
- horizontally broken specification tables

---

# 21. Google Search Console

Before/after launch:

- verify property access
- submit sitemap
- inspect important URLs
- monitor indexing
- monitor 404/redirect problems
- monitor search queries and landing pages
- check manual/security issues if reported

SEO should be monitored after launch rather than treated as finished on deployment day.

---

# 22. Analytics

Use privacy-conscious analytics appropriate to the business.

At minimum, track business outcomes such as:

- product-page engagement
- contact-form submissions
- phone/contact CTA clicks where technically appropriate

Do not add heavy third-party tracking solely for vanity metrics.

---

# 23. SEO Acceptance Criteria

Before launch:

- keyword/intent research completed
- legacy URLs reviewed
- redirect map prepared
- titles/descriptions reviewed
- sitemap valid
- robots valid
- canonical hostname consistent
- product/category/brand indexing rules correct
- structured data validated
- mobile pages verified
- no accidental admin/search indexing
- Search Console plan ready

After launch:

- redirects rechecked
- sitemap submitted
- important pages inspected
- index coverage monitored
- rankings/queries reviewed over time
