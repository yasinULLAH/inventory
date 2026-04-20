# BNI Enterprises Bike Dealer Management System

Author: Yasin Ullah

## English Documentation

### 1. App Overview
BNI Enterprises Bike Dealer Management System is a single-file PHP and MySQL business management application built for electric bike dealership operations. It manages purchases, inventory, sales, returns, cheques, customer records, supplier records, ledgers, reports, settings, invoice printing, exports, database backup, and database restore in one place. [file:1]

The application is designed around a full dealership workflow: first the business installs the database, then logs in as admin, records purchase orders, adds bike units into inventory, sells bikes to customers, tracks payments and cheques, processes returns, reviews ledgers, and uses reports to monitor stock, tax, profit, and business movement. [file:1]

### 2. Core Architecture
- Built as a single `index.php` application with PHP, HTML, CSS, and JavaScript together in one file. [file:1]
- Uses MySQL with automatic database creation and table installation. [file:1]
- Uses session-based admin authentication. [file:1]
- Includes dark/light theme support stored in settings. [file:1]
- Uses responsive custom UI with sidebar navigation, topbar controls, tables, filters, forms, print views, modals, and status badges. [file:1]

### 3. Database Installation and First Run
When the application starts, it checks whether the database and required tables exist. If not, it shows a setup screen with an Install Database button that automatically creates the database and all required tables. [file:1]

The installation process creates these core tables:
- `settings` [file:1]
- `suppliers` [file:1]
- `customers` [file:1]
- `models` [file:1]
- `purchaseorders` [file:1]
- `bikes` [file:1]
- `chequeregister` [file:1]
- `payments` [file:1]
- `ledger` [file:1]

The installer also inserts default settings such as company name, branch name, tax rate, currency, tax basis, theme, invoice purchase-price visibility setting, and an admin password hash. It also seeds default bike models, one supplier, and four customers. [file:1]

### 4. Default Configuration
Default settings included in the app are:
- Company Name: BNI Enterprises. [file:1]
- Branch Name: Dera (Ahmed Metro). [file:1]
- Currency: Rs. [file:1]
- Tax Rate: 0.1. [file:1]
- Tax Applied On: purchase price by default. [file:1]
- Theme: dark by default. [file:1]
- Show Purchase Price on Invoice: disabled by default. [file:1]
- Default admin password hash corresponds to `admin123`. [file:1]

The seeded customers are Ahmed Ali, Muhammad Usman, Bilal Hussain, and Zafar Iqbal, and the default supplier is Default Supplier. Multiple electric bike and scooter models are also preloaded to make the app usable immediately after setup. [file:1]

### 5. Authentication and Session Handling
The login system is admin-based. The username is checked against `admin`, and the password is verified against the saved hashed password from settings. [file:1]

The app also includes two session protection rules:
- Absolute session expiry after 8 hours. [file:1]
- Idle logout after 40 minutes of inactivity. [file:1]

When a session expires because of idle time or maximum session length, the app destroys the session and redirects the user back to login with a message. [file:1]

### 6. Navigation Structure
The main sidebar provides the following modules:
- Dashboard. [file:1]
- Purchase Entry. [file:1]
- Inventory Stock. [file:1]
- Sales Entry. [file:1]
- Returns. [file:1]
- Cheque Register. [file:1]
- Customer Ledger. [file:1]
- Supplier Ledger. [file:1]
- Reports. [file:1]
- Models. [file:1]
- Customers. [file:1]
- Suppliers. [file:1]
- Settings. [file:1]

The topbar also provides current date/time display and theme toggle functionality. [file:1]

### 7. Dashboard Module
The dashboard is the management summary screen. It shows high-level cards and activity tables that help the owner quickly understand the business position. [file:1]

Dashboard cards include:
- In Stock count. [file:1]
- Total Sold count. [file:1]
- Returned count. [file:1]
- Purchase Value. [file:1]
- Sales Value. [file:1]
- Total Tax Paid. [file:1]
- Total Profit. [file:1]
- Pending Cheques count and amount. [file:1]

The dashboard also includes:
- A pending cheque warning banner when pending cheques exist. [file:1]
- Model-wise stock summary showing inventory, sold, returned, and available units per model. [file:1]
- Recent 10 sales table. [file:1]
- Recent 10 purchases table. [file:1]

This module gives an at-a-glance operational snapshot without needing to open every page separately. [file:1]

### 8. Purchase Entry Module
The Purchase Entry module records purchase orders from suppliers and inserts one or more bike units into inventory. [file:1]

Purchase order fields include:
- Order date. [file:1]
- Inventory date. [file:1]
- Supplier. [file:1]
- Cheque number. [file:1]
- Bank name. [file:1]
- Cheque date. [file:1]
- Cheque amount. [file:1]
- Notes. [file:1]

Inside the same purchase order, the user can add multiple bike rows dynamically. Each bike unit can contain:
- Chassis number. [file:1]
- Motor number. [file:1]
- Model. [file:1]
- Color. [file:1]
- Purchase price. [file:1]
- Safeguard notes. [file:1]
- Accessories. [file:1]
- Notes. [file:1]

