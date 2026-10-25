# Stock Report

An **inventory report** in **Reports**: one row per product, or per option value for products with options, showing quantity, option-adjusted price, SKU, model, manufacturer, tax class and status, with filters and CSV export.

| | |
|---|---|
| Type | Standard OpenCart 3 report extension (no OCMOD) |
| Files | `admin/controller/extension/report/stock_report.php`, `admin/model/extension/report/stock_report.php`, language file, `stock_report_form.twig`, `stock_report_info.twig` |
| Database changes | None (settings stored in `oc_setting`) |
| Tested on | OpenCart 3.0.3.8 – 3.0.5.1 (files lint and parse; report queries run against MySQL 8.4) |

## Features

- **Filters:** product name (partial match) and *Quantity at or below* (e.g. `5` for a low-stock list, `0` for out of stock).
- **Option-level stock:** a product with *Size: 100 ml / 50 ml* gets one row per size, with that size's own quantity and price.
- **Highlights:** quantity ≤ 0 in red, ≤ 5 in amber. Product names link to the product form.
- **CSV export** of all filtered rows (UTF-8 with BOM so Excel shows non-Latin names correctly). Cells starting with `=`, `+`, `-` or `@` are prefixed with `'` to block spreadsheet formula injection.
- **Permissions:** uses the standard report permission (`extension/report/stock_report`).

## Installation

1. Upload `dist/stock-report.ocmod.zip` in **Extensions → Installer**.
2. **Extensions → Extensions → Reports**, install *Stock Report*, edit it, set **Status: Enabled** and save.
3. Open **Reports** and choose *Stock Report*.

Installing through *Extensions* gives the current admin group access/modify permission. Grant other groups access under **System → Users → User Groups**.

## Uninstall

Uninstall it in **Extensions → Extensions → Reports**, then delete the five files listed above.

## Changes from the original ("SStock Report" v1.1.0)

Rewritten (v2.0.0) as a standard report extension:

| v1.1.0 | Problem | Now |
|---|---|---|
| OCMOD added the report to the report list directly | Skipped OpenCart's `hasPermission('access', …)` check: anyone who could open Reports saw it | Standard extension with status, sort order and permission |
| Loaded `stock_report_form.twig`, which was not included | Settings page failed | Settings form added |
| Used `p.product_priority` and `p.keep_selling` | Store-specific columns: SQL error on a standard OpenCart | Removed |
| Excel export via `PHPExcel` | Library not included in OpenCart 3 (and abandoned) | CSV, no dependency |
| Option names joined without a language filter | Duplicate rows on multi-language stores | Joined in the admin language |
| `pov.quantity != ''` to pick option stock | An option with quantity 0 showed the product quantity | `IF(pov.product_option_value_id IS NULL, …)` |
| Date filters referenced alias `o` (not in the query) | Would fail if used (they were hidden in the UI) | Removed |
| Tax class shown as an ID | Unreadable | Tax class title |
