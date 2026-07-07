# Dashboard Module

## Purpose
The Dashboard is the central command center of the BNI Enterprises BDMS application. It provides real-time visibility into the business's financial and operational health through KPI cards, interactive Chart.js visualizations, model-wise stock summaries, recent activity tables, and quick-action navigation buttons.

## Key Features
- **KPI Cards:** In Stock, Sold, Returned, Damaged/Lost, Purchase Value, Sales Value, Total Tax, Total Profit, Expenses, Pending Cheques, Today's Sales, Customers, Suppliers counts
- **Interactive Charts:** Sales Trend (line), Model-wise Stock (doughnut), Income vs Expense (bar), Inventory Status (pie) — powered by Chart.js
- **Quick Action Buttons:** New Sale, New Purchase, Add Customer, Add Expense, Process Return, View Inventory, New Quotation, Installments, Daily Report, Profit Report
- **Model-wise Stock Summary Table:** Total inventory, sold, returned, and available per model
- **Recent Activity:** Last 10 sales table and last 10 purchases table
- **Warning Banner:** Pending cheques and overdue installments highlighted
- **Pending Bank Deposits:** Shows total allocated but undeposited amount

## Filters & Controls
- All data is auto-aggregated from live database
- Charts auto-update on page load with fresh data

## Data Architecture (Tables)
| MODEL | CATEGORY | INVENTORY | SOLD | RETURNED | AVAILABLE |
| --- | --- | --- | --- | --- | --- |
| E8S M2 Electric Scooter | Electric Scooter | 2 | 1 | 0 | 1 |
| E8S Pro Electric Scooter | Electric Scooter | 1 | 0 | 0 | 1 |
| LY SI Electric Bike | Electric Bike | 2 | 0 | 0 | 2 |

| DATE | CHASSIS | MODEL | PRICE | MARGIN |
| --- | --- | --- | --- | --- |
| 18/03/2026 | TH12L72300000416 | Thrill Pro LFP Electric Bike | Rs. 246,000 | Rs. 34,076 |
| 18/03/2026 | T910G72260008679 | T9 Sports Electric Bike | Rs. 179,000 | Rs. 17,560 |
| 12/03/2026 | M615L72300006176 | M6 Lithium NP Electric Bike | Rs. 285,000 | Rs. 29,760 |

| DATE | CHASSIS | MODEL | PRICE | STATUS |
| --- | --- | --- | --- | --- |
| 05/02/2026 | LY05G48270002304 | LY SI Electric Bike | Rs. 125,225 | IN_STOCK |
| 05/02/2026 | LY05G48270002202 | LY SI Electric Bike | Rs. 125,225 | IN_STOCK |
| 05/02/2026 | DD35G48130001177 | W. Bike H2 Electric Bike | Rs. 94,595 | IN_STOCK |

## Visual Evidence
![Dashboard Full Capture](../screenshots/dashboard.png)
