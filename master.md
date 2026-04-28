# 🏍️ BNI Enterprises - Bike Dealer Management System (BDMS)
## The Definitive Master Documentation (v2.0.0)

This is the exhaustive, complete, and definitive master documentation for the **BNI Enterprises Bike Dealer Management System (BDMS)**. This document contains every single detail, module specification, database schema, business logic formula, and visual evidence associated with the application. Nothing has been omitted.

---

## 📑 Table of Contents
1. [Project Overview](#1-project-overview)
2. [Technical Architecture & System Requirements](#2-technical-architecture--system-requirements)
3. [Complete Database Schema (All 16 Tables)](#3-complete-database-schema)
4. [Role-Based Access Control (RBAC) & Security](#4-role-based-access-control-rbac--security)
5. [Exhaustive Module Breakdown](#5-exhaustive-module-breakdown)
    - [Intelligent Dashboard](#a-intelligent-dashboard)
    - [Inventory / Stock Control](#b-inventory--stock-control)
    - [Purchase Entry & Procurement](#c-purchase-entry--procurement)
    - [Sales Entry, Invoicing & Quotations](#d-sales-entry-invoicing--quotations)
    - [Installment & Financing System](#e-installment--financing-system)
    - [Returns Management](#f-returns-management)
    - [Financial Ecosystem: Cheques & Ledgers](#g-financial-ecosystem-cheques--ledgers)
    - [Customers & Suppliers (CRM)](#h-customers--suppliers-crm)
    - [System Settings & Calibration](#i-system-settings--calibration)
6. [Business Logic, Math & Tax Formulas](#6-business-logic-math--tax-formulas)
7. [Bilingual User Manual (Urdu / اردو)](#7-bilingual-user-manual-urdu--اردو)

---

## 1. Project Overview
**BNI Enterprises BDMS** is an enterprise-grade inventory and financial management solution engineered from the ground up by **Yasin Ullah**. Designed specifically for high-volume motorcycle dealerships, it eliminates manual ledgers by digitizing the entire business lifecycle: procurement, inventory tracking (chassis/motor level), dynamic sales margins, tax calculations, installment scheduling, and double-entry accounting via ledgers and cheque registers.

---

## 2. Technical Architecture & System Requirements
- **Backend:** PHP (7.4 or 8.x compatibility).
- **Database:** MySQL 5.7+ or MariaDB.
- **Frontend Stack:** HTML5, CSS3 (Vanilla + Custom CSS Variables), JavaScript (ES6), jQuery, Select2 (Dropdowns), DataTables (Grids), SweetAlert2 (Notifications).
- **Security Protocols:**
  - Password Hashing: `PASSWORD_DEFAULT` (Bcrypt).
  - CSRF Protection: Unique tokens per session validated on every POST request.
  - Brute Force Protection: IP banning after 7 failed attempts within 3 hours.
  - Session Hardening: Configurable idle timeouts (default 40 mins) and absolute timeouts (default 8 hours).
  - Captcha: Custom-generated SVG mathematical captcha for login.

---

## 3. Complete Database Schema
The system operates on 16 highly normalized, relational tables.

1. `settings`: Stores global configurations (Company name, tax rates, themes, timeouts).
2. `suppliers`: Supplier directory (`name`, `contact`, `address`).
3. `customers`: Customer CRM (`name`, `phone`, `cnic`, `is_filer`, `address`).
4. `models`: Master list of bike variants (`model_code`, `model_name`, `category`, `short_code`).
5. `accessories`: Helmets, chargers, etc. (`sku`, `purchase_price`, `selling_price`, `current_stock`).
6. `purchase_orders`: Header records for procurement (`order_date`, `supplier_id`, `total_amount`).
7. `bikes`: **The core table.** Tracks every unit (`chassis_number`, `motor_number`, `purchase_price`, `selling_price`, `status`: `in_stock`/`sold`/`returned`/`reserved`, `margin`, `tax_amount`).
8. `sale_accessories`: Linkage mapping sold bikes to purchased accessories.
9. `payments`: Global transaction log for all cash, bank transfer, and cheque movements.
10. `installments`: Tracks specific monthly payments, due dates, amounts paid, and penalty fees.
11. `ledger`: Double-entry accounting system mapping debits and credits to customers and suppliers.
12. `roles`: Access levels (e.g., Administrator, Manager).
13. `role_permissions`: Granular page-level permissions (View, Add, Edit, Delete).
14. `users`: System operators linked to roles.
15. `income_expenses`: Operational accounting for daily showroom expenses or external income.
16. `quotations`: Pre-sales documents with validity dates, converting to live sales.

---

## 4. Role-Based Access Control (RBAC) & Security
The system uses a strict RBAC engine. 
- **Administrator:** Unrestricted access to all modules, settings, and destructive actions.
- **Manager (Default):** Limited to basic operations like viewing the dashboard and inventory, without access to global settings or role deletion.
- Permissions are evaluated at the page and action level (`can_view`, `can_add`, `can_edit`, `can_delete`).

---

## 5. Exhaustive Module Breakdown

### A. Intelligent Dashboard
**Purpose:** The nerve center providing real-time tracking, reporting, and classification of critical business records.
- **Data Points Shown:** 
  - Model-wise availability (e.g., E8S M2 Electric Scooter: 2 Inventory, 1 Sold, 1 Available).
  - Recent Sales grid showing Date, Chassis, Model, Price, and Exact Margin.
  - Recent Stock additions showing Date, Chassis, Price, and Status.
- **Visual Evidence:**
![Dashboard](audit_assets/screenshots/dashboard.png)

### B. Inventory / Stock Control
**Purpose:** Granular tracking of every physical asset.
- **Filters & Controls:** Text Search, Status (In Stock, Sold, Returned, Reserved), Model Dropdown, Color, Date Range (From/To).
- **Grid Columns:** Sr#, Chassis, Motor#, Model, Color, Purchase Price, Status, Selling Price, Selling Date, Margin, Actions (View, Add to Cart, Edit, Delete).
- **Features:** Bulk Deletion, Bulk CSV Export.
- **Visual Evidence:**
![Inventory / Stock Full Capture](audit_assets/screenshots/inventory.png)

### C. Purchase Entry & Procurement
**Purpose:** Workflow for ingesting new stock from suppliers.
- **Fields:** Order Date, Inventory Date, Supplier, Cheque Number/Bank/Date/Amount (for direct payment linking), PO Notes.
- **Bike Data Entry:** Chassis Number (Unique validation), Motor Number, Model, Color, Purchase Price, Safeguard Notes.
- **Modal Additions:** Built-in modals to add new Suppliers or Models without leaving the purchase screen.
- **Visual Evidence:**
![Purchase Entry Full Capture](audit_assets/screenshots/purchase.png)
![Purchase Modal](audit_assets/screenshots/purchase_modal__.png)

### D. Sales Entry, Invoicing & Quotations
**Purpose:** Revenue generation and client fulfillment.
- **Sales Fields:** Select Bike, Selling Date, Selling Price, Purchase Price (Visible for Profit Guard), Tax Amount (Auto-calculated), Margin/Profit (Auto-calculated).
- **Customer Details:** Select existing or enter Name, Phone, CNIC, Address.
- **Payment & Installments:** Payment Method (Cash, Cheque, etc.), Down Payment, Total Installments, Installment Amount, First Due Date.
- **Accessories:** Select accessories given with the sale, adjust final price and discounts.
- **Quotations:** Create quotes that reserve bikes temporarily and can be converted to 1-click sales.
- **Visual Evidence:**
![Sales Entry Full Capture](audit_assets/screenshots/sale.png)
![Sale Modal](audit_assets/screenshots/sale_modal__.png)

### E. Installment & Financing System
**Purpose:** Management of monthly payment plans.
- **Functionality:** When a sale is made with a down payment less than the total, the system generates monthly installment rows.
- **Tracking:** Monitors `Amount Paid` vs `Installment Amount`. Automatically handles `Penalty Fees` for late payments. Links directly to the ledger upon payment collection.

### F. Returns Management
**Purpose:** Handling canceled sales and asset recovery.
- **Fields:** Select Sold Bike, Return Date, Return Amount (Refund), Refund Method (Cash/Cheque), Return Notes.
- **Logic:** Reverts the bike status from `sold` back to `returned` (making it available in stock), reverses the ledger entries, and logs the refund payment.
- **Visual Evidence:**
![Returns Full Capture](audit_assets/screenshots/returns.png)

### G. Financial Ecosystem: Cheques & Ledgers
**Purpose:** Complete financial transparency.
- **Cheque Register:** Tracks all cheques (Payments & Receipts). Fields include Bank, Date, Amount, Type, Status (Pending, Cleared, Bounced), Party, and Reference.
- **Ledgers:** Automated double-entry system. Every sale debits the customer ledger; every payment credits it. Same for suppliers. Shows running balances.
- **Visual Evidence:**
![Cheque Register Full Capture](audit_assets/screenshots/cheques.png)
![Customer Ledger Full Capture](audit_assets/screenshots/customer_ledger.png)
![Supplier Ledger Full Capture](audit_assets/screenshots/supplier_ledger.png)

### H. Customers & Suppliers (CRM)
**Purpose:** Directory and relationship management.
- **Customer Grid:** Name, Phone, CNIC, Address, Total Bikes Purchased, Total Amount Spent.
- **Supplier Grid:** Name, Contact, Address, Total Orders, Total Paid.
- **Visual Evidence:**
![Customers Full Capture](audit_assets/screenshots/customers.png)
![Suppliers Full Capture](audit_assets/screenshots/suppliers.png)

### I. System Settings & Calibration
**Purpose:** Application behavior configuration.
- **Configurable Options:** Company Name, Branch Name, Currency Symbol, Tax Rate (%), Tax Calculated On (Purchase Price vs Selling Price), Show Purchase Price on Invoice (Toggle), Session Timeouts (Idle and Absolute).
- **Maintenance:** 1-Click Database Backup (.sql export) and Database Restore facility. Theme toggling (Dark/Light). Password updating.
- **Visual Evidence:**
![Settings Full Capture](audit_assets/screenshots/settings.png)

---

## 6. Business Logic, Math & Tax Formulas

The BDMS is built on strict financial rules to ensure accounting accuracy:

**1. Base Profit Calculation:**
`Margin = Selling Price - Purchase Price - Tax Amount`

**2. Tax Logic (Dynamic based on settings):**
- *If Tax on Purchase:* `Tax Amount = Purchase Price * (Tax Rate / 100)`
- *If Tax on Selling:* `Tax Amount = Selling Price * (Tax Rate / 100)`

**3. Total Sale Value (With Accessories):**
`Total Sale Amount = Selling Price + Sum(Accessories Final Price)`

**4. Installment Calculation:**
`Remaining Balance = Total Sale Amount - Down Payment`
`Monthly Installment = Remaining Balance / Total Installments`

**5. Ledger Double Entry (Example: Sale):**
- System creates a `Debit` entry for the Customer for the `Total Sale Amount`.
- If a Down Payment is made, system creates a `Credit` entry for the Customer for the `Down Payment Amount`.
- Running balance is strictly maintained.

---

## 7. Bilingual User Manual (Urdu / اردو)

**بی این آئی انٹرپرائزز (BNI Enterprises) - موٹر سائیکل شو روم مینجمنٹ سسٹم**
**مکمل اور جامع گائیڈ (صرف کلائنٹ کے لیے)**

یہ دستاویز آپ کی نئی کمپیوٹر ایپ کو استعمال کرنے اور سمجھنے کے لیے بنائی گئی ہے۔ اس ایپ کا مقصد آپ کے شو روم کے تمام حساب کتاب کو خودکار اور آسان بنانا ہے تاکہ آپ کو رجسٹروں اور پنسل کے حساب سے نجات مل سکے۔

### 1. آپ کی تجاویز اور ہمارا کام (ہم نے کیا بنایا؟)
*   **0.1% ٹیکس کا حساب:** ایپ اب ہر بائیک کی قیمت پر 0.1% (یا آپ کی مرضی کا) ٹیکس خود نکال لیتی ہے۔
*   **منافع (Margin) کی رپورٹ:** ایپ اب "فروخت کی قیمت" سے "خریداری" اور "ٹیکس" نکال کر آپ کا اصل نفع خود دکھاتی ہے۔
*   **چیسس اور موٹر نمبر ٹریکنگ:** ہر بائیک اپنے چیسس اور موٹر نمبر سے پہچانی جاتی ہے، جس سے چوری یا غلطی کا خطرہ ختم ہو جاتا ہے۔
*   **چیک اور بینک ٹریکنگ:** ایپ میں ایک مکمل "Cheque Register" ہے جہاں آپ یو بی ایل اور میزان بینک کے چیکوں کا ریکارڈ رکھ سکتے ہیں۔
*   **واپسی کا نظام (Returns):** اگر کوئی بائیک واپس آئے تو اس کا اندراج الگ سے ہوتا ہے اور وہ خود بخود اسٹاک میں واپس آ جاتی ہے۔

### 2. ایپ کو کیسے استعمال کریں؟ (قدم بہ قدم گائیڈ)
**پہلا قدم: نئی بائیک شامل کریں (Purchase)**
*   "Purchase Entry" والے خانے میں جائیں۔
*   بائیک کا چیسس نمبر، موٹر نمبر اور ماڈل لکھیں۔ خریداری کی قیمت درج کریں اور محفوظ کریں۔

**دوسرا قدم: بائیک فروخت کریں (Sale)**
*   "Sales Entry" میں جائیں، فہرست سے بائیک منتخب کریں۔
*   گاہک کا نام، فون نمبر اور فروخت کی قیمت لکھیں۔ اگر قسطیں ہیں تو ڈاؤن پیمنٹ اور قسطوں کی تعداد درج کریں۔

**تیسرا قدم: رسید پرنٹ کریں**
*   بیچنے کے بعد "Print Invoice" کا بٹن دبائیں۔ کمپیوٹر سے گاہک کے لیے ایک خوبصورت رسید نکل آئے گی۔

**چوتھا قدم: کھاتے چیک کریں (Ledger)**
*   کس نے کتنے پیسے دیے اور کتنے باقی ہیں، یہ سب "Customer Ledger" میں دیکھا جا سکتا ہے۔

### 5. عام سوالات اور جوابات (QA)
**سوال: اگر بائیک واپس آ جائے تو کیا ہوگا؟**
**جواب:** آپ "Returns" والے بٹن پر جائیں گے۔ ایپ خود بخود اسے اسٹاک میں واپس ڈال دے گی اور گاہک کا حساب برابر کر دے گی۔

**سوال: کیا میرا ڈیٹا محفوظ ہے؟**
**جواب:** جی ہاں، "Settings" میں "Backup" کا آپشن دیا گیا ہے۔ آپ اپنا سارا ریکارڈ ایک کلک سے محفوظ کر سکتے ہیں۔

---
*End of Master Documentation. Generated dynamically for BNI Enterprises BDMS v2.0.0.*
