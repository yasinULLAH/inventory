# Customer Ledger Module

## Purpose
Full financial accounting for each customer. Shows all debit/credit transactions with running balance, Due vs Advance indicators, payment recording, advance/loan handling, and purchase history.

## Form Fields & Controls
- **SELECT CUSTOMER**: [select] - Choose customer to view ledger.

## Summary Cards
- Total Billed (all debits)
- Total Paid (all credits)
- Remaining Balance (Due in red / Advance in green)

## Actions
- **+ Receive Payment**: Record payment from customer. Auto-distributes to oldest pending installments first. Fields: Date, Amount, Payment Method, Cheque details, Notes.
- **💸 Make Payment (Advance/Loan)**: Record payment to customer (advance/loan). Fields: Date, Amount, Payment Method, Notes.

## Data Display
- Full transaction table with Date, Description, Debit, Credit, Running Balance
- Purchase history: bikes bought by this customer with model, chassis, sale date, price
- Print-optimized view

## Visual Evidence
![Customer Ledger Full Capture](../screenshots/customer_ledger.png)
