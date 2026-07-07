# Sales Entry Module

## Purpose
Converts an in-stock bike into a sold bike with full financial tracking. Supports cash, cheque, installment plans, accessories bundling, money destination allocation, and professional invoice generation.

## Form Fields & Controls

### Bike Selection
- **SELECT BIKE**: [select] - In-stock bikes with chassis/model/color. Auto-fills purchase price, model details.
- **SELLING DATE**: [date] - Sale date.
- **SELLING PRICE (Rs.)**: [number] - Sale price. Auto-calculates tax and margin.
- **PURCHASE PRICE**: [number] - Read-only, auto-filled from bike record.
- **TAX AMOUNT**: [text] - Auto-calculated based on settings (tax on purchase or selling price).
- **MARGIN / PROFIT**: [text] - Auto-calculated: Selling Price - Purchase Price - Tax.

### Customer
- **CUSTOMER**: [select] - Select from existing customers or click "+" to add new. Shows filer status and advance/due balance.
- **Walk-in / Cash Customer**: Option for unregistered cash sales (full payment enforced).

### Down Payment
- **DOWN PAYMENT**: [number] - Amount received upfront.
- **PAYMENT METHOD**: [select] - Cash, Cheque, Bank Transfer, Online.
- **CHEQUE NUMBER**: [text] - Appears when payment is cheque.
- **BANK NAME**: [text] - Appears when payment is cheque.
- **CHEQUE DATE**: [date] - Appears when payment is cheque.
- **CHEQUE AMOUNT**: [number] - Appears when payment is cheque.

### Installment Plan
- **TOTAL INSTALLMENTS**: [number] - Number of monthly installments.
- **INSTALLMENT AMOUNT**: [text] - Auto-calculated from remaining balance.
- **FIRST DUE DATE**: [date] - First installment due date (default: 1 month from sale).

### Accessories (Dynamic Rows)
- **ACCESSORY**: [select] - Select from existing accessories inventory.
- **QUANTITY**: [number] - Quantity sold.
- **UNIT PRICE**: [number] - Auto-filled from accessory record.
- **DISCOUNT**: [number] - Discount amount.
- **FINAL PRICE**: [number] - Auto-calculated.
- **NOTES**: [text] - Sale notes.
- **+ Add Accessory**: Add more accessory rows.

### Money Allocation (Collapsible)
- **DESTINATION**: [select] - Select money destination (bank/person/wallet).
- **AMOUNT**: [number] - Amount allocated to this destination.
- **+ Add Allocation**: Add more destinations.

## Visual Evidence
![Sales Entry Full Capture](../screenshots/sale.png)

### Interface Variation: + Modal
Captures supplementary data during complex transactions.

![+ Modal](../screenshots/sale_modal__.png)
