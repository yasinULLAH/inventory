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
- **Real-time KPI Cards:** Visual indicators for In-Stock units, Sold units, Returns, Damaged/Lost, Total Purchase/Sales Value, Tax, Net Profit, Expenses, Today's Sales, Pending Cheques, Customers, and Suppliers.
- **Charts:** Interactive Chart.js visualizations — Sales Trend (line), Model-wise Stock (doughnut), Income vs Expense (bar), Inventory Status (pie).
- **Model-wise Summary:** A comprehensive table showing inventory distribution by model (Total vs Available).
- **Financial Alerts:** Highlighted warnings for **Pending Cheques** and **Overdue Installments** requiring attention.
- **Quick Actions:** One-click buttons for New Sale, New Purchase, Add Customer, Add Expense, Process Return, View Inventory, New Quotation, Installments, Daily Report, Profit Report.
- **Recent Activity:** Quick view of the last 10 sales and purchases for immediate oversight.
- **Pending Bank Deposits:** New card showing total money allocated but not yet deposited to bank.

**اردو:**
- **لائیو اسٹیٹسٹکس:** اسٹاک، فروخت، واپسی، اور کل منافع کی فوری معلومات۔
- **چارٹس:** سیلز ٹرینڈ، ماڈل وائز اسٹاک، انکم بمقابلہ اخراجات، انوینٹری اسٹیٹس کے متحرک چارٹس۔
- **ماڈل کے لحاظ سے خلاصہ:** ہر ماڈل کی دستیابی اور فروخت کا مکمل چارٹ۔
- **فنانشل الرٹس:** پینڈنگ چیکس اور اوورڈیو اقساط کے بارے میں خودکار وارننگز۔
- **فوری کارروائی:** نیو سیل، نیو پرچیز، کسٹمر، اخراجات، ریٹرن، انوینٹری، کوٹیشن، اقساط، رپورٹس کے شارٹ کٹ بٹن۔
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
- **Status Engine:** Tracks bikes through 6 states: `In Stock`, `Sold`, `Returned`, `Returned to Supplier`, `Reserved`, `Damaged/Lost`.
- **Bike History Timeline:** A granular view showing the exact lifecycle of a specific chassis (Purchased -> Stocked -> Sold -> Returned).
- **Bulk Actions:** Multi-select bikes for mass deletion or exporting to CSV. Full CSV export of filtered inventory.
- **Filtering:** Deep search by Date Range, Model, Status, Color, or Keywords.
- **Edit Modal:** Inline edit for color, purchase price, status, notes, image. Status change to Damaged/Lost auto-creates expense entry.
- **DataTables:** Responsive table with column filtering, state saving, page totals (purchase price, selling price, margin).

**اردو:**
- **اسٹیٹس انجن:** بائیک کے 6 مختلف مراحل (اسٹاک، فروخت، واپسی، سپلائر کو واپسی، ریزرو، خراب/گم) کی نگرانی۔
- **ٹائم لائن:** ہر بائیک کی مکمل ہسٹری—کب خریدی گئی، کب اسٹاک میں آئی اور کب فروخت ہوئی۔
- **بلک ایکشنز:** ایک ساتھ کئی بائیکس کو ڈیلیٹ یا ایکسل میں ایکسپورٹ کرنے کی سہولت۔
- **ایڈٹ موڈل:** رنگ، قیمت، اسٹیٹس، نوٹس اور تصویر میں ترمیم۔ ڈیمیجڈ/لاسٹ کرنے پر خودکار اخراجات میں اضافہ۔

### **D. Sales, Tax & Invoicing / فروخت اور انوائسنگ**
**English:**
- **Profit Guard:** Shows the Purchase Price during sales entry to ensure margins are maintained.
- **Dynamic Tax Logic:** Calculates tax based on system settings (Percentage of Purchase vs Selling Price).
- **Smart Invoicing:** Generates professional, printable invoices including company branding, customer CNIC, and bike technical specs.
- **Payment Diversity:** Support for Cash, Cheque, Bank Transfer, and Online payments.
- **Installment Plans:** Auto-generate monthly installment schedules with due dates, amounts, penalty fees, and status tracking (pending/paid/overdue/cancelled).
- **Accessories on Sale:** Add accessories (helmets, chargers, locks) with quantity, unit price, discount, and auto stock deduction.
- **Walk-in Customer Support:** Full-payment enforced for unregistered cash customers.
- **Quotation Conversion:** Convert pending quotations to sales with one click — preserves bike, customer, accessories, and installment data.
- **Customer Advance Display:** Shows available advance/balance when selecting customer during sale.

