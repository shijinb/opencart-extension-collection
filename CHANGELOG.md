# Changelog

Format based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Each extension has its own version (in its `install.xml` and README).

## [Unreleased] – 2026-10-25

First public packaging of the extensions (originally written 2021 for the NatureLoc store). The first commit contains the original files unchanged, so every change below can be reviewed in the history.

### Security
- order-filter-marketing-code 1.1.0, order-filter-telephone 1.1.0: escape filter values (SQL injection).
- order-prev-next-navigation 1.1.0: cast `order_id` to int (SQL injection).
- stock-report 2.0.0: use the standard report permission; the original skipped the access check.
- schema-json-ld 2.0.0: encode JSON-LD with `JSON_HEX_TAG` (XSS through product or review text).
- stock-report 2.0.0: protect CSV export against formula injection.

### Fixed
- order filters: count and sales queries failed with `Unknown column 'o.…'`.
- product-category-breadcrumbs 2.0.0: fatal error on OpenCart 3.0.5.1 (anchor method no longer exists). Now builds the full trail from `category_path`.
- schema-json-ld 2.0.0: real review ratings instead of a fixed 4; out-of-stock products no longer marked InStock; decoded URLs and text; exact average rating; store name from settings.
- search-only-product-tags 2.0.0: operator precedence in multi-word tag search; added `install.sql`.
- stock-report 2.0.0: removed store-specific columns and the missing PHPExcel dependency; correct quantity for options with 0 stock; no duplicate rows on multi-language stores.
- product-storefront-link 1.2.0: works when the default language ID is not 1 and for products without an SEO keyword.
- order-quick-save 1.1.0: loading state on the shipping Quick Save button.

### Changed
- hidden-product-tags renamed **search-only-product-tags**; hidden links on the product page removed (against Google's spam policies).
- parent-category-breadcrumb renamed **product-category-breadcrumbs**.
- google-shopping-sku-as-gtin 1.1.0: only GTIN-format SKUs (8/12/13/14 digits) are sent.
- customer-online-forwarded-ip 1.1.0: first address of `X-Forwarded-For`, validated.

### Added
- OCMOD verification harness, unit tests, MySQL integration tests, CI workflow, build script, documentation.
