# Order List Filter: Telephone

Adds a **Telephone** filter to **Sales → Orders**, to find a customer's orders by phone number.

| | |
|---|---|
| Type | OCMOD (9 operations) |
| Modifies | `admin/controller/sale/order.php`, `admin/model/sale/order.php`, `order_list.twig`, `admin/language/en-gb/sale/order.php` |
| Database changes | None (filters `oc_order.telephone`) |
| Tested on | OpenCart 3.0.3.8 – 3.0.5.1 (patch applies, lints, and queries run against MySQL 8.4) |

## Behaviour

Exact match on the telephone stored with the order, so `+91 98470 00001` and `9847000001` are different values. It combines with every core filter and is kept across sorting and pagination.

## Installation

1. Build the package with `scripts/build.sh` (or download `ocmod-packages` from a CI run): `dist/order-filter-telephone.ocmod.zip`.
2. In the admin, go to **Extensions → Installer** and upload `order-filter-telephone.ocmod.zip`.
3. Go to **Extensions → Modifications** and click **Refresh** (top right).

## Uninstall

**Extensions → Modifications**, select the modification, **Delete**, then **Refresh**.

## Changes from the original ("Mobile No Filter" v1.0)

Same fixes as [order-filter-marketing-code](../order-filter-marketing-code/README.md#changes-from-the-original-v10): SQL escaping, the missing table alias in `getTotalOrders()`, URL encoding, and removal of the operation on the non-existent `export()`.

## Possible improvement

Normalising phone numbers (digits only) on both sides would make the match independent of formatting. That needs a generated column or a normalised copy of the number, so it is not part of this extension.