Important purchase workflow behavior:
- Each saved purchase creates one purchase order record. [file:1]
- Each bike row creates a separate bike inventory record with status `instock`. [file:1]
- Tax amount is calculated per bike using the configured tax rate. [file:1]
- If a cheque number and cheque amount are entered, a cheque register entry is also created as type `payment` and status `pending`, linked to the purchase order. [file:1]
- The system shows how many bikes were saved and can report row-level save errors. [file:1]

The module also includes quick-add modal forms to add a new supplier or a new model directly from the purchase screen. [file:1]

A client-important safeguard exists here: chassis uniqueness is checked because the bikes table stores `chassisnumber` as unique, and the purchase screen also includes an AJAX duplicate chassis check before save. [file:1]

### 9. Inventory Stock Module
The Inventory module is the main stock management page. It lists all bikes with filtering, totals, view details, editing, deletion, bulk deletion, CSV export, and item-level status tracking. [file:1]

Inventory filters include:
- Search by chassis, motor, model, or color. [file:1]
- Status filter for `instock`, `sold`, `returned`, and `reserved`. [file:1]
- Model filter. [file:1]
- Color filter. [file:1]
- Inventory date range. [file:1]

Inventory list columns include:
- Serial number. [file:1]
- Chassis number. [file:1]
- Motor number. [file:1]
- Model. [file:1]
- Color. [file:1]
- Purchase price. [file:1]
- Status. [file:1]
- Selling price. [file:1]
- Selling date. [file:1]
- Margin. [file:1]
- Actions. [file:1]

Inventory actions include:
- View bike details. [file:1]
- Edit bike fields such as color, purchase price, status, notes, and safeguard notes. [file:1]
- Delete individual bike records. [file:1]
- Bulk delete selected bikes. [file:1]
- Bulk export selected bikes to CSV. [file:1]
- Full inventory export to CSV, optionally filtered by status. [file:1]

The inventory table also displays page totals for purchase price, selling price, and margin. Row colors visually distinguish sold, returned, and reserved records. [file:1]

#### Inventory detail view
The bike detail page shows a complete profile of a selected bike. It includes:
- Chassis number. [file:1]
- Motor number. [file:1]
- Model and model code. [file:1]
- Category. [file:1]
- Color. [file:1]
- Status. [file:1]
- Purchase price. [file:1]
- Selling price. [file:1]
- Tax amount. [file:1]
- Margin. [file:1]
- Order date. [file:1]
- Inventory date. [file:1]
- Selling date. [file:1]
- Customer details. [file:1]
- Supplier details. [file:1]
- Accessories. [file:1]
- Safeguard notes. [file:1]
- General notes. [file:1]

It also provides a printable timeline-style lifecycle view that can show when the bike was added, sold, and returned. [file:1]

### 10. Sales Entry Module
The Sales Entry module converts an in-stock bike into a sold bike. [file:1]

Sales form inputs include:
- Bike selection from in-stock bikes. [file:1]
- Selling date. [file:1]
- Selling price. [file:1]
- Customer. [file:1]
- Payment method: cash, cheque, bank transfer, or online. [file:1]
- Optional cheque details if payment method is cheque. [file:1]
- Accessories given. [file:1]
- Notes. [file:1]

The page automatically displays and calculates:
- Selected bike purchase price. [file:1]
- Tax amount. [file:1]
- Margin/profit. [file:1]

Sales save behavior includes:
- Updates the bike with selling price, selling date, customer, tax amount, margin, accessories, and notes. [file:1]
- Changes bike status to `sold`. [file:1]
- Creates a payment record linked to the sale. [file:1]
- If cheque payment is used, creates a cheque register entry of type `receipt` and status `pending`. [file:1]
- Creates a customer ledger credit entry describing the sale. [file:1]
- Stores the last sold bike in session so an invoice can be printed immediately. [file:1]

The tax calculation base depends on the setting `taxon`, which can use either purchase price or selling price. Margin is calculated as selling price minus purchase price minus tax. [file:1]

A walk-in or cash customer flow is supported because the customer selector allows a `Walk-in Cash Customer` option. [file:1]

### 11. Sale Invoice Printing
After recording a sale, the app can generate a printable invoice for that bike sale. [file:1]

Invoice includes:
- Company name. [file:1]
- Branch name. [file:1]
- Invoice number generated as `INV-YYYYMMDD-XXX`. [file:1]
- Sale date. [file:1]
- Customer name. [file:1]
- Customer phone. [file:1]
- Customer CNIC. [file:1]
- Bike details including model, category, chassis, motor, color, and accessories. [file:1]
- Purchase price, selling price, tax amount, and total amount structure. [file:1]

The settings page controls whether purchase price appears on the invoice using the `showpurchaseoninvoice` setting. [file:1]

The invoice page has print-friendly styling and can be printed directly from the browser. [file:1]

### 12. Returns Module
The Returns module handles return adjustments for bikes that were already sold. [file:1]

Return form fields include:
- Sold bike selection. [file:1]
- Return date. [file:1]
- Return amount. [file:1]
- Refund method: cash or cheque. [file:1]
- Optional cheque number, bank name, and cheque date when refund method is cheque. [file:1]
- Return notes. [file:1]

