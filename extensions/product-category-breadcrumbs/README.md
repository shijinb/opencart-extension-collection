# Product Category Breadcrumbs

When a product page is opened **without a category path** (from search, the home page, a module, an external link or Google), core OpenCart shows only *Home › Product*. This adds the product's category trail:

```
Home › Skin Care › Face › Face Gels › Aloe Vera Gel
```

| | |
|---|---|
| Type | OCMOD (2 operations) |
| Modifies | `catalog/model/catalog/category.php`, `catalog/controller/product/product.php` |
| Database changes | None (reads OpenCart's `oc_category_path`) |
| Tested on | OpenCart 3.0.3.8 – 3.0.5.1 (patch applies, lints, and the trail query runs against MySQL 8.4) |

Breadcrumbs help visitors move up to the category. With [schema-json-ld](../schema-json-ld/), the same trail is also published as `BreadcrumbList` structured data.

## Rules

- Uses the product's **deepest** enabled category in the current store. On a tie, the lowest `category_id` wins.
- If any category in the trail is disabled, no trail is added, so there are no links to disabled categories.
- Pages opened with `?path=` keep the core breadcrumbs.
- Manufacturer or search breadcrumbs that core adds are kept after the category trail.

## Installation

1. Build the package with `scripts/build.sh` (or download `ocmod-packages` from a CI run): `dist/product-category-breadcrumbs.ocmod.zip`.
2. In the admin, go to **Extensions → Installer** and upload `product-category-breadcrumbs.ocmod.zip`.
3. Go to **Extensions → Modifications** and click **Refresh** (top right).

## Uninstall

**Extensions → Modifications**, select the modification, **Delete**, then **Refresh**.

## Changes from the original ("Parent Breadcrumb Category" v1.0)

Rewritten (v2.0.0). The original:

- **broke on OpenCart 3.0.5.1**: its model method was anchored to `getTotalCategoriesByCategoryId()`, which no longer exists there, so the method was never added but the controller still called it (a fatal error on every product page reached without `path`);
- used whichever category happened to be **second** in the product's category list and showed only that one level;
- replaced the breadcrumb array, dropping core manufacturer/search crumbs.

The rewrite uses OpenCart's `category_path` table, which stores every category's ancestors, to build the full trail in two queries.
