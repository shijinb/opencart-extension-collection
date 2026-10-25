# Product Phrase Search

Makes the storefront search match the **phrase as typed** instead of each word separately.

| | |
|---|---|
| Type | OCMOD (2 operations) |
| Modifies | `catalog/model/catalog/product.php` (`getProducts()`, `getTotalProducts()`) |
| Tested on | OpenCart 3.0.3.8 – 3.0.5.1 (patch applies, lints, and search queries run against MySQL 8.4) |

## Example

| Search `aloe vera` | Core OpenCart | With this extension |
|---|---|---|
| *Aloe Vera Gel* | ✔ | ✔ |
| *Cooling Gel with Vera and Aloe Extract* | ✔ (both words appear) | ✘ |

## Trade-off

Phrase matching is more precise but less forgiving: `vera aloe` no longer finds *Aloe Vera Gel*. It suits catalogues with well-known product names. Pair it with [search-only-product-tags](../search-only-product-tags/) to add the alternative phrasings customers use.

## Installation

1. Build the package with `scripts/build.sh` (or download `ocmod-packages` from a CI run): `dist/product-phrase-search.ocmod.zip`.
2. In the admin, go to **Extensions → Installer** and upload `product-phrase-search.ocmod.zip`.
3. Go to **Extensions → Modifications** and click **Refresh** (top right).

## Uninstall

**Extensions → Modifications**, select the modification, **Delete**, then **Refresh**.

## Changes from the original (v1.1)

Repeated whitespace in the search is collapsed before matching (`aloe   vera` = `aloe vera`). Commented-out code removed.
