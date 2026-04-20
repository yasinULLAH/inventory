# ⚡ BNI Enterprises: Professional Bike Dealer Management System (BDMS)
## 🏍️ موٹر سائیکل ڈیلر مینجمنٹ سسٹم - مکمل اور جامع دستاویزات

---

## 📖 Project Overview / پراجیکٹ کا تعارف
**English:**
The **BNI Enterprises BDMS** is an enterprise-grade inventory and financial management solution developed by **Yasin Ullah**. It is specifically engineered for motorcycle dealerships to handle high-volume stock, complex tax calculations, and multi-party financial accounting. It provides a seamless transition from procurement (Purchase) to revenue (Sales) with integrated Cheque and Ledger management.

**اردو:**
بی این آئی انٹرپرائزز (BDMS) ایک پروفیشنل انوینٹری اور فنانشل مینجمنٹ سسٹم ہے جسے **یاسین اللہ** نے تیار کیا ہے۔ یہ خاص طور پر موٹر سائیکل ڈیلرز کے لیے بنایا گیا ہے تاکہ اسٹاک، ٹیکس کے حساب کتاب، اور مالی کھاتوں (Ledgers) کو مکمل طور پر ڈیجیٹل کیا جا سکے۔ یہ سسٹم خریداری سے لے کر فروخت تک کے تمام مراحل کو نہایت باریکی اور درستی سے منظم کرتا ہے۔

---

## 🚀 1. Installation & Deployment / انسٹالیشن اور استعمال

### **System Requirements / سسٹم کی ضروریات**
- **PHP:** 7.4 or 8.x
- **Database:** MySQL 5.7+ or MariaDB
- **Web Server:** Apache (XAMPP/WAMP) or any Linux-based hosting.

### **Quick Start / فوری شروعات**
1. **Upload:** Place all source files in your server directory.
2. **Auto-Installation:** Open the application URL. The system will detect if the database is missing.
3. **Initialize:** Click the **"⚡ Install Database"** button.
   - *Logic:* This automatically creates the `bni_enterprises2` database and 9 relational tables with default settings and seed data (Models, Customers, etc.).
4. **Access:** Default login is `admin` / `admin123`.

---

## 🛠️ 2. Core Modules & Functionalities / سسٹم کے ماڈیولز اور ان کی کارکردگی

### **A. Intelligent Dashboard / ذہین ڈیش بورڈ**
**English:**
- **Real-time KPI Cards:** Visual indicators for In-Stock units, Sold units, Returns, Total Sales Value, and Net Profit Margin.
- **Model-wise Summary:** A comprehensive table showing inventory distribution by model (Total vs Available).
- **Financial Alerts:** Highlighted warnings for **Pending Cheques** requiring clearance.
- **Recent Activity:** Quick view of the last 10 sales and purchases for immediate oversight.

**اردو:**
- **لائیو اسٹیٹسٹکس:** اسٹاک، فروخت، واپسی، اور کل منافع کی فوری معلومات۔
- **ماڈل کے لحاظ سے خلاصہ:** ہر ماڈل کی دستیابی اور فروخت کا مکمل چارٹ۔
- **فنانشل الرٹس:** پینڈنگ چیکس کے بارے میں خودکار وارننگز۔
- **حالیہ سرگرمی:** آخری 10 خریداریوں اور فروخت کی فوری لسٹ۔

### **B. Purchase Order Management / خریداری کا اندراج**
**English:**
- **Multi-Unit Entry:** Add multiple bikes under a single supplier invoice.
- **Data Points:** Chassis No (Unique), Motor No, Color, Purchase Price, Safeguard Notes, and Accessories.
- **Duplicate Prevention:** AJAX-based real-time validation checks if a Chassis Number already exists to prevent data corruption.
- **Payment Linking:** Direct integration with the Cheque Register if paid via bank instruments.

**اردو:**
- **بلک انٹری:** ایک ہی انوائس پر کئی بائیکس شامل کرنے کی سہولت۔
- **ڈیٹا اندراج:** چیسس نمبر (منفرد)، موٹر نمبر، رنگ، قیمتِ خرید، سیف گارڈ نوٹس اور اضافی سامان۔
- **دوہرے اندراج کی روک تھام:** چیسس نمبر کو فوری طور پر چیک کرنے کے لیے AJAX سسٹم۔
- **ادائیگی:** خریداری کے وقت چیک کی تفصیلات خودکار طریقے سے چیک رجسٹر میں منتقل ہو جاتی ہیں۔

### **C. Advanced Inventory Control / انوینٹری مینجمنٹ**
**English:**
- **Status Engine:** Tracks bikes through 4 states: `In Stock`, `Sold`, `Returned`, `Reserved`.
- **Bike History Timeline:** A granular view showing the exact lifecycle of a specific chassis (Purchased -> Stocked -> Sold -> Returned).
- **Bulk Actions:** Multi-select bikes for mass deletion or exporting to CSV.
- **Filtering:** Deep search by Date Range, Model, Status, or Keywords.

