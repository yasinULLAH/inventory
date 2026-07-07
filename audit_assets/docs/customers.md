# Customers Module

## Purpose
Manages customer master records with purchase history tracking, filer status, and direct linkage to customer ledger.

## Form Fields & Controls
- **NAME**: [text] - Customer full name.
- **PHONE**: [text] - Contact phone number.
- **CNIC**: [text] - Pakistan national ID card number.
- **IS FILER**: [checkbox] - Tax filer status (affects tax reporting).
- **ADDRESS**: [textarea] - Residential/business address.

## Data Architecture (Tables)
| SR# | NAME | PHONE | CNIC | FILER | ADDRESS | BIKES PURCHASED | TOTAL AMOUNT | ACTIONS |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | Ahmed Ali | 0321-1234567 | 35201-1234567-1 | ✅ Filer | Dera Ghazi Khan, Punjab | 0 | Rs. 0.00 | 📒 ✏ 🗑 |
| 2 | Bilal Hussain | 0345-9876543 | 35201-9876543-5 | ❌ Non-Filer | Rajanpur, Punjab | 0 | Rs. 0.00 | 📒 ✏ 🗑 |
| 3 | Muhammad Usman | 0333-7654321 | 35201-7654321-3 | ✅ Filer | Muzaffargarh, Punjab | 0 | Rs. 0.00 | 📒 ✏ 🗑 |

### Actions
- **📒 Ledger**: View customer's full financial ledger.
- **✏ Edit**: Modify customer details.
- **🗑 Delete**: Remove customer (blocked if linked to sales).
- **Name Change Propagation**: Name changes auto-update historical payment records.

## Visual Evidence
![Customers Full Capture](../screenshots/customers.png)