Return processing behavior includes:
- Updates the bike status to `returned`. [file:1]
- Stores return date, return amount, and return notes against the bike record. [file:1]
- If refund is by cheque, creates a cheque register entry of type `refund` and status `pending`. [file:1]
- Creates a customer ledger debit entry for the return/refund. [file:1]

This module ensures a returned sale remains historically traceable rather than being silently removed from business records. [file:1]

### 13. Cheque Register Module
The Cheque Register is a centralized page for all purchase, sales, and refund cheques. [file:1]

Cheque records can come from:
- Purchase orders as payment cheques. [file:1]
- Sales as receipt cheques. [file:1]
- Returns as refund cheques. [file:1]

Cheque fields tracked include:
- Cheque number. [file:1]
- Bank name. [file:1]
- Cheque date. [file:1]
- Amount. [file:1]
- Type: payment, receipt, refund. [file:1]
- Status: pending, cleared, bounced, cancelled. [file:1]
- Party name. [file:1]
- Reference type. [file:1]
- Reference ID. [file:1]
- Notes. [file:1]

Cheque filters include:
- Status. [file:1]
- Type. [file:1]
- Bank. [file:1]
- Date from. [file:1]
- Date to. [file:1]

Cheque actions include:
- Mark pending cheque as cleared. [file:1]
- Mark pending cheque as bounced. [file:1]
- Delete cheque entry. [file:1]

The page also shows status summary boxes for pending, cleared, bounced, and cancelled cheque totals and counts. [file:1]

### 14. Customer Ledger Module
The Customer Ledger module displays financial movement for a selected customer. [file:1]

It reads ledger entries where `partytype` is `customer` and displays:
- Date. [file:1]
- Description. [file:1]
- Debit. [file:1]
- Credit. [file:1]
- Running balance. [file:1]

Sale entries increase customer-side credit, while returns create debit entries. The ledger can also be printed for record sharing or paper filing. [file:1]

The page also displays customer identity details such as name, phone, CNIC, and address. [file:1]

### 15. Supplier Ledger Module
The Supplier Ledger module shows purchase-order-side financial history for a selected supplier. [file:1]

For each purchase order, it displays:
- Order date. [file:1]
- Cheque number. [file:1]
- Bank. [file:1]
- Cheque date. [file:1]
- Units purchased. [file:1]
- Cheque amount. [file:1]
- Bikes total. [file:1]
- Running balance. [file:1]

The supplier section also shows supplier contact and address information. This is useful for reconciling supplier payments against received inventory. [file:1]

### 16. Reports Module
The Reports module is one of the richest parts of the app. It contains multiple report tabs for business analysis. [file:1]

Available report sections include:
- Current Stock. [file:1]
- Sold Bikes. [file:1]
- Model-wise. [file:1]
- Tax Report. [file:1]
- Profit Margin. [file:1]
- Bank Cheque. [file:1]
- Monthly Summary. [file:1]
- Daily Ledger. [file:1]
- Purchase vs Sales. [file:1]

Most reports support date filters, and some use year or day-specific selection inputs. [file:1]

#### Current Stock Report
Shows current available bikes with their key stock data. [file:1]

#### Sold Bikes Report
Shows sold bikes with customer, sale, tax, and margin information. [file:1]

#### Model-wise Report
Shows per-model totals including inventory count, sold count, available count, returned count, total purchase, total sales, and total margin. [file:1]

#### Tax Report
Aggregates tax information by month and total period tax. [file:1]

#### Profit Margin Report
Shows month-wise sold count, total purchase, total sales, total tax, net profit, and average margin. [file:1]

#### Bank Cheque Report
Groups cheque information by bank, type, and status, with totals for pending, cleared, bounced, cancelled, count, and amount. [file:1]

#### Monthly Summary Report
Compares monthly purchased units and value against sold units, sales value, and profit for a selected year. [file:1]

#### Daily Ledger Report
Shows same-day sales and same-day inventory additions together so management can review what happened on a particular date. [file:1]

#### Purchase vs Sales Report
Shows month-by-month purchased units/value, sold units/value, and the difference between sales value and purchase value. [file:1]

These reports turn the application from a simple record-keeping tool into a business monitoring system. [file:1]

### 17. Models Module
The Models page is the catalog manager for all bike and scooter models. [file:1]

Each model record includes:
- Model code. [file:1]
- Model name. [file:1]
- Category. [file:1]
- Short code. [file:1]

The module supports:
- Add model. [file:1]
- Edit model. [file:1]
- Delete model. [file:1]
- View counts of total inventory, in stock, and sold for each model. [file:1]

This module standardizes model names across purchases, inventory, sales, and reports. [file:1]

### 18. Customers Module
The Customers page manages customer master records. [file:1]

Each customer includes:
- Name. [file:1]
- Phone. [file:1]
- CNIC. [file:1]
- Address. [file:1]

The module supports:
- Add customer. [file:1]
- Edit customer. [file:1]
- Delete customer. [file:1]
- View customer purchase count and total purchased amount in listing. [file:1]

