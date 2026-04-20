# ⚡ BNI Enterprises: Bike Dealer Management System (BDMS)
## 🏍️ موٹر سائیکل ڈیلر مینجمنٹ سسٹم - مکمل رہنمائی اور دستاویزات

---

## 📖 Introduction / تعارف
**English:**
The **BNI Enterprises BDMS** is a full-scale Enterprise Resource Planning (ERP) solution tailored specifically for motorcycle showrooms. It manages the entire lifecycle of a vehicle—from procurement from suppliers to customer sales, including complex financial tracking via cheques and ledgers.

**اردو:**
بی این آئی انٹرپرائزز (BDMS) ایک مکمل ای آر پی (ERP) سسٹم ہے جو خاص طور پر موٹر سائیکل شو رومز کے لیے بنایا گیا ہے۔ یہ سسٹم گاڑی کی خریداری سے لے کر گاہک کو فروخت کرنے تک کے تمام مراحل، اور مالی لین دین (چیک اور کھاتہ جات) کو نہایت آسانی اور مہارت سے منظم کرتا ہے۔

---

## 🚀 1. Installation & Initial Setup / انسٹالیشن اور ابتدائی ترتیب

### **Technical Requirements / تکنیکی ضروریات**
- **Server:** XAMPP, WAMP, or any Web Hosting with PHP 7.4+ support.
- **Database:** MySQL / MariaDB.
- **Browser:** Chrome, Firefox, or Edge (Optimized for Desktop).

### **Setup Steps / ترتیب کے مراحل**
1. **Upload:** Copy all files to your `htdocs` or public folder.
2. **Database Auto-Config:** You do **not** need to create tables manually. Simply open the app in your browser.
3. **One-Click Install:** The system will detect a missing database. Click **"⚡ Install Database"** to automatically:
   - Create the `bni_enterprises2` database.
   - Generate all 9 relational tables.
   - Inject default settings, bike models, and demo customers.
4. **Login:**
   - **User:** `admin`
   - **Pass:** `admin123`

---

## 🛠️ 2. Core Modules & Functionality / ماڈیولز اور ان کی کارکردگی

### **A. Dashboard (The Nerve Center) / ڈیش بورڈ**
- **Real-time Statistics:** Instant view of "In-Stock", "Sold", and "Returned" counts.
- **Valuation:** Total capital invested in inventory vs. total sales generated.
- **Profit Monitoring:** Net Margin tracking after accounting for taxes.
- **Model Summary:** A tabular breakdown of which models are selling and which are in stock.

### **B. Purchase Entry (Procurement) / اسٹاک کی خریداری**
- **Batch Processing:** Enter multiple bikes (Chassis, Motor, Color, Price) under a single Purchase Order (PO).
- **AJAX Uniqueness Check:** The system alerts you immediately if a Chassis Number already exists in the database to prevent double entries.
- **Financial Integration:** If paid via cheque, a record is automatically created in the **Cheque Register**.

### **C. Inventory & Stock Control / انوینٹری کنٹرول**
- **Status Tracking:** Every bike is tagged as `in_stock`, `sold`, `returned`, or `reserved`.
- **The Timeline Feature:** Click any bike to see its "Birth to Sale" history (Date of Purchase -> Date of Inventory -> Date of Sale).
- **Bulk Operations:** Select multiple bikes to delete or export to CSV (Excel).

### **D. Sales & Invoicing / سیلز اور انوائسنگ**
- **Customer Linking:** Sell to "Walk-in" clients or link to a registered customer.
- **Profit Logic:** The system calculates: `Margin = Selling Price - Purchase Price - Tax`.
- **Branded Invoice:** Generates a professional PDF-style printable invoice with your company header.

### **E. Financials (Cheques & Ledgers) / مالیات اور کھاتہ جات**
- **Cheque Register:** A specialized module to track "Pending", "Cleared", and "Bounced" cheques.
- **Customer/Supplier Ledgers:** Accurate "Running Balance" tracking. Every sale or return automatically updates the debit/credit column of the party involved.

