# BNI Enterprises: Bike Dealer Management System
## Official System Documentation & User Manual v2026.3

Welcome to the comprehensive documentation for the **BNI Enterprises Bike Dealer Management System**. This guide provides a deep-dive into every module of the application, designed to help both administrators and clients understand the full functional scope and operational flow of the system.

---

## 📑 Table of Contents
1. [Project Overview](#-project-overview)
2. [Core Dashboard & Analytics](#-core-dashboard--analytics)
3. [Purchase & Procurement](#-purchase--procurement)
4. [Inventory & Stock Management](#-inventory--stock-management)
5. [Sales & Revenue Capture](#-sales--revenue-capture)
6. [Returns & Adjustments](#-returns--adjustments)
7. [Financial Controls (Cheques & Ledgers)](#-financial-controls-cheques--ledgers)
8. [Reporting & Business Intelligence](#-reporting--business-intelligence)
9. [Administrative Controls](#-administrative-controls)
10. [Technical Specifications](#-technical-specifications)

---

## 🚀 Project Overview
**BNI Enterprises** is a robust, end-to-end management solution specifically tailored for Electric Bike dealerships. It streamlines the lifecycle of inventory from procurement to final sale, including integrated financial tracking, tax calculations (FBR compliant), and comprehensive reporting.

---

## 📊 Core Dashboard & Analytics
The Dashboard serves as the command center of the application, providing real-time visibility into the health of the business.

### Key Features:
- **Global Statistics**: Real-time counters for Total Stock, Sold Units, Returns, Purchase Value, and Sales Value.
- **Profit Tracking**: Instant view of total margin and profits generated.
- **Model Summary**: A categorized breakdown of inventory status (Available vs. Sold) for every bike model.
- **Activity Feeds**: Quick view of the 10 most recent sales and purchases.

![Dashboard Overview](screenshots/dashboard.png)

---

## 📦 Purchase & Procurement
This module manages the intake of new inventory. It is designed to handle bulk imports of bike units while simultaneously recording financial liabilities.

### Functional Scope:
- **Batch Entry**: Add multiple bike units (Chassis, Motor, Model, Color) in a single transaction.
- **Supplier Integration**: Link purchases directly to established suppliers.
- **Financial Linking**: Record cheque details (Number, Bank, Amount) at the time of purchase to update supplier ledgers automatically.
- **Safeguard Notes**: Document included accessories (Chargers, Helmets, etc.) per unit.

![Purchase Entry](screenshots/purchase.png)
*Interface Variation: Modal for adding new suppliers on the fly.*
![Add Supplier Modal](screenshots/purchase_modal__.png)

---

## 📋 Inventory & Stock Management
The heart of the system, the Inventory module, provides a granular view of every asset owned by the dealership.

### Features:
- **Status Tracking**: Visual badges indicating `IN STOCK`, `SOLD`, `RETURNED`, or `RESERVED`.
- **Advanced Filtering**: Search by Chassis #, Motor #, Model, or Color.
- **Bulk Actions**: Export selected records to CSV or perform bulk deletions.
- **History Timeline**: View the complete lifecycle of a specific bike, from purchase date to the final customer sale.

![Inventory Management](screenshots/inventory.png)

---

## 🛒 Sales & Revenue Capture
The Sales module facilitates the transition of assets from inventory to revenue.

### Highlights:
- **Smart Prefill**: Selecting a bike automatically fetches its purchase price and calculates the minimum tax-compliant selling price.
- **Margin Calculation**: Real-time calculation of profit/margin before the sale is finalized.
- **Customer Profiling**: Link sales to existing customers or create "Walk-in" records.
- **Invoice Generation**: Automatically generates professional, printable invoices with custom company branding.

![Sales Entry](screenshots/sale.png)
*Interface Variation: Customer selection and quick-add modal.*
![Add Customer Modal](screenshots/sale_modal__.png)

---

## ↩ Returns & Adjustments
Handles the reversal of sales transactions with proper financial auditing.

### Controls:
- **Refund Management**: Track whether refunds were issued via Cash or Cheque.
- **Financial Reversal**: Automatically updates the Customer Ledger to reflect the returned amount and restores the bike unit to inventory status.

![Returns Module](screenshots/returns.png)

---

## 💳 Financial Controls: Cheques & Ledgers
Integrated accounting ensures that no transaction goes unrecorded.

### Cheque Register:
- Track `Pending`, `Cleared`, and `Bounced` cheques for both payments (to suppliers) and receipts (from customers).
![Cheque Register](screenshots/cheques.png)

### Ledgers:
- **Customer Ledger**: A chronological statement of all transactions with a specific client, including running balances.
- **Supplier Ledger**: Detailed tracking of procurement costs and payment history for every vendor.
![Customer Ledger](screenshots/customer_ledger.png)
![Supplier Ledger](screenshots/supplier_ledger.png)

---

## 📊 Reporting & Business Intelligence
Comprehensive reporting tools for data-driven decision making.

### Available Reports:
- **Tax Report**: Monthly breakdown of tax liabilities (GST/Sales Tax).
- **Monthly Summary**: Comparison of units purchased vs. units sold.
- **Daily Ledger**: A "Day Book" view of every financial movement.
- **Profit/Margin Analysis**: Granular view of profitability by model or time period.

![Reports Module](screenshots/reports.png)

---

## ⚙ Administrative Controls
Manage the core "DNA" of your system.

- **Model Management**: Define Bike Models, Categories (Scooter/Bike), and Short Codes.
- **System Settings**: Configure Company Name, Tax Rates, and Currency Symbols.
- **Security**: Manage admin passwords and system-wide theme preferences (Dark/Light mode).
- **Data Protection**: Full SQL Backup and Restore functionality to prevent data loss.

![Settings](screenshots/settings.png)
![Models Management](screenshots/models.png)

---

## 🛠 Technical Specifications
- **Architecture**: PHP 8.x / MySQL 8.x
- **UI/UX**: Vanilla CSS with Mobile-Responsive Grid System.
- **Standardization**: UTF-8 encoding for multilingual support.
- **Compliance**: Built-in tax calculation engine based on configurable percentages.

---
*Documentation generated by OMNI Audit Protocol v2026.3*