**اردو:**
- **منافع کی حفاظت:** سیلز کے وقت قیمتِ خرید دکھانا تاکہ منافع یقینی بنایا جا سکے۔
- **ٹیکس سسٹم:** سیٹنگز کے مطابق خودکار ٹیکس کا حساب (خریداری یا فروخت کی قیمت پر)۔
- **انوائس:** پروفیشنل اور پرنٹ ایبل رسید جس میں کمپنی کا نام، گاہک کا شناختی کارڈ اور بائیک کی تفصیلات شامل ہوتی ہیں۔
- **قسطوں کا نظام:** خودکار ماہانہ قسط جنریشن، ادائیگی، جرمانہ اور اوورڈیو ٹریکنگ۔
- **ایکسسریز:** بائیک کے ساتھ ہیلمٹ، چارجر وغیرہ کی فروخت اور اسٹاک میں خودکار کمی۔
- **واک ان کسٹمر:** بغیر رجسٹریشن کے نقد فروخت کی سہولت۔
- **کوٹیشن کنورژن:** ایک کلک میں کوٹیشن کو فروخت میں تبدیل کریں۔
- **کسٹمر ایڈوانس:** فروخت کے وقت کسٹمر کا ایڈوانس بیلنس خودکار دکھائی دیتا ہے۔

### **E. Financial Ecosystem (Cheques & Ledgers) / چیک رجسٹر اور لیجرز**
**English:**
- **Cheque Life-cycle:** Track every cheque from 'Pending' to 'Cleared' or 'Bounced'. Bounce handling auto-reverses ledger entries and installment adjustments with penalty fees.
- **Party Ledgers:** Every transaction (Sale/Return/Payment) automatically posts a Debit or Credit entry to the specific Customer or Supplier Ledger.
- **Running Balance:** Real-time calculation of outstanding amounts for every business contact. Clear Due/Advance indicators with color coding.
- **Printable Ledgers:** Customer and supplier ledgers can be printed for physical record-keeping.

**اردو:**
- **چیک مینجمنٹ:** چیک کا اسٹیٹس (پینڈنگ، کلیئر، باؤنس) مانیٹر کرنے کا مکمل نظام۔ باؤنس پر خودکار لیجر اور قسط ایڈجسٹمنٹ۔
- **کھاتہ جات (Ledgers):** ہر فروخت یا واپسی پر گاہک یا سپلائر کے کھاتے میں خودکار اندراج۔
- **بیلنس:** ہر پارٹی کے بقایا جات کا فوری اور درست حساب۔ ڈیو/ایڈوانس کے کلر کوڈڈ اشارے۔
- **پرنٹ ایبل لیجر:** کسٹمر اور سپلائر کے لیجر پرنٹ کریں۔

### **F. Installment Management / قسطوں کا نظام**
**English:**
- **Auto-Generation:** When a bike is sold with down payment less than total, monthly installment rows are auto-created.
- **Payment Recording:** Record partial or full payments with penalty fees, payment method (cash/cheque/bank/online), and cheque details.
- **Overdue Detection:** Automatic highlighting of past-due installments.
- **Auto-Distribution:** Payments auto-allocate to oldest pending installments first.
- **Cancellation:** Installments auto-cancelled on sale return.

**اردو:**
- **خودکار جنریشن:** جب ڈاؤن پیمنٹ کل رقم سے کم ہو تو ماہانہ قسطیں خودکار بن جاتی ہیں۔
- **ادائیگی ریکارڈ:** جزوی/مکمل ادائیگی، جرمانہ، ادائیگی کا طریقہ اور چیک کی تفصیلات ریکارڈ کریں۔
- **اوورڈیو ڈیٹیکشن:** مقررہ تاریخ گزرنے پر قسط سرخ نشان زد ہو جاتی ہے۔
- **خودکار تقسیم:** ادائیگی پہلے پرانی قسطوں پر لگتی ہے۔
- **منسوخی:** واپسی پر تمام زیر التواء قسطیں خودکار منسوخ۔

