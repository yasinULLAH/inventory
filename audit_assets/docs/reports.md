# Reports Module

## Purpose
Comprehensive business intelligence with 15 specialized sub-reports covering stock, sales, tax, profit, cheques, monthly summaries, daily activity, accessories, installments, and money tracking.

## Form Fields & Controls
- **FROM DATE**: [date] - Filter start date.
- **TO DATE**: [date] - Filter end date.
- **YEAR**: [number] - Year filter for monthly reports.
- **MONTH**: [select] - Month filter.

## Report Tabs

### 1. Current Stock
Lists all in-stock bikes with chassis, motor, model, category, color, purchase price, inventory date, and days in stock.

### 2. Sold Bikes
Sold bikes within date range with customer, sale price, tax, margin, and profit summary.

### 3. Model-wise Sales
Aggregated per model: inventory count, sold count, available count, returned count, damaged/lost count, total purchase, total sales, total margin.

### 4. Tax Report
Monthly tax collection summary with total units sold and total tax per month.

### 5. Profit / Margin
Monthly profit analysis with sold count, total purchase, total sales, total tax, net profit, and average margin percentage.

### 6. Bank / Cheque Report
Cheque payments grouped by bank and transaction type. Shows pending/cleared/bounced/cancelled counts and amounts.

### 7. Monthly Summary
Yearly comparison: units purchased vs sold, purchase value vs sales value, and profit per month.

### 8. Daily Ledger
Single-date snapshot showing sales, inventory additions, expenses, and other income for that day.

### 9. Purchase vs Sales
Month-wise comparison of purchased units/value vs sold units/value with variance.

### 10. Accessory Stock
Current accessory inventory with stock quantity, purchase value, selling value, and potential profit.

### 11. Installments Summary
Per-customer installment totals: total due, total paid, penalties, overdue balance, and status breakdown.

### 12. Money by Destination
Per-destination allocation totals with date filtering. Shows how much sale money went to each bank/person/wallet.

### 13. Money by Sale
Per-sale breakdown showing destination allocation chips/badges.

### 14. Untracked Sales
Sales with no or partial money allocation. Direct "Track" action button to allocate.

### 15. Money Flow
Monthly money flow per destination type (bank/person/wallet) with totals.

## Visual Evidence
![Reports Full Capture](../screenshots/reports.png)
