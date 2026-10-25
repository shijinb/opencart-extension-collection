# Order Edit: Quick Save

Adds **Quick Save** buttons to the *Customer*, *Payment Address* and *Shipping Address* tabs of **Sales → Orders → Edit**. Each one updates that section of the existing order straight away, and the tabs can be opened in any order.

| | |
|---|---|
| Type | OCMOD (15 operations) |
| Modifies | `admin/view/template/sale/order_form.twig`, `admin/language/en-gb/en-gb.php`, `catalog/controller/api/{customer,payment,shipping}.php`, matching catalog language files, `catalog/model/checkout/order.php` |
| Tested on | OpenCart 3.0.3.8 – 3.0.5.1 (patch applies, lints and parses; **not** exercised end to end, see Testing) |

## Why

In core OpenCart 3, correcting a typo in a shipping address means stepping through every tab (customer, cart, addresses, shipping and payment method, totals) and pressing the final *Save*, which rebuilds the whole order. Quick Save updates only the section you changed.

## How it works

The buttons post the tab's fields to the same catalog API endpoints the core buttons use (`api/customer`, `api/payment/address`, `api/shipping/address`) with an extra `quicksave` flag. The API validates the data as usual and stores it in the API session. With the flag set, it also writes the section to `oc_order` through three new model methods: `editCustomer()`, `editPayment()` and `editShipping()`.

**Not changed:** products, totals, shipping and payment methods, and the order history. For example, changing the shipping country does not recalculate shipping costs or taxes. Use the core flow for that.

## Installation

1. Build the package with `scripts/build.sh` (or download `ocmod-packages` from a CI run): `dist/order-quick-save.ocmod.zip`.
2. In the admin, go to **Extensions → Installer** and upload `order-quick-save.ocmod.zip`.
3. Go to **Extensions → Modifications** and click **Refresh** (top right).

## Uninstall

**Extensions → Modifications**, select the modification, **Delete**, then **Refresh**.

## Security

Quick Save inherits the protection of the core order editor: the admin needs the *modify* permission for `sale/order` and a valid API session (`api_token`). All values are escaped (`$this->db->escape()`, `(int)` casts).

## Testing

Automated checks cover the patch application, PHP lint and Twig parsing on four versions. The flow itself needs a browser and a running store, so it was **not** verified in this repository. The original version was used on the author's own OpenCart 3 store; verify this version manually before relying on it:

1. Edit an order, change the shipping city, click *Quick Save* on the Shipping Address tab.
2. Reload the order info page: the city is updated, products and totals are unchanged.

## Changes from the original (v1.0)

- Fixed the Shipping Quick Save button's loading state, which targeted the core button instead of itself.
- `order_id` cast to `int` in the API controllers.
- Metadata and description.