### **G. Income & Expense Tracking / آمدنی اور اخراجات**
**English:**
- **Dual Tracking:** Separate module for non-bike income and expenses with category-based organization.
- **Summary Cards:** Total income, total expense, net balance, avg transaction, top categories.
- **Charts:** Income by category (doughnut), expense by category (doughnut), daily trend (line).
- **Auto-Expense:** Marking a bike as "Damaged/Lost" auto-creates an "Inventory Loss" expense entry.
- **Category Autocomplete:** Auto-suggests existing categories during entry.

**اردو:**
- **دوہرا ریکارڈ:** بائیک سے متعلقہ اور غیر متعلقہ آمدنی/اخراجات کا علیحدہ اندراج۔
- **خلاصہ کارڈز:** کل آمدنی، اخراجات، خالص بیلنس، اوسط، نمایاں کیٹگری۔
- **چارٹس:** زمرہ وار اور روزانہ رجحان کے چارٹس۔
- **خودکار اخراجات:** بائیک کو ڈیمیجڈ/لاسٹ کرنے پر "انوینٹری نقصان" کا خودکار اندراج۔

### **H. Quotations / کوٹیشنز**
**English:**
- **Quote Creation:** Create quotations with customer, bike, accessories, quoted price, validity date.
- **Convert to Sale:** One-click conversion — auto-creates sale, payment, ledger entries, and installments.
- **Status Tracking:** Pending, Accepted, Rejected, Converted.
- **Edit/Delete:** Full CRUD with permission checks on pending quotations.

**اردو:**
- **کوٹیشن بنانا:** کسٹمر، بائیک، ایکسیسریز، قیمت اور میعاد کے ساتھ کوٹیشن بنائیں۔
- **فروخت میں تبدیل:** ایک کلک سے کوٹیشن کو مکمل فروخت میں تبدیل کریں۔
- **اسٹیٹس ٹریکنگ:** پینڈنگ، قبول شدہ، مسترد، تبدیل شدہ۔

### **I. Money Destination Tracking / رقم کی منزلیں**
**English:**
- **Destination CRUD:** Manage bank accounts, persons, and wallets as money destinations.
- **Full Account Details:** Account title, number, branch, opening balance, contact person/phone.
- **Active/Inactive Toggle:** Disable destinations without deleting.
- **Allocation Tracking:** Total amount allocated per destination visible in listing.
- **9 Default Destinations:** Pre-seeded for immediate use.

**اردو:**
- **ڈیسٹینیشن مینجمنٹ:** بینک، شخص، والٹ کو بطور منزل شامل کریں۔
- **مکمل اکاؤنٹ تفصیلات:** اکاؤنٹ ٹائٹل، نمبر، برانچ، اوپننگ بیلنس، رابطہ۔
- **ایکٹو/غیر ایکٹو:** ڈیلیٹ کیے بغیر غیر فعال کریں۔
- **9 ڈیفالٹ ڈیسٹینیشنز:** فوری استعمال کے لیے پہلے سے شامل۔

### **J. Money Tracking / رقم کی ٹریکنگ**
**English:**
- **Sale Allocations:** Track where each sale's money goes — to bank, person, or wallet.
- **Multiple Allocations:** One sale can be split across multiple destinations.
- **Smart Bike Filtering:** Dropdown only shows bikes that still have remaining amount to allocate — fully tracked bikes are hidden automatically.
- **Auto-Fill Amount:** When a bike is selected, the amount field auto-fills with the full remaining amount by default — no manual typing needed.
- **Deposit Status:** Shows what percentage of allocation has been deposited to bank.
- **Inline Sale Entry:** Optional collapsible section during sale to track destinations.
- **Reports:** Money by Destination, Money by Sale, Untracked Sales, Money Flow.

**اردو:**
- **فروخت کی رقم کی منزل:** ہر فروخت کی رقم کہاں گئی (بینک/شخص/والٹ) ریکارڈ کریں۔
- **متعدد منزلیں:** ایک فروخت کی رقم کئی جگہ تقسیم کی جا سکتی ہے۔
- **ڈپازٹ اسٹیٹس:** کتنی فیصد رقم بینک پہنچی۔
- **رپورٹس:** ڈیسٹینیشن وار، سیل وار، غیر ٹریک شدہ، منی فلو رپورٹس۔