Customers can also be added quickly from inside the Sales screen through a modal. [file:1]

### 19. Suppliers Module
The Suppliers page manages supplier master records. [file:1]

Each supplier includes:
- Name. [file:1]
- Contact. [file:1]
- Address. [file:1]

The module supports:
- Add supplier. [file:1]
- Edit supplier. [file:1]
- Delete supplier. [file:1]

Suppliers can also be created quickly from the Purchase screen through a modal. [file:1]

### 20. Settings Module
The Settings page controls application-wide business configuration. [file:1]

Settings available include:
- Company name. [file:1]
- Branch name. [file:1]
- Currency symbol. [file:1]
- Tax rate. [file:1]
- Tax calculated on purchase price or selling price. [file:1]
- Show or hide purchase price on invoice. [file:1]
- Change admin password. [file:1]

Password behavior in settings:
- A new password is saved only if provided. [file:1]
- Password length must be at least 8 characters before update. [file:1]
- The password is hashed before storage. [file:1]

The settings page also provides:
- Full SQL database backup download. [file:1]
- SQL database restore upload. [file:1]

The backup includes data from settings, suppliers, customers, models, purchaseorders, bikes, chequeregister, payments, and ledger. The restore function accepts `.sql` files and executes the SQL via multi-query. [file:1]

### 21. Export, Print, and File Utilities
The app includes several output and portability tools:
- Inventory CSV export for filtered list. [file:1]
- Bulk selected inventory export to CSV. [file:1]
- Printable bike details view. [file:1]
- Printable sale invoice. [file:1]
- Printable customer ledger. [file:1]
- SQL backup export. [file:1]
- SQL backup restore. [file:1]

These features make it easier to share records with management, accountants, or clients. [file:1]

### 22. Data Relationships and Business Effects
This app is not just isolated forms; entries affect multiple modules across the system. [file:1]

#### When a purchase order is saved
- Purchase order is created. [file:1]
- One or more bikes are added to inventory. [file:1]
- Each bike starts as `instock`. [file:1]
- Tax amount is calculated per bike. [file:1]
- Optional cheque creates a pending payment cheque entry. [file:1]
- Inventory, dashboard, reports, supplier ledger, and cheque register all become affected. [file:1]

#### When a sale is recorded
- The bike moves from `instock` to `sold`. [file:1]
- Selling price, selling date, customer, tax amount, margin, accessories, and notes are stored. [file:1]
- Payment record is created. [file:1]
- Optional cheque creates a pending receipt cheque entry. [file:1]
- Customer ledger receives a credit entry. [file:1]
- Dashboard, inventory, cheques, customer ledger, invoice printing, and reports are all updated by that sale. [file:1]

#### When a return is processed
- The bike moves from `sold` to `returned`. [file:1]
- Return amount, return date, and notes are stored. [file:1]
- Optional refund cheque creates a pending refund cheque entry. [file:1]
- Customer ledger receives a debit entry. [file:1]
- Dashboard, inventory, cheques, reports, and bike history view all reflect the return. [file:1]

This interconnected behavior is important for client understanding because every business action updates multiple parts of the system automatically. [file:1]

### 23. UI and Usability Features
The app contains several user-experience behaviors:
- Responsive layout for desktop and small screens. [file:1]
- Sidebar collapse and mobile sidebar overlay behavior. [file:1]
- Toast-style success and error messages. [file:1]
- Modal dialogs for quick add actions. [file:1]
- Filter bars on list pages. [file:1]
- Badges for statuses. [file:1]
- Print-specific page styling. [file:1]
- Dark and light theme support. [file:1]
- Remembered sidebar collapsed state via local storage. [file:1]

### 24. Seeded Data Included
The app installer preloads realistic model records for electric bikes and scooters, one default supplier, and exactly four sample customers so the interface is not empty after installation. [file:1]

This helps demonstration, testing, onboarding, and early live usage. [file:1]

### 25. Limitations and Important Notes
- The application uses a single admin login flow rather than multiple role-based users. [file:1]
- It is designed to run from a local/server PHP environment with MySQL credentials defined at the top of `index.php`. [file:1]
- Business tax is stored as a numeric setting and applied as a percentage-based calculation in purchase and sale flows. [file:1]
- Cheque status changes are manual through the cheque register page. [file:1]
- Inventory statuses supported in the system are `instock`, `sold`, `returned`, and `reserved`, though reservation workflow handling is lighter than purchase/sale/return flows. [file:1]

### 26. Step-by-Step Usage Guide
#### First-time setup
1. Place `index.php` on a PHP server with MySQL available. [file:1]
2. Update database credentials at the top of the file if needed. [file:1]
3. Open the app in a browser. [file:1]
4. Click Install Database on the setup screen. [file:1]
5. Log in using username `admin` and the configured admin password. The default stored password corresponds to `admin123`. [file:1]

#### Daily purchase workflow
1. Open Purchase Entry. [file:1]
2. Select supplier and enter order details. [file:1]
3. Add one or more bike rows. [file:1]
4. Fill chassis, model, color, price, and any notes. [file:1]
5. Save the purchase order. [file:1]
6. Verify new bikes in Inventory Stock. [file:1]

