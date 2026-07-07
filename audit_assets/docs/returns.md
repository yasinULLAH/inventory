# Returns Module

## Purpose
Handles return adjustments for bikes. Two subtypes: Sales Returns (customer returns sold bike) and Purchase Returns (dealer returns in-stock bike to supplier). Full accounting reversals with automatic installment cancellation, accessory stock restoration, and money allocation clearing.

## Form Fields & Controls

### Sales Returns (Customer → Dealer)
- **SELECT SOLD BIKE**: [select] - Select from sold bikes.
- **RETURN DATE**: [date] - Return processing date.
- **RETURN AMOUNT (Rs.)**: [number] - Refund amount.
- **REFUND METHOD**: [select] - Cash, Cheque, Bank Transfer, Online.
- **CHEQUE NUMBER**: [text] - Appears when refund method is cheque.
- **BANK NAME**: [text] - Appears when refund method is cheque.
- **CHEQUE DATE**: [date] - Appears when refund method is cheque.
- **RETURN NOTES**: [textarea] - Reason and details for return.

### Purchase Returns (Dealer → Supplier)
- **SELECT IN-STOCK BIKE**: [select] - Select from in-stock bikes.
- **RETURN DATE**: [date] - Return processing date.
- **REFUND RECEIVED AMOUNT (Rs.)**: [number] - Auto-filled from purchase price.
- **REFUND RECEIPT METHOD**: [select] - Cash, Cheque, Bank Transfer, Online.
- **CHEQUE NUMBER**: [text] - Appears when method is cheque.
- **BANK NAME**: [text] - Appears when method is cheque.
- **CHEQUE DATE**: [date] - Appears when method is cheque.
- **RETURN NOTES**: [textarea] - Reason for return.

## Automatic Actions on Sales Return
- Bike status: `sold` → `returned`
- Payment recorded as customer_refund
- Reversal ledger entries created
- All pending installments auto-cancelled
- Accessory stock auto-restored
- Money allocations auto-cleared

## Automatic Actions on Purchase Return
- Bike status: `in_stock` → `returned_to_supplier`
- Payment recorded as supplier_refund
- Reversal ledger entries created

## Visual Evidence
![Returns Full Capture](../screenshots/returns.png)