### **K. Bank Deposits / بینک ڈپازٹ**
**English:**
- **Deposit Recording:** Record actual bank deposits with date, amount, deposit type (cash/cheque/transfer/online), reference number.
- **Receipt Upload:** Upload bank slip images (auto-compressed to max 200KB).
- **Link to Sales:** Link deposits to specific bike sale allocations — track which sale money went into which deposit.
- **Smart Bike Selection:** Dropdown only shows bikes that have been allocated to the selected bank destination and still have undeposited amount remaining.
- **Auto-Fill Amount:** When a bike is linked, the amount field auto-fills with the full remaining deposit amount; you can adjust it as needed.
- **Amount Validation:** Client-side clamping prevents entering more than the bike's remaining deposit amount.
- **Searchable Dropdowns:** All bike selection dropdowns use Select2 with search — easy to find bikes even as the list grows.
- **Pending Deposit Tracking:** Dashboard shows total allocated but undeposited amount.

**اردو:**
- **ڈپازٹ ریکارڈ:** بینک میں جمع کروائی گئی رقم کی تاریخ، مقدار، قسم اور حوالہ نمبر کے ساتھ ریکارڈ۔
- **رسید اپ لوڈ:** بینک سلپ کی تصویر اپ لوڈ کریں (خودکار کمپریس)۔
- **فروخت سے لنک:** بتائیں کہ کون سی بائیک کی فروخت کی رقم ڈپازٹ میں شامل ہے۔
- **زیر التواء ڈپازٹ:** ڈیش بورڈ پر وہ رقم دکھائی دیتی ہے جو ایلوکیٹ تو ہو گئی لیکن بینک نہیں گئی۔

### **L. Models Management / بائیک ماڈلز**
**English:**
- **CRUD:** Model code, name, category, short code, image upload.
- **Inventory Stats:** Total inventory, in stock, sold count per model.
- **Quick Actions:** Purchase/Sell pre-filtered by model.
- **Delete Protection:** Cannot delete model linked to bikes.

**اردو:**
- **ماڈل کا انتظام:** کوڈ، نام، کیٹگری، شارٹ کوڈ، تصویر کے ساتھ۔
- **اسٹاک کے اعدادوشمار:** ہر ماڈل کی کل تعداد، اسٹاک، فروخت۔

### **M. Accessories Management / ایکسیسریز**
**English:**
- **CRUD:** Name, SKU, purchase/selling price, current stock.
- **Analytics:** Total items, stock value (PP/SP), sold quantity, revenue, profit, discounts.
- **Charts:** Top 5 sold accessories (bar chart).
- **Auto Stock Deduction:** Stock decreases automatically on sale.
- **Custom Accessory:** Add custom items during sale if not in inventory.

**اردو:**
- **ایکسیسری کا انتظام:** نام، SKU، خرید/فروخت قیمت، اسٹاک۔
- **تجزیہ:** کل اشیاء، اسٹاک ویلیو، فروخت کردہ تعداد، منافع۔
- **خودکار اسٹاک کمی:** فروخت پر اسٹاک خودکار کم ہوتا ہے۔

### **N. Users & Roles / صارفین اور رولز**
**English:**
- **Role Management:** Create custom roles with granular page-level permissions (view/add/edit/delete) for 20+ pages.
- **User Management:** Full CRUD with strong password enforcement, active/inactive toggle.
- **Administrator Protection:** Admin role cannot be deleted/renamed. Users cannot delete themselves.

**اردو:**
- **رولز:** اپنی مرضی کے رول بنائیں اور 20+ پیجز کے لیے اجازتیں دیں۔
- **صارفین:** نئے صارف بنائیں، مضبوط پاس ورڈ، فعال/غیر فعال کریں۔
- **ایڈمن تحفظ:** ایڈمن رول حذف نہیں کیا جا سکتا۔

### **O. Settings & Maintenance / ترتیبات اور دیکھ بھال**
**English:**
- **Company Config:** Name, branch, currency, tax rate, tax base, invoice settings.
- **Session Timeouts:** Configurable idle (default 40 min) and absolute (default 8 hours) timeouts.
- **Password Change:** Strong password enforcement with bcrypt hashing.
- **Database Backup:** One-click full SQL dump of all 20+ tables.
- **Database Restore:** Upload SQL file to restore (with overwrite warning).
- **App Logo Upload:** Auto-generates all icon sizes (favicon, apple-touch, PWA manifest).
- **Landing Page Management:** Manage hero, leadership team, gallery, bike/quote requests, social media links for public landing page.