#### Daily sales workflow
1. Open Sales Entry. [file:1]
2. Select an in-stock bike. [file:1]
3. Review purchase price, tax, and auto-calculated margin. [file:1]
4. Choose customer or leave walk-in selection. [file:1]
5. Select payment method and fill optional cheque details if needed. [file:1]
6. Record the sale. [file:1]
7. Print the invoice if required. [file:1]

#### Return workflow
1. Open Returns. [file:1]
2. Select a sold bike. [file:1]
3. Enter return date and amount. [file:1]
4. Choose refund method. [file:1]
5. Add cheque details if refund is by cheque. [file:1]
6. Process the return. [file:1]

#### Monitoring workflow
1. Use Dashboard for daily status. [file:1]
2. Use Inventory for stock checking. [file:1]
3. Use Cheque Register for pending and bounced cheques. [file:1]
4. Use Customer and Supplier Ledgers for relationship tracking. [file:1]
5. Use Reports for business analysis. [file:1]
6. Use Settings for business profile, password, backup, and restore. [file:1]

### 27. Urdu Documentation

### 1. ایپ کا تعارف
BNI Enterprises Bike Dealer Management System ایک مکمل کاروباری ایپ ہے جو الیکٹرک بائیک ڈیلرشپ کے روزانہ کے کام کو ایک ہی جگہ پر منظم کرنے کے لیے بنائی گئی ہے۔ اس میں خریداری، اسٹاک، فروخت، واپسی، چیک رجسٹر، گاہک، سپلائر، لیجر، رپورٹس، انوائس پرنٹنگ، بیک اپ اور ریسٹور سب شامل ہیں۔ [file:1]

یہ ایپ پورے کاروباری بہاؤ کے مطابق کام کرتی ہے۔ پہلے ڈیٹا بیس انسٹال ہوتا ہے، پھر ایڈمن لاگ اِن کرتا ہے، خریداری درج کرتا ہے، بائیکس اسٹاک میں آتی ہیں، بعد میں فروخت ہوتی ہیں، ادائیگیاں اور چیکس ریکارڈ ہوتے ہیں، ضرورت پڑنے پر ریٹرن پروسیس ہوتی ہے، اور آخر میں رپورٹس اور لیجر کے ذریعے مکمل نگرانی کی جاتی ہے۔ [file:1]

### 2. بنیادی نظام
- پوری ایپ ایک ہی `index.php` فائل میں بنی ہوئی ہے۔ [file:1]
- MySQL ڈیٹا بیس خودکار طور پر انسٹال ہو سکتا ہے۔ [file:1]
- لاگ اِن سیشن پر مبنی ایڈمن سسٹم ہے۔ [file:1]
- ڈارک اور لائٹ تھیم دونوں موجود ہیں۔ [file:1]
- سائیڈبار، فارم، فلٹر، جدول، موڈل، پرنٹ ویو اور اسٹیٹس بیجز موجود ہیں۔ [file:1]

### 3. پہلی بار انسٹالیشن
ایپ کھلنے پر یہ چیک کرتی ہے کہ ڈیٹا بیس اور ٹیبلز موجود ہیں یا نہیں۔ اگر موجود نہ ہوں تو Install Database بٹن کے ذریعے سارا ڈیٹا بیس اور تمام ضروری ٹیبلز خودکار طور پر بن جاتے ہیں۔ [file:1]

انسٹالیشن کے وقت `settings`, `suppliers`, `customers`, `models`, `purchaseorders`, `bikes`, `chequeregister`, `payments`, اور `ledger` ٹیبلز بنتی ہیں۔ ساتھ ہی بنیادی سیٹنگز، ڈیفالٹ سپلائر، چار گاہک، اور کئی بائیک ماڈلز بھی شامل کیے جاتے ہیں۔ [file:1]

### 4. ڈیفالٹ سیٹنگز
اس ایپ میں شروع سے کچھ بنیادی سیٹنگز شامل ہوتی ہیں:
- Company Name: BNI Enterprises. [file:1]
- Branch Name: Dera (Ahmed Metro). [file:1]
- Currency: Rs. [file:1]
- Tax Rate: 0.1. [file:1]
- Tax purchase price پر لگتا ہے بطور ڈیفالٹ۔ [file:1]
- تھیم dark ہوتی ہے۔ [file:1]
- Invoice پر purchase price بطور ڈیفالٹ hide ہوتی ہے۔ [file:1]
- ڈیفالٹ ایڈمن پاس ورڈ `admin123` کے مطابق محفوظ کیا گیا ہے۔ [file:1]

### 5. لاگ اِن اور سیشن
لاگ اِن صرف ایڈمن کے لیے ہے۔ یوزرنیم `admin` چیک ہوتا ہے اور پاس ورڈ محفوظ شدہ hashed password سے verify ہوتا ہے۔ [file:1]

سیکیورٹی کے لیے دو اصول موجود ہیں:
- 8 گھنٹے بعد سیشن خود ختم ہو جاتا ہے۔ [file:1]
- 40 منٹ تک غیر فعالیت ہو تو idle logout ہو جاتا ہے۔ [file:1]

