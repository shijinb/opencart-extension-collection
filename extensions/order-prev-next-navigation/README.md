# Order Info: Previous / Next Navigation

Adds **Previous order / Next order** buttons and an *Order (#id) – Status* header to **Sales → Orders → View**, so staff can work through orders without going back to the list.

| | |
|---|---|
| Type | OCMOD (4 operations) |
| Modifies | `admin/controller/sale/order.php`, `admin/model/sale/order.php`, `order_info.twig`, `admin/language/en-gb/sale/order.php` |
| Tested on | OpenCart 3.0.3.8 – 3.0.5.1 (patch applies, lints, and `getAdjacentOrderId()` runs against MySQL 8.4) |

## Behaviour

- *Previous* and *Next* follow the order ID, skipping missing orders (status 0) and excluded statuses.
- Excluded by default: **Canceled (7), Failed (10), Refunded (11)**, which are the IDs in a default OpenCart install.
- List filters in the URL (customer, status, page and so on) are carried over, so *Back* still returns to the same list.

## Configuration

Order status IDs differ between stores. Check yours in **System → Localisation → Order Statuses** and edit `$excluded_status_ids` in `install.xml` before packaging. An empty array includes every status.

## Installation

1. Build the package with `scripts/build.sh` (or download `ocmod-packages` from a CI run): `dist/order-prev-next-navigation.ocmod.zip`.
2. In the admin, go to **Extensions → Installer** and upload `order-prev-next-navigation.ocmod.zip`.
3. Go to **Extensions → Modifications** and click **Refresh** (top right).

## Uninstall

**Extensions → Modifications**, select the modification, **Delete**, then **Refresh**.

## Changes from the original (v1.0)

| v1.0 | Now |
|---|---|
| `order_id` from the URL put into SQL without a cast (**SQL injection**) | `(int)` cast |
| Two near-identical model methods | One `getAdjacentOrderId($order_id, 'previous'|'next')` |
| Excluded statuses hidden in the SQL | Named, documented array |
| Hardcoded English "Prev"/"Next" and inline colour styles | Language strings and default Bootstrap styling |
