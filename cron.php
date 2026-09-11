<?php
/**
 * ============================================================================
 *  BNI Enterprises — Daily Database Backup Cron Script
 * ============================================================================
 *
 *  WHAT THIS FILE DOES
 *  -------------------
 *  1. Connects to the live MySQL database.
 *  2. Exports a COMPLETE dump (schema + data of every table).
 *  3. Compresses it with gzip into the "thedbbackups" folder.
 *  4. Keeps ONLY the newest 20 backups — as soon as the 21st backup is
 *     created, the oldest one is deleted automatically.
 *  5. OPTIONALLY emails the backup to you as an attachment (set
 *     $EMAIL_BACKUP = true below).
 *
 *  SCHEDULE — RUN ONCE PER DAY
 *  ----------------------------
 *  This file is designed to be triggered by a cron job every 24 hours.
 *  It is NOT meant to be opened directly in a browser. See the setup
 *  instructions in the comments at the very bottom of this file.
 *
 *  HOW THIS FILE AND THE BACKUPS ARE SECURED
 *  -----------------------------------------
 *  - The "thedbbackups" folder is sealed with a .htaccess that denies ALL
 *    web access, plus a blank index.html. Backups can never be listed or
 *    downloaded from the browser.
 *  - When triggered over HTTP, a secret "key" must match, otherwise the
 *    request is rejected with a 403 Forbidden.
 *  - PHP error output is disabled, so no server paths or DB credentials can
 *    ever leak through an error message.
 *  - The backup filename is a fixed pattern (no user input), so it cannot be
 *    used to write or read arbitrary files.
 * ============================================================================
 */

/* ===========================================================================
 * CONFIGURATION — EDIT THESE VALUES FOR YOUR LIVE SERVER
 * =========================================================================*/

// Live database credentials (production).
$DB_HOST = 'localhost:3306';
$DB_USER = 'gobuykar_yasin';
$DB_PASS = 'yasin@1234';
$DB_NAME = 'gobuykar_bni';

// Folder (relative to THIS file) where backups are stored.
$BACKUP_DIR = __DIR__ . '/thedbbackups';

// Maximum number of backups to keep. Oldest is removed beyond this number.
$MAX_BACKUPS = 20;

// Secret key required when this script is triggered over HTTP.
// CHANGE THIS to a long random value (e.g. 40+ random letters/numbers).
// You only need it if you run the cron job via URL (see instructions below).
$SECRET_KEY = 'CHANGE_ME_TO_A_LONG_RANDOM_SECRET_STRING';

// ---------------------------------------------------------------------------
// EMAIL BACKUP (OPTIONAL)
// ---------------------------------------------------------------------------
// Set $EMAIL_BACKUP to true to also send the freshly-created backup file to
// your inbox as an email attachment after every run. Leave false to disable.
// See the "EMAIL SETUP INSTRUCTIONS" in the comments at the bottom.
$EMAIL_BACKUP = false;

// The address the backup should be sent TO.
$EMAIL_TO = 'you@example.com';

// The "From" address. On most shared hosts this must be a valid address on
// your own domain (e.g. backup@yourdomain.com) or the mail may be rejected.
$EMAIL_FROM = 'backup@yourdomain.com';

/* ===========================================================================
 * Nothing below this line normally needs to be changed.
 * =========================================================================*/

// Never reveal errors to the browser (prevents leaking DB paths/credentials).
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// Basic hardening headers (only if headers have not already been sent).
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
}

$IS_CLI = (PHP_SAPI === 'cli');

/**
 * Stop execution, logging the reason without leaking internals.
 */
