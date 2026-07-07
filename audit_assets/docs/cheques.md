# Cheque Register / Payments Module

## Purpose
Centralized payment and cheque management across all transaction types (purchase, sale, installment, expense, supplier payment, customer refund). Supports full cheque lifecycle from pending to cleared/bounced/cancelled with automatic financial reversals.

## Form Fields & Controls
- **STATUS**: [select] - Filter by: All, Pending, Cleared, Bounced, Cancelled.
- **TYPE**: [select] - Filter by transaction type: All, Purchase, Sale, Installment, Expense Payment, Supplier Payment, Customer Refund, etc.
- **BANK**: [text] - Filter by bank name.
- **FROM**: [date] - Date range start.
- **TO**: [date] - Date range end.

## Summary Cards
- Pending: Count and total amount
- Cleared: Count and total amount
- Bounced: Count and total amount
- Cancelled: Count and total amount

## Data Architecture (Tables)
| SR# | DATE | CHEQUE # | BANK | CHEQUE DATE | AMOUNT | TYPE | STATUS | PARTY | REFERENCE | ACTIONS |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 1 | 18/03/2026 | D72981756 | Meezan | 18/03/2026 | Rs. 1,241,441.00 | PAYMENT | CLEARED | Default Supplier | PO #4 | ✓ ✗ 🗑 |

### Actions
- **✓ Clear**: Mark pending cheque as cleared.
- **✗ Bounce**: Mark pending cheque as bounced (auto-reverses ledger/installments, adds penalty).
- **🗑 Delete**: Delete payment entry (with confirmation).

## Bounce Handling
When a cheque is marked as bounced:
- Ledger entries auto-reversed
- If installment payment: installment status reverted to pending
- Penalty fee recorded in ledger
- Notification shown

## Visual Evidence
![Cheque Register Full Capture](../screenshots/cheques.png)
