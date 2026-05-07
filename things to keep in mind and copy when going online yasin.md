


Here is the exact checklist of files and folders you need to copy from your local computer to the `public_html` (or `htdocs`/root) folder on your live online server.

### 1. Core Files (Must Upload)
*   **`index.php`** (This is your entire application)
*   **`.htaccess`** (The security file we just created. *Note: Ensure your FTP/File Manager is set to "Show Hidden Files" so you don't miss this one.*)

### 2. Assets & Scripts (Must Upload)
*   **`logo.png`** (Your sidebar and invoice logo)
*   **`chart.js`** (The local script file powering your dashboard charts)
*   **Favicon files:**
    *   `favicon.ico`
    *   `favicon.svg`
    *   `favicon-96x96.png`
    *   `apple-touch-icon.png`

### 3. Directories
*   **`uploads/`** 
    *   *If you want to keep your local bike/model images:* Upload the folder and all its contents.
    *   *If you are starting fresh:* Create an empty folder named `uploads` on the live server.
    *   **Crucial Step:** Once uploaded, right-click the `uploads` folder in your hosting File Manager and set its **permissions to `755`** (Read & Execute for public, Write for owner). This allows PHP to save new images but stops hackers from modifying the folder.

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