function cron_fail($message)
{
    global $IS_CLI;
    error_log('[cron.php] ' . $message);
    if ($IS_CLI) {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
    http_response_code(500);
    exit('Backup failed. Please check the server error log.');
}

/**
 * Create and lock down the backups folder.
 * - .htaccess denies all web access (Apache 2.4 and 2.2 syntax).
 * - A blank index.html is an extra layer in case .htaccess is ignored.
 */
function ensure_backup_folder($dir)
{
    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0700, true)) {
            cron_fail('Unable to create backup folder.');
        }
    }

    $htaccess_rules = "Options -Indexes\n"
        . "<IfModule mod_authz_core.c>\n"
        . "    Require all denied\n"
        . "</IfModule>\n"
        . "<IfModule !mod_authz_core.c>\n"
        . "    Order allow,deny\n"
        . "    Deny from all\n"
        . "</IfModule>\n";

    $htaccess = $dir . '/.htaccess';
    if (!is_file($htaccess) || @file_get_contents($htaccess) !== $htaccess_rules) {
        @file_put_contents($htaccess, $htaccess_rules, LOCK_EX);
    }

    $index = $dir . '/index.html';
    if (!is_file($index)) {
        @file_put_contents($index, "<!-- Access denied -->\n", LOCK_EX);
    }

    @chmod($dir, 0700);
}

/**
 * Email a file as an attachment using PHP's built-in mail() function.
 * No external library (PHPMailer etc.) is required — works on shared hosting.
 *
 * Returns true on success, false on failure.
 */
function send_backup_email($file_path, $filename, $to, $from, $subject)
{
    if (!is_file($file_path)) {
        return false;
    }
    $content = @file_get_contents($file_path);
    if ($content === false) {
        return false;
    }

    // Strip CR/LF from addresses/subject to prevent email header injection.
    $to      = str_replace(["\r", "\n"], '', trim((string) $to));
    $from    = str_replace(["\r", "\n"], '', trim((string) $from));
    $subject = str_replace(["\r", "\n"], '', (string) $subject);
    $filename = basename(str_replace(["\r", "\n"], '', (string) $filename));

    if ($to === '' || $from === '' || $filename === '') {
        return false;
    }

    $encoded  = chunk_split(base64_encode($content), 76, "\r\n");
    $boundary = 'bni_' . md5(uniqid((string) mt_rand(), true));

    // Sensible MIME type based on the file extension.
    $mime = (substr($filename, -3) === '.gz') ? 'application/gzip' : 'application/sql';

    $headers = 'From: ' . $from . "\r\n"
        . 'Reply-To: ' . $from . "\r\n"
        . 'MIME-Version: 1.0' . "\r\n"
        . 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

    $body = '--' . $boundary . "\r\n"
        . 'Content-Type: text/plain; charset=UTF-8' . "\r\n"
        . 'Content-Transfer-Encoding: 8bit' . "\r\n\r\n"
        . "Attached is the latest database backup.\n\n"
        . '--' . $boundary . "\r\n"
        . 'Content-Type: ' . $mime . '; name="' . $filename . '"' . "\r\n"
        . 'Content-Transfer-Encoding: base64' . "\r\n"
        . 'Content-Disposition: attachment; filename="' . $filename . '"' . "\r\n\r\n"
        . $encoded
        . '--' . $boundary . "--\r\n";

    return @mail($to, $subject, $body, $headers);
}

/* ------------------------- Access control ------------------------------ */