---

## 📉 3. Business Logic & Calculations / کاروباری حساب کتاب کی منطق

### **1. Tax Calculation / ٹیکس کا حساب**
The system supports two tax modes (configurable in Settings):
- **Tax on Purchase:** Tax is calculated as a % of what you paid the supplier.
- **Tax on Sale:** Tax is calculated as a % of the price you sold it to the customer.
- *Formula:* `(Base Price * Tax Rate) / 100`.

### **2. Profit Margin / منافع کا حساب**
Profit is not just Sales minus Purchase. The system deducts the calculated tax to give you the **Net Profit**.
- *Formula:* `Margin = Selling Price - (Purchase Price + Tax Amount)`.

---

## 📂 4. Database Schema (For Developers) / ڈیٹا بیس کی ساخت

| Table Name | Description |
| :--- | :--- |
| `settings` | Stores company info, tax rates, and UI theme. |
| `suppliers` | Directory of vendors/suppliers. |
| `customers` | Directory of customers with CNIC and phone records. |
| `models` | List of bike models (e.g., T9 Sports, E8S Pro). |
| `purchase_orders`| Links a purchase transaction to a supplier. |
| `bikes` | The master table containing Chassis, Motor, and Status. |
| `cheque_register`| Financial instrument tracking for all transactions. |
| `payments` | General payment history (Cash/Online/Bank). |
| `ledger` | Debit/Credit entries for party-wise accounting. |

---

## ⚙️ 5. Maintenance & Security / دیکھ بھال اور سیکیورٹی

- **Theme Toggle:** Switch between **Dark Mode** (for low light) and **Light Mode** (for printing/day use).
- **Database Backup:** Download a full SQL snapshot of your data from the Settings page.
- **Session Security:** Automatic logout after 40 minutes of inactivity to protect your data.
- **Password Protection:** Securely hash passwords using industrial-standard `PASSWORD_DEFAULT`.

---

## 🇵🇰 مکمل اردو گائیڈ (User Manual)

### **1. اسٹاک کیسے شامل کریں؟**
'Purchase Entry' پر جائیں، سپلائر منتخب کریں اور بائیک کا چیسس اور انجن نمبر لکھیں۔ اگر آپ چیک کے ذریعے ادائیگی کر رہے ہیں تو وہیں چیک کی تفصیل درج کریں، جو خودکار طریقے سے فنانس سیکشن میں چلی جائے گی۔

### **2. فروخت (Sale) کیسے کریں؟**
'Sales Entry' پر جائیں، اسٹاک سے بائیک منتخب کریں۔ سسٹم خود بخود قیمت خرید دکھائے گا تاکہ آپ اپنا منافع (Profit) دیکھ کر گاہک سے سودا کر سکیں۔ 'Save' کرنے پر رسید (Invoice) خودکار طریقے سے بن جائے گی۔

### **3. چیک باؤنس ہو جائے تو کیا کریں؟**
'Cheque Register' میں جائیں، متعلقہ چیک تلاش کریں اور 'Bounce' کے بٹن پر کلک کریں۔ سسٹم اس کے مالی اثرات کو کھاتے میں اپ ڈیٹ کر دے گا۔

### **4. ڈیٹا کا بیک اپ کیسے لیں؟**
'Settings' میں جائیں اور 'Backup' کے بٹن پر کلک کریں۔ ایک فائل ڈاؤن لوڈ ہوگی جس میں آپ کا تمام ڈیٹا محفوظ ہوگا۔ اسے ہر ہفتے ڈاؤن لوڈ کرنا بہتر ہے۔

---

### **Developer Details**
- **Lead Developer:** Yasin Ullah
- **System Version:** 1.0.0 (Stable)
- **Project Name:** BNI Enterprises Inventory System

---
*© 2026 BNI Enterprises. All Rights Reserved. This documentation is intended for client use and system understanding.*
