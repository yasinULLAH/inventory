

Here is the exact checklist of files and folders you need to copy from your local computer to the `public_html` (or `htdocs`/root) folder on your live online server.

### 1. Core Files (Must Upload)
*   **`index.php`** (This is your entire application)
*   **`landing.php`** (Your public-facing company website)
*   **`serve_img.php`** (Secure image proxy)
*   **`.htaccess`** (The security file. *Note: Ensure your FTP/File Manager is set to "Show Hidden Files" so you don't miss this one.*)

### 2. Assets & Scripts (Must Upload)
*   **`logo.png`** (Your sidebar and invoice logo)
*   **Favicon files:**
    *   `favicon.ico`
    *   `favicon.svg`
    *   `favicon-96x96.png`
    *   `apple-touch-icon.png`
    *   `web-app-manifest-192x192.png`
    *   `web-app-manifest-512x512.png`
*   **`site.webmanifest`** (PWA manifest)

### 3. Directories
*   **`uploads/`** 
    *   *If you want to keep your local bike/model images:* Upload the folder and all its contents.
    *   *If you are starting fresh:* Create an empty folder named `uploads` on the live server.
    *   **Crucial Step:** Once uploaded, right-click the `uploads` folder in your hosting File Manager and set its **permissions to `755`**.
*   **`receipts/`** (For bank deposit receipt images)
    *   Create this folder and set permissions to `755`.

### 4. Sound Files (Optional)
*   `woosh.wav`, `woosh2.wav`, `woosh3.wav` — UI sound effects.

---

### ⚠️ IMPORTANT: The Database is NOT a file!
You cannot just drag and drop the database. To move your database to the live server:

1. **Export Local DB:** Go to your local phpMyAdmin, select `bni_enterprises2`, and click **Export** -> Download as `.sql`.
2. **Create Live DB:** Go to your live server control panel (cPanel/Hostinger/etc.), create a new MySQL Database, a new MySQL User, and link them together with "All Privileges".
3. **Import Live DB:** Open phpMyAdmin on your live server, select the new database, and click **Import** -> Upload your `.sql` file.
4. **Update `index.php`:** Open `index.php` on the live server and update lines 3-6 with your new live database credentials:
   ```php
   $db_host = 'localhost'; // Usually remains localhost
   $db_user = 'your_live_db_username';
   $db_pass = 'your_live_db_password';
   $db_name = 'your_live_database_name';
   ```

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
