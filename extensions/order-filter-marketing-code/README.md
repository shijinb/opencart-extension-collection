# Order List Filter: Marketing Tracking Code

Adds a **Marketing Code** filter to **Sales → Orders**, to list the orders placed through a marketing campaign link.

| | |
|---|---|
| Type | OCMOD (9 operations) |
| Modifies | `admin/controller/sale/order.php`, `admin/model/sale/order.php`, `order_list.twig`, `admin/language/en-gb/sale/order.php` |
| Database changes | None (filters the existing `oc_order.tracking` column) |
| Tested on | OpenCart 3.0.3.8 – 3.0.5.1 (patch applies, lints, and `getOrders` / `getTotalOrders` / `getTotalSales` run against MySQL 8.4) |

## How it works

OpenCart stores the campaign code in `oc_order.tracking` when a visitor arrives via `index.php?tracking=CODE` (**Marketing → Marketing**). The filter matches that code **exactly**, and it combines with the core filters (status, customer, date, total).

The filter is preserved in sorting, pagination and the *Back* links from the order pages.

## Installation

1. Build the package with `scripts/build.sh` (or download `ocmod-packages` from a CI run): `dist/order-filter-marketing-code.ocmod.zip`.
2. In the admin, go to **Extensions → Installer** and upload `order-filter-marketing-code.ocmod.zip`.
3. Go to **Extensions → Modifications** and click **Refresh** (top right).

## Uninstall

**Extensions → Modifications**, select the modification, **Delete**, then **Refresh**.

Works together with [order-filter-telephone](../order-filter-telephone/). Both add fields to the same filter panel.

## Changes from the original (v1.0)

| Issue in v1.0 | Fix |
|---|---|
| **SQL injection.** The filter value went into SQL unescaped | `$this->db->escape()` |
| `getTotalOrders()` used `o.tracking`, but that query has no `o` alias → *Unknown column 'o.tracking'*, so the filtered list failed | `tracking` without alias in the count/sales queries |
| Filter value added to URLs without encoding | `urlencode(html_entity_decode(...))`, like the core filters |
| Operation targeting `export()`, which does not exist in core | Removed |

Both v1.0 issues were reproduced against MySQL. With v1.0, `x' OR '1'='1` returned every order and the count query failed. The integration tests now cover both cases.
