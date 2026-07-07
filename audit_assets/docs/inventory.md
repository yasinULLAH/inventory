# Inventory / Stock Module

## Purpose
The Inventory module is the main stock management page. It lists all bikes with advanced filtering, totals, view details, editing, deletion, bulk actions, CSV export, and item-level lifecycle timeline.

## Form Fields & Controls
- **SEARCH**: [text] - Search by chassis, motor, model, or color.
- **STATUS**: [select] - Filter by status: All, In Stock, Sold, Returned, Returned to Supplier, Reserved, Damaged/Lost.
- **MODEL**: [select] - Filter by bike model.
- **COLOR**: [text] - Filter by color.
- **FROM**: [date] - Inventory date range start.
- **TO**: [date] - Inventory date range end.
- **Apply Filters**: [button] - Apply selected filters.
- **CSV Export**: [button] - Export filtered view to CSV.
- **Bulk Delete**: [button] - Delete selected bikes (only non-sold/non-returned).
- **Select All**: [checkbox] - Select/deselect all visible rows.

## Data Architecture (Tables)
|  | SR# | IMAGE | CHASSIS | MOTOR# | MODEL | COLOR | PURCHASE PRICE | STATUS | SELLING PRICE | SELLING DATE | MARGIN | ACTIONS |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
|  | 1 | 🖼 | LY05G48270002304 | XRLY48052125D0002228 | LY SI Electric Bike | Black | Rs. 125,225.00 | IN_STOCK | - | - | - | 👁 🛒 ✏ 🗑 |
|  | 2 | 🖼 | LY05G48270002202 | XRLY48052125D0002322 | LY SI Electric Bike | Grey | Rs. 125,225.00 | IN_STOCK | - | - | - | 👁 🛒 ✏ 🗑 |
|  | 3 | 🖼 | DD35G48130001177 | 48V350WA8T454708922 | W. Bike H2 Electric Bike | Black | Rs. 94,595.00 | IN_STOCK | - | - | - | 👁 🛒 ✏ 🗑 |

### Actions per bike
- **👁 View**: Full bike detail page with lifecycle timeline.
- **🛒 Sell**: Quick-link to sales entry with bike pre-selected (in-stock only).
- **↩ Return**: Quick-link to returns (sold only).
- **✏ Edit**: Inline modal to edit color, price, status, notes, image. Status change to Damaged/Lost auto-creates expense.
- **🗑 Delete**: Delete record (in-stock/reserved only).

## Page Totals
- Total Purchase Price (visible rows)
- Total Selling Price (sold rows)
- Total Margin (sold rows)

## Bike Detail View
Complete bike profile including: chassis, motor, model, category, color, status, purchase/selling price, tax, margin, order/inventory/sale dates, customer, supplier, accessories, safeguard notes, notes, image, and lifecycle timeline.

## Visual Evidence
![Inventory / Stock Full Capture](../screenshots/inventory.png)
