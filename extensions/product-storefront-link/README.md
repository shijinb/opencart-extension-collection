# Product Form: Storefront Link

When editing an existing product, the form title shows the **product name linked to its storefront page** (new tab), so you can check changes straight away.

| | |
|---|---|
| Type | OCMOD (2 operations) |
| Modifies | `admin/controller/catalog/product.php`, `admin/view/template/catalog/product_form.twig` |
| Tested on | OpenCart 3.0.3.8 – 3.0.5.1 (patch applies, lints and parses) |

## Link rules

- SEO URLs enabled and the product has a keyword for the **default store** in the store's **default language**: `https://store.example/<keyword>`
- Otherwise: `https://store.example/index.php?route=product/product&product_id=N`
- Uses `HTTPS_CATALOG` when defined, else `HTTP_CATALOG`.
- New products (not saved yet) keep the normal *Add Product* title.

## Installation

1. Build the package with `scripts/build.sh` (or download `ocmod-packages` from a CI run): `dist/product-storefront-link.ocmod.zip`.
2. In the admin, go to **Extensions → Installer** and upload `product-storefront-link.ocmod.zip`.
3. Go to **Extensions → Modifications** and click **Refresh** (top right).

## Uninstall

**Extensions → Modifications**, select the modification, **Delete**, then **Refresh**.

## Changes from the original ("Product Edit Preview" v1.1)

- The original read `product_seo_url[0][1]`, which only works when the default language has ID 1. It now looks up the default language.
- Products without an SEO keyword, or stores with SEO URLs off, now get the `index.php` link instead of no link.
- Added `rel="noopener"` to the new-tab link.

## Limitations

Multi-store setups always link to the default store (store 0).
