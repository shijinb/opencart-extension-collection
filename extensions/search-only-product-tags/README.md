# Search-Only Product Tags

Adds a **Search-only Tags** field to each product. The keywords make the product findable in the storefront search (synonyms, common misspellings, regional names) but are never shown to customers.

| | |
|---|---|
| Type | OCMOD (5 operations) + SQL |
| Modifies | admin product form, language and model; `catalog/model/catalog/product.php` |
| Database changes | Adds `hidden_tag` (TEXT NULL) to `oc_product_description` |
| Tested on | OpenCart 3.0.3.8 – 3.0.5.1 (patch applies, lints, and search queries run against MySQL 8.4) |

## Example

For *Aloe Vera Gel*, add the search-only tags `kattar vazha, aloe jel`. A search for `kattar vazha` or `aloe jel` now finds the product, and the product page shows nothing extra.

## Installation

1. Run `install.sql` on the store database. Replace `oc_` with your `DB_PREFIX` first.
2. Upload `dist/search-only-product-tags.ocmod.zip` in **Extensions → Installer**.
3. **Extensions → Modifications → Refresh**.

The field appears on the **General** tab of the product form, above *Product Tags*, for each language.

## Uninstall

1. **Extensions → Modifications**, delete the modification, **Refresh**.
2. Optionally run `uninstall.sql`. This permanently deletes the stored tags.

## Changes from the original ("Hidden Product Tags" v1.1)

| Original | Now | Why |
|---|---|---|
| Rendered the tags on the product page inside `<p style="display:none">` links | Removed | Hidden text and links are against Google's spam policies and can get pages demoted |
| `pd.tag LIKE … OR pd.hidden_tag LIKE …` without parentheses | Wrapped in parentheses | OpenCart joins each word's condition with `AND`, so the bare `OR` broke multi-word tag searches |
| No way to create the column | `install.sql` / `uninstall.sql` | The original failed on a fresh store with *Unknown column 'hidden_tag'* |
| Admin save required `hidden_tag` in the POST data | Defaults to empty | Avoids PHP notices from imports or API calls that do not send the field |

## Limitations

- Admin label and help text are English only (`admin/language/en-gb`); other admin languages show the language key.
- Matching uses `LIKE '%word%'`, like the core tag search. Large catalogues may want a full-text index instead.