### 6. مین مینیو اور ماڈیولز
سائیڈبار میں درج ذیل ماڈیولز موجود ہیں:
- Dashboard. [file:1]
- Purchase Entry. [file:1]
- Inventory Stock. [file:1]
- Sales Entry. [file:1]
- Returns. [file:1]
- Cheque Register. [file:1]
- Customer Ledger. [file:1]
- Supplier Ledger. [file:1]
- Reports. [file:1]
- Models. [file:1]
- Customers. [file:1]
- Suppliers. [file:1]
- Settings. [file:1]

اوپر والی بار میں تاریخ/وقت اور theme toggle بھی موجود ہے۔ [file:1]

### 7. ڈیش بورڈ
ڈیش بورڈ فوری کاروباری خلاصہ دیتا ہے۔ اس میں کاروبار کی موجودہ حالت ایک نظر میں نظر آتی ہے۔ [file:1]

ڈیش بورڈ میں یہ چیزیں نظر آتی ہیں:
- In Stock count. [file:1]
- Total Sold count. [file:1]
- Returned count. [file:1]
- Purchase Value. [file:1]
- Sales Value. [file:1]
- Total Tax Paid. [file:1]
- Total Profit. [file:1]
- Pending Cheques count and amount. [file:1]

اس کے علاوہ:
- Pending cheques warning banner. [file:1]
- Model-wise stock summary. [file:1]
- Recent 10 sales. [file:1]
- Recent 10 purchases. [file:1]

### 8. Purchase Entry
یہ ماڈیول سپلائر سے خریداری درج کرنے کے لیے ہے۔ ایک purchase order کے اندر ایک یا ایک سے زیادہ بائیکس شامل کی جا سکتی ہیں۔ [file:1]

Purchase order میں یہ معلومات شامل ہوتی ہیں:
- Order date. [file:1]
- Inventory date. [file:1]
- Supplier. [file:1]
- Cheque number. [file:1]
- Bank name. [file:1]
- Cheque date. [file:1]
- Cheque amount. [file:1]
- Notes. [file:1]

ہر بائیک row میں یہ معلومات آتی ہیں:
- Chassis number. [file:1]
- Motor number. [file:1]
- Model. [file:1]
- Color. [file:1]
- Purchase price. [file:1]
- Safeguard notes. [file:1]
- Accessories. [file:1]
- Notes. [file:1]

Purchase save ہونے پر:
- Purchase order بنتا ہے۔ [file:1]
- ہر بائیک inventory میں `instock` status کے ساتھ شامل ہوتی ہے۔ [file:1]
- Tax calculate ہوتا ہے۔ [file:1]
- اگر cheque معلومات دی گئی ہوں تو cheque register میں `payment` type اور `pending` status کے ساتھ اندراج بنتا ہے۔ [file:1]

اسی صفحے سے نیا supplier اور نیا model بھی modal کے ذریعے شامل کیا جا سکتا ہے۔ [file:1]

### 9. Inventory Stock
یہ پورے اسٹاک کا مرکزی صفحہ ہے۔ تمام بائیکس کی listing، filter، view, edit, delete, bulk delete اور export کی سہولت یہاں موجود ہے۔ [file:1]

Filters میں شامل ہیں:
- Search by chassis, motor, model, color. [file:1]
- Status filter. [file:1]
- Model filter. [file:1]
- Color filter. [file:1]
- Inventory date range. [file:1]

اس صفحے پر درج ذیل کالم دکھائے جاتے ہیں:
- Serial number. [file:1]
- Chassis. [file:1]
- Motor. [file:1]
- Model. [file:1]
- Color. [file:1]
- Purchase price. [file:1]
- Status. [file:1]
- Selling price. [file:1]
- Selling date. [file:1]
- Margin. [file:1]
- Actions. [file:1]

یہاں سے آپ:
- مکمل تفصیل دیکھ سکتے ہیں۔ [file:1]
- رنگ، purchase price، status، notes edit کر سکتے ہیں۔ [file:1]
- ایک بائیک delete کر سکتے ہیں۔ [file:1]
- کئی بائیکس bulk delete کر سکتے ہیں۔ [file:1]
- منتخب بائیکس CSV میں export کر سکتے ہیں۔ [file:1]
- پوری inventory CSV میں export کر سکتے ہیں۔ [file:1]

Bike detail view میں بائیک کی مکمل life history بھی دیکھی جا سکتی ہے، جیسے inventory میں کب آئی، کب sold ہوئی، اور کب returned ہوئی۔ [file:1]

### 10. Sales Entry
Sales Entry کے ذریعے inventory میں موجود بائیک کو sold کیا جاتا ہے۔ [file:1]

Sales form میں یہ چیزیں ہوتی ہیں:
- In-stock bike selection. [file:1]
- Selling date. [file:1]
- Selling price. [file:1]
- Customer. [file:1]
- Payment method: cash, cheque, bank transfer, online. [file:1]
- Cheque details اگر payment cheque ہو۔ [file:1]
- Accessories given. [file:1]
- Notes. [file:1]

