# Purchase Entry Module

## Purpose
This module records purchase orders from suppliers and inserts one or more bike units into inventory. Supports dynamic bike rows, multiple payment methods, inline supplier/model creation, image upload, and real-time chassis uniqueness validation.

## Form Fields & Controls
- **ORDER DATE**: [date] - Purchase order date.
- **INVENTORY DATE**: [date] - Date bikes arrived in inventory.
- **SUPPLIER**: [select] - Select from existing suppliers or click "+" to add new via modal.
- **NOTES**: [textarea] - Purchase order notes.

### Payment Rows (Dynamic)
- **PAYMENT TYPE**: [select] - Cash, Cheque, Bank Transfer, Online.
- **CHEQUE NUMBER**: [text] - Appears when payment type is cheque.
- **BANK NAME**: [text] - Appears when payment type is cheque.
- **CHEQUE DATE**: [date] - Appears when payment type is cheque.
- **AMOUNT**: [number] - Payment amount.
- **+ Add Payment**: Click to add more payment rows.

### Bike Unit Rows (Dynamic)
- **CHASSIS NUMBER**: [text] - Unique chassis number. AJAX check for duplicates.
- **MOTOR NUMBER**: [text] - Engine/motor number.
- **MODEL**: [select] - Select from existing models or click "+" to add new via modal.
- **COLOR**: [text] - Bike color.
- **PURCHASE PRICE (Rs.)**: [number] - Per-unit purchase price.
- **SAFEGUARD NOTES**: [text] - Helmet, warranty, tire details, etc.
- **IMAGE**: [file] - Bike photo upload (auto-resize to 800px max, JPEG).
- **NOTES**: [text] - Additional bike notes.
- **✕ Remove**: Remove this bike row.

### Summary
- **Total Payment**: Sum of all payment row amounts.
- **Total Purchase**: Sum of all bike purchase prices.
- **Difference**: Payment vs Purchase difference.
- **Auto-Divide**: Toggle to auto-split total payment across all bikes.

### Modal Forms
- **Add Supplier**: Name, Contact, Address — inline modal.
- **Add Model**: Model Code, Model Name, Category, Short Code, Image — inline modal.

## Visual Evidence
![Purchase Entry Full Capture](../screenshots/purchase.png)

### Interface Variation: + Modal
Captures supplementary data during complex transactions.

![+ Modal](../screenshots/purchase_modal__.png)
