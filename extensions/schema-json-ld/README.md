# Schema.org JSON-LD Structured Data

Adds schema.org **JSON-LD** to OpenCart 3 pages so search engines can read products, prices, availability, ratings and site structure.

| Page | Structured data |
|---|---|
| Product | `Product` with `Offer`, `AggregateRating`, up to 5 `Review`s; `BreadcrumbList` |
| Category, manufacturer | `BreadcrumbList` |
| Home, information, contact, sitemap | `Organization` (store name, URL, logo, contact) |

| | |
|---|---|
| Type | OCMOD (8 operations) + library class `system/library/schemaorg/builder.php` |
| Database changes | None |
| Tested on | OpenCart 3.0.3.8 – 3.0.5.1 (patch applies and lints; model queries run against MySQL 8.4; builder covered by 29 unit checks) |

> Structured data makes pages **eligible** for rich results. Google decides whether to show them, so nothing here guarantees rich results or rankings.

## Example output (product page)

```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Aloe & Neem Gel",
  "description": "Cooling gel. For dry skin.",
  "image": ["https://shop.example/image/cache/catalog/aloe-500x500.jpg"],
  "sku": "AN-100",
  "gtin13": "8901234567890",
  "brand": { "@type": "Brand", "name": "Example Herbals" },
  "offers": {
    "@type": "Offer",
    "url": "https://shop.example/index.php?route=product/product&product_id=42",
    "price": "12.50",
    "priceCurrency": "INR",
    "availability": "https://schema.org/InStock",
    "priceValidUntil": "2099-12-31"
  },
  "aggregateRating": { "@type": "AggregateRating", "ratingValue": 4.5, "reviewCount": 2, "bestRating": 5, "worstRating": 1 },
  "review": [
    { "@type": "Review", "author": { "@type": "Person", "name": "Asha" },
      "reviewRating": { "@type": "Rating", "ratingValue": 5, "bestRating": 5, "worstRating": 1 },
      "datePublished": "2024-05-01", "reviewBody": "Works well." }
  ]
}
```

## Where the values come from

| Property | Source |
|---|---|
| `name`, `description` | Product name; meta description, else product description (HTML removed, max 5,000 chars) |
| `image` | Main and additional images at the theme's popup size |
| `sku`, `mpn`, `model` | Product fields (omitted when empty) |
| `gtin12` / `gtin13` | UPC / EAN, only when exactly 12 / 13 digits |
| `brand` | Manufacturer |
| `offers.price` | Special price if active, else price; including tax if *Display Prices With Tax* is on; in the visitor's selected currency |
| `offers.availability` | `InStock` when quantity > 0, else `OutOfStock` |
| `offers.priceValidUntil` | End date of the active special, when it has one |
| `aggregateRating` | Average and count of **approved** reviews (only when reviews are enabled) |
| `review` | 5 most recent approved reviews with their own ratings |
| `Organization` | Store name, URL, logo, telephone, email from **System → Settings** |

## Design

- `Schemaorg\Builder` is plain PHP with no OpenCart dependency. Controllers collect store data and the builder shapes, cleans and encodes it, which keeps it unit-testable.
- Output uses `JSON_HEX_TAG`, so store data containing `</script>` cannot close the script tag (an XSS risk with plain `json_encode`).
- HTML entities are decoded and tags removed. OpenCart stores `&amp;`-encoded text and builds `&amp;`-separated URLs for HTML, and both were wrong inside JSON.
- The OCMOD adds `addSchema()` / `getSchemas()` to `Document` and prints each entry in `<head>` from `common/header.twig` (all themes).

## Installation

1. Build the package with `scripts/build.sh` (or download `ocmod-packages` from a CI run): `dist/schema-json-ld.ocmod.zip`.
2. In the admin, go to **Extensions → Installer** and upload `schema-json-ld.ocmod.zip`.
3. Go to **Extensions → Modifications** and click **Refresh** (top right).

## Uninstall

**Extensions → Modifications**, select the modification, **Delete**, then **Refresh**.

The library file is copied to `system/library/schemaorg/builder.php` by the installer. Delete it manually after uninstalling.

## Validate

After installing, check a product URL with the [Rich Results Test](https://search.google.com/test/rich-results) and the [Schema Markup Validator](https://validator.schema.org/).

## Changes from the original ("Google Rich Snippets-Schema" v1.0)

Rewritten (v2.0.0). Problems in v1.0:

| v1.0 | Impact | Now |
|---|---|---|
| Every review had a hardcoded rating of **4** | Misrepresented ratings, which is against Google's structured-data policies | Each review's real rating |
| `availability` was `InStock` when quantity **≥ 0** | Sold-out products advertised as in stock | Quantity > 0 |
| Store name hardcoded in the code | Not reusable | Store name from settings |
| URLs contained `&amp;` | Invalid URLs in JSON | Decoded |
| Plain `json_encode` inside `<script>` | Possible XSS from product or review text | `JSON_HEX_TAG` |
| `aggregateRating` used OpenCart's rounded integer rating | 4.5 shown as 5 | Exact average |
| `priceValidUntil` could be `0000-00-00` | Invalid date | Omitted when unset |
| SKU overwrote MPN; `WebPage` block with empty fields | Wrong or empty data | Removed |
| Modified OpenCart's main product SQL query | Fragile across versions | Separate small query (`getStructuredDataSummary()`) |
| `BreadcrumbList` items used `@id` | Non-standard | `name` + `item` per Google's format |

## Limitations

- Price is the price for the default customer group, without quantity discounts.
- Availability does not reflect *Stock Checkout* (backorders) or option-level stock.
- If your theme already outputs structured data, check for duplicates in the Rich Results Test.