یہ صفحہ خودکار طور پر یہ حساب دکھاتا ہے:
- Purchase price. [file:1]
- Tax amount. [file:1]
- Margin/profit. [file:1]

Sale save ہونے پر:
- بائیک `sold` ہو جاتی ہے۔ [file:1]
- Selling price, date, customer, tax, margin, accessories اور notes محفوظ ہوتے ہیں۔ [file:1]
- Payment record بنتا ہے۔ [file:1]
- اگر payment cheque ہو تو cheque register میں `receipt` type کی entry بنتی ہے۔ [file:1]
- Customer ledger میں credit entry بنتی ہے۔ [file:1]
- Invoice فوری print کی جا سکتی ہے۔ [file:1]

Walk-in customer کا آپشن بھی موجود ہے۔ [file:1]

### 11. Sale Invoice
Sale کے بعد printable invoice بنتی ہے۔ [file:1]

Invoice میں شامل ہوتا ہے:
- Company name. [file:1]
- Branch name. [file:1]
- Invoice number. [file:1]
- Date. [file:1]
- Customer name, phone, CNIC. [file:1]
- Bike details. [file:1]
- Purchase price, selling price, tax amount. [file:1]

Settings کے مطابق purchase price کو invoice پر show یا hide کیا جا سکتا ہے۔ [file:1]

### 12. Returns
Returns ماڈیول sold بائیک کی واپسی کے لیے استعمال ہوتا ہے۔ [file:1]

اس میں شامل ہوتا ہے:
- Sold bike selection. [file:1]
- Return date. [file:1]
- Return amount. [file:1]
- Refund method: cash یا cheque. [file:1]
- اگر cheque ہو تو cheque details. [file:1]
- Return notes. [file:1]

Return process ہونے پر:
- بائیک `returned` status میں چلی جاتی ہے۔ [file:1]
- Return amount, date, notes محفوظ ہوتے ہیں۔ [file:1]
- اگر refund cheque سے ہو تو cheque register میں `refund` type کی entry بنتی ہے۔ [file:1]
- Customer ledger میں debit entry بنتی ہے۔ [file:1]

### 13. Cheque Register
Cheque Register تمام cheques کو ایک جگہ دکھاتا ہے۔ [file:1]

یہ cheques تین طرح کے ہو سکتے ہیں:
- Purchase payment cheques. [file:1]
- Sale receipt cheques. [file:1]
- Return refund cheques. [file:1]

ہر cheque کے لیے یہ معلومات محفوظ ہوتی ہیں:
- Cheque number. [file:1]
- Bank name. [file:1]
- Date. [file:1]
- Amount. [file:1]
- Type. [file:1]
- Status. [file:1]
- Party name. [file:1]
- Reference type اور reference ID. [file:1]
- Notes. [file:1]

Filter بھی موجود ہیں، اور actions میں clear، bounce، اور delete شامل ہیں۔ [file:1]

### 14. Customer Ledger
یہ ماڈیول منتخب customer کی مالی history دکھاتا ہے۔ [file:1]

یہاں یہ چیزیں نظر آتی ہیں:
- Date. [file:1]
- Description. [file:1]
- Debit. [file:1]
- Credit. [file:1]
- Running balance. [file:1]

Sale کی صورت میں credit entry آتی ہے، جبکہ return کی صورت میں debit entry بنتی ہے۔ Customer کی basic information بھی ساتھ دکھتی ہے، اور ledger print بھی ہو سکتا ہے۔ [file:1]

### 15. Supplier Ledger
یہ ماڈیول منتخب supplier کی خریداری history دکھاتا ہے۔ [file:1]

اس میں:
- Order date. [file:1]
- Cheque. [file:1]
- Bank. [file:1]
- Cheque date. [file:1]
- Units. [file:1]
- Cheque amount. [file:1]
- Bikes total. [file:1]
- Running balance. [file:1]

یہ supplier reconciliation کے لیے بہت مفید ہے۔ [file:1]

### 16. Reports
Reports ماڈیول میں کئی analytical رپورٹس موجود ہیں:
- Current Stock. [file:1]
- Sold Bikes. [file:1]
- Model-wise. [file:1]
- Tax Report. [file:1]
- Profit Margin. [file:1]
- Bank Cheque. [file:1]
- Monthly Summary. [file:1]
- Daily Ledger. [file:1]
- Purchase vs Sales. [file:1]

ان رپورٹس کے ذریعے stock, sales, tax, profit, cheque performance, monthly trend, daily activity، اور model-wise performance دیکھی جا سکتی ہے۔ [file:1]

### 17. Models
Models page تمام بائیک ماڈلز manage کرتی ہے۔ [file:1]

ہر model میں:
- Model code. [file:1]
- Model name. [file:1]
- Category. [file:1]
- Short code. [file:1]

یہاں add, edit, delete کے ساتھ inventory count, in stock count, اور sold count بھی دیکھے جا سکتے ہیں۔ [file:1]

### 18. Customers
Customers page پر گاہک manage ہوتے ہیں۔ [file:1]