**اردو:**
- **کمپنی کی ترتیبات:** نام، برانچ، کرنسی، ٹیکس، انوائس۔
- **سیشن ٹائم آؤٹ:** غیرفعالیت (40 منٹ) اور مطلق (8 گھنٹے)۔
- **پاس ورڈ تبدیل:** مضبوط پاس ورڈ کی شرط۔
- **ڈیٹا بیک اپ:** ایک کلک میں تمام ٹیبلز کا SQL بیک اپ۔
- **ڈیٹا ریسٹور:** SQL فائل اپ لوڈ کر کے بحال کریں۔
- **لوگو اپ لوڈ:** خودکار آئیکن جنریشن۔
- **لینڈنگ پیج:** ہیرو، لیڈرشپ، گیلری، بائیک/کوٹیشن کی درخواستوں کا انتظام۔

### **P. Landing Page / عوامی ویب سائٹ**
**English:**
- **Public-Facing Site:** `landing.php` — Full company showcase with 3D particle background (Three.js).
- **Hero Section:** Dynamic title/subtitle managed from admin settings.
- **Leadership Team:** Manage team members with name, position, image, message.
- **Image Gallery:** Upload and manage gallery images.
- **Bike Requests:** Public form for bike inquiries, managed in admin.
- **Quote Requests:** Public form for quote inquiries, managed in admin.
- **Social Media:** Facebook, Instagram, Twitter, WhatsApp links configurable.
- **Vision/Mission:** Editable statements from admin.

**اردو:**
- **عوامی سائٹ:** کمپنی کی نمائش کے لیے ایک خوبصورت لینڈنگ پیج۔
- **ہیرو سیکشن:** تھری ڈی اینیمیشن کے ساتھ کمپنی کا تعارف۔
- **لیڈرشپ ٹیم:** ٹیم ممبران کا انتظام۔
- **گیلری:** تصاویر کا البم۔
- **بائیک/کوٹیشن کی درخواستیں:** عوام سے درخواستیں وصول کریں۔

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

The system operates on 23+ highly optimized tables, including:
1.  **`settings`**: Configuration, branding, and security.
2.  **`suppliers`**: Supplier directory and contact info.
3.  **`customers`**: Customer database (CNIC/Phone/Address/Is Filer).
4.  **`models`**: Master list of bike variants (with image).
5.  **`accessories`**: Sellable accessories/parts with stock tracking.
6.  **`purchase_orders`**: Header records for procurement.
7.  **`bikes`**: Central inventory table (Chassis/Motor/Pricing/Status/Return info).
8.  **`sale_accessories`**: Accessories sold with each bike.
9.  **`payments`**: Transaction log for all cash/cheque/bank/online flows.
10. **`installments`**: Installment plans with due dates, amounts, penalties, status.
11. **`cheque_register`**: Consolidated cheque tracking across all transaction types.
12. **`ledger`**: Double-entry accounting for financial transparency.
13. **`income_expenses`**: Non-bike income and expense tracking.
14. **`quotations`**: Pre-sales quotes with conversion to sale.
15. **`money_destinations`**: Master list of banks, persons, and wallets for money tracking (with account details, opening balance).
16. **`sale_money_allocations`**: Links sold bikes to money destinations with amounts, dates, and audit info.
17. **`bank_deposits`**: Actual bank deposit records with receipt images.
18. **`deposit_allocations`**: Links deposits to specific sale allocations.
19. **`users`**: System users with role assignment.
20. **`roles`**: Role definitions for RBAC.
21. **`role_permissions`**: Granular page-level permissions (view/add/edit/delete) for 20+ pages.
22. **`leadership`**, **`gallery`**, **`bike_requests`**, **`quote_requests`**: Public landing page content management.

---

## ⚙️ 5. Security & Maintenance / سیکیورٹی اور دیکھ بھال

- **Session Hardening:** Automatic idle timeout (configurable, default 40 mins) and absolute session expiration (default 8 hours).
- **Authentication:** Passwords encrypted using `PASSWORD_DEFAULT` (Bcrypt).
- **Brute-Force Protection:** IP banned after 7 failed login attempts for 3 hours.
- **CAPTCHA:** Math-based SVG CAPTCHA on login (5-minute lifetime, auto-refresh).
- **CSRF Protection:** Unique token on every POST request, validated server-side.
- **.htaccess Security:** Blocks sensitive files, sets security headers, prevents uploads execution.
- **RBAC:** Role-Based Access Control with granular page/action permissions for 20+ modules.
- **Theme Engine:** Instant toggle between **Dark** and **Light** modes for user comfort (persistent via localStorage).
- **Data Portability:** Integrated Database Backup tool exports the entire system (23+ tables) into a single `.sql` file. Restore also available.