**اردو:**
- **اسٹیٹس انجن:** بائیک کے مختلف مراحل (اسٹاک، فروخت، واپسی) کی نگرانی۔
- **ٹائم لائن:** ہر بائیک کی مکمل ہسٹری—کب خریدی گئی، کب اسٹاک میں آئی اور کب فروخت ہوئی۔
- **بلک ایکشنز:** ایک ساتھ کئی بائیکس کو ڈیلیٹ یا ایکسل میں ایکسپورٹ کرنے کی سہولت۔

### **D. Sales, Tax & Invoicing / فروخت اور انوائسنگ**
**English:**
- **Profit Guard:** Shows the Purchase Price during sales entry to ensure margins are maintained.
- **Dynamic Tax Logic:** Calculates tax based on system settings (Percentage of Purchase vs Selling Price).
- **Smart Invoicing:** Generates professional, printable invoices including company branding, customer CNIC, and bike technical specs.
- **Payment Diversity:** Support for Cash, Cheque, Bank Transfer, and Online payments.

**اردو:**
- **منافع کی حفاظت:** سیلز کے وقت قیمتِ خرید دکھانا تاکہ منافع یقینی بنایا جا سکے۔
- **ٹیکس سسٹم:** سیٹنگز کے مطابق خودکار ٹیکس کا حساب (خریداری یا فروخت کی قیمت پر)۔
- **انوائس:** پروفیشنل اور پرنٹ ایبل رسید جس میں کمپنی کا نام، گاہک کا شناختی کارڈ اور بائیک کی تفصیلات شامل ہوتی ہیں۔

### **E. Financial Ecosystem (Cheques & Ledgers) / چیک رجسٹر اور لیجرز**
**English:**
- **Cheque Life-cycle:** Track every cheque from 'Pending' to 'Cleared' or 'Bounced'.
- **Party Ledgers:** Every transaction (Sale/Return/Payment) automatically posts a Debit or Credit entry to the specific Customer or Supplier Ledger.
- **Running Balance:** Real-time calculation of outstanding amounts for every business contact.

**اردو:**
- **چیک مینجمنٹ:** چیک کا اسٹیٹس (پینڈنگ، کلیئر، باؤنس) مانیٹر کرنے کا مکمل نظام۔
- **کھاتہ جات (Ledgers):** ہر فروخت یا واپسی پر گاہک یا سپلائر کے کھاتے میں خودکار اندراج۔
- **بیلنس:** ہر پارٹی کے بقایا جات کا فوری اور درست حساب۔

---

## 📈 3. Business Logic & Math / کاروباری منطق اور فارمولے

### **1. Net Margin Calculation / خالص منافع**
The system calculates net profit after tax deductions:
- **Formula:** `Profit = Selling Price - (Purchase Price + Tax Amount)`
*This ensures the business sees real profit, not just the difference in price.*

### **2. Tax Policy / ٹیکس پالیسی**
Administrators can choose where tax is applied in the Settings:
- **Mode A:** `% of Purchase Price` (Standard for most dealers).
- **Mode B:** `% of Selling Price`.

---

## 📂 4. Relational Database Architecture / ڈیٹا بیس کی ساخت

The system operates on 9 highly optimized tables:
1.  **`settings`**: Configuration, branding, and security.
2.  **`suppliers`**: Supplier directory and contact info.
3.  **`customers`**: Customer database (CNIC/Phone/Address).
4.  **`models`**: Master list of bike variants.
5.  **`purchase_orders`**: Header records for procurement.
6.  **`bikes`**: Central inventory table (Chassis/Motor/Pricing/Status).
7.  **`cheque_register`**: Financial tracking of all bank instruments.
8.  **`payments`**: Transaction log for cash/online flows.
9.  **`ledger`**: Double-entry accounting for financial transparency.

---

## ⚙️ 5. Security & Maintenance / سیکیورٹی اور دیکھ بھال

- **Session Hardening:** Automatic idle timeout (40 mins) and session expiration (8 hours) to protect sensitive data.
- **Authentication:** Passwords are encrypted using `PASSWORD_DEFAULT` (Bcrypt).
- **Theme Engine:** Instant toggle between **Dark** and **Light** modes for user comfort.
- **Data Portability:** Integrated Database Backup tool exports the entire system into a single `.sql` file.

---

## 🇵🇰 استعمال کرنے کا طریقہ (User Manual)

1.  **پہلا قدم:** 'Suppliers' میں جا کر اپنے ڈیلرز شامل کریں اور 'Models' میں بائیک کے ماڈلز بنائیں۔
2.  **خریداری:** 'Purchase Entry' میں بائیک کا چیسس اور انجن نمبر لکھیں۔ سسٹم خود بخود ٹیکس کا حساب لگا لے گا۔
3.  **فروخت:** 'Sales Entry' میں بائیک منتخب کریں، گاہک کی تفصیل لکھیں اور 'Record Sale' پر کلک کریں۔
4.  **رسید:** سیلز کے بعد 'Print Invoice' پر کلک کر کے گاہک کو رسید دیں۔
5.  **رپورٹس:** 'Dashboard' پر جا کر کل منافع اور اسٹاک کی صورتحال مانیٹر کریں۔

---

### **Technical Meta**
- **Author:** Yasin Ullah
- **Version:** 1.0.0 (Gold Master)
- **License:** Proprietary / Client Exclusive

---
*© 2026 BNI Enterprises. This documentation is intended to provide a full operational understanding of the BDMS application.*