// HTTP access requires the correct secret key. CLI (cron) access is trusted.
if (!$IS_CLI) {
    $provided = isset($_GET['key']) ? (string) $_GET['key'] : '';
    if (!hash_equals($SECRET_KEY, $provided)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

/* ----------------------- Connect to the database ----------------------- */

$conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    cron_fail('Database connection failed.');
}
$conn->set_charset('utf8mb4');

/* -------------------- Prepare folder and output file ------------------- */

ensure_backup_folder($BACKUP_DIR);

$stamp = date('Ymd_His');
$use_gzip = function_exists('gzopen');

if ($use_gzip) {
    $final_file = $BACKUP_DIR . '/bni_backup_' . $stamp . '.sql.gz';
    $tmp_file = $final_file . '.tmp';
    $out = gzopen($tmp_file, 'wb9');
} else {
    $final_file = $BACKUP_DIR . '/bni_backup_' . $stamp . '.sql';
    $tmp_file = $final_file . '.tmp';
    $out = fopen($tmp_file, 'wb');
}
if (!$out) {
    $conn->close();
    cron_fail('Unable to open backup file for writing.');
}

// Small helper to write a line to the (possibly gzip) output stream.
$write = function ($str) use ($use_gzip, $out) {
    if ($use_gzip) {
        gzwrite($out, $str);
    } else {
        fwrite($out, $str);
    }
};

/* ------------------------- Generate the dump --------------------------- */

$write("-- BNI Enterprises Database Backup\n");
$write("-- Database: $DB_NAME\n");
$write("-- Generated: " . date('Y-m-d H:i:s') . "\n\n");
$write("SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
$write("SET FOREIGN_KEY_CHECKS=0;\n");
$write("START TRANSACTION;\n\n");

$tables = [];
$res = $conn->query('SHOW TABLES');
if (!$res) {
    if ($use_gzip) gzclose($out); else fclose($out);
    @unlink($tmp_file);
    $conn->close();
    cron_fail('Unable to read database table list.');
}
while ($row = $res->fetch_row()) {
    if (!empty($row[0])) {
        $tables[] = $row[0];
    }
}

foreach ($tables as $table) {
    // Escape backticks in the table name for safety inside identifiers.
    $safe = str_replace('`', '``', $table);

    $cr = $conn->query("SHOW CREATE TABLE `$safe`");
    if (!$cr) {
        if ($use_gzip) gzclose($out); else fclose($out);
        @unlink($tmp_file);
        $conn->close();
        cron_fail('Unable to read schema for table: ' . $table);
    }
    $create_row = $cr->fetch_row();
    $create_sql = $create_row[1] ?? '';
    if ($create_sql === '') {
        if ($use_gzip) gzclose($out); else fclose($out);
        @unlink($tmp_file);
        $conn->close();
        cron_fail('Schema export failed for table: ' . $table);
    }

    $write("-- --------------------------------------------\n");
    $write("-- Table: `$safe`\n");
    $write("-- --------------------------------------------\n");
    $write("DROP TABLE IF EXISTS `$safe`;\n");
    $write($create_sql . ";\n\n");

    $rows = $conn->query("SELECT * FROM `$safe`");
    if ($rows && $rows->num_rows > 0) {
        while ($row = $rows->fetch_assoc()) {
            $vals = [];
            foreach (array_values($row) as $v) {
                $vals[] = ($v === null)
                    ? 'NULL'
                    : "'" . mysqli_real_escape_string($conn, (string) $v) . "'";
            }
            $cols = '`' . implode('`,`', array_map(function ($c) {
                return str_replace('`', '``', $c);
            }, array_keys($row))) . '`';

            $write("INSERT INTO `$safe` ($cols) VALUES (" . implode(',', $vals) . ");\n");
        }
        $write("\n");
    }
}

$write("COMMIT;\n");
$write("SET FOREIGN_KEY_CHECKS=1;\n");

if ($use_gzip) {
    gzclose($out);
} else {
    fclose($out);
}

// Atomically move the temp file into place (avoids half-written backups).
if (!@rename($tmp_file, $final_file)) {
    @unlink($tmp_file);
    $conn->close();
    cron_fail('Unable to finalize backup file.');
}

/* ------------- Prune old backups (keep newest MAX_BACKUPS) -------------- */

$files = glob($BACKUP_DIR . '/bni_backup_*.sql*');
if (is_array($files)) {
    // Ignore any leftover temporary files.
    $files = array_values(array_filter($files, function ($f) {
        return substr($f, -4) !== '.tmp';
    }));
    // Timestamped names sort chronologically (oldest first).
    sort($files, SORT_STRING);
    while (count($files) > $MAX_BACKUPS) {
        $oldest = array_shift($files);
        @unlink($oldest);
    }
}

$conn->close();

/* ------------------- Email the backup (if enabled) ---------------------- */

$email_status = '';
if ($EMAIL_BACKUP) {
    $email_subject = 'Database Backup — ' . $DB_NAME . ' (' . date('Y-m-d') . ')';
    if (send_backup_email($final_file, basename($final_file), $EMAIL_TO, $EMAIL_FROM, $email_subject)) {
        $email_status = 'Backup emailed to ' . $EMAIL_TO;
    } else {
        $email_status = 'Email backup FAILED';
        error_log('[cron.php] Email backup failed.');
    }
}

/* ------------------------------ Report --------------------------------- */

$remaining = glob($BACKUP_DIR . '/bni_backup_*.sql*');
$count = is_array($remaining)
    ? count(array_filter($remaining, function ($f) { return substr($f, -4) !== '.tmp'; }))
    : 0;

if ($IS_CLI) {
    echo "Backup OK: " . basename($final_file) . " (" . $count . " backup(s) kept).\n";
    if ($email_status !== '') {
        echo $email_status . "\n";
    }
} else {
    http_response_code(200);
    echo 'OK';
}

/* ===========================================================================
 * SETUP INSTRUCTIONS — HOSTERPK / cPanel CRON JOB
 * ===========================================================================
 *
 *  1. Upload cron.php to your server (same folder as index.php, e.g.
 *     public_html/inventory/cron.php). The "thedbbackups" folder is created
 *     automatically on first run and is locked down automatically.
 *
 *  2. In cPanel open "Cron Jobs" (under the Advanced section).
 *
 *  3. Choose "Once Per Day" (or set a custom time, e.g. Minute: 30, Hour: 3).
 *
 *  4. Enter ONE of the following commands.
 *
 *     OPTION A — Recommended (runs PHP directly, no key needed):
 *        php -q /home/CPANEL_USER/public_html/inventory/cron.php
 *        (Replace CPANEL_USER with your cPanel username and fix the folder
 *         path to match where you uploaded the file.)
 *
 *     OPTION B — Via URL (useful if PHP CLI is not available). You MUST first
 *     set $SECRET_KEY above to a long random value, then use:
 *        wget -q -O /dev/null "https://yourdomain.com/inventory/cron.php?key=YOUR_SECRET_KEY"
 *
 *  5. Save the cron job. It will now run every day, keep the 20 newest
 *     backups in "thedbbackups", and delete the oldest one each time.
 *
 *  EMAIL SETUP INSTRUCTIONS
 *  -------------------------
 *  1. Set $EMAIL_BACKUP = true (near the top of this file).
 *  2. Set $EMAIL_TO to the address that should receive the backup.
 *  3. Set $EMAIL_FROM to a valid address on YOUR domain, e.g.
 *     backup@yourdomain.com. On most shared hosts, using a foreign "From"
 *     (like Gmail) will cause the email to be rejected or marked as spam.
 *     For best results, create a real mailbox (e.g. "backup") in cPanel →
 *     Email Accounts, and use that address here.
 *  4. Save and run once (or wait for the next cron run). You should receive
 *     the .sql.gz backup as an attachment. If it does not arrive:
 *       - Check the server error log for "[cron.php] Email backup failed."
 *       - Confirm PHP's mail() works on your account (test with a simple
 *         mail() script), or switch to your host's SMTP via a library.
 *
 *  NOTE ON SIZE: The backup is emailed as a base64 attachment, which makes
 *  it about 33% larger than the .sql.gz file on disk. For very large
 *  databases it may be better to rely on the server copy only, or attach an
 *  off-site copy via FTP instead of email.
 *
 *  IMPORTANT SECURITY NOTES
 *  -------------------------
 *  - Always change $SECRET_KEY if you use Option B.
 *  - Never place backups inside a publicly writable folder; this script uses
 *    the "thedbbackups" folder and seals it with .htaccess automatically.
 *  - Periodically download a copy of your backups to your own computer as an
 *    off-site copy (the server copy alone is not a complete backup strategy).
 * =========================================================================*/