Fields:
- Name. [file:1]
- Phone. [file:1]
- CNIC. [file:1]
- Address. [file:1]

Features:
- Add customer. [file:1]
- Edit customer. [file:1]
- Delete customer. [file:1]
- Purchase count and total purchase amount دیکھنا۔ [file:1]

### 19. Suppliers
Suppliers page پر supplier records manage ہوتے ہیں۔ [file:1]

Fields:
- Name. [file:1]
- Contact. [file:1]
- Address. [file:1]

Features:
- Add supplier. [file:1]
- Edit supplier. [file:1]
- Delete supplier. [file:1]

### 20. Settings
Settings page سے پوری ایپ کی بنیادی configuration کنٹرول ہوتی ہے۔ [file:1]

اس میں شامل ہیں:
- Company name. [file:1]
- Branch name. [file:1]
- Currency symbol. [file:1]
- Tax rate. [file:1]
- Tax purchase price یا selling price پر لگانا۔ [file:1]
- Invoice پر purchase price دکھانی ہے یا نہیں۔ [file:1]
- Admin password change. [file:1]

اسی صفحے سے:
- SQL backup download کیا جا سکتا ہے۔ [file:1]
- SQL backup restore کیا جا سکتا ہے۔ [file:1]

### 21. Export اور Print Features
ایپ میں یہ اہم output features بھی موجود ہیں:
- Inventory CSV export. [file:1]
- Selected bikes bulk CSV export. [file:1]
- Printable bike details. [file:1]
- Printable sale invoice. [file:1]
- Printable customer ledger. [file:1]
- SQL backup export. [file:1]
- SQL restore. [file:1]

### 22. ایک کام کا دوسرے ماڈیول پر اثر
یہ ایپ اس طرح بنی ہے کہ ایک جگہ درج کی گئی معلومات کئی دوسرے حصوں کو بھی اپڈیٹ کرتی ہیں۔ [file:1]

اگر purchase save ہو:
- Inventory update ہوتی ہے۔ [file:1]
- Dashboard update ہوتا ہے۔ [file:1]
- Reports update ہوتی ہیں۔ [file:1]
- Supplier ledger متاثر ہوتا ہے۔ [file:1]
- Cheque register بھی update ہو سکتا ہے۔ [file:1]

اگر sale save ہو:
- Inventory status sold ہو جاتا ہے۔ [file:1]
- Payment record بنتا ہے۔ [file:1]
- Customer ledger update ہوتی ہے۔ [file:1]
- Cheque register update ہو سکتا ہے۔ [file:1]
- Invoice generate ہو سکتی ہے۔ [file:1]
- Dashboard اور reports دونوں update ہوتے ہیں۔ [file:1]

اگر return process ہو:
- Inventory status returned ہو جاتا ہے۔ [file:1]
- Customer ledger debit ہو جاتی ہے۔ [file:1]
- Cheque register میں refund entry آ سکتی ہے۔ [file:1]
- Reports اور dashboard میں return ظاہر ہوتی ہے۔ [file:1]

### 23. استعمال کا مکمل طریقہ
#### پہلی بار
1. `index.php` سرور پر رکھیں۔ [file:1]
2. اگر ضرورت ہو تو database credentials تبدیل کریں۔ [file:1]
3. براؤزر میں ایپ کھولیں۔ [file:1]
4. Install Database پر کلک کریں۔ [file:1]
5. `admin` سے login کریں۔ ڈیفالٹ password `admin123` کے مطابق محفوظ ہے۔ [file:1]

#### روزانہ خریداری
1. Purchase Entry کھولیں۔ [file:1]
2. Supplier اور order details درج کریں۔ [file:1]
3. ایک یا زیادہ بائیکس add کریں۔ [file:1]
4. Chassis، model، color، price اور notes درج کریں۔ [file:1]
5. Save کریں۔ [file:1]
6. Inventory میں جا کر تصدیق کریں۔ [file:1]

#### روزانہ فروخت
1. Sales Entry کھولیں۔ [file:1]
2. In-stock bike منتخب کریں۔ [file:1]
3. Price اور customer معلومات درج کریں۔ [file:1]
4. Payment method منتخب کریں۔ [file:1]
5. اگر cheque ہو تو cheque details درج کریں۔ [file:1]
6. Sale record کریں۔ [file:1]
7. Invoice print کریں۔ [file:1]

#### ریٹرن
1. Returns page کھولیں۔ [file:1]
2. Sold bike منتخب کریں۔ [file:1]
3. Return date اور amount درج کریں۔ [file:1]
4. Refund method منتخب کریں۔ [file:1]
5. اگر cheque ہو تو cheque details درج کریں۔ [file:1]
6. Process Return کریں۔ [file:1]

#### روزانہ نگرانی
1. Dashboard دیکھیں۔ [file:1]
2. Inventory سے stock check کریں۔ [file:1]
3. Cheque Register سے pending cheques دیکھیں۔ [file:1]
4. Ledgers سے customer اور supplier history دیکھیں۔ [file:1]
5. Reports سے business analysis کریں۔ [file:1]
6. Settings سے backup لیں اور password update کریں۔ [file:1]
