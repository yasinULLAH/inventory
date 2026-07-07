# Suppliers Module

## Purpose
Manages supplier master records with purchase order tracking and direct linkage to supplier ledger.

## Form Fields & Controls
- **NAME**: [text] - Supplier company/person name.
- **CONTACT**: [text] - Contact phone number.
- **ADDRESS**: [textarea] - Business address.

## Data Architecture (Tables)
| SR# | NAME | CONTACT | ADDRESS | ORDERS | TOTAL PAID | ACTIONS |
| --- | --- | --- | --- | --- | --- | --- |
| 1 | Default Supplier | 0300-0000000 | Pakistan | 4 | Rs. 5,289,061.00 | 📒 ✏ 🗑 |

### Actions
- **📒 Ledger**: View supplier's full financial ledger.
- **✏ Edit**: Modify supplier details.
- **🗑 Delete**: Remove supplier (blocked if linked to purchase orders).
- **Name Change Propagation**: Name changes auto-update historical payment records.

## Visual Evidence
![Suppliers Full Capture](../screenshots/suppliers.png)