---

## 🇵🇰 استعمال کرنے کا طریقہ (User Manual)

1.  **پہلا قدم:** 'Suppliers' میں جا کر اپنے ڈیلرز شامل کریں اور 'Models' میں بائیک کے ماڈلز بنائیں۔ Accessories بھی شامل کریں۔
2.  **خریداری:** 'Purchase Entry' میں بائیک کا چیسس اور انجن نمبر لکھیں۔ ایک سے زیادہ بائیکس اور ادائیگیاں ایک ساتھ شامل کریں۔
3.  **فروخت:** 'Sales Entry' میں بائیک منتخب کریں، گاہک کی تفصیل لکھیں، ایکسیسریز شامل کریں، اور قسطیں بنائیں۔
4.  **رسید:** سیلز کے بعد 'Print Invoice' پر کلک کر کے گاہک کو خوبصورت رسید دیں۔
5.  **ادائیگی:** چیک کلیئر/باؤنس کریں اور قسطوں کی ادائیگی ریکارڈ کریں۔
6.  **رپورٹس:** 'Dashboard' اور 'Reports' میں جا کر اسٹاک، منافع، ٹیکس اور روزانہ کاروبار مانیٹر کریں۔
7.  **رقم کی ٹریکنگ:** 'Money Destinations' میں بینک، شخص، یا والٹ شامل کریں — 'Money Tracking' میں ہر سیل کی رقم کہاں گئی ٹریک کریں — 'Bank Deposits' میں اصل بینک ڈپازٹ ریکارڈ کریں۔
8.  **بیک اپ:** 'Settings' سے وقتاً فوقتاً ڈیٹا بیس کا بیک اپ لیں۔

---

### **Technical Meta**
- **Author:** Yasin Ullah (Bannu Software Solutions)
- **Version:** 2.0.0
- **License:** Proprietary / Client Exclusive
- **WhatsApp:** 0336-1593533
- **Website:** https://www.yasinbss.com

---
*© 2026 BNI Enterprises. This documentation is intended to provide a full operational understanding of the BDMS application.*


---

## 🆕 Recent System Updates (Bank Deposits & Money Tracking)
**English:**
The system has been recently upgraded to include comprehensive money flow tracking:
- **Money Destinations:** Track business funds across Banks, Persons, and Wallets.
- **Sale Allocations:** Allocate exactly where the bike's sale money is going.
- **Bank Deposits:** Record actual bank deposits, link them to specific bike sales, and upload deposit receipts.
- **Smart UI:** Searchable Select2 dropdowns, auto-filling remaining amounts, and a Dashboard widget for Pending Deposits.
- **Auto-Cleanup:** Returning a sale now automatically deletes its linked installments, accessory stock deductions, and money allocations.

**Urdu / اردو:**
سسٹم میں حال ہی میں پیسوں کے حساب کتاب (Money Flow Tracking) کے نئے فیچرز شامل کیے گئے ہیں:
- **منی ڈیسٹینیشنز (Money Destinations):** اپنے بینک اکاؤنٹس، کیش، اور والٹس کو سسٹم میں شامل کریں۔
- **رقم کی ایلوکیشن:** بائیک بیچنے کے بعد بتائیں کہ اس کی رقم کس بینک یا شخص کے پاس گئی ہے۔
- **بینک ڈپازٹ:** بینک جا کر پیسے جمع کروانے کا ریکارڈ رکھیں، بائیک کی سیل سے لنک کریں، اور بینک سلپ اپ لوڈ کریں۔
- **سمارٹ فلٹرز:** ڈیش بورڈ پر Pending Deposits کا کارڈ جو یاد دلاتا ہے کہ کتنے پیسے بینک میں جمع کروانے ہیں۔
- **خودکار واپسی:** سیل ریٹرن پر اقساط، ایکسسریز کا سٹاک، اور منی ٹریکنگ خودکار طور پر ایڈجسٹ ہو جاتی ہے۔
