# Supplier Ledger Module

## Purpose
Full accounting for each supplier. Shows purchase orders, payments, refunds with running balance, Payable vs Advance indicators, and payment/refund recording.

## Form Fields & Controls
- **SELECT SUPPLIER**: [select] - Choose supplier to view ledger.

## Summary Cards
- Total Purchases
- Total Paid
- Remaining Balance (Payable in red / Advance in green)

## Actions
- **+ Make Payment**: Record payment to supplier. Fields: Date, Amount, Payment Method, Cheque details, Notes.
- **💸 Receive Refund**: Record refund received from supplier. Fields: Date, Amount, Payment Method, Notes.

## Data Display
- Merged transaction table: purchase orders (debit), payments (credit), refunds (credit reversal) with running balance
- Supplier details: contact, address
- Print-optimized view

## Visual Evidence
![Supplier Ledger Full Capture](../screenshots/supplier_ledger.png)
