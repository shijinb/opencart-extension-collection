# Google Shopping: SKU as GTIN

Lets OpenCart's bundled **Google Shopping** extension send the product **SKU** as the GTIN when the SKU field holds the barcode.

| | |
|---|---|
| Type | OCMOD (2 operations) |
| Modifies | `system/library/googleshopping/googleshopping.php` |
| Requires | The core *Google Shopping* advertising extension (OpenCart 3.0.3.x and later) |
| Tested on | OpenCart 3.0.3.8 – 3.0.5.1 (patch applies and lints) |

## Why

The core feed reads the GTIN only from the UPC, EAN, JAN or ISBN fields. Many stores enter the barcode in the SKU field, so products are sent without a GTIN and Google Merchant Center reports missing identifiers.

## Behaviour

- If the SKU is **8, 12, 13 or 14 digits** it is used as the GTIN.
- Otherwise the core order applies: UPC, EAN, JAN, ISBN.

The digit check keeps internal SKUs such as `TSHIRT-RED-XL` out of the GTIN field. It does **not** verify the GTIN check digit, and it cannot tell whether a numeric SKU really is a GS1 barcode. Only use this if your numeric SKUs are real GTINs. Google rejects or penalises invalid GTINs.

## Installation

1. Build the package with `scripts/build.sh` (or download `ocmod-packages` from a CI run): `dist/google-shopping-sku-as-gtin.ocmod.zip`.
2. In the admin, go to **Extensions → Installer** and upload `google-shopping-sku-as-gtin.ocmod.zip`.
3. Go to **Extensions → Modifications** and click **Refresh** (top right).

## Uninstall

**Extensions → Modifications**, select the modification, **Delete**, then **Refresh**.

## Changes from the original (v1.0)

- Added the 8/12/13/14-digit check. The original sent any non-empty SKU as the GTIN.
