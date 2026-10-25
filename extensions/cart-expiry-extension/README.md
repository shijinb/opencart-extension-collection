# Cart Expiry Extension

Keeps guest shopping carts for **7 days** instead of OpenCart's default **1 hour**.

| | |
|---|---|
| Type | OCMOD (1 operation) |
| Modifies | `system/library/cart/cart.php` |
| Database changes | None |
| Tested on | OpenCart 3.0.3.8, 3.0.3.9, 3.0.4.1, 3.0.5.1 (patch applies and lints; see [Testing](../../README.md#testing)) |

## Why

On every request, OpenCart 3's `Cart` constructor deletes cart rows that belong to no customer (`customer_id = 0`) and were added more than one hour ago. A guest who adds products, leaves and returns the next day finds an empty cart. This changes the cleanup window to 7 days.

## Configuration

The period is fixed in `install.xml`. To change it, edit the replacement text `INTERVAL 7 DAY` (any MySQL interval, e.g. `INTERVAL 48 HOUR`) before packaging.

## Installation

1. Build the package with `scripts/build.sh` (or download `ocmod-packages` from a CI run): `dist/cart-expiry-extension.ocmod.zip`.
2. In the admin, go to **Extensions → Installer** and upload `cart-expiry-extension.ocmod.zip`.
3. Go to **Extensions → Modifications** and click **Refresh** (top right).

## Uninstall

**Extensions → Modifications**, select the modification, **Delete**, then **Refresh**.

## Limitations

- The guest cart is tied to the PHP session ID. If the session cookie expires earlier (`session.cookie_lifetime`, OpenCart's session expiry), the rows still exist but are no longer linked to the visitor.
- Longer retention means more rows in `oc_cart`; the cleanup still runs, only later.

## Changes from the original (v1.0)

Metadata only (name, code, link). Behaviour unchanged.
