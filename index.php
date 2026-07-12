<?php
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_samesite', 'Strict');
$request_is_https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? null) == 443)
    || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
$request_host = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')));
$is_local_host = in_array($request_host, ['localhost', '127.0.0.1', '::1', ''], true) || str_ends_with($request_host, '.local');
if (!$request_is_https && !$is_local_host && preg_match('/^[a-z0-9.-]+$/', $request_host)) {
    header('Location: https://' . $request_host . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}
if ($request_is_https) {
    ini_set('session.cookie_secure', '1');
    header('Strict-Transport-Security: max-age=300');
}
session_start();
header('X-Frame-Options: DENY');
header("Content-Security-Policy: frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'root';
$db_name = 'bni_enterprises2';
$app_version = '2.0.0';
$author = 'Yasin Ullah';
$_SESSION['captcha_lifetime'] = $_SESSION['captcha_lifetime'] ?? time() + 300;
if (time() > $_SESSION['captcha_lifetime']) {
    unset($_SESSION['captcha_code']);
    $_SESSION['captcha_lifetime'] = time() + 300;
}
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $params = [];
        if (!empty($_GET['page'])) {
            $params[] = 'page=' . urlencode($_GET['page']);
            if (!empty($_GET['edit_id'])) {
                $params[] = 'edit_id=' . urlencode($_GET['edit_id']);
            }
        }
        $params[] = 'err=' . urlencode('Security token missing or expired. Please try again.');
        header('Location: index.php' . (!empty($params) ? '?' . implode('&', $params) : ''));
        exit;
    }
}
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT & ~E_USER_DEPRECATED);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

function get_client_ip()
{
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

$ip_address = get_client_ip();
$ban_file = sys_get_temp_dir() . '/bni_bans.json';
$bans = file_exists($ban_file) ? json_decode(file_get_contents($ban_file), true) : [];
if (isset($bans[$ip_address]['ban_until']) && $bans[$ip_address]['ban_until'] > time()) {
    $ban_until_time = date('d/m/Y H:i:s', $bans[$ip_address]['ban_until']);
    die('<div style="padding:40px;text-align:center;font-family:sans-serif"><h2>🚫 Access Denied</h2><p>Too many failed login attempts. Your IP has been temporarily banned until ' . $ban_until_time . '.</p></div>');
}

function record_failed_attempt()
{
    global $ip_address, $ban_file, $bans;
    $handle = fopen($ban_file, 'c+');
    if (!$handle || !flock($handle, LOCK_EX)) {
        if ($handle) fclose($handle);
        return;
    }
    rewind($handle);
    $stored = json_decode(stream_get_contents($handle) ?: '[]', true);
    $bans = is_array($stored) ? $stored : [];
    $bans[$ip_address]['count'] = ($bans[$ip_address]['count'] ?? 0) + 1;
    $bans[$ip_address]['updated_at'] = time();
    if ($bans[$ip_address]['count'] >= 7) {
        $bans[$ip_address]['ban_until'] = time() + (3 * 3600);
        $bans[$ip_address]['count'] = 0;
    }
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($bans));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
}

function reset_attempts()
{
    global $ip_address, $ban_file, $bans;
    $handle = fopen($ban_file, 'c+');
    if (!$handle || !flock($handle, LOCK_EX)) {
        if ($handle) fclose($handle);
        return;
    }
    rewind($handle);
    $stored = json_decode(stream_get_contents($handle) ?: '[]', true);
    $bans = is_array($stored) ? $stored : [];
    unset($bans[$ip_address]);
    ftruncate($handle, 0);
    rewind($handle);
    fwrite($handle, json_encode($bans));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
}

function db_connect($create_db = false)
{
    global $db_host, $db_user, $db_pass, $db_name;
    if ($create_db) {
        $conn = new mysqli($db_host, $db_user, $db_pass);
    } else {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    }
    if ($conn->connect_error) {
        error_log('DB Connection Error: ' . $conn->connect_error);
        return null;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function ensure_secure_backup_storage()
{
    $configured_dir = trim((string) getenv('BNI_BACKUP_DIR'));
    $document_root = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: dirname(__DIR__);
    $root_dir = $configured_dir !== '' ? $configured_dir : dirname($document_root) . DIRECTORY_SEPARATOR . 'inventory_secure_storage';
    $backup_dir = $root_dir . DIRECTORY_SEPARATOR . 'auto_backups';
    $deny_rules = "Options -Indexes\n<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n</IfModule>\n";
    foreach ([$root_dir, $backup_dir] as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new Exception('Unable to create secure backup directory.');
        }
        $htaccess_path = $dir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($htaccess_path) || file_get_contents($htaccess_path) !== $deny_rules) {
            if (file_put_contents($htaccess_path, $deny_rules, LOCK_EX) === false) {
                throw new Exception('Unable to secure backup directory with .htaccess rules.');
            }
        }
    }
    return [
        'root_dir' => $root_dir,
        'backup_dir' => $backup_dir,
        'state_file' => $backup_dir . DIRECTORY_SEPARATOR . 'backup_state.json',
    ];
}

function get_secure_backup_paths()
{
    $paths = ensure_secure_backup_storage();
    if (!is_file($paths['state_file']) && file_put_contents($paths['state_file'], "{}\n", LOCK_EX) === false) {
        throw new Exception('Unable to initialize backup state file.');
    }
    return $paths;
}

function get_database_table_list($conn)
{
    $tables = [];
    $res = $conn->query('SHOW TABLES');
    if (!$res) {
        throw new Exception('Unable to read database table list.');
    }
    while ($row = $res->fetch_row()) {
        if (!empty($row[0])) {
            $tables[] = $row[0];
        }
    }
    return $tables;
}

function build_full_database_dump($conn, $author)
{
    $tables = get_database_table_list($conn);
    $sql_dump = "-- BNI Enterprises Full Database Backup\n";
    $sql_dump .= '-- Generated: ' . date('Y-m-d H:i:s') . "\n";
    $sql_dump .= '-- Author: ' . $author . "\n\n";
    $sql_dump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $sql_dump .= "SET FOREIGN_KEY_CHECKS=0;\n";
    $sql_dump .= "SET AUTOCOMMIT=0;\n";
    $sql_dump .= "START TRANSACTION;\n\n";
    foreach ($tables as $table_name) {
        $safe_table = str_replace('`', '``', $table_name);
        $create_row = $conn->query("SHOW CREATE TABLE `$safe_table`");
        if (!$create_row) {
            throw new Exception('Unable to read schema for table: ' . $table_name);
        }
        $create_data = $create_row->fetch_row();
        $create_sql = $create_data[1] ?? '';
        if ($create_sql === '') {
            throw new Exception('Schema export failed for table: ' . $table_name);
        }
        $sql_dump .= "-- --------------------------------------------\n";
        $sql_dump .= "-- Table: `$safe_table`\n";
        $sql_dump .= "-- --------------------------------------------\n";
        $sql_dump .= "DROP TABLE IF EXISTS `$safe_table`;\n";
        $sql_dump .= $create_sql . ";\n\n";
        $rows = $conn->query("SELECT * FROM `$safe_table`");
        if ($rows && $rows->num_rows > 0) {
            while ($row = $rows->fetch_assoc()) {
                $vals = array_map(function ($v) use ($conn) {
                    return $v === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, (string) $v) . "'";
                }, array_values($row));
                $cols = '`' . implode('`,`', array_keys($row)) . '`';
                $sql_dump .= "INSERT INTO `$safe_table` ($cols) VALUES (" . implode(',', $vals) . ");\n";
            }
            $sql_dump .= "\n";
        }
    }
    $sql_dump .= "COMMIT;\n";
    $sql_dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $sql_dump;
}

function execute_sql_batch($conn, $sql)
{
    if (!$conn->multi_query($sql)) {
        return [false, $conn->error];
    }
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
        if ($conn->errno) {
            return [false, $conn->error];
        }
        if (!$conn->more_results()) {
            break;
        }
        if (!$conn->next_result()) {
            return [false, $conn->error ?: 'A statement in the SQL batch failed.'];
        }
    } while (true);
    return [true, null];
}

function run_auto_database_backup($conn, $author)
{
    $interval_days = 4;
    $max_backups = 20;
    $interval_seconds = $interval_days * 86400;
    $paths = get_secure_backup_paths();
    $lock_handle = fopen($paths['state_file'], 'r+');
    if (!$lock_handle) {
        throw new Exception('Unable to open backup state file.');
    }
    if (!flock($lock_handle, LOCK_EX | LOCK_NB)) {
        fclose($lock_handle);
        return false;
    }

    $now = time();
    $state = [];
    try {
        rewind($lock_handle);
        $raw_state = stream_get_contents($lock_handle);
        if (!empty($raw_state)) {
            $decoded = json_decode($raw_state, true);
            if (is_array($decoded)) {
                $state = $decoded;
            }
        }

        $last_backup_at = (int) ($state['last_backup_at'] ?? 0);
        if ($last_backup_at > 0 && ($now - $last_backup_at) < $interval_seconds) {
            $state['next_backup_due_at'] = $last_backup_at + $interval_seconds;
            $state['last_checked_at'] = $now;
            ftruncate($lock_handle, 0);
            rewind($lock_handle);
            fwrite($lock_handle, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            fflush($lock_handle);
            flock($lock_handle, LOCK_UN);
            fclose($lock_handle);
            return false;
        }

        $filename = 'bni_auto_backup_' . date('Ymd_His', $now) . '.sql';
        $tmp_path = $paths['backup_dir'] . DIRECTORY_SEPARATOR . $filename . '.tmp';
        $final_path = $paths['backup_dir'] . DIRECTORY_SEPARATOR . $filename;
        $sql_dump = build_full_database_dump($conn, $author);
        if (file_put_contents($tmp_path, $sql_dump, LOCK_EX) === false) {
            throw new Exception('Unable to write temporary backup file.');
        }
        if (!rename($tmp_path, $final_path)) {
            @unlink($tmp_path);
            throw new Exception('Unable to finalize backup file.');
        }

        $backup_files = glob($paths['backup_dir'] . DIRECTORY_SEPARATOR . 'bni_auto_backup_*.sql') ?: [];
        sort($backup_files, SORT_STRING);
        while (count($backup_files) > $max_backups) {
            $old_file = array_shift($backup_files);
            if (is_file($old_file)) {
                @unlink($old_file);
            }
        }

        $state = [
            'last_backup_at' => $now,
            'last_backup_file' => basename($final_path),
            'last_checked_at' => $now,
            'next_backup_due_at' => $now + $interval_seconds,
            'interval_days' => $interval_days,
            'max_backups' => $max_backups,
            'backup_count' => count($backup_files),
            'storage_dir' => str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $paths['backup_dir']),
            'last_error' => null,
        ];
        ftruncate($lock_handle, 0);
        rewind($lock_handle);
        fwrite($lock_handle, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fflush($lock_handle);
        flock($lock_handle, LOCK_UN);
        fclose($lock_handle);
        return true;
    } catch (Exception $e) {
        $state['last_checked_at'] = $now;
        $state['last_error'] = $e->getMessage();
        ftruncate($lock_handle, 0);
        rewind($lock_handle);
        fwrite($lock_handle, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fflush($lock_handle);
        flock($lock_handle, LOCK_UN);
        fclose($lock_handle);
        throw $e;
    }
}

function get_sale_total_for_bike($conn, $bike_id)
{
    $stmt = $conn->prepare("SELECT b.selling_price, COALESCE(SUM(sa.final_price),0) AS acc_total
        FROM bikes b
        LEFT JOIN sale_accessories sa ON sa.bike_id=b.id
        WHERE b.id=?
        GROUP BY b.id
        LIMIT 1");
    $stmt->bind_param('i', $bike_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ? ((float) $row['selling_price'] + (float) $row['acc_total']) : 0.0;
}

function sync_purchase_order_totals($conn, $purchase_order_id)
{
    if ($purchase_order_id <= 0) return;
    $stmt = $conn->prepare('UPDATE purchase_orders po SET total_units=(SELECT COUNT(*) FROM bikes WHERE purchase_order_id=po.id), total_amount=(SELECT COALESCE(SUM(purchase_price),0) FROM bikes WHERE purchase_order_id=po.id) WHERE po.id=?');
    $stmt->bind_param('i', $purchase_order_id);
    $stmt->execute();
}

function get_allocated_total_for_bike($conn, $bike_id, $exclude_allocation_id = 0)
{
    if ($exclude_allocation_id > 0) {
        $stmt = $conn->prepare('SELECT COALESCE(SUM(amount),0) AS total FROM sale_money_allocations WHERE bike_id=? AND id!=?');
        $stmt->bind_param('ii', $bike_id, $exclude_allocation_id);
    } else {
        $stmt = $conn->prepare('SELECT COALESCE(SUM(amount),0) AS total FROM sale_money_allocations WHERE bike_id=?');
        $stmt->bind_param('i', $bike_id);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (float) ($row['total'] ?? 0);
}

function assert_allocation_within_sale_total($conn, $bike_id, $amount, $exclude_allocation_id = 0)
{
    $bike_stmt = $conn->prepare("SELECT status FROM bikes WHERE id=? LIMIT 1 FOR UPDATE");
    $bike_stmt->bind_param('i', $bike_id);
    $bike_stmt->execute();
    $bike = $bike_stmt->get_result()->fetch_assoc();
    if (!$bike || $bike['status'] !== 'sold') {
        throw new Exception('Allocations can only be added to sold bikes.');
    }
    $sale_total = get_sale_total_for_bike($conn, $bike_id);
    $already_allocated = get_allocated_total_for_bike($conn, $bike_id, $exclude_allocation_id);
    if (($already_allocated + $amount) - $sale_total > 0.0001) {
        throw new Exception('Allocation exceeds the remaining sale value for this bike.');
    }
}

function lock_in_stock_bike($conn, $bike_id)
{
    $stmt = $conn->prepare("SELECT * FROM bikes WHERE id=? AND status='in_stock' LIMIT 1 FOR UPDATE");
    $stmt->bind_param('i', $bike_id);
    $stmt->execute();
    $bike = $stmt->get_result()->fetch_assoc();
    if (!$bike) {
        throw new Exception('Bike not found or already sold.');
    }
    return $bike;
}

function attach_sale_accessories($conn, $bike_id, $selected_accessories)
{
    $total_acc_price = 0.0;
    if (empty($selected_accessories)) {
        return $total_acc_price;
    }
    $sa_stmt = $conn->prepare('INSERT INTO sale_accessories (bike_id, accessory_id, quantity, unit_price, discount_amount, final_price) VALUES (?,?,?,?,?,?)');
    $stock_stmt = $conn->prepare('SELECT name, current_stock, selling_price FROM accessories WHERE id=? LIMIT 1 FOR UPDATE');
    $decrement_stmt = $conn->prepare('UPDATE accessories SET current_stock=current_stock-? WHERE id=? AND current_stock>=?');
    foreach ($selected_accessories as $data) {
        $acc_input = $data['id'] ?? '';
        $qty = (int) ($data['quantity'] ?? 0);
        $unit_price = round((float) ($data['unit_price'] ?? 0), 2);
        $discount = round((float) ($data['discount'] ?? 0), 2);
        if (empty($acc_input) || $qty <= 0) {
            continue;
        }
        if (!is_numeric($acc_input)) {
            $new_name = clean_text($acc_input, 255);
            if ($new_name === '' || $unit_price < 0) {
                throw new Exception('Custom accessory name and price are invalid.');
            }
            $dummy_sku = 'CST-' . bin2hex(random_bytes(6));
            $ins = $conn->prepare('INSERT INTO accessories (name, sku, selling_price, current_stock) VALUES (?, ?, ?, 0)');
            $ins->bind_param('ssd', $new_name, $dummy_sku, $unit_price);
            $ins->execute();
            $acc_id = $conn->insert_id;
        } else {
            $acc_id = (int) $acc_input;
            $stock_stmt->bind_param('i', $acc_id);
            $stock_stmt->execute();
            $stock_row = $stock_stmt->get_result()->fetch_assoc();
            if (!$stock_row) {
                throw new Exception('Selected accessory was not found.');
            }
            if ((int) $stock_row['current_stock'] < $qty) {
                throw new Exception('Accessory "' . $stock_row['name'] . '" does not have enough stock.');
            }
            $unit_price = round((float) $stock_row['selling_price'], 2);
            $decrement_stmt->bind_param('iii', $qty, $acc_id, $qty);
            $decrement_stmt->execute();
            if ($decrement_stmt->affected_rows !== 1) {
                throw new Exception('Accessory stock changed during sale. Please review stock and try again.');
            }
        }
        $gross_price = round($unit_price * $qty, 2);
        if ($discount < 0 || $discount > $gross_price) {
            throw new Exception('Accessory discount must be between zero and its gross selling price.');
        }
        $final_price = round($gross_price - $discount, 2);
        $sa_stmt->bind_param('iiiddd', $bike_id, $acc_id, $qty, $unit_price, $discount, $final_price);
        $sa_stmt->execute();
        $total_acc_price += $final_price;
    }
    return $total_acc_price;
}

function get_sale_accessory_cost_for_bike($conn, $bike_id)
{
    $stmt = $conn->prepare('SELECT COALESCE(SUM(sa.quantity * a.purchase_price),0) AS total FROM sale_accessories sa JOIN accessories a ON a.id=sa.accessory_id WHERE sa.bike_id=?');
    $stmt->bind_param('i', $bike_id);
    $stmt->execute();
    return (float) ($stmt->get_result()->fetch_assoc()['total'] ?? 0);
}

function get_total_linked_deposit_amount($conn, $deposit_id)
{
    $stmt = $conn->prepare('SELECT COALESCE(SUM(amount),0) AS total FROM deposit_allocations WHERE deposit_id=?');
    $stmt->bind_param('i', $deposit_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return (float) ($row['total'] ?? 0);
}

function sync_customer_payment_names($conn, $customer_id, $old_name, $new_name)
{
    if ($customer_id <= 0 || $old_name === $new_name) {
        return;
    }
    $sale_stmt = $conn->prepare("UPDATE payments p
        JOIN bikes b ON b.id=p.reference_id
        SET p.party_name=?
        WHERE p.transaction_type IN ('sale','customer_refund') AND b.customer_id=?");
    $sale_stmt->bind_param('si', $new_name, $customer_id);
    $sale_stmt->execute();

    $inst_stmt = $conn->prepare("UPDATE payments p
        JOIN installments i ON i.id=p.reference_id
        SET p.party_name=?
        WHERE p.transaction_type='installment' AND i.customer_id=?");
    $inst_stmt->bind_param('si', $new_name, $customer_id);
    $inst_stmt->execute();

    $adv_stmt = $conn->prepare("UPDATE payments
        SET party_name=?
        WHERE transaction_type IN ('customer_advance','sale') AND customer_id=?");
    $adv_stmt->bind_param('si', $new_name, $customer_id);
    $adv_stmt->execute();
}

function sync_supplier_payment_names($conn, $supplier_id, $old_name, $new_name)
{
    if ($supplier_id <= 0 || $old_name === $new_name) {
        return;
    }
    $purchase_stmt = $conn->prepare("UPDATE payments p
        JOIN purchase_orders po ON po.id=p.reference_id
        SET p.party_name=?
        WHERE p.transaction_type='supplier_payment' AND po.supplier_id=?");
    $purchase_stmt->bind_param('si', $new_name, $supplier_id);
    $purchase_stmt->execute();

    $refund_stmt = $conn->prepare("UPDATE payments p
        JOIN bikes b ON b.id=p.reference_id
        JOIN purchase_orders po ON po.id=b.purchase_order_id
        SET p.party_name=?
        WHERE p.transaction_type='supplier_refund' AND po.supplier_id=?");
    $refund_stmt->bind_param('si', $new_name, $supplier_id);
    $refund_stmt->execute();

    $standalone_stmt = $conn->prepare("UPDATE payments
        SET party_name=?
        WHERE transaction_type IN ('supplier_payment','supplier_refund') AND supplier_id=?");
    $standalone_stmt->bind_param('si', $new_name, $supplier_id);
    $standalone_stmt->execute();
}

function replace_deposit_links($conn, $deposit_id, $destination_id, $bike_links)
{
    $delete_stmt = $conn->prepare('DELETE FROM deposit_allocations WHERE deposit_id=?');
    $delete_stmt->bind_param('i', $deposit_id);
    $delete_stmt->execute();
    if (empty($bike_links)) {
        return;
    }
    $allocations_stmt = $conn->prepare("SELECT sma.id,
            sma.amount,
            COALESCE(SUM(da.amount),0) AS deposited_amount
        FROM sale_money_allocations sma
        LEFT JOIN deposit_allocations da ON da.allocation_id=sma.id AND da.deposit_id!=?
        WHERE sma.bike_id=? AND sma.destination_id=?
        GROUP BY sma.id, sma.amount
        ORDER BY sma.allocation_date ASC, sma.id ASC
        FOR UPDATE");
    $insert_stmt = $conn->prepare('INSERT INTO deposit_allocations (deposit_id, allocation_id, bike_id, amount) VALUES (?,?,?,?)');
    foreach ($bike_links as $link) {
        $bike_id = (int) ($link['bike_id'] ?? 0);
        $amount = (float) ($link['amount'] ?? 0);
        if ($bike_id <= 0 || $amount <= 0) {
            continue;
        }
        $bike_stmt = $conn->prepare("SELECT status FROM bikes WHERE id=? LIMIT 1 FOR UPDATE");
        $bike_stmt->bind_param('i', $bike_id);
        $bike_stmt->execute();
        $bike = $bike_stmt->get_result()->fetch_assoc();
        if (!$bike || $bike['status'] !== 'sold') {
            throw new Exception('Only sold bikes can be linked to a bank deposit.');
        }
        $allocations_stmt->bind_param('iii', $deposit_id, $bike_id, $destination_id);
        $allocations_stmt->execute();
        $allocations = $allocations_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if (empty($allocations)) {
            throw new Exception('A linked bike must first be allocated to the selected bank destination.');
        }
        $remaining_to_assign = $amount;
        foreach ($allocations as $allocation) {
            $allocation_remaining = (float) $allocation['amount'] - (float) $allocation['deposited_amount'];
            if ($allocation_remaining <= 0) {
                continue;
            }
            $slice = min($remaining_to_assign, $allocation_remaining);
            $allocation_id = (int) $allocation['id'];
            $insert_stmt->bind_param('iiid', $deposit_id, $allocation_id, $bike_id, $slice);
            $insert_stmt->execute();
            $remaining_to_assign -= $slice;
            if ($remaining_to_assign <= 0.0001) {
                break;
            }
        }
        if ($remaining_to_assign > 0.0001) {
            throw new Exception('Deposit link amount exceeds the remaining allocation for one of the selected bikes.');
        }
    }
}

function current_user($conn)
{
    static $user_cache = null;
    if ($user_cache !== null)
        return $user_cache;
    if (!isset($_SESSION['user_id']))
        return null;
    $stmt = $conn->prepare('SELECT u.*, r.name as role_name FROM users u LEFT JOIN roles r ON u.role_id=r.id WHERE u.id=? AND u.is_active=1 LIMIT 1');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user_cache = $stmt->get_result()->fetch_assoc();
    if (!$user_cache) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
    return $user_cache;
}

function has_permission($conn, $page, $action = 'view')
{
    static $perm_cache = [];
    $user = current_user($conn);
    if (!$user)
        return false;
    if ($user['role_name'] === 'Administrator')
        return true;
    $cache_key = $page . '_' . $action;
    if (isset($perm_cache[$cache_key]))
        return $perm_cache[$cache_key];
    $col = 'can_view';
    if ($action === 'add')
        $col = 'can_add';
    if ($action === 'edit')
        $col = 'can_edit';
    if ($action === 'delete')
        $col = 'can_delete';
    $stmt = $conn->prepare("SELECT $col FROM role_permissions WHERE role_id=? AND page=? LIMIT 1");
    $stmt->bind_param('is', $user['role_id'], $page);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $perm_cache[$cache_key] = $res && $res[$col] == 1;
    return $perm_cache[$cache_key];
}

function require_permission($conn, $page, $action = 'view')
{
    if (!has_permission($conn, $page, $action)) {
        $user = current_user($conn);
        $fallback = 'index.php';
        if ($user) {
            $stmt = $conn->prepare('SELECT page FROM role_permissions WHERE role_id=? AND can_view=1 LIMIT 1');
            $stmt->bind_param('i', $user['role_id']);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $fallback = $res ? 'index.php?page=' . $res['page'] : 'index.php?logout=1&logout_token=' . urlencode($_SESSION['csrf_token'] ?? '');
        }
        die('<meta http-equiv="refresh" content="10;url=' . $fallback . '"><div style="padding:40px;text-align:center;font-family:sans-serif"><h2>⛔ Access Denied</h2><p>You do not have permission to ' . $action . ' ' . $page . '.</p><p style="font-size:0.9rem;color:#888">Auto-redirecting in 10 seconds...</p><a href="' . $fallback . '" style="display:inline-block;padding:8px 16px;background:#4a9eff;color:#fff;text-decoration:none;border-radius:2px;margin-top:10px">Go Back</a></div>');
    }
}

function generate_svg_captcha($text)
{
    header('Content-Type: image/svg+xml');
    $width = 120;
    $height = 40;
    $svg = '<?xml version="1.0" encoding="UTF-8"?>';
    $svg .= '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
    $svg .= '<rect width="100%" height="100%" fill="#f9f9f9" rx="2" ry="2" />';
    for ($i = 0; $i < 6; $i++) {
        $x1 = rand(0, $width);
        $y1 = rand(0, $height);
        $x2 = rand(0, $width);
        $y2 = rand(0, $height);
        $svg .= '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" stroke="#bbb" stroke-width="2" opacity="0.6" />';
    }
    $svg .= '<text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" font-family="monospace" font-size="18" font-weight="bold" fill="#333" letter-spacing="1">' . $text . '</text>';
    $svg .= '</svg>';
    echo $svg;
    exit;
}

if (isset($_GET['captcha'])) {
    $operand1 = rand(1, 10);
    $operand2 = rand(1, 10);
    $operator = ['+', '-'][rand(0, 1)];
    if ($operator === '-' && $operand2 > $operand1) {
        $temp = $operand1;
        $operand1 = $operand2;
        $operand2 = $temp;
    }
    $result = ($operator == '+') ? ($operand1 + $operand2) : ($operand1 - $operand2);
    $_SESSION['captcha_code'] = $result;
    $_SESSION['captcha_lifetime'] = time() + 300;
    $equation = $operand1 . ' ' . $operator . ' ' . $operand2 . ' = ?';
    generate_svg_captcha($equation);
}

function install_database()
{
    return true;  /*
                   * PRODUCTION OPTIMIZATION: DB Creation disabled
                   * global $db_name;
                   * $conn = db_connect(true);
                   * if (!$conn)
                   *     return false;
                   * $conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                   * $conn->select_db($db_name);
                   * $tables = [
                   *     'CREATE TABLE IF NOT EXISTS `settings` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `setting_key` VARCHAR(100) UNIQUE NOT NULL,
                   *         `setting_value` TEXT
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                   *     'CREATE TABLE IF NOT EXISTS `suppliers` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `name` VARCHAR(255) NOT NULL,
                   *         `contact` VARCHAR(100),
                   *         `address` TEXT,
                   *         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                   *     'CREATE TABLE IF NOT EXISTS `customers` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `name` VARCHAR(255) NOT NULL,
                   *         `phone` VARCHAR(50),
                   *         `cnic` VARCHAR(20),
                   *         `is_filer` TINYINT(1) DEFAULT 1,
                   *         `address` TEXT,
                   *         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                   *     'CREATE TABLE IF NOT EXISTS `models` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `model_code` VARCHAR(50) NOT NULL,
                   *         `model_name` VARCHAR(255) NOT NULL,
                   *         `category` VARCHAR(100),
                   *         `short_code` VARCHAR(20),
                   *         `image` VARCHAR(255) NULL,
                   *         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                   *     'CREATE TABLE IF NOT EXISTS `accessories` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `name` VARCHAR(255) NOT NULL,
                   *         `sku` VARCHAR(100) UNIQUE,
                   *         `purchase_price` DECIMAL(15,2) DEFAULT 0.00,
                   *         `selling_price` DECIMAL(15,2) DEFAULT 0.00,
                   *         `current_stock` INT DEFAULT 0,
                   *         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                   *         `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                   *     'CREATE TABLE IF NOT EXISTS `purchase_orders` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `order_date` DATE,
                   *         `supplier_id` INT,
                   *         `total_units` INT,
                   *         `total_amount` DECIMAL(15,2) DEFAULT 0.00,
                   *         `notes` TEXT,
                   *         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                   *         FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                   *     "CREATE TABLE IF NOT EXISTS `bikes` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `purchase_order_id` INT,
                   *         `order_date` DATE,
                   *         `inventory_date` DATE,
                   *         `chassis_number` VARCHAR(100) UNIQUE NOT NULL,
                   *         `motor_number` VARCHAR(100),
                   *         `model_id` INT,
                   *         `color` VARCHAR(50),
                   *         `purchase_price` DECIMAL(15,2),
                   *         `selling_price` DECIMAL(15,2) NULL,
                   *         `selling_date` DATE NULL,
                   *         `customer_id` INT NULL,
                   *         `tax_amount` DECIMAL(15,2) DEFAULT 0,
                   *         `margin` DECIMAL(15,2) DEFAULT 0,
                   *         `status` ENUM('in_stock','sold','returned','returned_to_supplier','reserved','damaged_lost') DEFAULT 'in_stock',
                   *         `return_date` DATE NULL,
                   *         `return_amount` DECIMAL(15,2) NULL,
                   *         `return_notes` TEXT NULL,
                   *         `safeguard_notes` TEXT NULL,
                   *         `notes` TEXT,
                   *         `image` VARCHAR(255) NULL,
                   *         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                   *         `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                   *         FOREIGN KEY (`model_id`) REFERENCES `models`(`id`) ON DELETE SET NULL,
                   *         FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
                   *         FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders`(`id`) ON DELETE SET NULL
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                   *     'CREATE TABLE IF NOT EXISTS `sale_accessories` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `bike_id` INT NOT NULL,
                   *         `accessory_id` INT NOT NULL,
                   *         `quantity` INT NOT NULL,
                   *         `unit_price` DECIMAL(15,2) NOT NULL,
                   *         `discount_amount` DECIMAL(15,2) DEFAULT 0.00,
                   *         `final_price` DECIMAL(15,2) NOT NULL,
                   *         FOREIGN KEY (`bike_id`) REFERENCES `bikes`(`id`) ON DELETE CASCADE,
                   *         FOREIGN KEY (`accessory_id`) REFERENCES `accessories`(`id`) ON DELETE RESTRICT
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                   *     "CREATE TABLE IF NOT EXISTS `payments` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `payment_date` DATE NOT NULL,
                   *         `payment_type` ENUM('cash','cheque','bank_transfer','online','other') NOT NULL,
                   *         `amount` DECIMAL(15,2) NOT NULL,
                   *         `cheque_number` VARCHAR(50) NULL,
                   *         `bank_name` VARCHAR(100) NULL,
                   *         `cheque_date` DATE NULL,
                   *         `transaction_type` ENUM('purchase','sale','installment','expense_payment','supplier_payment','customer_refund','customer_advance','supplier_refund') NOT NULL,
                   *         `reference_id` INT NULL,
                   *         `party_name` VARCHAR(255),
                   *         `notes` TEXT,
                   *         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                   *     "CREATE TABLE IF NOT EXISTS `installments` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `bike_id` INT NOT NULL,
                   *         `customer_id` INT NOT NULL,
                   *         `due_date` DATE NOT NULL,
                   *         `installment_amount` DECIMAL(15,2) NOT NULL,
                   *         `amount_paid` DECIMAL(15,2) DEFAULT 0.00,
                   *         `penalty_fee` DECIMAL(15,2) DEFAULT 0.00,
                   *         `status` ENUM('pending','paid','overdue','cancelled') DEFAULT 'pending',
                   *         `payment_id` INT NULL,
                   *         `notes` TEXT NULL,
                   *         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                   *         `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                   *         FOREIGN KEY (`bike_id`) REFERENCES `bikes`(`id`) ON DELETE CASCADE,
                   *         FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE CASCADE,
                   *         FOREIGN KEY (`payment_id`) REFERENCES `payments`(`id`) ON DELETE SET NULL
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                   *     "CREATE TABLE IF NOT EXISTS `ledger` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `entry_date` DATE,
                   *         `entry_type` ENUM('debit','credit'),
                   *         `amount` DECIMAL(15,2),
                   *         `party_type` ENUM('customer','supplier','other'),
                   *         `party_id` INT,
                   *         `description` TEXT,
                   *         `reference_type` VARCHAR(50),
                   *         `reference_id` INT,
                   *         `balance` DECIMAL(15,2),
                   *         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                   *     'CREATE TABLE IF NOT EXISTS `roles` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `name` VARCHAR(100) UNIQUE NOT NULL,
                   *         `description` TEXT,
                   *         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                   *     'CREATE TABLE IF NOT EXISTS `role_permissions` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `role_id` INT NOT NULL,
                   *         `page` VARCHAR(50) NOT NULL,
                   *         `can_view` TINYINT(1) DEFAULT 0,
                   *         `can_add` TINYINT(1) DEFAULT 0,
                   *         `can_edit` TINYINT(1) DEFAULT 0,
                   *         `can_delete` TINYINT(1) DEFAULT 0,
                   *         UNIQUE KEY `role_page` (`role_id`,`page`),
                   *         FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                   *     'CREATE TABLE IF NOT EXISTS `users` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `username` VARCHAR(50) UNIQUE NOT NULL,
                   *         `password_hash` VARCHAR(255) NOT NULL,
                   *         `full_name` VARCHAR(255),
                   *         `role_id` INT,
                   *         `is_active` TINYINT(1) DEFAULT 1,
                   *         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                   *         FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE SET NULL
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                   *     "CREATE TABLE IF NOT EXISTS `income_expenses` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `entry_date` DATE NOT NULL,
                   *         `type` ENUM('income','expense') NOT NULL,
                   *         `category` VARCHAR(100) NOT NULL,
                   *         `amount` DECIMAL(15,2) NOT NULL,
                   *         `payment_method` ENUM('cash','cheque','bank_transfer','online','other') DEFAULT 'cash',
                   *         `reference` VARCHAR(255),
                   *         `notes` TEXT,
                   *         `created_by` INT,
                   *         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                   *         FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                   *     "CREATE TABLE IF NOT EXISTS `quotations` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `quote_date` DATE NOT NULL,
                   *         `customer_id` INT,
                   *         `bike_id` INT,
                   *         `accessories_json` TEXT,
                   *         `quoted_price` DECIMAL(15,2) NOT NULL,
                   *         `valid_until` DATE,
                   *         `status` ENUM('pending','accepted','rejected','converted') DEFAULT 'pending',
                   *         `notes` TEXT,
                   *         `created_by` INT,
                   *         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                   *         FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
                   *         FOREIGN KEY (`bike_id`) REFERENCES `bikes`(`id`) ON DELETE SET NULL,
                   *         FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                   * ];
                   * foreach ($tables as $sql) {
                   *     if (!$conn->query($sql)) {
                   *         $conn->close();
                   *         return false;
                   *     }
                   * }
                   * $defaults = [
                   *     ['company_name', 'BNI Enterprises'],
                   *     ['branch_name', 'Dera (Ahmed Metro)'],
                   *     ['tax_rate', '0.1'],
                   *     ['currency', 'Rs.'],
                   *     ['tax_on', 'purchase_price'],
                   *     ['theme', 'dark'],
                   *     ['admin_password', password_hash('admin123', PASSWORD_DEFAULT)],
                   *     ['show_purchase_on_invoice', '0'],
                   *     ['session_timeout_idle', '2400'],
                   *     ['session_timeout_absolute', '28800'],
                   * ];
                   * $stmt = $conn->prepare('INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?)');
                   * foreach ($defaults as $d) {
                   *     $stmt->bind_param('ss', $d[0], $d[1]);
                   *     $stmt->execute();
                   * }
                   * $stmt->close();
                   * $conn->query("INSERT IGNORE INTO roles (id, name, description) VALUES (1,'Administrator','Full access')");
                   * $conn->query("INSERT IGNORE INTO roles (id, name, description) VALUES (2,'Manager','Limited access')");
                   * $admin_hash = password_hash('admin123!', PASSWORD_DEFAULT);
                   * $conn->query("INSERT IGNORE INTO users (id, username, password_hash, full_name, role_id, is_active) VALUES (1,'admin','$admin_hash','System Administrator',1,1)");
                   * $pages = ['dashboard', 'inventory', 'purchase', 'sale', 'customers', 'suppliers', 'models', 'reports', 'returns', 'payments', 'settings', 'roles', 'users', 'income_expense', 'accessories', 'quotations', 'installments', 'landing_page'];
                   * foreach ($pages as $p) {
                   *     $conn->query("INSERT IGNORE INTO role_permissions (role_id, page, can_view, can_add, can_edit, can_delete) VALUES (1,'$p',1,1,1,1)");
                   * }
                   * $conn->query("INSERT IGNORE INTO role_permissions (role_id, page, can_view, can_add, can_edit, can_delete) VALUES (2,'dashboard',1,0,0,0)");
                   * $stmt->close();
                   * $new_tables = [
                   *     'CREATE TABLE IF NOT EXISTS `leadership` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `name` VARCHAR(255) NOT NULL,
                   *         `position` VARCHAR(255),
                   *         `image` VARCHAR(255),
                   *         `message` TEXT,
                   *         `sort_order` INT DEFAULT 0,
                   *         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                   *     'CREATE TABLE IF NOT EXISTS `gallery` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `title` VARCHAR(255),
                   *         `description` TEXT,
                   *         `image` VARCHAR(255),
                   *         `sort_order` INT DEFAULT 0,
                   *         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
                   *     "CREATE TABLE IF NOT EXISTS `bike_requests` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `customer_name` VARCHAR(255) NOT NULL,
                   *         `customer_phone` VARCHAR(50) NOT NULL,
                   *         `bike_details` TEXT,
                   *         `status` ENUM('pending','contacted','fulfilled','cancelled') DEFAULT 'pending',
                   *         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                   *     "CREATE TABLE IF NOT EXISTS `quote_requests` (
                   *         `id` INT AUTO_INCREMENT PRIMARY KEY,
                   *         `customer_name` VARCHAR(255) NOT NULL,
                   *         `customer_phone` VARCHAR(50) NOT NULL,
                   *         `bike_id` INT,
                   *         `details` TEXT,
                   *         `status` ENUM('pending','sent','accepted','rejected') DEFAULT 'pending',
                   *         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                   *         FOREIGN KEY (`bike_id`) REFERENCES `bikes`(`id`) ON DELETE SET NULL
                   *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                   * ];
                   * foreach ($new_tables as $sql) {
                   *     $conn->query($sql);
                   * }
                   * $lp_defaults = [
                   *     ['landing_hero_title', 'Experience the Future of Mobility'],
                   *     ['landing_hero_subtitle', 'Premium Electric Bikes for a Greener Tomorrow'],
                   *     ['company_address', '123 Bike Street, Dera Ghazi Khan, Punjab, Pakistan'],
                   *     ['company_map_iframe', ''],
                   *     ['company_whatsapp', '923000000000'],
                   *     ['company_email', 'info@bnienterprises.com'],
                   *     ['social_facebook', 'https://facebook.com'],
                   *     ['social_instagram', 'https://instagram.com'],
                   *     ['social_twitter', 'https://twitter.com'],
                   *     ['vision_statement', 'To be the leading provider of eco-friendly transportation in the region.'],
                   *     ['mission_statement', 'Providing high-quality electric bikes and exceptional service to our customers.'],
                   * ];
                   * $stmt_lp = $conn->prepare('INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?)');
                   * foreach ($lp_defaults as $d) {
                   *     $stmt_lp->bind_param('ss', $d[0], $d[1]);
                   *     $stmt_lp->execute();
                   * }
                   * $stmt_lp->close();
                   * $models_seed = [
                   *     ['LY SI', 'LY SI Electric Bike', 'Electric Bike', 'LY'],
                   *     ['T9 Sports', 'T9 Sports Electric Bike', 'Electric Bike', 'T9'],
                   *     ['T9 Sports LFP', 'T9 Sports LFP Electric Bike', 'Electric Bike', 'T9 LFP'],
                   *     ['T9 Eco', 'T9 Eco Electric Bike', 'Electric Bike', 'T9 Eco'],
                   *     ['Thrill Pro', 'Thrill Pro Electric Bike', 'Electric Bike', 'TP'],
                   *     ['Thrill Pro LFP', 'Thrill Pro LFP Electric Bike', 'Electric Bike', 'TP LFP'],
                   *     ['E8S M2', 'E8S M2 Electric Scooter', 'Electric Scooter', 'E8S'],
                   *     ['E8S Pro', 'E8S Pro Electric Scooter', 'Electric Scooter', 'E8S Pro'],
                   *     ['M6 K6', 'M6 K6 Electric Bike', 'Electric Bike', 'M6'],
                   *     ['M6 NP', 'M6 NP Electric Bike', 'Electric Bike', 'M6 NP'],
                   *     ['M6 Lithium NP', 'M6 Lithium NP Electric Bike', 'Electric Bike', 'M6 L'],
                   *     ['Premium', 'Premium Electric Bike', 'Electric Bike', 'Premium'],
                   *     ['W. Bike H2', 'W. Bike H2 Electric Bike', 'Electric Bike', 'W. Bike'],
                   * ];
                   * $r = $conn->query('SELECT COUNT(*) as c FROM `models`');
                   * $row = $r->fetch_assoc();
                   * if ($row['c'] == 0) {
                   *     $stmt = $conn->prepare('INSERT INTO `models` (`model_code`,`model_name`,`category`,`short_code`) VALUES (?,?,?,?)');
                   *     foreach ($models_seed as $m) {
                   *         $stmt->bind_param('ssss', $m[0], $m[1], $m[2], $m[3]);
                   *         $stmt->execute();
                   *     }
                   *     $stmt->close();
                   * }
                   * $r2 = $conn->query('SELECT COUNT(*) as c FROM `suppliers`');
                   * $row2 = $r2->fetch_assoc();
                   * if ($row2['c'] == 0) {
                   *     $conn->query("INSERT INTO `suppliers` (`name`,`contact`,`address`) VALUES ('Default Supplier','0300-0000000','Pakistan')");
                   * }
                   * $r3 = $conn->query('SELECT COUNT(*) as c FROM `customers`');
                   * $row3 = $r3->fetch_assoc();
                   * if ($row3['c'] == 0) {
                   *     $customers_seed = [
                   *         ['Ahmed Ali', '0321-1234567', '35201-1234567-1', 1, 'Dera Ghazi Khan, Punjab'],
                   *         ['Muhammad Usman', '0333-7654321', '35201-7654321-3', 0, 'Muzaffargarh, Punjab'],
                   *         ['Bilal Hussain', '0345-9876543', '35201-9876543-5', 1, 'Rajanpur, Punjab'],
                   *         ['Zafar Iqbal', '0312-4567890', '35201-4567890-7', 0, 'Layyah, Punjab'],
                   *     ];
                   *     $stmt = $conn->prepare('INSERT INTO `customers` (`name`,`phone`,`cnic`,`is_filer`,`address`) VALUES (?,?,?,?,?)');
                   *     foreach ($customers_seed as $c) {
                   *         $stmt->bind_param('sssis', $c[0], $c[1], $c[2], $c[3], $c[4]);
                   *         $stmt->execute();
                   *     }
                   *     $stmt->close();
                   * }
                   * $r4 = $conn->query('SELECT COUNT(*) as c FROM `accessories`');
                   * $row4 = $r4->fetch_assoc();
                   * if ($row4['c'] == 0) {
                   *     $accessories_seed = [
                   *         ['Helmet', 'HLM001', 500, 750, 20],
                   *         ['Charger 60V', 'CHR60V01', 1500, 2200, 15],
                   *         ['Tyre Puncture Kit', 'TPK001', 300, 500, 30],
                   *         ['Disc Lock', 'DLCK001', 800, 1200, 10],
                   *         ['Basket', 'BSKT001', 600, 900, 25],
                   *     ];
                   *     $stmt = $conn->prepare('INSERT INTO `accessories` (`name`,`sku`,`purchase_price`,`selling_price`,`current_stock`) VALUES (?,?,?,?,?)');
                   *     foreach ($accessories_seed as $acc) {
                   *         $stmt->bind_param('ssddi', $acc[0], $acc[1], $acc[2], $acc[3], $acc[4]);
                   *         $stmt->execute();
                   *     }
                   *     $stmt->close();
                   * }
                   * $conn->close();
                   * return true;
                   */
}

function get_setting($key)
{
    static $settings_cache = null;
    if ($settings_cache === null) {
        $conn = db_connect();
        if (!$conn)
            return null;
        $r = $conn->query('SELECT setting_key, setting_value FROM settings');
        $settings_cache = [];
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $settings_cache[$row['setting_key']] = $row['setting_value'];
            }
        }
        $conn->close();
    }
    return $settings_cache[$key] ?? null;
}

function fmt_money($val)
{
    global $currency;
    return $currency . ' ' . number_format((float) $val, 2);
}

function fmt_date($d)
{
    if (!$d || $d === '0000-00-00')
        return '-';
    try {
        $dt = new DateTime($d);
        return $dt->format('d/m/Y');
    } catch (Exception $e) {
        return $d;
    }
}

function sanitize($val)
{
    return htmlspecialchars(html_entity_decode(strip_tags(trim((string) $val)), ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_administrator($conn)
{
    $user = current_user($conn);
    return $user && (int) $user['role_id'] === 1 && $user['role_name'] === 'Administrator';
}

function require_any_permission($conn, array $checks)
{
    foreach ($checks as $check) {
        if (has_permission($conn, $check[0], $check[1] ?? 'view')) return;
    }
    http_response_code(403);
    exit('Forbidden');
}

function clean_text($val, $max_length = 10000)
{
    $value = trim(strip_tags((string) $val));
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max_length, 'UTF-8');
    }
    return substr($value, 0, $max_length);
}

function normalize_public_url($value, array $allowed_hosts = [])
{
    $value = trim((string) $value);
    if ($value === '') return '';
    if (!filter_var($value, FILTER_VALIDATE_URL)) throw new Exception('A public URL is invalid.');
    $parts = parse_url($value);
    if (($parts['scheme'] ?? '') !== 'https') throw new Exception('Public URLs must use HTTPS.');
    if ($allowed_hosts && !in_array(strtolower($parts['host'] ?? ''), $allowed_hosts, true)) {
        throw new Exception('The map URL must use an approved Google Maps host.');
    }
    return $value;
}

function valid_date($value, $allow_empty = false)
{
    if ($value === '' || $value === null) {
        return $allow_empty;
    }
    $date = DateTime::createFromFormat('!Y-m-d', (string) $value);
    return $date && $date->format('Y-m-d') === $value;
}

function require_enum($value, array $allowed, $label)
{
    if (!in_array($value, $allowed, true)) {
        throw new Exception('Invalid ' . $label . '.');
    }
    return $value;
}

function require_positive_money($value, $label, $allow_zero = false)
{
    if (!is_numeric($value) || !is_finite((float) $value)) {
        throw new Exception($label . ' must be a valid number.');
    }
    $amount = round((float) $value, 2);
    if (($allow_zero && $amount < 0) || (!$allow_zero && $amount <= 0)) {
        throw new Exception($label . ($allow_zero ? ' cannot be negative.' : ' must be greater than zero.'));
    }
    return $amount;
}

function csv_safe($value)
{
    $value = (string) $value;
    if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
        $value = "'" . $value;
    }
    return $value;
}

function stream_csv_row($stream, array $row)
{
    fputcsv($stream, array_map('csv_safe', $row));
}

function validate_password_strength($password)
{
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*\-]).{8,}$/', $password) === 1;
}

function assert_valid_payment_method($method)
{
    return require_enum($method, ['cash', 'cheque', 'bank_transfer', 'online', 'other'], 'payment method');
}

function assert_valid_bike_status_transition($old_status, $new_status)
{
    $allowed = [
        'in_stock' => ['in_stock', 'reserved', 'damaged_lost'],
        'reserved' => ['reserved', 'in_stock', 'damaged_lost'],
        'sold' => ['sold'],
        'returned' => ['returned'],
        'returned_to_supplier' => ['returned_to_supplier'],
        'damaged_lost' => ['damaged_lost', 'in_stock'],
    ];
    if (!isset($allowed[$old_status]) || !in_array($new_status, $allowed[$old_status], true)) {
        throw new Exception('Invalid inventory status transition from ' . $old_status . ' to ' . $new_status . '. Use the Sale or Returns workflow for financial status changes.');
    }
}

function defang_spam($val)
{
    $val = sanitize($val);
    $val = str_ireplace(['http://', 'https://', 'www.'], ['hxxp://', 'hxxps://', 'www[.]'], $val);
    $val = preg_replace('/([a-zA-Z0-9])\.([a-zA-Z]{2,6})\b/', '$1[.]$2', $val);
    return $val;
}

function handle_image_upload($file, $dest_dir = 'uploads/')
{
    $dest_dir = basename($dest_dir) . '/';
    if (!isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK)
        return null;
    if (($file['size'] ?? 0) <= 0 || ($file['size'] ?? 0) > 10485760)
        return null;
    if (!is_dir($dest_dir))
        mkdir($dest_dir, 0755, true);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']))
        return null;
    $filename = uniqid('img_') . '.jpg';
    $dest = $dest_dir . $filename;
    $info = getimagesize($file['tmp_name']);
    if (!$info)
        return null;
    if (($info[0] * $info[1]) > 40000000)
        return null;
    $img = null;
    if ($info[2] == IMAGETYPE_JPEG)
        $img = imagecreatefromjpeg($file['tmp_name']);
    elseif ($info[2] == IMAGETYPE_PNG)
        $img = imagecreatefrompng($file['tmp_name']);
    elseif ($info[2] == IMAGETYPE_WEBP)
        $img = imagecreatefromwebp($file['tmp_name']);
    elseif ($info[2] == IMAGETYPE_GIF)
        $img = imagecreatefromgif($file['tmp_name']);
    if (!$img)
        return null;
    $w = imagesx($img);
    $h = imagesy($img);
    $new_w = min($w, 800);
    $new_h = ($new_w / $w) * $h;
    $new_img = imagecreatetruecolor($new_w, $new_h);
    if ($info[2] == IMAGETYPE_PNG || $info[2] == IMAGETYPE_GIF) {
        $white = imagecolorallocate($new_img, 255, 255, 255);
        imagefill($new_img, 0, 0, $white);
    }
    imagecopyresampled($new_img, $img, 0, 0, 0, 0, $new_w, $new_h, $w, $h);
    $quality = 90;
    do {
        ob_start();
        imagejpeg($new_img, null, $quality);
        $size = ob_get_length();
        $imgData = ob_get_clean();
        $quality -= 10;
    } while ($size > 307200 && $quality > 10);
    file_put_contents($dest, $imgData);
    imagedestroy($img);
    imagedestroy($new_img);
    return $dest;
}

function handle_bike_image_upload($file, $dest_dir = 'uploads/')
{
    $dest_dir = basename($dest_dir) . '/';
    if (!isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK)
        return null;
    if (($file['size'] ?? 0) <= 0 || ($file['size'] ?? 0) > 10485760)
        return null;
    if (!is_dir($dest_dir))
        mkdir($dest_dir, 0755, true);
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']))
        return null;
    $filename = uniqid('bike_') . '.jpg';
    $dest = $dest_dir . $filename;
    $info = getimagesize($file['tmp_name']);
    if (!$info)
        return null;
    if (($info[0] * $info[1]) > 40000000)
        return null;
    $img = null;
    if ($info[2] == IMAGETYPE_JPEG)
        $img = imagecreatefromjpeg($file['tmp_name']);
    elseif ($info[2] == IMAGETYPE_PNG)
        $img = imagecreatefrompng($file['tmp_name']);
    elseif ($info[2] == IMAGETYPE_WEBP)
        $img = imagecreatefromwebp($file['tmp_name']);
    elseif ($info[2] == IMAGETYPE_GIF)
        $img = imagecreatefromgif($file['tmp_name']);
    if (!$img)
        return null;
    $orig_w = imagesx($img);
    $orig_h = imagesy($img);
    $target_w = 1200;
    $target_h = 800;
    $safe_w = 1080;
    $safe_h = 720;
    $scale = min($safe_w / $orig_w, $safe_h / $orig_h);
    $new_w = ceil($scale * $orig_w);
    $new_h = ceil($scale * $orig_h);
    $dst_x = (int) (($target_w - $new_w) / 2);
    $dst_y = (int) (($target_h - $new_h) / 2);
    $canvas = imagecreatetruecolor($target_w, $target_h);
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $white);
    imagecopyresampled($canvas, $img, $dst_x, $dst_y, 0, 0, $new_w, $new_h, $orig_w, $orig_h);
    imagejpeg($canvas, $dest, 90);
    imagedestroy($img);
    imagedestroy($canvas);
    return $dest;
}

function handle_receipt_upload($file, $dest_dir = 'receipts/')
{
    $dest_dir = basename($dest_dir) . '/';
    if (!is_dir($dest_dir))
        mkdir($dest_dir, 0755, true);
    if (!isset($file['error']) || is_array($file['error']) || $file['error'] !== UPLOAD_ERR_OK)
        return null;
    if (($file['size'] ?? 0) <= 0 || ($file['size'] ?? 0) > 10485760)
        return null;
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf']))
        return null;
    $filename = uniqid('receipt_') . '.jpg';
    $dest = $dest_dir . $filename;
    if ($ext === 'pdf') {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if ($finfo->file($file['tmp_name']) !== 'application/pdf')
            return null;
        $pdf_dest = $dest_dir . bin2hex(random_bytes(16)) . '.pdf';
        if (!move_uploaded_file($file['tmp_name'], $pdf_dest) && !copy($file['tmp_name'], $pdf_dest))
            return null;
        return $pdf_dest;
    }
    $info = getimagesize($file['tmp_name']);
    if (!$info)
        return null;
    if (($info[0] * $info[1]) > 40000000)
        return null;
    $img = null;
    if ($info[2] == IMAGETYPE_JPEG)
        $img = imagecreatefromjpeg($file['tmp_name']);
    elseif ($info[2] == IMAGETYPE_PNG)
        $img = imagecreatefrompng($file['tmp_name']);
    elseif ($info[2] == IMAGETYPE_WEBP)
        $img = imagecreatefromwebp($file['tmp_name']);
    elseif ($info[2] == IMAGETYPE_GIF)
        $img = imagecreatefromgif($file['tmp_name']);
    if (!$img)
        return null;
    $w = imagesx($img);
    $h = imagesy($img);
    $new_w = min($w, 1200);
    $new_h = ($new_w / $w) * $h;
    $new_img = imagecreatetruecolor($new_w, $new_h);
    if ($info[2] == IMAGETYPE_PNG || $info[2] == IMAGETYPE_GIF) {
        $white = imagecolorallocate($new_img, 255, 255, 255);
        imagefill($new_img, 0, 0, $white);
    }
    imagecopyresampled($new_img, $img, 0, 0, 0, 0, $new_w, $new_h, $w, $h);
    $quality = 85;
    do {
        ob_start();
        imagejpeg($new_img, null, $quality);
        $size = ob_get_length();
        $imgData = ob_get_clean();
        $quality -= 10;
    } while ($size > 204800 && $quality > 10);
    file_put_contents($dest, $imgData);
    imagedestroy($img);
    imagedestroy($new_img);
    return $dest;
}

function update_app_icons($file)
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK)
        return false;
    $info = getimagesize($file['tmp_name']);
    if (!$info)
        return false;
    $src = null;
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $src = imagecreatefromjpeg($file['tmp_name']);
            break;
        case IMAGETYPE_PNG:
            $src = imagecreatefrompng($file['tmp_name']);
            break;
        case IMAGETYPE_WEBP:
            $src = imagecreatefromwebp($file['tmp_name']);
            break;
        case IMAGETYPE_GIF:
            $src = imagecreatefromgif($file['tmp_name']);
            break;
    }
    if (!$src)
        return false;
    imagealphablending($src, false);
    imagesavealpha($src, true);
    $sizes = [
        'logo.png' => [0, 0],
        'favicon-96x96.png' => [96, 96],
        'apple-touch-icon.png' => [180, 180],
        'web-app-manifest-192x192.png' => [192, 192],
        'web-app-manifest-512x512.png' => [512, 512],
    ];
    foreach ($sizes as $filename => $dim) {
        $w = $dim[0];
        $h = $dim[1];
        if ($w == 0) {
            $w = imagesx($src);
            $h = imagesy($src);
        }
        $dst = imagecreatetruecolor($w, $h);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, imagesx($src), imagesy($src));
        imagepng($dst, $filename);
        imagedestroy($dst);
    }
    $ico_size = 32;
    $dst_ico = imagecreatetruecolor($ico_size, $ico_size);
    imagealphablending($dst_ico, false);
    imagesavealpha($dst_ico, true);
    imagecopyresampled($dst_ico, $src, 0, 0, 0, 0, $ico_size, $ico_size, imagesx($src), imagesy($src));
    imagepng($dst_ico, 'favicon.ico');
    copy('favicon.ico', 'logo.ico');
    imagedestroy($dst_ico);
    $base64 = base64_encode(file_get_contents('favicon-96x96.png'));
    $svg_content = '<?xml version="1.0" encoding="UTF-8"?><svg xmlns="http://www.w3.org/2000/svg" width="96" height="96"><image href="data:image/png;base64,' . $base64 . '" width="96" height="96"/></svg>';
    file_put_contents('favicon.svg', $svg_content);
    imagedestroy($src);
    return true;
}

$db_exists = true;

/*
 * PRODUCTION OPTIMIZATION: Removed runtime schema checks
 * $conn_check = db_connect();
 * if ($conn_check) {
 *     $res = $conn_check->query("SHOW TABLES LIKE 'leadership'");
 *     if ($res->num_rows == 0) {
 *         install_database();
 *     }
 *     $conn_check->close();
 * }
 * if (isset($_POST['do_install'])) {
 *     if (install_database()) {
 *         $db_exists = true;
 *         header('Location: index.php');
 *         exit;
 *     }
 * }
 */
if ($db_exists) {
    $theme = get_setting('theme') ?? 'dark';
    $idle_timeout = (int) (get_setting('session_timeout_idle') ?? 2400);
    $absolute_timeout = (int) (get_setting('session_timeout_absolute') ?? 28800);
    if (!isset($_SESSION['user_id'])) {
        if (isset($_POST['do_login'])) {
            $uname = trim($_POST['username'] ?? '');
            $upass = $_POST['password'] ?? '';
            $captcha = $_POST['captcha_code'] ?? '';
            if ($captcha === '' || !isset($_SESSION['captcha_code']) || !hash_equals((string) $_SESSION['captcha_code'], trim((string) $captcha))) {
                record_failed_attempt();
                $login_error = 'Invalid CAPTCHA.';
            } else {
                $conn_temp = db_connect();
                $stmt = $conn_temp->prepare('SELECT id, password_hash, is_active, role_id FROM users WHERE username=? LIMIT 1');
                $stmt->bind_param('s', $uname);
                $stmt->execute();
                $u = $stmt->get_result()->fetch_assoc();
                if ($u && $u['is_active'] && password_verify($upass, $u['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $u['id'];
                    $_SESSION['login_time'] = time();
                    $_SESSION['last_active'] = time();
                    reset_attempts();
                    unset($_SESSION['captcha_code']);
                    $redirect = 'index.php';
                    $stmt_rp = $conn_temp->prepare('SELECT page FROM role_permissions WHERE role_id=? AND can_view=1 ORDER BY id LIMIT 1');
                    $stmt_rp->bind_param('i', $u['role_id']);
                    $stmt_rp->execute();
                    $rp_res = $stmt_rp->get_result()->fetch_assoc();
                    if ($rp_res) {
                        $redirect = 'index.php?page=' . $rp_res['page'];
                    }
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    record_failed_attempt();
                    $login_error = 'Invalid username or password.';
                    unset($_SESSION['captcha_code']);
                }
                $conn_temp->close();
            }
        }
    } else {
        if (time() - ($_SESSION['login_time'] ?? time()) > $absolute_timeout) {
            session_destroy();
            header('Location: index.php?msg=session_expired');
            exit;
        }
        if (time() - ($_SESSION['last_active'] ?? $_SESSION['login_time'] ?? time()) > $idle_timeout) {
            session_destroy();
            header('Location: index.php?msg=idle_logout');
            exit;
        }
        $_SESSION['last_active'] = time();
        $valid_get_logout = isset($_GET['logout'], $_GET['logout_token']) && hash_equals($_SESSION['csrf_token'] ?? '', (string) $_GET['logout_token']);
        if (isset($_POST['do_logout']) || $valid_get_logout) {
            session_destroy();
            header('Clear-Site-Data: "cache", "cookies", "storage"');
            header('Location: index.php');
            exit;
        }
    }
}
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? '';
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';
if ($db_exists && isset($_SESSION['user_id'])) {
    $conn = db_connect();
    try {
        run_auto_database_backup($conn, $author);
    } catch (Exception $e) {
        error_log('Auto backup error: ' . $e->getMessage());
    }
    $currency = get_setting('currency') ?? 'Rs.';
    $tax_rate = (float) (get_setting('tax_rate') ?? 0.1);
    $tax_on = get_setting('tax_on') ?? 'purchase_price';
    $protected_pages = ['purchase', 'inventory', 'sale', 'returns', 'payments', 'customers', 'suppliers', 'models', 'reports', 'customer_ledger', 'supplier_ledger', 'settings', 'roles', 'users', 'income_expense', 'accessories', 'quotations', 'installments', 'money_destinations', 'money_tracking', 'bank_deposits'];
    if (in_array($page, $protected_pages)) {
        require_permission($conn, $page, 'view');
    }
    if (isset($_GET['receipt_id'])) {
        require_permission($conn, 'bank_deposits', 'view');
        $receipt_id = (int) $_GET['receipt_id'];
        $receipt_stmt = $conn->prepare('SELECT receipt_image FROM bank_deposits WHERE id=? LIMIT 1');
        $receipt_stmt->bind_param('i', $receipt_id);
        $receipt_stmt->execute();
        $receipt_row = $receipt_stmt->get_result()->fetch_assoc();
        $receipt_root = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'receipts');
        $receipt_file = $receipt_row ? realpath(__DIR__ . DIRECTORY_SEPARATOR . $receipt_row['receipt_image']) : false;
        if (!$receipt_root || !$receipt_file || !is_file($receipt_file) || strpos($receipt_file, $receipt_root . DIRECTORY_SEPARATOR) !== 0) {
            http_response_code(404);
            exit('Receipt not found.');
        }
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($receipt_file);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'], true)) {
            http_response_code(403);
            exit('Unsupported receipt type.');
        }
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="receipt-' . $receipt_id . '.' . pathinfo($receipt_file, PATHINFO_EXTENSION) . '"');
        header('Cache-Control: private, no-store');
        readfile($receipt_file);
        exit;
    }
    if ($page === 'roles' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['save_role'])) {
            $id = (int) ($_POST['id'] ?? 0);
            require_permission($conn, 'roles', $id > 0 ? 'edit' : 'add');
            $name = clean_text($_POST['name'] ?? '');
            $desc = clean_text($_POST['description'] ?? '');
            if (empty($name)) {
                $err = 'Role name cannot be empty.';
                goto end_roles_post;
            }
            if ($id == 1 && $name !== 'Administrator') {
                $name = 'Administrator';
            }
            if ($id) {
                $stmt = $conn->prepare('UPDATE roles SET name=?, description=? WHERE id=?');
                $stmt->bind_param('ssi', $name, $desc, $id);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare('INSERT INTO roles (name, description) VALUES (?,?)');
                $stmt->bind_param('ss', $name, $desc);
                $stmt->execute();
                $id = $conn->insert_id;
            }
            $conn->query("DELETE FROM role_permissions WHERE role_id=$id");
            $all_pages_perm = ['dashboard', 'inventory', 'purchase', 'sale', 'customers', 'suppliers', 'models', 'reports', 'returns', 'payments', 'settings', 'roles', 'users', 'income_expense', 'accessories', 'quotations', 'installments', 'money_destinations', 'money_tracking', 'bank_deposits', 'customer_ledger', 'supplier_ledger', 'landing_page'];
            $stmtp = $conn->prepare('INSERT INTO role_permissions (role_id, page, can_view, can_add, can_edit, can_delete) VALUES (?,?,?,?,?,?)');
            foreach ($all_pages_perm as $p) {
                $v = isset($_POST['perm'][$p]['view']) ? 1 : 0;
                $a = isset($_POST['perm'][$p]['add']) ? 1 : 0;
                $e = isset($_POST['perm'][$p]['edit']) ? 1 : 0;
                $d = isset($_POST['perm'][$p]['delete']) ? 1 : 0;
                $stmtp->bind_param('isiiii', $id, $p, $v, $a, $e, $d);
                $stmtp->execute();
            }
            $msg = 'Role and permissions saved successfully.';
            header('Location: index.php?page=roles&msg=' . urlencode($msg));
            exit;
        }
        if (isset($_POST['delete_role'])) {
            require_permission($conn, 'roles', 'delete');
            $id = (int) $_POST['id'];
            if ($id == 1) {
                $err = 'Administrator role cannot be deleted.';
            } else {
                $stmt_users = $conn->prepare('SELECT COUNT(*) FROM users WHERE role_id = ?');
                $stmt_users->bind_param('i', $id);
                $stmt_users->execute();
                $user_count = $stmt_users->get_result()->fetch_row()[0];
                if ($user_count > 0) {
                    $err = 'Cannot delete role: There are users assigned to this role.';
                } else {
                    $stmt = $conn->prepare('DELETE FROM roles WHERE id=?');
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $msg = 'Role deleted successfully.';
                }
            }
            header('Location: index.php?page=roles&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        end_roles_post:;
    }
    if ($page === 'users' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['save_user'])) {
            $id = (int) ($_POST['id'] ?? 0);
            require_permission($conn, 'users', $id > 0 ? 'edit' : 'add');
            $username = clean_text($_POST['username'] ?? '');
            $full_name = clean_text($_POST['full_name'] ?? '');
            $role_id = (int) ($_POST['role_id'] ?? 2);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $pass = $_POST['password'] ?? '';
            if (($id === 1 || $role_id === 1) && !is_administrator($conn)) {
                $err = 'Only the primary Administrator can manage Administrator accounts or assignments.';
                goto end_users_post;
            }
            if (empty($username) || empty($role_id)) {
                $err = 'Username and Role are required.';
                goto end_users_post;
            }
            if (!validate_password_strength($pass) && !empty($pass)) {
                $err = 'Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.';
                goto end_users_post;
            }
            if ($id) {
                $user_q = $conn->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
                $user_q->bind_param('si', $username, $id);
                $user_q->execute();
                if ($user_q->get_result()->num_rows > 0) {
                    $err = 'Username already exists.';
                    goto end_users_post;
                }
                if ($pass) {
                    $hash = password_hash($pass, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare('UPDATE users SET username=?, full_name=?, role_id=?, is_active=?, password_hash=? WHERE id=?');
                    $stmt->bind_param('ssissi', $username, $full_name, $role_id, $is_active, $hash, $id);
                } else {
                    $stmt = $conn->prepare('UPDATE users SET username=?, full_name=?, role_id=?, is_active=? WHERE id=?');
                    $stmt->bind_param('ssiii', $username, $full_name, $role_id, $is_active, $id);
                }
                $stmt->execute();
                $msg = 'User updated successfully.';
            } else {
                if (empty($pass)) {
                    $err = 'Password is required for new users.';
                    goto end_users_post;
                }
                $user_q = $conn->prepare('SELECT id FROM users WHERE username = ?');
                $user_q->bind_param('s', $username);
                $user_q->execute();
                if ($user_q->get_result()->num_rows > 0) {
                    $err = 'Username already exists.';
                    goto end_users_post;
                }
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('INSERT INTO users (username, password_hash, full_name, role_id, is_active) VALUES (?,?,?,?,?)');
                $stmt->bind_param('sssii', $username, $hash, $full_name, $role_id, $is_active);
                $stmt->execute();
                $msg = 'User added successfully.';
            }
            header('Location: index.php?page=users&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        if (isset($_POST['delete_user'])) {
            require_permission($conn, 'users', 'delete');
            $id = (int) $_POST['id'];
            if ($id == 1 || $id == $_SESSION['user_id']) {
                $err = 'Cannot delete administrative or currently logged-in user.';
            } else {
                $stmt = $conn->prepare('DELETE FROM users WHERE id=?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $msg = 'User deleted successfully.';
            }
            header('Location: index.php?page=users&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        end_users_post:;
    }
    if ($page === 'income_expense' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['save_entry'])) {
            $id = (int) ($_POST['id'] ?? 0);
            require_permission($conn, 'income_expense', $id > 0 ? 'edit' : 'add');
            $entry_date = clean_text($_POST['entry_date'] ?? date('Y-m-d'));
            $type = clean_text($_POST['type'] ?? 'expense');
            $category = clean_text($_POST['category'] ?? '');
            $amount = (float) ($_POST['amount'] ?? 0);
            $payment_method = clean_text($_POST['payment_method'] ?? 'cash');
            $reference = clean_text($_POST['reference'] ?? '');
            $notes = clean_text($_POST['notes'] ?? '');
            $created_by = $_SESSION['user_id'];
            if (!valid_date($entry_date) || !in_array($type, ['income', 'expense'], true) || empty($category) || $amount <= 0 || !in_array($payment_method, ['cash', 'cheque', 'bank_transfer', 'online', 'other'], true)) {
                $err = 'All required fields must be filled and amount must be positive.';
                goto end_income_expense_post;
            }
            if ($id) {
                $stmt = $conn->prepare('UPDATE income_expenses SET entry_date=?, type=?, category=?, amount=?, payment_method=?, reference=?, notes=? WHERE id=?');
                $stmt->bind_param('sssdsssi', $entry_date, $type, $category, $amount, $payment_method, $reference, $notes, $id);
            } else {
                $stmt = $conn->prepare('INSERT INTO income_expenses (entry_date, type, category, amount, payment_method, reference, notes, created_by) VALUES (?,?,?,?,?,?,?,?)');
                $stmt->bind_param('sssdsssi', $entry_date, $type, $category, $amount, $payment_method, $reference, $notes, $created_by);
            }
            $stmt->execute();
            $msg = 'Entry saved successfully.';
            header('Location: index.php?page=income_expense&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        if (isset($_POST['delete_entry'])) {
            require_permission($conn, 'income_expense', 'delete');
            $id = (int) $_POST['id'];
            $stmt = $conn->prepare('DELETE FROM income_expenses WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $msg = 'Entry deleted successfully.';
            header('Location: index.php?page=income_expense&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        end_income_expense_post:;
    }
    if (isset($_POST['toggle_theme'])) {
        $new_theme = ($theme === 'dark') ? 'light' : 'dark';
        $stmt = $conn->prepare("UPDATE settings SET setting_value=? WHERE setting_key='theme'");
        $stmt->bind_param('s', $new_theme);
        $stmt->execute();
        $stmt->close();
        header('Location: index.php?' . http_build_query($_GET));
        exit;
    }
    if ($page === 'purchase' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_purchase'])) {
        require_permission($conn, 'purchase', 'add');
        $order_date = clean_text($_POST['order_date'] ?? date('Y-m-d'));
        $inventory_date = clean_text($_POST['inventory_date'] ?? date('Y-m-d'));
        $supplier_id = (int) ($_POST['supplier_id'] ?? 0);
        $po_notes = clean_text($_POST['po_notes'] ?? '');
        $bikes_data = isset($_POST['bikes']) && is_array($_POST['bikes']) ? $_POST['bikes'] : [];
        $payments_data = isset($_POST['payments']) && is_array($_POST['payments']) ? $_POST['payments'] : [];
        if (!valid_date($order_date) || !valid_date($inventory_date) || $supplier_id <= 0 || empty($bikes_data)) {
            $err = 'Purchase order requires date, supplier and at least one bike.';
            goto end_purchase_post;
        }
        $conn->begin_transaction();
        try {
            $total_units = 0;
            $po_total_amount = 0.0;
            $po_stmt = $conn->prepare('INSERT INTO purchase_orders (order_date,supplier_id,total_units,total_amount,notes) VALUES (?,?,?,?,?)');
            $po_stmt->bind_param('sidss', $order_date, $supplier_id, $total_units, $po_total_amount, $po_notes);
            $po_stmt->execute();
            $po_id = $conn->insert_id;
            $po_stmt->close();
            $bike_stmt = $conn->prepare("INSERT INTO bikes (purchase_order_id,order_date,inventory_date,chassis_number,motor_number,model_id,color,purchase_price,tax_amount,tax_rate_applied,tax_basis,status,safeguard_notes,notes,image) VALUES (?,?,?,?,?,?,?,?,?,?,?,'in_stock',?,?,?)");
            $saved_count = 0;
            $saved_total_amount = 0.0;
            $errors_list = [];
            $uploaded_bike_images = [];
            foreach ($bikes_data as $key => $b) {
                $chassis = clean_text($b['chassis'] ?? '', 100);
                $motor = clean_text($b['motor'] ?? '', 100);
                $model_id = (int) ($b['model_id'] ?? 0);
                $color = clean_text($b['color'] ?? '', 50);
                $pp = (float) ($b['purchase_price'] ?? 0);
                $safe_notes = clean_text($b['safeguard_notes'] ?? '');
                $bnotes = clean_text($b['notes'] ?? '');
                if (empty($chassis) || $model_id <= 0 || $pp <= 0) {
                    $errors_list[] = 'Bike entry requires Chassis, Model, and Purchase Price. Skipping incomplete bike.';
                    continue;
                }
                $bike_img = null;
                if (isset($_FILES['bikes']['name'][$key]['image']) && $_FILES['bikes']['error'][$key]['image'] === UPLOAD_ERR_OK) {
                    $file_arr = [
                        'name' => $_FILES['bikes']['name'][$key]['image'],
                        'type' => $_FILES['bikes']['type'][$key]['image'],
                        'tmp_name' => $_FILES['bikes']['tmp_name'][$key]['image'],
                        'error' => $_FILES['bikes']['error'][$key]['image'],
                        'size' => $_FILES['bikes']['size'][$key]['image']
                    ];
                    $bike_img = handle_bike_image_upload($file_arr);
                    if ($bike_img) $uploaded_bike_images[] = $bike_img;
                }
                $base_tax = ($tax_on === 'selling_price') ? 0 : $pp;
                $tax = ($base_tax * $tax_rate);
                $bike_stmt->bind_param('issssisdddssss', $po_id, $order_date, $inventory_date, $chassis, $motor, $model_id, $color, $pp, $tax, $tax_rate, $tax_on, $safe_notes, $bnotes, $bike_img);
                if (!$bike_stmt->execute()) {
                    if ($bike_img && is_file($bike_img)) {
                        @unlink($bike_img);
                        $uploaded_bike_images = array_values(array_diff($uploaded_bike_images, [$bike_img]));
                    }
                    $errors_list[] = "Chassis $chassis: Could not be saved (duplicate or database constraint error).";
                } else {
                    $saved_count++;
                    $saved_total_amount += $pp;
                }
            }
            $bike_stmt->close();
            if ($saved_count <= 0) {
                throw new Exception('No bikes were saved. Purchase order was not created.');
            }
            $upd_po_stmt = $conn->prepare('UPDATE purchase_orders SET total_units=?, total_amount=? WHERE id=?');
            $upd_po_stmt->bind_param('idi', $saved_count, $saved_total_amount, $po_id);
            $upd_po_stmt->execute();
            $upd_po_stmt->close();
            $purchase_payment_total = 0.0;
            foreach ($payments_data as $payment_check) {
                $purchase_payment_total += max(0, (float) ($payment_check['amount'] ?? 0));
            }
            if ($purchase_payment_total - $saved_total_amount > 0.0001) {
                throw new Exception('Supplier payments cannot exceed this purchase order total.');
            }
            foreach ($payments_data as $p) {
                $pay_type = clean_text($p['payment_type'] ?? 'cash', 30);
                assert_valid_payment_method($pay_type);
                $pay_amount = (float) ($p['amount'] ?? 0);
                $chq_num = $pay_type === 'cheque' ? sanitize($p['cheque_number'] ?? '') : null;
                $bank_name = $pay_type === 'cheque' ? sanitize($p['bank_name'] ?? '') : null;
                $chq_date = $pay_type === 'cheque' && !empty($p['cheque_date']) ? $p['cheque_date'] : null;
                if ($pay_amount > 0) {
                    $sup_r = $conn->query("SELECT name FROM suppliers WHERE id=$supplier_id");
                    $sup_row = $sup_r ? $sup_r->fetch_assoc() : null;
                    $party = $sup_row ? $sup_row['name'] : 'Unknown Supplier';
                    $payment_status = $pay_type === 'cheque' ? 'pending' : 'cleared';
                    $pay_stmt = $conn->prepare("INSERT INTO payments (payment_date, payment_type, amount, cheque_number, bank_name, cheque_date, transaction_type, reference_id, supplier_id, party_name, notes, status) VALUES (?,?,?,?,?,?,'supplier_payment',?,?,?,?,?)");
                    $pay_stmt->bind_param('ssdsssiisss', $order_date, $pay_type, $pay_amount, $chq_num, $bank_name, $chq_date, $po_id, $supplier_id, $party, $po_notes, $payment_status);
                    $pay_stmt->execute();
                    $pay_stmt->close();
                }
            }
            $conn->commit();
            if (!empty($errors_list)) {
                $err = "Saved $saved_count bikes. Some errors occurred: " . implode('; ', $errors_list);
            } else {
                $msg = "Purchase order saved. $saved_count bike(s) added to inventory.";
            }
            $_SESSION['last_purchase_id'] = $po_id;
        } catch (Exception $e) {
            $conn->rollback();
            foreach ($uploaded_bike_images ?? [] as $uploaded_bike_image) {
                if (is_file($uploaded_bike_image)) @unlink($uploaded_bike_image);
            }
            $err = 'Transaction failed: ' . $e->getMessage();
        }
        header('Location: index.php?page=purchase&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
        end_purchase_post:;
    }
    if ($page === 'suppliers' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'add') {
            require_permission($conn, 'suppliers', 'add');
            $name = clean_text($_POST['name'] ?? '');
            $contact = clean_text($_POST['contact'] ?? '');
            $address = clean_text($_POST['address'] ?? '');
            if (empty($name)) {
                $err = 'Supplier name is required.';
            } else {
                $st = $conn->prepare('INSERT INTO suppliers (name,contact,address) VALUES (?,?,?)');
                $st->bind_param('sss', $name, $contact, $address);
                $st->execute();
                $st->close();
                $msg = 'Supplier added successfully.';
            }
        } elseif ($action === 'edit') {
            require_permission($conn, 'suppliers', 'edit');
            $sid = (int) ($_POST['id'] ?? 0);
            $name = clean_text($_POST['name'] ?? '');
            $contact = clean_text($_POST['contact'] ?? '');
            $address = clean_text($_POST['address'] ?? '');
            if (empty($name) || $sid <= 0) {
                $err = 'Supplier ID and name are required.';
            } else {
                $old_stmt = $conn->prepare('SELECT name FROM suppliers WHERE id=? LIMIT 1');
                $old_stmt->bind_param('i', $sid);
                $old_stmt->execute();
                $old_row = $old_stmt->get_result()->fetch_assoc();
                $old_name = $old_row['name'] ?? '';
                $st = $conn->prepare('UPDATE suppliers SET name=?,contact=?,address=? WHERE id=?');
                $st->bind_param('sssi', $name, $contact, $address, $sid);
                $st->execute();
                $st->close();
                sync_supplier_payment_names($conn, $sid, $old_name, $name);
                $msg = 'Supplier updated successfully.';
            }
        } elseif ($action === 'delete') {
            require_permission($conn, 'suppliers', 'delete');
            $sid = (int) ($_POST['id'] ?? 0);
            $stmt_check = $conn->prepare('SELECT COUNT(*) FROM purchase_orders WHERE supplier_id = ?');
            $stmt_check->bind_param('i', $sid);
            $stmt_check->execute();
            $order_count = $stmt_check->get_result()->fetch_row()[0];
            if ($order_count > 0) {
                $err = 'Cannot delete supplier: There are associated purchase orders.';
            } else {
                $st = $conn->prepare('DELETE FROM suppliers WHERE id=?');
                $st->bind_param('i', $sid);
                $st->execute();
                $st->close();
                $msg = 'Supplier deleted.';
            }
        }
        header('Location: index.php?page=suppliers&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'customers' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'add') {
            require_permission($conn, 'customers', 'add');
            $name = clean_text($_POST['name'] ?? '');
            $phone = clean_text($_POST['phone'] ?? '');
            $cnic = clean_text($_POST['cnic'] ?? '');
            $is_filer = isset($_POST['is_filer']) ? 1 : 0;
            $address = clean_text($_POST['address'] ?? '');
            if (empty($name)) {
                $err = 'Customer name is required.';
            } else {
                $st = $conn->prepare('INSERT INTO customers (name,phone,cnic,is_filer,address) VALUES (?,?,?,?,?)');
                $st->bind_param('sssis', $name, $phone, $cnic, $is_filer, $address);
                $st->execute();
                $st->close();
                $msg = 'Customer added.';
            }
        } elseif ($action === 'edit') {
            require_permission($conn, 'customers', 'edit');
            $cid = (int) ($_POST['id'] ?? 0);
            $name = clean_text($_POST['name'] ?? '');
            $phone = clean_text($_POST['phone'] ?? '');
            $cnic = clean_text($_POST['cnic'] ?? '');
            $is_filer = isset($_POST['is_filer']) ? 1 : 0;
            $address = clean_text($_POST['address'] ?? '');
            if (empty($name) || $cid <= 0) {
                $err = 'Customer ID and name are required.';
            } else {
                $old_stmt = $conn->prepare('SELECT name FROM customers WHERE id=? LIMIT 1');
                $old_stmt->bind_param('i', $cid);
                $old_stmt->execute();
                $old_row = $old_stmt->get_result()->fetch_assoc();
                $old_name = $old_row['name'] ?? '';
                $st = $conn->prepare('UPDATE customers SET name=?,phone=?,cnic=?,is_filer=?,address=? WHERE id=?');
                $st->bind_param('ssiisi', $name, $phone, $cnic, $is_filer, $address, $cid);
                $st->execute();
                $st->close();
                sync_customer_payment_names($conn, $cid, $old_name, $name);
                $msg = 'Customer updated.';
            }
        } elseif ($action === 'delete') {
            require_permission($conn, 'customers', 'delete');
            $cid = (int) ($_POST['id'] ?? 0);
            $stmt_check = $conn->prepare('SELECT COUNT(*) FROM bikes WHERE customer_id = ?');
            $stmt_check->bind_param('i', $cid);
            $stmt_check->execute();
            $bike_count = $stmt_check->get_result()->fetch_row()[0];
            if ($bike_count > 0) {
                $err = 'Cannot delete customer: There are associated bike sales.';
            } else {
                $st = $conn->prepare('DELETE FROM customers WHERE id=?');
                $st->bind_param('i', $cid);
                $st->execute();
                $st->close();
                $msg = 'Customer deleted.';
            }
        }
        header('Location: index.php?page=customers&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'models' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'add') {
            require_permission($conn, 'models', 'add');
            $mc = clean_text($_POST['model_code'] ?? '');
            $mn = clean_text($_POST['model_name'] ?? '');
            $cat = clean_text($_POST['category'] ?? '');
            $sc = clean_text($_POST['short_code'] ?? '');
            $top_speed = clean_text($_POST['top_speed'] ?? '');
            $max_range = clean_text($_POST['max_range'] ?? '');
            $img_path = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $img_path = handle_bike_image_upload($_FILES['image']);
            }
            if (empty($mc) || empty($mn)) {
                $err = 'Model code and name are required.';
            } else {
                $st = $conn->prepare('INSERT INTO models (model_code,model_name,category,short_code,image,top_speed,max_range) VALUES (?,?,?,?,?,?,?)');
                $st->bind_param('sssssss', $mc, $mn, $cat, $sc, $img_path, $top_speed, $max_range);
                $st->execute();
                $st->close();
                $msg = 'Model added.';
            }
        } elseif ($action === 'edit') {
            require_permission($conn, 'models', 'edit');
            $mid = (int) ($_POST['id'] ?? 0);
            $mc = clean_text($_POST['model_code'] ?? '');
            $mn = clean_text($_POST['model_name'] ?? '');
            $cat = clean_text($_POST['category'] ?? '');
            $sc = clean_text($_POST['short_code'] ?? '');
            $top_speed = clean_text($_POST['top_speed'] ?? '');
            $max_range = clean_text($_POST['max_range'] ?? '');
            $img_path = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $img_path = handle_bike_image_upload($_FILES['image']);
            }
            if (empty($mc) || empty($mn) || $mid <= 0) {
                $err = 'Model ID, code and name are required.';
            } else {
                if ($img_path) {
                    $st = $conn->prepare('UPDATE models SET model_code=?,model_name=?,category=?,short_code=?,image=?,top_speed=?,max_range=? WHERE id=?');
                    $st->bind_param('sssssssi', $mc, $mn, $cat, $sc, $img_path, $top_speed, $max_range, $mid);
                } else {
                    $st = $conn->prepare('UPDATE models SET model_code=?,model_name=?,category=?,short_code=?,top_speed=?,max_range=? WHERE id=?');
                    $st->bind_param('ssssssi', $mc, $mn, $cat, $sc, $top_speed, $max_range, $mid);
                }
                $st->execute();
                $st->close();
                $msg = 'Model updated.';
            }
        } elseif ($action === 'delete') {
            require_permission($conn, 'models', 'delete');
            $mid = (int) ($_POST['id'] ?? 0);
            $stmt_check = $conn->prepare('SELECT COUNT(*) FROM bikes WHERE model_id = ?');
            $stmt_check->bind_param('i', $mid);
            $stmt_check->execute();
            $bike_count = $stmt_check->get_result()->fetch_row()[0];
            if ($bike_count > 0) {
                $err = 'Cannot delete model: There are associated bikes.';
            } else {
                $st = $conn->prepare('DELETE FROM models WHERE id=?');
                $st->bind_param('i', $mid);
                $st->execute();
                $st->close();
                $msg = 'Model deleted.';
            }
        }
        header('Location: index.php?page=models&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'accessories' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'add') {
            require_permission($conn, 'accessories', 'add');
            $name = clean_text($_POST['name'] ?? '');
            $sku = clean_text($_POST['sku'] ?? '');
            $purchase_price = (float) ($_POST['purchase_price'] ?? 0);
            $selling_price = (float) ($_POST['selling_price'] ?? 0);
            $current_stock = (int) ($_POST['current_stock'] ?? 0);
            if (empty($name) || empty($sku) || $purchase_price < 0 || $selling_price < 0 || $current_stock < 0) {
                $err = 'All fields are required and prices/stock must be non-negative.';
            } else {
                $st = $conn->prepare('INSERT INTO accessories (name,sku,purchase_price,selling_price,current_stock) VALUES (?,?,?,?,?)');
                $st->bind_param('ssddi', $name, $sku, $purchase_price, $selling_price, $current_stock);
                $st->execute();
                $st->close();
                $msg = 'Accessory added.';
            }
        } elseif ($action === 'edit') {
            require_permission($conn, 'accessories', 'edit');
            $acc_id = (int) ($_POST['id'] ?? 0);
            $name = clean_text($_POST['name'] ?? '');
            $sku = clean_text($_POST['sku'] ?? '');
            $purchase_price = (float) ($_POST['purchase_price'] ?? 0);
            $selling_price = (float) ($_POST['selling_price'] ?? 0);
            $current_stock = (int) ($_POST['current_stock'] ?? 0);
            if (empty($name) || empty($sku) || $purchase_price < 0 || $selling_price < 0 || $current_stock < 0 || $acc_id <= 0) {
                $err = 'All fields are required and prices/stock must be non-negative.';
            } else {
                $st = $conn->prepare('UPDATE accessories SET name=?,sku=?,purchase_price=?,selling_price=?,current_stock=? WHERE id=?');
                $st->bind_param('ssddii', $name, $sku, $purchase_price, $selling_price, $current_stock, $acc_id);
                $st->execute();
                $st->close();
                $msg = 'Accessory updated.';
            }
        } elseif ($action === 'delete') {
            require_permission($conn, 'accessories', 'delete');
            $acc_id = (int) ($_POST['id'] ?? 0);
            $stmt_check = $conn->prepare('SELECT COUNT(*) FROM sale_accessories WHERE accessory_id = ?');
            $stmt_check->bind_param('i', $acc_id);
            $stmt_check->execute();
            $sale_count = $stmt_check->get_result()->fetch_row()[0];
            if ($sale_count > 0) {
                $err = 'Cannot delete accessory: It has been sold with bikes.';
            } else {
                $st = $conn->prepare('DELETE FROM accessories WHERE id=?');
                $st->bind_param('i', $acc_id);
                $st->execute();
                $st->close();
                $msg = 'Accessory deleted.';
            }
        }
        header('Location: index.php?page=accessories&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'quotations' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['save_quote'])) {
            $id = (int) ($_POST['id'] ?? 0);
            require_permission($conn, 'quotations', $id > 0 ? 'edit' : 'add');
            $quote_date = clean_text($_POST['quote_date'] ?? date('Y-m-d'));
            $customer_id = (int) ($_POST['customer_id'] ?? 0);
            $bike_id = (int) ($_POST['bike_id'] ?? 0);
            $quoted_price = (float) ($_POST['quoted_price'] ?? 0);
            $is_installment = isset($_POST['is_installment']) ? 1 : 0;
            $down_payment = (float) ($_POST['down_payment'] ?? 0);
            $total_installments = (int) ($_POST['total_installments'] ?? 0);
            $installment_amount = (float) ($_POST['installment_amount'] ?? 0);
            $valid_until = clean_text($_POST['valid_until'] ?? '');
            $notes = clean_text($_POST['notes'] ?? '');
            $accessories_data = $_POST['accessories'] ?? [];
            $created_by = $_SESSION['user_id'];
            if (empty($quote_date) || $customer_id <= 0 || $bike_id <= 0 || $quoted_price <= 0 || empty($valid_until)) {
                $err = 'All required fields must be filled.';
                goto end_quotations_post;
            }
            if (!valid_date($quote_date) || !valid_date($valid_until) || $valid_until < $quote_date || $down_payment < 0 || $down_payment > $quoted_price || $total_installments < 0 || $installment_amount < 0) {
                $err = 'Quotation dates, payment amounts, or installment details are invalid.';
                goto end_quotations_post;
            }
            $accessories_json = json_encode($accessories_data);
            if ($id) {
                $stmt = $conn->prepare('UPDATE quotations SET quote_date=?, customer_id=?, bike_id=?, accessories_json=?, quoted_price=?, is_installment=?, down_payment=?, total_installments=?, installment_amount=?, valid_until=?, notes=? WHERE id=?');
                $stmt->bind_param('siisdididsis', $quote_date, $customer_id, $bike_id, $accessories_json, $quoted_price, $is_installment, $down_payment, $total_installments, $installment_amount, $valid_until, $notes, $id);
            } else {
                $stmt = $conn->prepare('INSERT INTO quotations (quote_date, customer_id, bike_id, accessories_json, quoted_price, is_installment, down_payment, total_installments, installment_amount, valid_until, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->bind_param('siisdididsis', $quote_date, $customer_id, $bike_id, $accessories_json, $quoted_price, $is_installment, $down_payment, $total_installments, $installment_amount, $valid_until, $notes, $created_by);
            }
            $stmt->execute();
            $msg = 'Quotation saved successfully.';
            header('Location: index.php?page=quotations&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        if (isset($_POST['convert_quote_to_sale'])) {
            require_permission($conn, 'quotations', 'edit');
            require_permission($conn, 'sale', 'add');
            $quote_id = (int) ($_POST['quote_id'] ?? 0);
            if ($quote_id <= 0) {
                $err = 'Invalid quotation ID.';
                goto end_quotations_post;
            }
            $conn->begin_transaction();
            try {
                $quote_stmt = $conn->prepare("SELECT * FROM quotations WHERE id=? AND status='pending' LIMIT 1 FOR UPDATE");
                $quote_stmt->bind_param('i', $quote_id);
                $quote_stmt->execute();
                $quote = $quote_stmt->get_result()->fetch_assoc();
                if (!$quote) {
                    throw new Exception('Quotation not found or already converted/cancelled.');
                }
                if (!empty($quote['valid_until']) && $quote['valid_until'] < date('Y-m-d')) {
                    throw new Exception('This quotation has expired and cannot be converted. Extend its validity first.');
                }
                $bike_id = (int) $quote['bike_id'];
                $selling_price = (float) $quote['quoted_price'];
                $customer_id = (int) $quote['customer_id'];
                $accessories_data = json_decode($quote['accessories_json'], true) ?: [];
                $sale_date = date('Y-m-d');
                $bike = lock_in_stock_bike($conn, $bike_id);
                $base = ($tax_on === 'selling_price') ? $selling_price : $bike['purchase_price'];
                $tax_amount = ($base * $tax_rate);
                $margin = $selling_price - $bike['purchase_price'] - $tax_amount;
                $st = $conn->prepare("UPDATE bikes SET selling_price=?,selling_date=?,customer_id=?,tax_amount=?,margin=?,tax_rate_applied=?,tax_basis=?,status='sold' WHERE id=?");
                $st->bind_param('dsidddsi', $selling_price, $sale_date, $customer_id, $tax_amount, $margin, $tax_rate, $tax_on, $bike_id);
                $st->execute();
                $total_acc_price = attach_sale_accessories($conn, $bike_id, $accessories_data);
                $accessory_cost = get_sale_accessory_cost_for_bike($conn, $bike_id);
                $cust_sql_id = (int) $customer_id;
                $cust_r = $conn->query("SELECT name FROM customers WHERE id=$cust_sql_id");
                $cust_row = $cust_r ? $cust_r->fetch_assoc() : null;
                $party_name = $cust_row ? $cust_row['name'] : 'Walk-in Customer';
                $total_sale_amount = $selling_price + $total_acc_price;
                $is_inst = $quote['is_installment'] == 1;
                $dp_amount = $is_inst ? (float) $quote['down_payment'] : $total_sale_amount;
                if ($dp_amount < 0 || $dp_amount - $total_sale_amount > 0.0001) {
                    throw new Exception('Quotation down payment exceeds the final sale total.');
                }
                $margin = round($total_sale_amount - ((float) $bike['purchase_price'] + $accessory_cost) - $tax_amount, 2);
                $margin_stmt = $conn->prepare('UPDATE bikes SET margin=? WHERE id=?');
                $margin_stmt->bind_param('di', $margin, $bike_id);
                $margin_stmt->execute();
                $payment_notes = ($is_inst ? 'Down Payment' : 'Full Payment') . " from Quotation #$quote_id";
                if ($dp_amount > 0) {
                    $pay_st = $conn->prepare("INSERT INTO payments (payment_date, payment_type, amount, transaction_type, reference_id, customer_id, party_name, notes, status) VALUES (?,'cash',?,'sale',?,?,?,?,'cleared')");
                    $pay_st->bind_param('sdiiss', $sale_date, $dp_amount, $bike_id, $customer_id, $party_name, $payment_notes);
                    $pay_st->execute();
                }
                $led_st = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'debit',?,'customer',?,?,'sale',?,?)");
                $desc = 'Sale of Chassis: ' . $bike['chassis_number'] . ' from Quote #' . $quote_id;
                $led_st->bind_param('sdisid', $sale_date, $total_sale_amount, $customer_id, $desc, $bike_id, $total_sale_amount);
                $led_st->execute();
                if ($dp_amount > 0) {
                    $led_dp_st = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'credit',?,'customer',?,?,'down_payment',?,?)");
                    $desc_dp = 'Payment for Quote #' . $quote_id;
                    $led_dp_st->bind_param('sdisid', $sale_date, $dp_amount, $customer_id, $desc_dp, $bike_id, $dp_amount);
                    $led_dp_st->execute();
                }
                if ($is_inst && $quote['total_installments'] > 0) {
                    $remaining_quote_balance = round($total_sale_amount - $dp_amount, 2);
                    $installment_per_month = round($remaining_quote_balance / $quote['total_installments'], 2);
                    $current_date = new DateTime($sale_date);
                    $current_date->modify('+1 month');
                    $inst_stmt = $conn->prepare("INSERT INTO installments (bike_id, customer_id, due_date, installment_amount, status, notes) VALUES (?,?,?,?,'pending',?)");
                    for ($i = 0; $i < $quote['total_installments']; $i++) {
                        $due_date = $current_date->format('Y-m-d');
                        $scheduled_before = $installment_per_month * $i;
                        $this_installment = ($i === (int) $quote['total_installments'] - 1) ? round($remaining_quote_balance - $scheduled_before, 2) : $installment_per_month;
                        $inst_notes = 'Installment ' . ($i + 1) . ' from Quote #' . $quote_id;
                        $inst_stmt->bind_param('iisds', $bike_id, $customer_id, $due_date, $this_installment, $inst_notes);
                        $inst_stmt->execute();
                        $current_date->modify('+1 month');
                    }
                }
                $conn->query("UPDATE quotations SET status='converted' WHERE id=$quote_id");
                $conn->commit();
                $_SESSION['last_sale_bike_id'] = $bike_id;
                $msg = 'Quotation converted to sale successfully. Margin: ' . fmt_money($margin);
            } catch (Exception $e) {
                $conn->rollback();
                $err = 'Failed to convert quotation to sale: ' . $e->getMessage();
            }
            header('Location: index.php?page=quotations&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        if (isset($_POST['delete_quote'])) {
            require_permission($conn, 'quotations', 'delete');
            $id = (int) $_POST['id'];
            $stmt = $conn->prepare('DELETE FROM quotations WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $msg = 'Quotation deleted successfully.';
            header('Location: index.php?page=quotations&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        end_quotations_post:;
    }
    if ($page === 'sale' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sale'])) {
        require_permission($conn, 'sale', 'add');
        $bike_id = (int) ($_POST['bike_id'] ?? 0);
        $selling_price = (float) ($_POST['selling_price'] ?? 0);
        $selling_date = clean_text($_POST['selling_date'] ?? date('Y-m-d'));
        $customer_id = empty($_POST['customer_id']) ? null : (int) $_POST['customer_id'];
        $down_payment = (float) ($_POST['down_payment'] ?? 0);
        $total_installments = (int) ($_POST['total_installments'] ?? 0);
        $installment_amount = (float) ($_POST['installment_amount'] ?? 0);
        $first_due_date = clean_text($_POST['first_due_date'] ?? '');
        $payment_method_dp = clean_text($_POST['payment_method_dp'] ?? 'cash');
        $cheque_number_dp = clean_text($_POST['cheque_number_dp'] ?? '');
        $bank_name_dp = clean_text($_POST['bank_name_dp'] ?? '');
        $cheque_date_dp = !empty($_POST['cheque_date_dp']) ? $_POST['cheque_date_dp'] : null;
        $sale_notes = clean_text($_POST['sale_notes'] ?? '');
        $selected_accessories = $_POST['selected_accessories'] ?? [];
        if ($bike_id && $selling_price > 0 && valid_date($selling_date) && $down_payment >= 0 && $total_installments >= 0) {
            $conn->begin_transaction();
            try {
                $bike = lock_in_stock_bike($conn, $bike_id);
                $base = ($tax_on === 'selling_price') ? $selling_price : $bike['purchase_price'];
                $tax_amount = ($base * $tax_rate);
                $margin = $selling_price - $bike['purchase_price'] - $tax_amount;
                $st = $conn->prepare("UPDATE bikes SET selling_price=?,selling_date=?,customer_id=?,tax_amount=?,margin=?,tax_rate_applied=?,tax_basis=?,status='sold',notes=? WHERE id=?");
                $st->bind_param('dsidddssi', $selling_price, $selling_date, $customer_id, $tax_amount, $margin, $tax_rate, $tax_on, $sale_notes, $bike_id);
                $st->execute();
                $st->close();
                $total_acc_price = attach_sale_accessories($conn, $bike_id, $selected_accessories);
                $accessory_cost = get_sale_accessory_cost_for_bike($conn, $bike_id);
                $margin = round(($selling_price + $total_acc_price) - ((float) $bike['purchase_price'] + $accessory_cost) - $tax_amount, 2);
                $margin_stmt = $conn->prepare('UPDATE bikes SET margin=? WHERE id=?');
                $margin_stmt->bind_param('di', $margin, $bike_id);
                $margin_stmt->execute();
                $cust_sql_id = (int) $customer_id;
                $cust_r = $conn->query("SELECT name FROM customers WHERE id=$cust_sql_id");
                $cust_row = $cust_r ? $cust_r->fetch_assoc() : null;
                $party_name = $cust_row ? $cust_row['name'] : 'Walk-in Customer';
                $payment_notes = 'Down Payment for Chassis: ' . $bike['chassis_number'];
                if ($down_payment > 0) {
                    $payment_status = $payment_method_dp === 'cheque' ? 'pending' : 'cleared';
                    $pay_st = $conn->prepare("INSERT INTO payments (payment_date, payment_type, amount, cheque_number, bank_name, cheque_date, transaction_type, reference_id, customer_id, party_name, notes, status) VALUES (?,?,?,?,?,?,'sale',?,?,?,?,?)");
                    $pay_st->bind_param('ssdsssiisss', $selling_date, $payment_method_dp, $down_payment, $cheque_number_dp, $bank_name_dp, $cheque_date_dp, $bike_id, $customer_id, $party_name, $payment_notes, $payment_status);
                    $pay_st->execute();
                    $pay_st->close();
                }
                $total_sale_amount = $selling_price + $total_acc_price;
                if ($down_payment - $total_sale_amount > 0.0001) {
                    throw new Exception('Down payment cannot exceed the total sale value.');
                }
                assert_valid_payment_method($payment_method_dp);
                $led_st = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'debit',?,'customer',?,?,'sale',?,?)");
                $desc = 'Sale of Chassis: ' . $bike['chassis_number'];
                $led_st->bind_param('sdisid', $selling_date, $total_sale_amount, $customer_id, $desc, $bike_id, $total_sale_amount);
                $led_st->execute();
                $led_st->close();
                if ($down_payment > 0) {
                    $led_dp_st = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'credit',?,'customer',?,?,'down_payment',?,?)");
                    $desc_dp = 'Down Payment for Chassis: ' . $bike['chassis_number'];
                    $led_dp_st->bind_param('sdisid', $selling_date, $down_payment, $customer_id, $desc_dp, $bike_id, $down_payment);
                    $led_dp_st->execute();
                    $led_dp_st->close();
                }
                $remaining_balance = $total_sale_amount - $down_payment;
                if (empty($customer_id) && round($remaining_balance, 2) > 0) {
                    throw new Exception('Walk-in customers must pay the full amount upfront. Partial payments are not allowed.');
                }
                if ($total_installments > 0 && $remaining_balance > 0) {
                    if (empty($customer_id) || !valid_date($first_due_date)) {
                        throw new Exception('A customer and valid first due date are required for installment sales.');
                    }
                    $installment_per_month = round($remaining_balance / $total_installments, 2);
                    $current_date = new DateTime($first_due_date);
                    $inst_stmt = $conn->prepare("INSERT INTO installments (bike_id, customer_id, due_date, installment_amount, status, notes) VALUES (?,?,?,?,'pending',?)");
                    for ($i = 0; $i < $total_installments; $i++) {
                        $due_date = $current_date->format('Y-m-d');
                        $scheduled_before = $installment_per_month * $i;
                        $this_installment = ($i === $total_installments - 1) ? round($remaining_balance - $scheduled_before, 2) : $installment_per_month;
                        $inst_notes = 'Installment ' . ($i + 1) . ' for Chassis ' . $bike['chassis_number'];
                        $inst_stmt->bind_param('iisds', $bike_id, $customer_id, $due_date, $this_installment, $inst_notes);
                        $inst_stmt->execute();
                        $current_date->modify('+1 month');
                    }
                    $msg .= ' Installment plan created.';
                }
                if ($remaining_balance > 0 && $total_installments === 0 && empty($customer_id)) {
                    throw new Exception('Walk-in customers cannot leave an outstanding balance.');
                }
                $sale_allocations = $_POST['money_alloc'] ?? [];
                if (!empty($sale_allocations)) {
                    $allocation_total = 0.0;
                    foreach ($sale_allocations as $alloc) {
                        $alloc_amount = (float) ($alloc['amount'] ?? 0);
                        if ($alloc_amount > 0) {
                            $allocation_total += $alloc_amount;
                        }
                    }
                    if ($allocation_total - $total_sale_amount > 0.0001) {
                        throw new Exception('Money allocations cannot exceed the total sale value.');
                    }
                    $user = current_user($conn);
                    $alloc_created_by = $user ? $user['id'] : null;
                    $alloc_stmt = $conn->prepare('INSERT INTO sale_money_allocations (bike_id, destination_id, amount, allocation_date, notes, created_by) VALUES (?,?,?,?,?,?)');
                    $alloc_dest_stmt = $conn->prepare('SELECT id FROM money_destinations WHERE id=? AND is_active=1 LIMIT 1');
                    foreach ($sale_allocations as $alloc) {
                        $alloc_dest_id = (int) ($alloc['destination_id'] ?? 0);
                        $alloc_amount = (float) ($alloc['amount'] ?? 0);
                        $alloc_date = $selling_date;
                        $alloc_note = clean_text($alloc['notes'] ?? '');
                        if ($alloc_dest_id > 0 && $alloc_amount > 0) {
                            $alloc_dest_stmt->bind_param('i', $alloc_dest_id);
                            $alloc_dest_stmt->execute();
                            if (!$alloc_dest_stmt->get_result()->fetch_assoc()) {
                                throw new Exception('A selected money destination is invalid or inactive.');
                            }
                            $alloc_stmt->bind_param('iidssi', $bike_id, $alloc_dest_id, $alloc_amount, $alloc_date, $alloc_note, $alloc_created_by);
                            $alloc_stmt->execute();
                        }
                    }
                    $msg .= ' Money allocation tracked.';
                }
                $conn->commit();
                $_SESSION['last_sale_bike_id'] = $bike_id;
                $msg = 'Sale recorded successfully. Margin: ' . fmt_money($margin) . '. ' . $msg;
            } catch (Exception $e) {
                $conn->rollback();
                $err = 'Sale transaction failed: ' . $e->getMessage();
            }
        } else {
            $err = 'Please fill all required fields correctly.';
        }
        header('Location: index.php?page=sale&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'returns' && $_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_return']) || isset($_POST['save_purchase_return']))) {
        require_permission($conn, 'returns', 'add');
        if (isset($_POST['save_purchase_return'])) {
            $bike_id = (int) ($_POST['bike_id'] ?? 0);
            $return_date = clean_text(!empty($_POST['return_date']) ? $_POST['return_date'] : date('Y-m-d'), 10);
            $return_amount = (float) ($_POST['return_amount'] ?? 0);
            $refund_method = clean_text($_POST['refund_method'] ?? 'cash');
            $cheque_number = clean_text($_POST['cheque_number'] ?? '');
            $bank_name = clean_text($_POST['bank_name'] ?? '');
            $cheque_date = !empty($_POST['cheque_date']) ? $_POST['cheque_date'] : null;
            $return_notes = clean_text($_POST['return_notes'] ?? '');
            if ($bike_id <= 0 || !valid_date($return_date) || $return_amount < 0) {
                $err = 'Please fill all required fields correctly.';
                goto end_returns_post;
            }
            $conn->begin_transaction();
            try {
                $bike_q = $conn->query("SELECT b.chassis_number, b.purchase_price, b.status, po.supplier_id, s.name AS sup_name FROM bikes b LEFT JOIN purchase_orders po ON b.purchase_order_id=po.id LEFT JOIN suppliers s ON po.supplier_id=s.id WHERE b.id=$bike_id FOR UPDATE");
                $bike_info = $bike_q ? $bike_q->fetch_assoc() : null;
                if (!$bike_info) {
                    throw new Exception('Bike not found for return.');
                }
                $full_reversal_amount = $bike_info['purchase_price'];
                if ($return_amount - $full_reversal_amount > 0.0001) {
                    throw new Exception('Supplier refund cannot exceed the original bike purchase value.');
                }
                $st = $conn->prepare("UPDATE bikes SET status='returned_to_supplier', return_date=?, return_amount=?, return_notes=?, tax_amount=0 WHERE id=? AND status='in_stock'");
                $st->bind_param('sdsi', $return_date, $return_amount, $return_notes, $bike_id);
                $st->execute();
                if ($st->affected_rows === 0) {
                    throw new Exception("Bike not found or not in 'in_stock' status.");
                }
                $st->close();
                $party_name = $bike_info['sup_name'] ?? 'Unknown Supplier';
                assert_valid_payment_method($refund_method);
                $payment_status = $refund_method === 'cheque' ? 'pending' : 'cleared';
                $pay_st = $conn->prepare("INSERT INTO payments (payment_date, payment_type, amount, cheque_number, bank_name, cheque_date, transaction_type, reference_id, supplier_id, party_name, notes, status) VALUES (?,?,?,?,?,?,'supplier_refund',?,?,?,?,?)");
                $pay_st->bind_param('ssdsssiisss', $return_date, $refund_method, $return_amount, $cheque_number, $bank_name, $cheque_date, $bike_id, $bike_info['supplier_id'], $party_name, $return_notes, $payment_status);
                $pay_st->execute();
                $pay_st->close();
                $led_st1 = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'debit',?,'supplier',?,?,'purchase_reversal',?,?)");
                $desc1 = 'Bike Returned to Supplier (Chassis: ' . $bike_info['chassis_number'] . ')';
                $led_st1->bind_param('sdisid', $return_date, $full_reversal_amount, $bike_info['supplier_id'], $desc1, $bike_id, $full_reversal_amount);
                $led_st1->execute();
                $led_st1->close();
                if ($return_amount > 0) {
                    $led_st2 = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'credit',?,'supplier',?,?,'supplier_refund',?,?)");
                    $desc2 = 'Refund received for Chassis: ' . $bike_info['chassis_number'];
                    $led_st2->bind_param('sdisid', $return_date, $return_amount, $bike_info['supplier_id'], $desc2, $bike_id, $return_amount);
                    $led_st2->execute();
                    $led_st2->close();
                }
                $conn->commit();
                $msg = 'Purchase Return processed successfully.';
            } catch (Exception $e) {
                $conn->rollback();
                $err = 'Return transaction failed: ' . $e->getMessage();
            }
            header('Location: index.php?page=returns&sub=purchase&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        $bike_id = (int) ($_POST['bike_id'] ?? 0);
        $return_date = clean_text(!empty($_POST['return_date']) ? $_POST['return_date'] : date('Y-m-d'), 10);
        $return_amount = (float) ($_POST['return_amount'] ?? 0);
        $refund_method = clean_text($_POST['refund_method'] ?? 'cash');
        $cheque_number = clean_text($_POST['cheque_number'] ?? '');
        $bank_name = clean_text($_POST['bank_name'] ?? '');
        $cheque_date = !empty($_POST['cheque_date']) ? $_POST['cheque_date'] : null;
        $return_notes = clean_text($_POST['return_notes'] ?? '');
        if ($bike_id <= 0 || !valid_date($return_date) || $return_amount < 0) {
            $err = 'Please fill all required fields correctly.';
            goto end_returns_post;
        }
        $conn->begin_transaction();
        try {
            $bike_q = $conn->query("SELECT b.chassis_number, b.customer_id, b.selling_price, b.status, c.name AS cust_name FROM bikes b LEFT JOIN customers c ON b.customer_id=c.id WHERE b.id=$bike_id FOR UPDATE");
            $bike_info = $bike_q ? $bike_q->fetch_assoc() : null;
            if (!$bike_info) {
                throw new Exception('Bike not found for return.');
            }
            $acc_q = $conn->query("SELECT SUM(final_price) as total_acc FROM sale_accessories WHERE bike_id=$bike_id");
            $acc_total = $acc_q ? (float) ($acc_q->fetch_assoc()['total_acc'] ?? 0) : 0;
            $full_reversal_amount = $bike_info['selling_price'] + $acc_total;
            $paid_q = $conn->query("SELECT COALESCE(SUM(p.amount),0) AS paid_total FROM payments p WHERE p.status!='bounced' AND ((p.transaction_type='sale' AND p.reference_id=$bike_id) OR (p.transaction_type='installment' AND p.reference_id IN (SELECT id FROM installments WHERE bike_id=$bike_id)))");
            $paid_total = (float) ($paid_q->fetch_assoc()['paid_total'] ?? 0);
            if ($return_amount - min($full_reversal_amount, $paid_total) > 0.0001) {
                throw new Exception('Customer refund cannot exceed the amount actually collected for this sale.');
            }
            $st = $conn->prepare("UPDATE bikes SET status='returned', return_date=?, return_amount=?, return_notes=?, tax_amount=0, margin=0 WHERE id=? AND status='sold'");
            $st->bind_param('sdsi', $return_date, $return_amount, $return_notes, $bike_id);
            $st->execute();
            if ($st->affected_rows === 0) {
                throw new Exception("Bike not found or not in 'sold' status to be returned.");
            }
            $st->close();
            $party_name = $bike_info['cust_name'] ?? 'Unknown Customer';
            assert_valid_payment_method($refund_method);
            $payment_status = $refund_method === 'cheque' ? 'pending' : 'cleared';
            $pay_st = $conn->prepare("INSERT INTO payments (payment_date, payment_type, amount, cheque_number, bank_name, cheque_date, transaction_type, reference_id, customer_id, party_name, notes, status) VALUES (?,?,?,?,?,?,'customer_refund',?,?,?,?,?)");
            $pay_st->bind_param('ssdsssiisss', $return_date, $refund_method, $return_amount, $cheque_number, $bank_name, $cheque_date, $bike_id, $bike_info['customer_id'], $party_name, $return_notes, $payment_status);
            $pay_st->execute();
            $pay_st->close();
            $led_st1 = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'credit',?,'customer',?,?,'return_reversal',?,?)");
            $desc1 = 'Bike Return (Reversal) for Chassis: ' . $bike_info['chassis_number'];
            $led_st1->bind_param('sdisid', $return_date, $full_reversal_amount, $bike_info['customer_id'], $desc1, $bike_id, $full_reversal_amount);
            $led_st1->execute();
            $led_st1->close();
            if ($return_amount > 0) {
                $led_st2 = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'debit',?,'customer',?,?,'return_refund',?,?)");
                $desc2 = 'Refund given for Chassis: ' . $bike_info['chassis_number'];
                $led_st2->bind_param('sdisid', $return_date, $return_amount, $bike_info['customer_id'], $desc2, $bike_id, $return_amount);
                $led_st2->execute();
                $led_st2->close();
            }
            $conn->query("UPDATE installments SET status='cancelled', notes=CONCAT(IFNULL(notes,''),' | Auto-cancelled on return') WHERE bike_id=$bike_id AND status IN ('pending','overdue')");
            $acc_restore = $conn->query("SELECT accessory_id, quantity FROM sale_accessories WHERE bike_id=$bike_id");
            if ($acc_restore) {
                while ($ar = $acc_restore->fetch_assoc()) {
                    $conn->query("UPDATE accessories SET current_stock = current_stock + {$ar['quantity']} WHERE id={$ar['accessory_id']}");
                }
            }
            $conn->query("DELETE FROM deposit_allocations WHERE bike_id=$bike_id");
            $conn->query("DELETE FROM sale_money_allocations WHERE bike_id=$bike_id");
            $conn->commit();
            $msg = 'Return processed successfully. Installments cancelled, accessory stock restored, allocations cleared.';
        } catch (Exception $e) {
            $conn->rollback();
            $err = 'Return transaction failed: ' . $e->getMessage();
        }
        header('Location: index.php?page=returns&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
        end_returns_post:;
    }
    if ($page === 'payments' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_permission($conn, 'payments', 'edit');
        if ($action === 'status') {
            $pid = (int) ($_POST['id'] ?? 0);
            $new_status = clean_text($_POST['status'] ?? '');
            if (!in_array($new_status, ['pending', 'cleared', 'bounced', 'cancelled'])) {
                $err = 'Invalid status.';
                goto end_payments_post;
            }
            $pay_q = $conn->query("SELECT * FROM payments WHERE id=$pid AND payment_type='cheque'");
            $pay = $pay_q ? $pay_q->fetch_assoc() : null;
            if ($pay) {
                $old_status = $pay['status'] ?? 'pending';
                if ($old_status === 'bounced' && $new_status !== 'bounced') {
                    $err = 'A bounced cheque is an accounting event and cannot be reactivated. Post a correcting payment instead.';
                    goto end_payments_post;
                }
                if ($old_status === 'cancelled' && $new_status !== 'cancelled') {
                    $err = 'A cancelled cheque cannot be reactivated. Post a new payment instead.';
                    goto end_payments_post;
                }
                if ($old_status === $new_status) {
                    $msg = 'Payment status is already up to date.';
                    goto end_payments_post;
                }
                if ($old_status !== 'bounced' && $new_status === 'bounced') {
                    $conn->begin_transaction();
                    try {
                        $stmt = $conn->prepare('UPDATE payments SET status=? WHERE id=?');
                        $stmt->bind_param('si', $new_status, $pid);
                        $stmt->execute();
                        $bounced_date = date('Y-m-d');
                        if (in_array($pay['transaction_type'], ['sale', 'installment'])) {
                            $cust_id = (int) ($pay['customer_id'] ?? 0);
                            if ($cust_id === 0 && $pay['transaction_type'] === 'sale') {
                                $br = $conn->query('SELECT customer_id FROM bikes WHERE id=' . (int) $pay['reference_id']);
                                $cust_id = $br && $br->num_rows > 0 ? (int) $br->fetch_assoc()['customer_id'] : 0;
                            } elseif ($cust_id === 0) {
                                $ir = $conn->query('SELECT customer_id FROM installments WHERE id=' . (int) $pay['reference_id']);
                                $cust_id = $ir && $ir->num_rows > 0 ? (int) $ir->fetch_assoc()['customer_id'] : 0;
                            }
                            if ($cust_id === 0 && !empty($pay['party_name'])) {
                                $cr = $conn->query("SELECT id FROM customers WHERE name='" . mysqli_real_escape_string($conn, $pay['party_name']) . "' LIMIT 1");
                                $cust_id = $cr && $cr->num_rows > 0 ? (int) $cr->fetch_assoc()['id'] : 0;
                            }
                            if ($cust_id > 0) {
                                $led_st = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'debit',?,'customer',?,?,'cheque_bounce',?,?)");
                                $desc = 'Cheque Bounced (Ref Payment #' . $pid . ')';
                                $led_st->bind_param('sdisid', $bounced_date, $pay['amount'], $cust_id, $desc, $pid, $pay['amount']);
                                $led_st->execute();
                            }
                        } elseif (in_array($pay['transaction_type'], ['supplier_payment', 'supplier_refund'])) {
                            $sup_id = (int) ($pay['supplier_id'] ?? 0);
                            if ($sup_id === 0 && $pay['reference_id'] > 0 && $pay['transaction_type'] === 'supplier_payment') {
                                $sr = $conn->query('SELECT supplier_id FROM purchase_orders WHERE id=' . (int) $pay['reference_id']);
                                $sup_id = $sr && $sr->num_rows > 0 ? (int) $sr->fetch_assoc()['supplier_id'] : 0;
                            }
                            if ($sup_id === 0 && !empty($pay['party_name'])) {
                                $sr2 = $conn->query("SELECT id FROM suppliers WHERE name='" . mysqli_real_escape_string($conn, $pay['party_name']) . "' LIMIT 1");
                                $sup_id = $sr2 && $sr2->num_rows > 0 ? (int) $sr2->fetch_assoc()['id'] : 0;
                            }
                            if ($sup_id > 0) {
                                $entry_type = $pay['transaction_type'] === 'supplier_payment' ? 'credit' : 'debit';
                                $led_st = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,?,?,'supplier',?,?,'cheque_bounce',?,?)");
                                $desc = 'Supplier Cheque Bounced (Ref Payment #' . $pid . ')';
                                $led_st->bind_param('ssdisid', $bounced_date, $entry_type, $pay['amount'], $sup_id, $desc, $pid, $pay['amount']);
                                $led_st->execute();
                            }
                        }
                        if ($pay['transaction_type'] === 'installment') {
                            $inst_id = (int) $pay['reference_id'];
                            $inst_q = $conn->query("SELECT amount_paid, penalty_fee, COALESCE(penalty_paid,0) AS penalty_paid, installment_amount FROM installments WHERE id=$inst_id FOR UPDATE");
                            if ($inst_q && $inst_q->num_rows > 0) {
                                $inst = $inst_q->fetch_assoc();
                                $pa_stmt = $conn->prepare('SELECT principal_amount, penalty_amount FROM installment_payment_allocations WHERE payment_id=? AND installment_id=? LIMIT 1');
                                $pa_stmt->bind_param('ii', $pid, $inst_id);
                                $pa_stmt->execute();
                                $payment_allocation = $pa_stmt->get_result()->fetch_assoc();
                                $principal_deduct = $payment_allocation ? (float) $payment_allocation['principal_amount'] : min((float) $pay['amount'], (float) $inst['amount_paid']);
                                $penalty_deduct = $payment_allocation ? (float) $payment_allocation['penalty_amount'] : max(0, (float) $pay['amount'] - $principal_deduct);
                                $new_amount_paid = max(0, (float) $inst['amount_paid'] - $principal_deduct);
                                $new_penalty_paid = max(0, (float) $inst['penalty_paid'] - $penalty_deduct);
                                $new_inst_status = ($new_amount_paid >= $inst['installment_amount'] && $new_penalty_paid >= $inst['penalty_fee']) ? 'paid' : 'pending';
                                $reverse_stmt = $conn->prepare('UPDATE installments SET amount_paid=?, penalty_paid=?, status=? WHERE id=?');
                                $reverse_stmt->bind_param('ddsi', $new_amount_paid, $new_penalty_paid, $new_inst_status, $inst_id);
                                $reverse_stmt->execute();
                            }
                        }
                        if ($pay['transaction_type'] !== 'installment') {
                            $mapped_stmt = $conn->prepare('SELECT installment_id, principal_amount, penalty_amount FROM installment_payment_allocations WHERE payment_id=?');
                            $mapped_stmt->bind_param('i', $pid);
                            $mapped_stmt->execute();
                            $mapped_rows = $mapped_stmt->get_result();
                            while ($mapped = $mapped_rows->fetch_assoc()) {
                                $mapped_inst_id = (int) $mapped['installment_id'];
                                $mapped_inst_q = $conn->query("SELECT installment_amount, amount_paid, penalty_fee, COALESCE(penalty_paid,0) AS penalty_paid FROM installments WHERE id=$mapped_inst_id FOR UPDATE");
                                $mapped_inst = $mapped_inst_q ? $mapped_inst_q->fetch_assoc() : null;
                                if (!$mapped_inst) continue;
                                $mapped_principal = max(0, (float) $mapped_inst['amount_paid'] - (float) $mapped['principal_amount']);
                                $mapped_penalty = max(0, (float) $mapped_inst['penalty_paid'] - (float) $mapped['penalty_amount']);
                                $mapped_status = ($mapped_principal >= $mapped_inst['installment_amount'] && $mapped_penalty >= $mapped_inst['penalty_fee']) ? 'paid' : 'pending';
                                $mapped_update = $conn->prepare('UPDATE installments SET amount_paid=?, penalty_paid=?, status=? WHERE id=?');
                                $mapped_update->bind_param('ddsi', $mapped_principal, $mapped_penalty, $mapped_status, $mapped_inst_id);
                                $mapped_update->execute();
                            }
                        }
                        $conn->commit();
                        $msg = 'Cheque marked as bounced. Accounting & installments reversed successfully.';
                    } catch (Exception $e) {
                        $conn->rollback();
                        $err = 'Failed to process bounced cheque: ' . $e->getMessage();
                    }
                } else {
                    $stmt = $conn->prepare('UPDATE payments SET status=? WHERE id=?');
                    $stmt->bind_param('si', $new_status, $pid);
                    $stmt->execute();
                    $msg = 'Payment status updated.';
                }
            } else {
                $err = 'Payment not found or not a cheque.';
            }
        }
        if ($action === 'delete') {
            require_permission($conn, 'payments', 'delete');
            $err = 'Payment deletion is disabled to protect accounting integrity. Use cheque status updates or post a correcting entry instead.';
        }
        header('Location: index.php?page=payments&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
        end_payments_post:;
    }
    if ($page === 'installments' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        require_permission($conn, 'installments', 'edit');
        if ($action === 'pay_installment') {
            $installment_id = (int) ($_POST['installment_id'] ?? 0);
            $payment_date = clean_text($_POST['payment_date'] ?? date('Y-m-d'));
            $payment_type = clean_text($_POST['payment_type'] ?? 'cash');
            $amount_paid = (float) ($_POST['amount_paid'] ?? 0);
            $penalty_fee = (float) ($_POST['penalty_fee'] ?? 0);
            $cheque_number = clean_text($_POST['cheque_number'] ?? '');
            $bank_name = clean_text($_POST['bank_name'] ?? '');
            $cheque_date = !empty($_POST['cheque_date']) ? $_POST['cheque_date'] : null;
            if ($installment_id <= 0 || empty($payment_date) || $amount_paid < 0 || $penalty_fee < 0 || ($amount_paid + $penalty_fee) <= 0) {
                $err = 'Missing required installment payment details.';
                goto end_installments_post;
            }
            $conn->begin_transaction();
            try {
                $inst_q = $conn->query("SELECT i.bike_id, i.customer_id, i.installment_amount, i.amount_paid, i.penalty_fee, COALESCE(i.penalty_paid,0) AS penalty_paid, i.status, b.chassis_number, c.name AS cust_name FROM installments i JOIN bikes b ON i.bike_id=b.id JOIN customers c ON i.customer_id=c.id WHERE i.id=$installment_id FOR UPDATE");
                $inst = $inst_q->fetch_assoc();
                if (!$inst) {
                    throw new Exception('Installment not found.');
                }
                if (!in_array($inst['status'], ['pending', 'overdue'], true)) {
                    throw new Exception('Only pending or overdue installments can receive payments.');
                }
                assert_valid_payment_method($payment_type);
                if (!valid_date($payment_date) || $penalty_fee < 0) {
                    throw new Exception('Payment date or penalty amount is invalid.');
                }
                $principal_remaining = round((float) $inst['installment_amount'] - (float) $inst['amount_paid'], 2);
                if ($amount_paid - $principal_remaining > 0.0001) {
                    throw new Exception('Principal payment exceeds the remaining installment balance.');
                }
                $total_payment = $amount_paid + $penalty_fee;
                $payment_notes = 'Installment payment for Chassis ' . $inst['chassis_number'] . " (ID: $installment_id)";
                $payment_status = $payment_type === 'cheque' ? 'pending' : 'cleared';
                $pay_st = $conn->prepare("INSERT INTO payments (payment_date, payment_type, amount, cheque_number, bank_name, cheque_date, transaction_type, reference_id, customer_id, party_name, notes, status) VALUES (?,?,?,?,?,?,'installment',?,?,?,?,?)");
                $pay_st->bind_param('ssdsssiisss', $payment_date, $payment_type, $total_payment, $cheque_number, $bank_name, $cheque_date, $installment_id, $inst['customer_id'], $inst['cust_name'], $payment_notes, $payment_status);
                $pay_st->execute();
                $payment_id = $conn->insert_id;
                $new_amount_paid = $inst['amount_paid'] + $amount_paid;
                $existing_penalty_due = max(0, (float) $inst['penalty_fee'] - (float) $inst['penalty_paid']);
                $new_penalty_assessment = max(0, $penalty_fee - $existing_penalty_due);
                $new_penalty_fee = (float) $inst['penalty_fee'] + $new_penalty_assessment;
                $new_penalty_paid = $inst['penalty_paid'] + $penalty_fee;
                $new_status = ($new_amount_paid >= $inst['installment_amount'] && $new_penalty_paid >= $new_penalty_fee) ? 'paid' : 'pending';
                $upd_inst_stmt = $conn->prepare('UPDATE installments SET amount_paid=?, penalty_fee=?, penalty_paid=?, status=?, payment_id=? WHERE id=?');
                $upd_inst_stmt->bind_param('dddsii', $new_amount_paid, $new_penalty_fee, $new_penalty_paid, $new_status, $payment_id, $installment_id);
                $upd_inst_stmt->execute();
                $alloc_pay_stmt = $conn->prepare('INSERT INTO installment_payment_allocations (payment_id, installment_id, principal_amount, penalty_amount) VALUES (?,?,?,?)');
                $alloc_pay_stmt->bind_param('iidd', $payment_id, $installment_id, $amount_paid, $penalty_fee);
                $alloc_pay_stmt->execute();
                if ($new_penalty_assessment > 0) {
                    $led_pen = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'debit',?,'customer',?,?,'penalty',?,?)");
                    $desc_pen = 'Penalty fee for Chassis: ' . $inst['chassis_number'];
                    $led_pen->bind_param('sdisid', $payment_date, $new_penalty_assessment, $inst['customer_id'], $desc_pen, $installment_id, $new_penalty_assessment);
                    $led_pen->execute();
                }
                $led_st = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'credit',?,'customer',?,?,'installment',?,?)");
                $desc = 'Installment payment for Chassis: ' . $inst['chassis_number'];
                $led_st->bind_param('sdisid', $payment_date, $total_payment, $inst['customer_id'], $desc, $installment_id, $total_payment);
                $led_st->execute();
                $conn->commit();
                $msg = 'Installment recorded successfully.';
            } catch (Exception $e) {
                $conn->rollback();
                $err = 'Installment payment failed: ' . $e->getMessage();
            }
            header('Location: index.php?page=installments&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        end_installments_post:;
    }
    if ($page === 'money_destinations' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['save_destination'])) {
            require_permission($conn, 'money_destinations', isset($_POST['id']) && (int) $_POST['id'] > 0 ? 'edit' : 'add');
            $id = (int) ($_POST['id'] ?? 0);
            $type = clean_text($_POST['type'] ?? 'bank');
            $name = clean_text($_POST['name'] ?? '');
            $details = clean_text($_POST['details'] ?? '');
            $account_title = clean_text($_POST['account_title'] ?? '');
            $account_no = clean_text($_POST['account_no'] ?? '');
            $branch = clean_text($_POST['branch'] ?? '');
            $opening_balance = (float) ($_POST['opening_balance'] ?? 0);
            $contact_person = clean_text($_POST['contact_person'] ?? '');
            $contact_phone = clean_text($_POST['contact_phone'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            if (empty($name) || !in_array($type, ['bank', 'person', 'wallet'], true) || $opening_balance < 0) {
                $err = 'Name and Type are required.';
                goto end_money_dest_post;
            }
            if ($id) {
                $stmt = $conn->prepare('UPDATE money_destinations SET type=?, name=?, details=?, account_title=?, account_no=?, branch=?, opening_balance=?, contact_person=?, contact_phone=?, is_active=? WHERE id=?');
                $stmt->bind_param('ssssssdssii', $type, $name, $details, $account_title, $account_no, $branch, $opening_balance, $contact_person, $contact_phone, $is_active, $id);
                $stmt->execute();
                $msg = 'Destination updated successfully.';
            } else {
                $stmt = $conn->prepare('INSERT INTO money_destinations (type, name, details, account_title, account_no, branch, opening_balance, contact_person, contact_phone, is_active) VALUES (?,?,?,?,?,?,?,?,?,?)');
                $stmt->bind_param('ssssssdssi', $type, $name, $details, $account_title, $account_no, $branch, $opening_balance, $contact_person, $contact_phone, $is_active);
                $stmt->execute();
                $msg = 'Destination added successfully.';
            }
            header('Location: index.php?page=money_destinations&msg=' . urlencode($msg));
            exit;
        }
        if (isset($_POST['delete_destination'])) {
            require_permission($conn, 'money_destinations', 'delete');
            $id = (int) $_POST['id'];
            $chk = $conn->prepare('SELECT COUNT(*) FROM sale_money_allocations WHERE destination_id = ?');
            $chk->bind_param('i', $id);
            $chk->execute();
            $alloc_count = $chk->get_result()->fetch_row()[0];
            if ($alloc_count > 0) {
                $err = 'Cannot delete: This destination has ' . $alloc_count . ' allocation(s) linked to it.';
            } else {
                $stmt = $conn->prepare('DELETE FROM money_destinations WHERE id=?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $msg = 'Destination deleted successfully.';
            }
            header('Location: index.php?page=money_destinations&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        end_money_dest_post:;
    }
    if ($page === 'money_tracking' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['save_allocation'])) {
            require_permission($conn, 'money_tracking', isset($_POST['id']) && (int) $_POST['id'] > 0 ? 'edit' : 'add');
            $id = (int) ($_POST['id'] ?? 0);
            $bike_id = (int) ($_POST['bike_id'] ?? 0);
            $destination_id = (int) ($_POST['destination_id'] ?? 0);
            $amount = (float) ($_POST['amount'] ?? 0);
            $allocation_date = clean_text($_POST['allocation_date'] ?? date('Y-m-d'));
            $alloc_notes = clean_text($_POST['alloc_notes'] ?? '');
            $user = current_user($conn);
            $created_by = $user ? $user['id'] : null;
            if ($bike_id <= 0 || $destination_id <= 0 || $amount <= 0 || !valid_date($allocation_date)) {
                $err = 'All fields are required and amount must be greater than 0.';
                goto end_money_track_post;
            }
            $destination_stmt = $conn->prepare('SELECT is_active FROM money_destinations WHERE id=? LIMIT 1');
            $destination_stmt->bind_param('i', $destination_id);
            $destination_stmt->execute();
            $destination_row = $destination_stmt->get_result()->fetch_assoc();
            if (!$destination_row || (!$id && !(int) $destination_row['is_active'])) {
                $err = 'Selected money destination does not exist or is inactive.';
                goto end_money_track_post;
            }
            $conn->begin_transaction();
            try {
                assert_allocation_within_sale_total($conn, $bike_id, $amount, $id);
            } catch (Exception $e) {
                $conn->rollback();
                $err = $e->getMessage();
                goto end_money_track_post;
            }
            if ($id) {
                $alloc_chk = $conn->prepare('SELECT bike_id, destination_id, COALESCE((SELECT SUM(amount) FROM deposit_allocations WHERE allocation_id=sale_money_allocations.id),0) AS deposited_total FROM sale_money_allocations WHERE id=? LIMIT 1');
                $alloc_chk->bind_param('i', $id);
                $alloc_chk->execute();
                $alloc_row = $alloc_chk->get_result()->fetch_assoc();
                if (!$alloc_row) {
                    $conn->rollback();
                    $err = 'Allocation record not found.';
                    goto end_money_track_post;
                }
                $deposited_total = (float) ($alloc_row['deposited_total'] ?? 0);
                if ($deposited_total - $amount > 0.0001) {
                    $conn->rollback();
                    $err = 'Allocation amount cannot be less than the amount already deposited against it.';
                    goto end_money_track_post;
                }
                if ($deposited_total > 0 && ((int) $alloc_row['bike_id'] !== $bike_id || (int) $alloc_row['destination_id'] !== $destination_id)) {
                    $conn->rollback();
                    $err = 'Bike and destination cannot be changed after deposits have been linked to this allocation.';
                    goto end_money_track_post;
                }
                $stmt = $conn->prepare('UPDATE sale_money_allocations SET bike_id=?, destination_id=?, amount=?, allocation_date=?, notes=? WHERE id=?');
                $stmt->bind_param('iidssi', $bike_id, $destination_id, $amount, $allocation_date, $alloc_notes, $id);
                $stmt->execute();
                $msg = 'Allocation updated successfully.';
            } else {
                $stmt = $conn->prepare('INSERT INTO sale_money_allocations (bike_id, destination_id, amount, allocation_date, notes, created_by) VALUES (?,?,?,?,?,?)');
                $stmt->bind_param('iidssi', $bike_id, $destination_id, $amount, $allocation_date, $alloc_notes, $created_by);
                $stmt->execute();
                $msg = 'Allocation recorded successfully.';
            }
            $conn->commit();
            header('Location: index.php?page=money_tracking&msg=' . urlencode($msg));
            exit;
        }
        if (isset($_POST['delete_allocation'])) {
            require_permission($conn, 'money_tracking', 'delete');
            $id = (int) $_POST['id'];
            $dep_chk = $conn->prepare('SELECT COUNT(*) FROM deposit_allocations WHERE allocation_id=?');
            $dep_chk->bind_param('i', $id);
            $dep_chk->execute();
            $dep_count = (int) $dep_chk->get_result()->fetch_row()[0];
            if ($dep_count > 0) {
                $err = 'Allocation cannot be deleted because bank deposits are already linked to it.';
                header('Location: index.php?page=money_tracking&err=' . urlencode($err));
                exit;
            }
            $stmt = $conn->prepare('DELETE FROM sale_money_allocations WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $msg = 'Allocation deleted successfully.';
            header('Location: index.php?page=money_tracking&msg=' . urlencode($msg));
            exit;
        }
        end_money_track_post:;
    }
    if ($page === 'bank_deposits' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['save_deposit'])) {
            require_permission($conn, 'bank_deposits', isset($_POST['id']) && (int) $_POST['id'] > 0 ? 'edit' : 'add');
            $id = (int) ($_POST['id'] ?? 0);
            $destination_id = (int) ($_POST['destination_id'] ?? 0);
            $deposit_date = clean_text($_POST['deposit_date'] ?? date('Y-m-d'));
            $amount = (float) ($_POST['amount'] ?? 0);
            $deposit_type = clean_text($_POST['deposit_type'] ?? 'cash');
            $reference_no = clean_text($_POST['reference_no'] ?? '');
            $deposited_by = clean_text($_POST['deposited_by'] ?? '');
            $notes = clean_text($_POST['deposit_notes'] ?? '');
            $user = current_user($conn);
            $created_by = $user ? $user['id'] : null;
            if ($destination_id <= 0 || $amount <= 0 || !valid_date($deposit_date) || !in_array($deposit_type, ['cash', 'cheque', 'transfer', 'online', 'other'], true)) {
                $err = 'Destination, amount and date are required.';
                goto end_bank_deposits_post;
            }
            $bank_destination_stmt = $conn->prepare("SELECT is_active FROM money_destinations WHERE id=? AND type='bank' LIMIT 1");
            $bank_destination_stmt->bind_param('i', $destination_id);
            $bank_destination_stmt->execute();
            $bank_destination = $bank_destination_stmt->get_result()->fetch_assoc();
            if (!$bank_destination || (!$id && !(int) $bank_destination['is_active'])) {
                $err = 'Selected bank destination does not exist or is inactive.';
                goto end_bank_deposits_post;
            }
            $bike_links = $_POST['bike_link'] ?? [];
            $receipt_path = null;
            if (isset($_FILES['receipt_image']) && !empty($_FILES['receipt_image']['name'])) {
                if ($_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
                    $receipt_path = handle_receipt_upload($_FILES['receipt_image']);
                    if (!$receipt_path)
                        $err .= 'Receipt upload failed. ';
                } else {
                    $err .= 'Receipt upload error. ';
                }
            }
            $conn->begin_transaction();
            try {
                if ($id) {
                    $dep_stmt = $conn->prepare('SELECT destination_id FROM bank_deposits WHERE id=? LIMIT 1 FOR UPDATE');
                    $dep_stmt->bind_param('i', $id);
                    $dep_stmt->execute();
                    $dep_row = $dep_stmt->get_result()->fetch_assoc();
                    if (!$dep_row) {
                        throw new Exception('Deposit record not found.');
                    }
                    $linked_total = get_total_linked_deposit_amount($conn, $id);
                    if ($linked_total - $amount > 0.0001) {
                        throw new Exception('Deposit amount cannot be less than the total amount already linked to bike allocations.');
                    }
                    if ($linked_total > 0 && (int) $dep_row['destination_id'] !== $destination_id) {
                        throw new Exception('Destination cannot be changed after bike allocations have been linked to this deposit.');
                    }
                    if ($receipt_path) {
                        $stmt = $conn->prepare('UPDATE bank_deposits SET destination_id=?, deposit_date=?, amount=?, deposit_type=?, reference_no=?, receipt_image=?, deposited_by=?, notes=? WHERE id=?');
                        $stmt->bind_param('isdsssssi', $destination_id, $deposit_date, $amount, $deposit_type, $reference_no, $receipt_path, $deposited_by, $notes, $id);
                    } else {
                        $stmt = $conn->prepare('UPDATE bank_deposits SET destination_id=?, deposit_date=?, amount=?, deposit_type=?, reference_no=?, deposited_by=?, notes=? WHERE id=?');
                        $stmt->bind_param('isdssssi', $destination_id, $deposit_date, $amount, $deposit_type, $reference_no, $deposited_by, $notes, $id);
                    }
                    $stmt->execute();
                    if (isset($_POST['bike_link']) && is_array($_POST['bike_link'])) {
                        $linked_amount = 0.0;
                        foreach ($bike_links as $link) {
                            $linked_amount += max(0, (float) ($link['amount'] ?? 0));
                        }
                        if ($linked_amount - $amount > 0.0001) {
                            throw new Exception('Linked bike amounts cannot exceed the deposit amount.');
                        }
                        replace_deposit_links($conn, $id, $destination_id, $bike_links);
                    }
                    $msg = 'Deposit updated successfully.';
                } else {
                    $stmt = $conn->prepare('INSERT INTO bank_deposits (destination_id, deposit_date, amount, deposit_type, reference_no, receipt_image, deposited_by, notes, created_by) VALUES (?,?,?,?,?,?,?,?,?)');
                    $stmt->bind_param('isdsssssi', $destination_id, $deposit_date, $amount, $deposit_type, $reference_no, $receipt_path, $deposited_by, $notes, $created_by);
                    $stmt->execute();
                    $deposit_id = $conn->insert_id;
                    $msg = 'Deposit recorded successfully.';
                    $linked_amount = 0.0;
                    foreach ($bike_links as $link) {
                        $la = (float) ($link['amount'] ?? 0);
                        if ($la > 0) {
                            $linked_amount += $la;
                        }
                    }
                    if ($linked_amount - $amount > 0.0001) {
                        throw new Exception('Linked bike amounts cannot exceed the deposit amount.');
                    }
                    if (!empty($bike_links)) {
                        replace_deposit_links($conn, $deposit_id, $destination_id, $bike_links);
                    }
                }
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                if ($receipt_path && is_file($receipt_path)) {
                    @unlink($receipt_path);
                }
                $err = 'Deposit transaction failed: ' . $e->getMessage();
            }
            header('Location: index.php?page=bank_deposits&msg=' . urlencode($msg) . '&err=' . urlencode($err));
            exit;
        }
        if (isset($_POST['delete_deposit'])) {
            require_permission($conn, 'bank_deposits', 'delete');
            $id = (int) $_POST['id'];
            $dep = $conn->query("SELECT receipt_image FROM bank_deposits WHERE id=$id")->fetch_assoc();
            $stmt = $conn->prepare('DELETE FROM bank_deposits WHERE id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            if ($dep && $dep['receipt_image'] && file_exists($dep['receipt_image'])) {
                unlink($dep['receipt_image']);
            }
            $msg = 'Deposit deleted successfully.';
            header('Location: index.php?page=bank_deposits&msg=' . urlencode($msg));
            exit;
        }
        end_bank_deposits_post:;
    }
    if ($page === 'inventory' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'delete') {
            require_permission($conn, 'inventory', 'delete');
            $bid = (int) ($_POST['id'] ?? 0);
            $stmt_check_sold = $conn->prepare('SELECT status, purchase_order_id FROM bikes WHERE id = ?');
            $stmt_check_sold->bind_param('i', $bid);
            $stmt_check_sold->execute();
            $delete_bike_row = $stmt_check_sold->get_result()->fetch_assoc();
            $conn->begin_transaction();
            $stmt = $conn->prepare('DELETE FROM bikes WHERE id=?');
            $stmt->bind_param('i', $bid);
            $stmt->execute();
            sync_purchase_order_totals($conn, (int) ($delete_bike_row['purchase_order_id'] ?? 0));
            $conn->commit();
            $msg = 'Bike deleted from inventory.';
        }
        if ($action === 'edit') {
            require_permission($conn, 'inventory', 'edit');
            $bid = (int) ($_POST['id'] ?? 0);
            $model_id = (int) ($_POST['model_id'] ?? 0);
            $color = clean_text($_POST['color'] ?? '');
            $pp = (float) ($_POST['purchase_price'] ?? 0);
            $status = clean_text($_POST['status'] ?? 'in_stock');
            $notes = clean_text($_POST['notes'] ?? '');
            $safe = clean_text($_POST['safeguard_notes'] ?? '');
            $order_date = clean_text($_POST['order_date'] ?? '');
            $inventory_date = clean_text($_POST['inventory_date'] ?? '');
            $chassis_number = clean_text($_POST['chassis_number'] ?? '');
            $motor_number = clean_text($_POST['motor_number'] ?? '');
            $img_path = null;
            $img_err = '';
            if (isset($_FILES['image']) && !empty($_FILES['image']['name'])) {
                if ($_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $img_path = handle_bike_image_upload($_FILES['image']);
                    if (!$img_path)
                        $img_err = 'Image processing failed. ';
                } else {
                    $img_err = 'Upload failed (max size ' . ini_get('upload_max_filesize') . ' exceeded). ';
                }
            }
            if ($bid <= 0 || $pp < 0 || $model_id <= 0 || !in_array($status, ['in_stock', 'reserved', 'damaged_lost', 'sold', 'returned', 'returned_to_supplier'], true)) {
                $err = 'Invalid bike ID, model, or purchase price.';
            } else {
                $old_bike_q = $conn->query("SELECT status, chassis_number, selling_price, purchase_order_id, tax_rate_applied, tax_basis FROM bikes WHERE id=$bid");
                $old_bike = $old_bike_q ? $old_bike_q->fetch_assoc() : null;
                if (!$old_bike) {
                    $err = 'Bike record not found.';
                    goto end_inventory_post;
                }
                if ($chassis_number !== '' && $chassis_number !== $old_bike['chassis_number']) {
                    $chk = $conn->query("SELECT id FROM bikes WHERE chassis_number='" . mysqli_real_escape_string($conn, $chassis_number) . "' AND id != $bid");
                    if ($chk && $chk->num_rows > 0) {
                        $err = 'Chassis number "' . sanitize($chassis_number) . '" already exists in the system.';
                        goto end_inventory_post;
                    }
                }
                $old_status = $old_bike['status'] ?? '';
                try {
                    assert_valid_bike_status_transition($old_status, $status);
                } catch (Exception $e) {
                    $err = $e->getMessage();
                    goto end_inventory_post;
                }
                $conn->begin_transaction();
                try {
                $recalc_tax = !empty($_POST['recalc_tax']);
                if ($recalc_tax) {
                    $use_tax_basis = $tax_on;
                    $use_tax_rate = $tax_rate;
                } else {
                    $use_tax_basis = $old_bike['tax_basis'] ?: $tax_on;
                    $use_tax_rate = $old_bike['tax_rate_applied'] !== null ? (float) $old_bike['tax_rate_applied'] : $tax_rate;
                }
                $base_tax = ($use_tax_basis === 'selling_price') ? (float) ($old_bike['selling_price'] ?: $pp) : $pp;
                $tax_amount = ($base_tax * $use_tax_rate);
                $margin = (float) $old_bike['selling_price'] > 0 ? ((float) $old_bike['selling_price'] - $pp - $tax_amount) : 0;
                if ($img_path) {
                    $stmt = $conn->prepare('UPDATE bikes SET model_id=?, color=?, purchase_price=?, tax_amount=?, margin=?, status=?, notes=?, safeguard_notes=?, image=?, order_date=?, inventory_date=?, chassis_number=?, motor_number=?' . ($recalc_tax ? ', tax_rate_applied=?, tax_basis=?' : '') . ' WHERE id=?');
                    if ($recalc_tax) {
                        $stmt->bind_param('isddssssssssdsi', $model_id, $color, $pp, $tax_amount, $margin, $status, $notes, $safe, $img_path, $order_date, $inventory_date, $chassis_number, $motor_number, $use_tax_rate, $use_tax_basis, $bid);
                    } else {
                        $stmt->bind_param('isddssssssssi', $model_id, $color, $pp, $tax_amount, $margin, $status, $notes, $safe, $img_path, $order_date, $inventory_date, $chassis_number, $motor_number, $bid);
                    }
                } else {
                    $stmt = $conn->prepare('UPDATE bikes SET model_id=?, color=?, purchase_price=?, tax_amount=?, margin=?, status=?, notes=?, safeguard_notes=?, order_date=?, inventory_date=?, chassis_number=?, motor_number=?' . ($recalc_tax ? ', tax_rate_applied=?, tax_basis=?' : '') . ' WHERE id=?');
                    if ($recalc_tax) {
                        $stmt->bind_param('isddsssssssdsi', $model_id, $color, $pp, $tax_amount, $margin, $status, $notes, $safe, $order_date, $inventory_date, $chassis_number, $motor_number, $use_tax_rate, $use_tax_basis, $bid);
                    } else {
                        $stmt->bind_param('isddsssssssi', $model_id, $color, $pp, $tax_amount, $margin, $status, $notes, $safe, $order_date, $inventory_date, $chassis_number, $motor_number, $bid);
                    }
                }
                $stmt->execute();
                sync_purchase_order_totals($conn, (int) ($old_bike['purchase_order_id'] ?? 0));
                if ($old_status !== $status) {
                    $history_stmt = $conn->prepare('INSERT INTO inventory_status_history (bike_id, chassis_number, old_status, new_status, changed_by, change_reason) VALUES (?,?,?,?,?,?)');
                    $change_reason = $notes !== '' ? $notes : 'Inventory edit';
                    $changed_by = (int) $_SESSION['user_id'];
                    $history_stmt->bind_param('isssis', $bid, $old_bike['chassis_number'], $old_status, $status, $changed_by, $change_reason);
                    $history_stmt->execute();
                }
                if ($old_status !== 'damaged_lost' && $status === 'damaged_lost') {
                    $entry_date = date('Y-m-d');
                    $category = 'Inventory Loss';
                    $reference = 'Bike ID: ' . $bid . ' (' . $old_bike['chassis_number'] . ')';
                    $exp_notes = 'Automated expense for Damaged/Lost bike.';
                    $created_by = $_SESSION['user_id'];
                    $exp_stmt = $conn->prepare("INSERT INTO income_expenses (entry_date, type, category, amount, payment_method, reference, notes, created_by) VALUES (?,'expense',?,?, 'other', ?, ?, ?)");
                    $exp_stmt->bind_param('ssdssi', $entry_date, $category, $pp, $reference, $exp_notes, $created_by);
                    $exp_stmt->execute();
                } elseif ($old_status === 'damaged_lost' && $status !== 'damaged_lost') {
                    $reference = 'Bike ID: ' . $bid . ' (' . $old_bike['chassis_number'] . ')';
                    $del_exp = $conn->prepare("DELETE FROM income_expenses WHERE category='Inventory Loss' AND reference=?");
                    $del_exp->bind_param('s', $reference);
                    $del_exp->execute();
                } elseif ($old_status === 'damaged_lost' && $status === 'damaged_lost') {
                    $reference = 'Bike ID: ' . $bid . ' (' . $old_bike['chassis_number'] . ')';
                    $upd_exp = $conn->prepare("UPDATE income_expenses SET amount=? WHERE category='Inventory Loss' AND reference=?");
                    $upd_exp->bind_param('ds', $pp, $reference);
                    $upd_exp->execute();
                }
                $msg = 'Bike updated. ' . $img_err;
                if ($img_err)
                    $err = trim($img_err);
                    $conn->commit();
                } catch (Exception $e) {
                    $conn->rollback();
                    $err = 'Bike update failed: ' . $e->getMessage();
                }
            }
        }
        if ($action === 'bulk_delete') {
            require_permission($conn, 'inventory', 'delete');
            $ids = $_POST['selected_bikes'] ?? [];
            $ids = array_map('intval', $ids);
            if (!empty($ids)) {
                $conn->begin_transaction();
                try {
                    foreach ($ids as $id) {
                        $stmt_check_sold = $conn->prepare('SELECT status, purchase_order_id FROM bikes WHERE id = ?');
                        $stmt_check_sold->bind_param('i', $id);
                        $stmt_check_sold->execute();
                        $bulk_bike_row = $stmt_check_sold->get_result()->fetch_assoc();
                        $stmt_delete = $conn->prepare('DELETE FROM bikes WHERE id = ?');
                        $stmt_delete->bind_param('i', $id);
                        $stmt_delete->execute();
                        sync_purchase_order_totals($conn, (int) ($bulk_bike_row['purchase_order_id'] ?? 0));
                    }
                    $conn->commit();
                    $msg = count($ids) . ' bike(s) deleted.';
                } catch (Exception $e) {
                    $conn->rollback();
                    $err .= 'Bulk deletion failed: ' . $e->getMessage();
                }
            }
        }
        if ($action === 'bulk_export') {
            $ids = $_POST['selected_bikes'] ?? [];
            $ids = array_map('intval', $ids);
            if (!empty($ids)) {
                $id_str = implode(',', $ids);
                $er = $conn->query("SELECT b.*, m.model_name, m.model_code FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.id IN ($id_str)");
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="inventory_export_' . date('Ymd') . '.csv"');
                $out = fopen('php://output', 'w');
                stream_csv_row($out, ['Sr', 'Chassis', 'Motor', 'Model', 'Color', 'Purchase Price', 'Tax', 'Status', 'Selling Price', 'Selling Date', 'Margin']);
                $sr = 1;
                while ($row = $er->fetch_assoc()) {
                    stream_csv_row($out, [$sr++, $row['chassis_number'], $row['motor_number'], $row['model_name'], $row['color'], $row['purchase_price'], $row['tax_amount'], $row['status'], $row['selling_price'], $row['selling_date'], $row['margin']]);
                }
                fclose($out);
                exit;
            }
        }
        header('Location: index.php?page=inventory&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
        end_inventory_post:;
    }
    if ($page === 'settings' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
        require_permission($conn, 'settings', 'edit');
        $submitted_tax_rate = $_POST['tax_rate'] ?? null;
        $submitted_tax_on = $_POST['tax_on'] ?? '';
        $submitted_idle = filter_var($_POST['session_timeout_idle'] ?? null, FILTER_VALIDATE_INT);
        $submitted_absolute = filter_var($_POST['session_timeout_absolute'] ?? null, FILTER_VALIDATE_INT);
        if (!is_numeric($submitted_tax_rate) || (float) $submitted_tax_rate < 0 || (float) $submitted_tax_rate > 100
            || !in_array($submitted_tax_on, ['purchase_price', 'selling_price'], true)
            || $submitted_idle === false || $submitted_idle < 300 || $submitted_idle > 86400
            || $submitted_absolute === false || $submitted_absolute < 900 || $submitted_absolute > 604800
            || $submitted_absolute < $submitted_idle) {
            $err = 'Settings are invalid. Tax must be 0-100%, idle timeout 300-86400 seconds, and absolute timeout 900-604800 seconds and not shorter than idle timeout.';
            goto end_settings_post;
        }
        $fields = ['company_name', 'branch_name', 'tax_rate', 'currency', 'tax_on', 'show_purchase_on_invoice', 'session_timeout_idle', 'session_timeout_absolute'];
        $st = $conn->prepare('UPDATE settings SET setting_value=? WHERE setting_key=?');
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $val = ($f === 'tax_rate') ? (string) ((float) $_POST[$f] / 100) : clean_text($_POST[$f]);
                $st->bind_param('ss', $val, $f);
                $st->execute();
            }
        }
        if (isset($_FILES['app_logo']) && $_FILES['app_logo']['error'] === UPLOAD_ERR_OK) {
            if (update_app_icons($_FILES['app_logo'])) {
                $msg .= ' Logo and icons updated.';
            } else {
                $err .= ' Failed to process logo upload.';
            }
        }
        if (!empty($_POST['new_password'])) {
            $new_password = $_POST['new_password'];
            $current_password = $_POST['current_password'] ?? '';
            $password_check = $conn->prepare('SELECT password_hash FROM users WHERE id=? LIMIT 1');
            $password_check->bind_param('i', $_SESSION['user_id']);
            $password_check->execute();
            $stored_password = $password_check->get_result()->fetch_assoc()['password_hash'] ?? '';
            if ($current_password === '' || !password_verify($current_password, $stored_password)) {
                $err = 'Current password is incorrect; password was not changed.';
                goto end_settings_post;
            }
            if (!validate_password_strength($new_password)) {
                $err = 'New password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.';
            } else {
                $np = password_hash($new_password, PASSWORD_DEFAULT);
                $pw_stmt = $conn->prepare('UPDATE users SET password_hash=? WHERE id=?');
                $uid = (int) $_SESSION['user_id'];
                $pw_stmt->bind_param('si', $np, $uid);
                $pw_stmt->execute();
                $pw_stmt->close();
                $msg .= ' Password updated.';
            }
        }
        $st->close();
        $msg .= 'Settings saved.';
        header('Location: index.php?page=settings&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
        end_settings_post:;
    }
    if ($page === 'settings' && isset($_GET['action']) && $_GET['action'] === 'backup') {
        if (!is_administrator($conn)) {
            die('Database backups are restricted to the primary Administrator.');
        }
        $sql_dump = build_full_database_dump($conn, $author);
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="bni_backup_' . date('Ymd_His') . '.sql"');
        echo $sql_dump;
        exit;
    }
    if ($page === 'settings' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_db']) && isset($_FILES['backup_file'])) {
        if (!is_administrator($conn)) {
            die('Database restore is restricted to the primary Administrator.');
        }
        $file = $_FILES['backup_file'];
        if ($file['error'] === UPLOAD_ERR_OK && strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) === 'sql' && $file['size'] <= 10485760) {
            $sql_content = file_get_contents($file['tmp_name']);
            if (!str_starts_with(ltrim($sql_content), '-- BNI Enterprises Full Database Backup')) {
                $err = 'Restore rejected: this is not a backup generated by this application.';
            } else {
                $pre_restore_dump = build_full_database_dump($conn, $author);
                [$restore_ok, $restore_error] = execute_sql_batch($conn, $sql_content);
                $conn->query('SET FOREIGN_KEY_CHECKS=1');
                if ($restore_ok) {
                $msg = 'Database restored successfully.';
                } else {
                    [$rollback_ok, $rollback_error] = execute_sql_batch($conn, $pre_restore_dump);
                    $conn->query('SET FOREIGN_KEY_CHECKS=1');
                    $err = 'Restore failed and the pre-restore snapshot was ' . ($rollback_ok ? 'restored successfully.' : 'not fully restored: ' . $rollback_error) . ' Cause: ' . $restore_error;
                }
            }
        } else {
            $err = 'Invalid file uploaded. Please upload a valid .sql file.';
        }
        header('Location: index.php?page=settings&msg=' . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'customer_ledger' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
        require_permission($conn, 'customer_ledger', 'add');
        $sel_cust = (int) ($_GET['cust_id'] ?? 0);
        $amount = (float) $_POST['amount'];
        $pay_date = clean_text($_POST['payment_date']);
        $pay_method = clean_text($_POST['payment_method']);
        $notes = clean_text($_POST['notes']);
        if ($amount > 0 && $sel_cust > 0 && valid_date($pay_date) && in_array($pay_method, ['cash', 'cheque', 'bank_transfer', 'online', 'other'], true)) {
            $conn->begin_transaction();
            try {
                $party_name = $conn->query("SELECT name FROM customers WHERE id=$sel_cust")->fetch_assoc()['name'] ?? 'Unknown';
                $st = $conn->prepare("INSERT INTO payments (payment_date, payment_type, amount, transaction_type, customer_id, party_name, notes, status) VALUES (?, ?, ?, 'sale', ?, ?, ?, ?)");
                $payment_status = $pay_method === 'cheque' ? 'pending' : 'cleared';
                $st->bind_param('ssdisss', $pay_date, $pay_method, $amount, $sel_cust, $party_name, $notes, $payment_status);
                $st->execute();
                $payment_id = $conn->insert_id;
                $led = $conn->prepare("INSERT INTO ledger (entry_date, entry_type, amount, party_type, party_id, description, reference_type, reference_id) VALUES (?, 'credit', ?, 'customer', ?, ?, 'payment', ?)");
                $desc = 'Payment Received: ' . $notes;
                $led->bind_param('sdisi', $pay_date, $amount, $sel_cust, $desc, $payment_id);
                $led->execute();
                $rem_amount = $amount;
                $inst_q = $conn->query("SELECT id, installment_amount, amount_paid, penalty_fee, COALESCE(penalty_paid,0) AS penalty_paid FROM installments WHERE customer_id=$sel_cust AND status IN ('pending', 'overdue') ORDER BY due_date ASC FOR UPDATE");
                $inst_alloc_stmt = $conn->prepare('INSERT INTO installment_payment_allocations (payment_id, installment_id, principal_amount, penalty_amount) VALUES (?,?,?,?)');
                while ($inst = $inst_q->fetch_assoc()) {
                    if ($rem_amount <= 0)
                        break;
                    $principal_due = max(0, (float) $inst['installment_amount'] - (float) $inst['amount_paid']);
                    $penalty_due = max(0, (float) $inst['penalty_fee'] - (float) $inst['penalty_paid']);
                    $due = $principal_due + $penalty_due;
                    if ($due > 0) {
                        $pay_to_inst = min($due, $rem_amount);
                        $penalty_payment = min($penalty_due, $pay_to_inst);
                        $principal_payment = $pay_to_inst - $penalty_payment;
                        $new_paid = (float) $inst['amount_paid'] + $principal_payment;
                        $new_penalty_paid = (float) $inst['penalty_paid'] + $penalty_payment;
                        $new_status = ($new_paid >= $inst['installment_amount'] && $new_penalty_paid >= $inst['penalty_fee']) ? 'paid' : 'pending';
                        $inst_update = $conn->prepare('UPDATE installments SET amount_paid=?, penalty_paid=?, status=?, payment_id=? WHERE id=?');
                        $inst_update->bind_param('ddsii', $new_paid, $new_penalty_paid, $new_status, $payment_id, $inst['id']);
                        $inst_update->execute();
                        $inst_alloc_stmt->bind_param('iidd', $payment_id, $inst['id'], $principal_payment, $penalty_payment);
                        $inst_alloc_stmt->execute();
                        $rem_amount -= $pay_to_inst;
                    }
                }
                $conn->commit();
                $msg = 'Payment recorded and distributed to installments successfully.';
            } catch (Exception $e) {
                $conn->rollback();
                $err = 'Error: ' . $e->getMessage();
            }
        } else {
            $err = 'Invalid payment amount or customer.';
        }
        header("Location: index.php?page=customer_ledger&cust_id=$sel_cust&msg=" . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'customer_ledger' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_payment_cust'])) {
        require_permission($conn, 'customer_ledger', 'add');
        $sel_cust = (int) ($_GET['cust_id'] ?? 0);
        $amount = (float) $_POST['amount'];
        $pay_date = clean_text($_POST['payment_date']);
        $pay_method = clean_text($_POST['payment_method']);
        $notes = clean_text($_POST['notes']);
        if ($amount > 0 && $sel_cust > 0 && valid_date($pay_date) && in_array($pay_method, ['cash', 'cheque', 'bank_transfer', 'online', 'other'], true)) {
            $party_name = $conn->query("SELECT name FROM customers WHERE id=$sel_cust")->fetch_assoc()['name'] ?? 'Unknown';
            $st = $conn->prepare("INSERT INTO payments (payment_date, payment_type, amount, transaction_type, customer_id, party_name, notes, status) VALUES (?, ?, ?, 'customer_advance', ?, ?, ?, ?)");
            $payment_status = $pay_method === 'cheque' ? 'pending' : 'cleared';
            $st->bind_param('ssdisss', $pay_date, $pay_method, $amount, $sel_cust, $party_name, $notes, $payment_status);
            $st->execute();
            $payment_id = $conn->insert_id;
            $led = $conn->prepare("INSERT INTO ledger (entry_date, entry_type, amount, party_type, party_id, description, reference_type, reference_id) VALUES (?, 'debit', ?, 'customer', ?, ?, 'advance_given', ?)");
            $desc = 'Advance / Loan Given: ' . $notes;
            $led->bind_param('sdisi', $pay_date, $amount, $sel_cust, $desc, $payment_id);
            $led->execute();
            $msg = 'Advance payment recorded successfully.';
        } else {
            $err = 'Invalid payment amount or customer.';
        }
        header("Location: index.php?page=customer_ledger&cust_id=$sel_cust&msg=" . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'supplier_ledger' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_sup_payment'])) {
        require_permission($conn, 'supplier_ledger', 'add');
        $sel_sup = (int) ($_GET['sup_id'] ?? 0);
        $amount = (float) $_POST['amount'];
        $pay_date = clean_text($_POST['payment_date']);
        $pay_method = clean_text($_POST['payment_method']);
        $notes = clean_text($_POST['notes']);
        if ($amount > 0 && $sel_sup > 0 && valid_date($pay_date) && in_array($pay_method, ['cash', 'cheque', 'bank_transfer', 'online', 'other'], true)) {
            $party_name = $conn->query("SELECT name FROM suppliers WHERE id=$sel_sup")->fetch_assoc()['name'] ?? 'Unknown';
            $st = $conn->prepare("INSERT INTO payments (payment_date, payment_type, amount, transaction_type, reference_id, supplier_id, party_name, notes, status) VALUES (?, ?, ?, 'supplier_payment', 0, ?, ?, ?, ?)");
            $payment_status = $pay_method === 'cheque' ? 'pending' : 'cleared';
            $st->bind_param('ssdisss', $pay_date, $pay_method, $amount, $sel_sup, $party_name, $notes, $payment_status);
            $st->execute();
            $msg = 'Supplier payment recorded successfully.';
        } else {
            $err = 'Invalid payment amount or supplier.';
        }
        header("Location: index.php?page=supplier_ledger&sup_id=$sel_sup&msg=" . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'supplier_ledger' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receive_sup_payment'])) {
        require_permission($conn, 'supplier_ledger', 'add');
        $sel_sup = (int) ($_GET['sup_id'] ?? 0);
        $amount = (float) $_POST['amount'];
        $pay_date = clean_text($_POST['payment_date']);
        $pay_method = clean_text($_POST['payment_method']);
        $notes = clean_text($_POST['notes']);
        if ($amount > 0 && $sel_sup > 0 && valid_date($pay_date) && in_array($pay_method, ['cash', 'cheque', 'bank_transfer', 'online', 'other'], true)) {
            $party_name = $conn->query("SELECT name FROM suppliers WHERE id=$sel_sup")->fetch_assoc()['name'] ?? 'Unknown';
            $st = $conn->prepare("INSERT INTO payments (payment_date, payment_type, amount, transaction_type, reference_id, supplier_id, party_name, notes, status) VALUES (?, ?, ?, 'supplier_refund', 0, ?, ?, ?, ?)");
            $payment_status = $pay_method === 'cheque' ? 'pending' : 'cleared';
            $st->bind_param('ssdisss', $pay_date, $pay_method, $amount, $sel_sup, $party_name, $notes, $payment_status);
            $st->execute();
            $msg = 'Supplier refund recorded successfully.';
        } else {
            $err = 'Invalid payment amount or supplier.';
        }
        header("Location: index.php?page=supplier_ledger&sup_id=$sel_sup&msg=" . urlencode($msg) . '&err=' . urlencode($err));
        exit;
    }
    if ($page === 'landing_page' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['save_landing_settings'])) {
            require_permission($conn, 'landing_page', 'edit');
            $lp_fields = ['landing_hero_title', 'landing_hero_subtitle', 'company_address', 'company_map_iframe', 'company_whatsapp', 'company_email', 'social_facebook', 'social_instagram', 'social_twitter', 'vision_statement', 'mission_statement'];
            $st = $conn->prepare('UPDATE settings SET setting_value=? WHERE setting_key=?');
            $conn->begin_transaction();
            try {
              foreach ($lp_fields as $f) {
                if (isset($_POST[$f])) {
                    $val = $_POST[$f];
                    if ($f === 'company_map_iframe' && preg_match('/src=["\']([^"\']+)["\']/', $val, $match)) {
                        $val = $match[1];
                    }
                    if ($f === 'company_map_iframe') {
                        $val = normalize_public_url($val, ['www.google.com', 'maps.google.com']);
                    } elseif (in_array($f, ['social_facebook', 'social_instagram', 'social_twitter'], true)) {
                        $val = normalize_public_url($val);
                    } else {
                        $val = clean_text($val);
                    }
                    $st->bind_param('ss', $val, $f);
                    $st->execute();
                }
              }
              $conn->commit();
              $msg = 'Landing page settings updated.';
            } catch (Exception $e) {
              $conn->rollback();
              $err = $e->getMessage();
            }
        }
        if (isset($_POST['save_leadership'])) {
            $lid = (int) ($_POST['id'] ?? 0);
            require_permission($conn, 'landing_page', $lid > 0 ? 'edit' : 'add');
            $name = clean_text($_POST['name'] ?? '');
            $pos = clean_text($_POST['position'] ?? '');
            $msg_text = clean_text($_POST['message'] ?? '');
            $sort = (int) ($_POST['sort_order'] ?? 0);
            $img_path = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $img_path = handle_image_upload($_FILES['image']);
            }
            if ($lid) {
                if ($img_path) {
                    $st = $conn->prepare('UPDATE leadership SET name=?, position=?, message=?, sort_order=?, image=? WHERE id=?');
                    $st->bind_param('sssisi', $name, $pos, $msg_text, $sort, $img_path, $lid);
                } else {
                    $st = $conn->prepare('UPDATE leadership SET name=?, position=?, message=?, sort_order=? WHERE id=?');
                    $st->bind_param('sssii', $name, $pos, $msg_text, $sort, $lid);
                }
                $st->execute();
                $msg = 'Leadership entry updated.';
            } else {
                $st = $conn->prepare('INSERT INTO leadership (name, position, message, sort_order, image) VALUES (?,?,?,?,?)');
                $st->bind_param('sssis', $name, $pos, $msg_text, $sort, $img_path);
                $st->execute();
                $msg = 'Leadership entry added.';
            }
        }
        if (isset($_POST['delete_leadership'])) {
            require_permission($conn, 'landing_page', 'delete');
            $lid = (int) $_POST['id'];
            $conn->query("DELETE FROM leadership WHERE id=$lid");
            $msg = 'Leadership entry deleted.';
        }
        if (isset($_POST['save_gallery'])) {
            $gid = (int) ($_POST['id'] ?? 0);
            require_permission($conn, 'landing_page', $gid > 0 ? 'edit' : 'add');
            $title = clean_text($_POST['title'] ?? '');
            $desc = clean_text($_POST['description'] ?? '');
            $sort = (int) ($_POST['sort_order'] ?? 0);
            $img_path = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $img_path = handle_image_upload($_FILES['image']);
            }
            if ($gid) {
                if ($img_path) {
                    $st = $conn->prepare('UPDATE gallery SET title=?, description=?, sort_order=?, image=? WHERE id=?');
                    $st->bind_param('ssisi', $title, $desc, $sort, $img_path, $gid);
                } else {
                    $st = $conn->prepare('UPDATE gallery SET title=?, description=?, sort_order=? WHERE id=?');
                    $st->bind_param('ssii', $title, $desc, $sort, $gid);
                }
                $st->execute();
                $msg = 'Gallery item updated.';
            } else {
                $st = $conn->prepare('INSERT INTO gallery (title, description, sort_order, image) VALUES (?,?,?,?)');
                $st->bind_param('ssis', $title, $desc, $sort, $img_path);
                $st->execute();
                $msg = 'Gallery item added.';
            }
        }
        if (isset($_POST['delete_gallery'])) {
            require_permission($conn, 'landing_page', 'delete');
            $gid = (int) $_POST['id'];
            $conn->query("DELETE FROM gallery WHERE id=$gid");
            $msg = 'Gallery item deleted.';
        }
        if (isset($_POST['update_request_status'])) {
            require_permission($conn, 'landing_page', 'edit');
            $rid = (int) $_POST['id'];
            $type = $_POST['type'];
            $status = clean_text($_POST['status']);
            $allowed_tables = ['bike' => 'bike_requests', 'quote' => 'quote_requests'];
            $allowed_statuses = [
                'bike' => ['pending', 'contacted', 'fulfilled', 'cancelled'],
                'quote' => ['pending', 'sent', 'accepted', 'rejected'],
            ];
            $table = $allowed_tables[$type] ?? null;
            if ($table && in_array($status, $allowed_statuses[$type], true)) {
                $rq_stmt = $conn->prepare("UPDATE $table SET status=? WHERE id=?");
                $rq_stmt->bind_param('si', $status, $rid);
                $rq_stmt->execute();
                $rq_stmt->close();
            }
            $msg = 'Request status updated.';
        }
        header('Location: index.php?page=landing_page&sub=' . ($_POST['sub'] ?? 'general') . '&msg=' . urlencode($msg));
        exit;
    }
    if ($page === 'inventory' && isset($_GET['export_csv']) && $_GET['export_csv'] == 1) {
        $status_f = sanitize($_GET['status_f'] ?? $_SESSION['inv_filters']['status_f'] ?? '');
        $model_f = (int) ($_GET['model_f'] ?? $_SESSION['inv_filters']['model_f'] ?? 0);
        $color_f = sanitize($_GET['color_f'] ?? $_SESSION['inv_filters']['color_f'] ?? '');
        $search_f = sanitize($_GET['search_f'] ?? $_SESSION['inv_filters']['search_f'] ?? '');
        $date_from = sanitize($_GET['date_from'] ?? $_SESSION['inv_filters']['date_from'] ?? '');
        $date_to = sanitize($_GET['date_to'] ?? $_SESSION['inv_filters']['date_to'] ?? '');
        $where_parts = ['1=1'];
        if ($status_f && in_array($status_f, ['in_stock', 'sold', 'returned', 'reserved']))
            $where_parts[] = "b.status='$status_f'";
        if ($model_f)
            $where_parts[] = "b.model_id=$model_f";
        if ($color_f)
            $where_parts[] = "b.color LIKE '%" . mysqli_real_escape_string($conn, $color_f) . "%'";
        if ($search_f)
            $where_parts[] = "(b.chassis_number LIKE '%" . mysqli_real_escape_string($conn, $search_f) . "%' OR b.motor_number LIKE '%" . mysqli_real_escape_string($conn, $search_f) . "%' OR m.model_name LIKE '%" . mysqli_real_escape_string($conn, $search_f) . "%' OR b.color LIKE '%" . mysqli_real_escape_string($conn, $search_f) . "%')";
        if ($date_from)
            $where_parts[] = "b.inventory_date >= '" . mysqli_real_escape_string($conn, $date_from) . "'";
        if ($date_to)
            $where_parts[] = "b.inventory_date <= '" . mysqli_real_escape_string($conn, $date_to) . "'";
        $where = implode(' AND ', $where_parts);
        $er = $conn->query("SELECT b.*, m.model_name, m.model_code, c.name as cust_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id WHERE $where ORDER BY b.id DESC");
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="inventory_' . date('Ymd') . '.csv"');
        $out = fopen('php://output', 'w');
        stream_csv_row($out, ['Sr', 'Chassis', 'Motor', 'Model', 'Color', 'Purchase Price', 'Tax', 'Status', 'Selling Price', 'Selling Date', 'Customer', 'Margin']);
        $sr = 1;
        while ($row = $er->fetch_assoc()) {
            stream_csv_row($out, [$sr++, $row['chassis_number'], $row['motor_number'], $row['model_name'], $row['color'], $row['purchase_price'], $row['tax_amount'], $row['status'], $row['selling_price'], $row['selling_date'], $row['cust_name'], $row['margin']]);
        }
        fclose($out);
        exit;
    }
    if (isset($_GET['ajax'])) {
        if ($_GET['ajax'] === 'check_chassis') {
            require_any_permission($conn, [['purchase', 'add'], ['inventory', 'view']]);
            $chassis = sanitize($_GET['chassis'] ?? '');
            $r = $conn->query("SELECT id FROM bikes WHERE chassis_number='" . mysqli_real_escape_string($conn, $chassis) . "'");
            echo ($r && $r->num_rows > 0) ? '1' : '0';
        } elseif ($_GET['ajax'] === 'get_suppliers') {
            require_any_permission($conn, [['purchase', 'add'], ['suppliers', 'view']]);
            $suppliers_list_ajax = $conn->query('SELECT id, name FROM suppliers ORDER BY name');
            echo json_encode($suppliers_list_ajax->fetch_all(MYSQLI_ASSOC));
        } elseif ($_GET['ajax'] === 'get_models') {
            require_any_permission($conn, [['purchase', 'add'], ['sale', 'add'], ['models', 'view']]);
            $models_list_ajax = $conn->query('SELECT id, model_code, model_name FROM models ORDER BY model_name');
            echo json_encode($models_list_ajax->fetch_all(MYSQLI_ASSOC));
        } elseif ($_GET['ajax'] === 'get_customers') {
            require_any_permission($conn, [['sale', 'add'], ['quotations', 'add'], ['customers', 'view']]);
            $customers_list_ajax = $conn->query('SELECT id, name, phone, is_filer FROM customers ORDER BY name');
            echo json_encode($customers_list_ajax->fetch_all(MYSQLI_ASSOC));
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= $db_exists ? (get_setting('theme') ?? 'dark') : 'dark' ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BNI Enterprises - Bike Dealer Management System</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/dt/dt-1.11.3/r-2.2.9/datatables.min.css"/>
<style>
:root {
--animate-duration: 0.15s;
--bg: #2b2b2b;
--bg2: #1e1e1e;
--bg3: #333333;
--surface: #3c3c3c;
--border: #555555;
--text: #d4d4d4;
--text2: #aaaaaa;
--text3: #777777;
--accent: #4a9eff;
--accent-h: #2a7edf;
--success: #4ec94e;
--success-h: #3aab3a;
--danger: #e74c3c;
--danger-h: #c0392b;
--warning: #e0a800;
--input-bg: #ffffff;
--input-text: #222222;
--input-border: #888888;
--sidebar-w: 220px;
--topbar-h: 48px;
--font: 'Segoe UI', Arial, Consolas, monospace;
}
[data-theme="light"] {
--bg: #f0f0f0;
--bg2: #e0e0e0;
--bg3: #d8d8d8;
--surface: #ffffff;
--border: #bbbbbb;
--text: #222222;
--text2: #555555;
--text3: #888888;
--accent: #1a6fc4;
--accent-h: #0d5aad;
--success: #2a8a2a;
--success-h: #1e6e1e;
--danger: #c0392b;
--danger-h: #962d22;
--warning: #b07d00;
--input-bg: #ffffff;
--input-text: #111111;
--input-border: #aaaaaa;
--bg2: #e8e8e8;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:14px;-webkit-text-size-adjust:none}
body{font-family:var(--font);background:var(--bg);color:var(--text);min-height:100vh;display:flex;flex-direction:column}
a{color:var(--accent);text-decoration:none}
a:hover{text-decoration:underline}
input,select,textarea,button{font-family:var(--font);font-size:0.9rem}
button{cursor:pointer}
table{border-collapse:collapse;width:100%}
img{display:block;max-width:100%}
.layout{display:flex;min-height:100vh;flex-direction:row}
.sidebar{width:var(--sidebar-w);background:var(--bg2);border-right:2px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100%;height:100dvh;z-index:100;overflow:hidden;transition:width 0.2s, transform 0.2s}
.sidebar nav { flex: 1; overflow-y: auto; padding-bottom: 15px; }
.sidebar nav::-webkit-scrollbar { width: 6px; }
.sidebar nav::-webkit-scrollbar-track { background: transparent; }
.sidebar nav::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
.sidebar nav::-webkit-scrollbar-thumb:hover { background: var(--text3); }
.sidebar-header{padding:12px 10px;border-bottom:2px solid var(--border);display:flex;align-items:center;justify-content:center;gap:10px;text-align:left;flex-shrink:0}
.sidebar-header .logo{width:35px;height:35px;object-fit:contain;flex-shrink:0}
.sidebar-header .company{font-size:0.85rem;font-weight:700;color:var(--accent);line-height:1.3}
.sidebar-header .branch{font-size:0.72rem;color:var(--text2);margin-top:2px}
.sidebar nav ul{list-style:none;padding:0;margin:0}
.sidebar nav ul li a{display:flex;align-items:center;gap:8px;padding:10px 14px;color:var(--text);font-size:0.83rem;border-bottom:1px solid var(--border);transition:background 0.15s}
.sidebar nav ul li a:hover{background:var(--surface);text-decoration:none}
.sidebar nav ul li a.active{background:var(--accent);color:#fff}
.sidebar nav ul li a .icon{font-size:1rem;min-width:18px;text-align:center}
.sidebar-footer{flex-shrink:0;padding:15px 10px calc(15px + env(safe-area-inset-bottom));border-top:2px solid var(--border);background:var(--bg2)}
.sidebar-footer form{display:inline}
.sidebar-footer button{background:var(--danger);color:#fff;border:1px solid var(--danger-h);padding:6px 14px;font-size:0.8rem;border-radius:2px;width:100%}
.main-wrap{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh;transition:margin-left 0.2s;min-width:0}
.topbar{height:var(--topbar-h);background:var(--bg2);border-bottom:2px solid var(--border);display:flex;align-items:center;padding:0 16px;position:sticky;top:0;z-index:50;gap:10px}
.topbar .hamburger{display:flex;background:none;border:1px solid var(--border);color:var(--text);padding:5px 8px;border-radius:2px;font-size:1.1rem;cursor:pointer}
.sidebar-toggle{display:none;align-items:center;justify-content:center;width:100%;padding:8px 0;border:none;background:var(--surface);color:var(--text);font-size:1rem;cursor:pointer;border-bottom:2px solid var(--border);transition:background 0.15s;flex-shrink:0}
.sidebar-toggle:hover{background:var(--bg3)}
@media (min-width: 601px) {
.sidebar-toggle{display:flex}
body.sidebar-collapsed { --sidebar-w: 60px; }
body.sidebar-collapsed .sidebar { overflow: hidden; }
body.sidebar-collapsed .sidebar-header .header-text { display: none; }
body.sidebar-collapsed .sidebar-header { padding: 15px 0; justify-content: center; }
body.sidebar-collapsed .sidebar-toggle .toggle-label { display: none; }
body.sidebar-collapsed .sidebar-toggle { padding: 10px 0; }
body.sidebar-collapsed nav ul li a { font-size: 0; justify-content: center; padding: 12px 0; gap: 0; }
body.sidebar-collapsed nav ul li a .icon { font-size: 1.2rem; margin: 0; min-width: auto; }
body.sidebar-collapsed nav ul li a .nav-label { display: none; }
body.sidebar-collapsed .sidebar-footer p { display: none; }
body.sidebar-collapsed .sidebar-footer form button { font-size: 0; padding: 10px 0; }
body.sidebar-collapsed .sidebar-footer form button::after { content: '🚪'; font-size: 1.1rem; }
body.sidebar-collapsed nav { overflow-y: auto; overflow-x: hidden; }
body.sidebar-collapsed nav::-webkit-scrollbar { width: 4px; }
body.sidebar-collapsed nav::-webkit-scrollbar-track { background: transparent; }
body.sidebar-collapsed nav::-webkit-scrollbar-thumb { background: var(--border); border-radius: 4px; }
body.sidebar-collapsed nav::-webkit-scrollbar-thumb:hover { background: var(--text3); }
}
@media(max-width:600px){
.sidebar,.sidebar-overlay{display:none!important}
.sidebar-toggle{display:none!important}
.bottom-nav{display:flex!important}
.hamburger{display:none!important}
}
.topbar .page-title{font-size:0.95rem;font-weight:700;color:var(--text);flex:1}
.topbar .topbar-actions{display:flex;gap:8px;align-items:center}
.topbar .topbar-actions form button{background:var(--surface);border:1px solid var(--border);color:var(--text);padding:4px 10px;font-size:0.78rem;border-radius:2px}
.topbar .topbar-actions form button:hover{background:var(--bg3)}
.content{flex:1;padding:16px;max-width:100%}
.toast-wrap{position:fixed;top:60px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px}
.toast{padding:10px 18px;border-radius:2px;font-size:0.85rem;border:1px solid;min-width:220px;max-width:340px;animation:fadeIn 0.15s;font-weight:600}
.toast.success{background:#1e4d1e;border-color:var(--success);color:#b8f0b8}
.toast.error{background:#4d1e1e;border-color:var(--danger);color:#f0b8b8}
[data-theme="light"] .toast.success{background:#d4f4d4;border-color:var(--success);color:#1a4d1a}
[data-theme="light"] .toast.error{background:#f4d4d4;border-color:var(--danger);color:#4d1a1a}
@keyframes fadeIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}
.fieldset{border:2px solid var(--border);padding:12px 14px;margin-bottom:14px;border-radius:2px;min-width:0;max-width:100%;animation: animate__fadeInUp 0.15s;}
.fieldset legend{font-size:0.8rem;font-weight:700;padding:0 6px;color:var(--accent);text-transform:uppercase;letter-spacing:0.5px}
.form-row{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:10px}
.form-group{display:flex;flex-direction:column;gap:3px;flex:1;min-width:140px}
.form-group label{font-size:0.78rem;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:0.3px}
.form-group label .req{color:var(--danger);margin-left:2px}
.form-group input,.form-group select,.form-group textarea{background:var(--input-bg);color:var(--input-text);border:1px solid var(--input-border);padding:7px 9px;border-radius:1px;font-size:0.87rem;outline:none;transition:border-color 0.15s}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:var(--accent)}
.form-group textarea{resize:vertical;min-height:60px}
.form-group select{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%23888'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 8px center;padding-right:26px}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border:1px solid;border-radius:2px;font-size:0.83rem;font-weight:600;cursor:pointer;text-decoration:none;white-space:nowrap;min-height:34px;transition:background 0.15s,border-color 0.15s}
.btn-primary{background:var(--accent);border-color:var(--accent-h);color:#fff}
.btn-primary:hover{background:var(--accent-h);text-decoration:none;color:#fff}
.btn-success{background:var(--success);border-color:var(--success-h);color:#fff}
.btn-success:hover{background:var(--success-h);text-decoration:none;color:#fff}
.btn-danger{background:var(--danger);border-color:var(--danger-h);color:#fff}
.btn-danger:hover{background:var(--danger-h);text-decoration:none;color:#fff}
.btn-default{background:var(--surface);border-color:var(--border);color:var(--text)}
.btn-default:hover{background:var(--bg3);text-decoration:none}
.btn-warning{background:var(--warning);border-color:#a06000;color:#fff}
.btn-warning:hover{background:#a06000;text-decoration:none;color:#fff}
.btn-sm{padding:4px 9px;font-size:0.77rem;min-height:28px}
.card-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px}
.card{background:var(--bg2);border:2px solid var(--border);padding:12px 14px;border-radius:2px;display:flex;align-items:center;gap:12px;animation: animate__fadeIn 0.15s}
.card .card-icon{font-size:1.8rem;min-width:40px;text-align:center}
.card .card-body .card-label{font-size:0.73rem;color:var(--text2);text-transform:uppercase;letter-spacing:0.5px;font-weight:700}
.card .card-body .card-value{font-size:1.3rem;font-weight:700;color:var(--text)}
.card .card-body .card-sub{font-size:0.75rem;color:var(--text3)}
.card.accent{border-color:var(--accent)}
.card.success{border-color:var(--success)}
.card.danger{border-color:var(--danger)}
.card.warning{border-color:var(--warning)}
.split-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.split-grid-3{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
.data-table-wrap{margin-bottom:14px;width:100%;max-width:100%;border:1px solid var(--border);overflow-x:auto;-webkit-overflow-scrolling:touch}
.data-table{width:100%;font-size:0.82rem;border-collapse:separate;border-spacing:0}
.data-table th,.data-table td{border:1px solid var(--border);padding:6px 9px}
.data-table th{white-space:nowrap;background:var(--bg2);color:var(--text);font-weight:700;text-align:left;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.3px;cursor:pointer;user-select:none}
.data-table td{word-break:break-word;overflow-wrap:anywhere}
.data-table th:hover{background:var(--surface)}
.data-table tbody tr:nth-child(even){background:var(--bg)}
.data-table tbody tr:nth-child(odd){background:var(--surface)}
.data-table tbody tr:hover{background:var(--bg3)}
.data-table tbody tr.row-sold{background:#1a3d1a !important}
.data-table tbody tr.row-returned{background:#3d1a1a !important}
.data-table tbody tr.row-reserved{background:#3d3000 !important}
[data-theme="light"] .data-table tbody tr.row-sold{background:#d4f4d4 !important}
[data-theme="light"] .data-table tbody tr.row-returned{background:#f4d4d4 !important}
[data-theme="light"] .data-table tbody tr.row-reserved{background:#f4f0d4 !important}
.data-table tfoot tr{background:var(--bg2);font-weight:700}
.data-table tfoot td{white-space:nowrap}
.data-table .actions-col{white-space:nowrap;display:flex;gap:4px;flex-wrap:wrap}
.pagination{display:flex;gap:4px;align-items:center;flex-wrap:wrap;margin-top:10px}
.pagination a,.pagination span{padding:5px 10px;border:1px solid var(--border);background:var(--surface);color:var(--text);font-size:0.8rem;border-radius:1px;text-decoration:none}
.pagination a:hover{background:var(--accent);color:#fff;border-color:var(--accent-h)}
.pagination .active-page{background:var(--accent);color:#fff;border-color:var(--accent-h)}
.badge{display:inline-block;padding:2px 7px;border-radius:1px;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.3px}
.badge-success{background:#1a4d1a;color:#8af08a}
.badge-danger{background:#4d1a1a;color:#f08a8a}
.badge-warning{background:#4d3a00;color:#f0c858}
.badge-info{background:#1a2d4d;color:#8ab8f0}
.badge-default{background:var(--surface);color:var(--text2);border:1px solid var(--border)}
[data-theme="light"] .badge-success{background:#d4f4d4;color:#1a4d1a}
[data-theme="light"] .badge-danger{background:#f4d4d4;color:#4d1a1a}
[data-theme="light"] .badge-warning{background:#f4e8d4;color:#4d2a00}
[data-theme="light"] .badge-info{background:#d4e8f4;color:#1a2d4d}
.badge-dark{background:#444;color:#ddd}
[data-theme="light"] .badge-dark{background:#ddd;color:#333}
.data-table tbody tr.row-damaged_lost{background:#3d2020 !important}
[data-theme="light"] .data-table tbody tr.row-damaged_lost{background:#f4d4d4 !important}
.filter-bar{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;margin-bottom:12px;padding:14px;background:var(--bg2);border:1px solid var(--border);border-radius:2px}
.filter-bar .form-group{min-width:140px;flex:1 1 auto}
.filter-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.filter-bar .form-group label{font-size:0.72rem}
.filter-bar .form-group input,.filter-bar .form-group select{font-size:0.82rem;padding:5px 7px}
.modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;height:100dvh;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex}
.modal{background:var(--bg2);border:2px solid var(--border);padding:18px;width:90%;max-width:500px;max-height:85vh;overflow-y:auto;border-radius:2px;position:relative;animation: animate__zoomIn 0.3s;}
.modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;border-bottom:1px solid var(--border);padding-bottom:8px}
.modal-header h3{font-size:0.9rem;font-weight:700;color:var(--accent);text-transform:uppercase}
.modal-close{background:var(--danger);border:none;color:#fff;padding:3px 8px;font-size:0.9rem;cursor:pointer;border-radius:1px}
.bike-row{background:var(--surface);border:1px solid var(--border);padding:10px;margin-bottom:8px;border-radius:2px;position:relative}
.bike-row-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.bike-row-num{font-size:0.78rem;font-weight:700;color:var(--accent);text-transform:uppercase}
.bike-row-del{background:var(--danger);border:none;color:#fff;padding:2px 8px;font-size:0.78rem;cursor:pointer;border-radius:1px}
.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg)}
.login-box{background:var(--bg2);border:2px solid var(--border);padding:30px;width:340px;border-radius:2px;animation: animate__fadeIn 0.15s}
.login-box h2{font-size:1.1rem;font-weight:700;color:var(--accent);text-align:center;margin-bottom:4px;text-transform:uppercase}
.login-box .login-sub{font-size:0.78rem;color:var(--text2);text-align:center;margin-bottom:20px}
.login-box .form-group{margin-bottom:12px}
.login-box .login-btn{width:100%;background:var(--accent);border:1px solid var(--accent-h);color:#fff;padding:9px;font-size:0.9rem;font-weight:700;border-radius:2px;cursor:pointer;margin-top:4px}
.login-box .login-btn:hover{background:var(--accent-h)}
.login-err{color:var(--danger);font-size:0.82rem;text-align:center;margin-bottom:10px;padding:6px;background:#4d1e1e;border:1px solid var(--danger);border-radius:1px}
[data-theme="light"] .login-err{background:#f4d4d4}
.install-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg)}
.install-box{background:var(--bg2);border:2px solid var(--accent);padding:30px;width:400px;border-radius:2px;text-align:center;animation: animate__fadeIn 0.15s}
.install-box h2{color:var(--accent);font-size:1.1rem;margin-bottom:8px}
.install-box p{color:var(--text2);font-size:0.83rem;margin-bottom:18px}
.sub-tabs{display:flex;gap:0;margin-bottom:14px;border-bottom:2px solid var(--border);flex-wrap:wrap}
.sub-tab{padding:7px 14px;background:var(--surface);border:1px solid var(--border);border-bottom:none;color:var(--text);font-size:0.8rem;font-weight:600;cursor:pointer;text-decoration:none;border-radius:2px 2px 0 0;margin-right:2px}
.sub-tab:hover{background:var(--bg3);text-decoration:none;color:var(--text)}
.sub-tab.active{background:var(--accent);color:#fff;border-color:var(--accent)}
.sub-panel{display:none}
.sub-panel.active{display:block}
.a4-invoice { background: #fff; color: #111; padding: 40px; font-family: 'Segoe UI', Arial, sans-serif; max-width: 800px; margin: 20px auto; border: 1px solid #ddd; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.a4-invoice .invoice-header { text-align: center; border-bottom: 3px solid #1a6fc4; padding-bottom: 15px; margin-bottom: 25px; }
.a4-invoice .invoice-header h1 { font-size: 1.8rem; color: #1a6fc4; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
.a4-invoice .invoice-header h2 { font-size: 1rem; color: #555; font-weight: 600; margin-top: 5px; }
.a4-invoice .invoice-meta { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 0.9rem; line-height: 1.5; }
.a4-invoice .invoice-section { margin-bottom: 20px; }
.a4-invoice .invoice-section h3 { font-size: 1rem; text-transform: uppercase; font-weight: 700; background: #f4f7f6; border-left: 4px solid #1a6fc4; padding: 6px 10px; margin-bottom: 10px; }
.a4-invoice .invoice-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
.a4-invoice .invoice-table th, .a4-invoice .invoice-table td { border: 1px solid #ddd; padding: 8px 12px; }
.a4-invoice .invoice-table th { background: #f8f9fa; font-weight: 700; text-align: left; }
.a4-invoice .invoice-total { text-align: right; font-size: 1.1rem; font-weight: 800; margin-top: 10px; padding: 10px; background: #eef2f5; border: 1px solid #cdd5dc; color: #111; }
.a4-invoice .invoice-footer { text-align: center; margin-top: 30px; border-top: 1px solid #ddd; padding-top: 15px; font-size: 0.8rem; color: #777; }
.thermal-receipt { background: #fff; color: #000; padding: 15px; font-family: 'Courier New', Courier, monospace; width: 80mm; margin: 0 auto; font-size: 12px; line-height: 1.4; }
.thermal-receipt .invoice-header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 10px; margin-bottom: 10px; }
.thermal-receipt .invoice-header h1 { font-size: 1.2rem; font-weight: bold; }
.thermal-receipt .invoice-header h2 { font-size: 0.85rem; font-weight: normal; }
.thermal-receipt .invoice-meta { margin-bottom: 10px; border-bottom: 1px dashed #000; padding-bottom: 10px; }
.thermal-receipt .invoice-section h3 { font-size: 0.9rem; font-weight: bold; border-bottom: 1px solid #000; display: inline-block; margin-bottom: 5px; }
.thermal-receipt .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
.thermal-receipt .invoice-table th, .thermal-receipt .invoice-table td { padding: 4px 2px; text-align: left; vertical-align: top; border-bottom: 1px dotted #ccc; }
.thermal-receipt .invoice-table th { border-bottom: 1px solid #000; font-weight: bold; }
.thermal-receipt .invoice-total { text-align: right; font-size: 1rem; font-weight: bold; border-top: 1px dashed #000; border-bottom: 1px dashed #000; padding: 5px 0; margin-top: 5px; }
.thermal-receipt .invoice-footer { text-align: center; margin-top: 15px; font-size: 10px; border-top: 1px dashed #000; padding-top: 5px; }
.timeline{list-style:none;padding:0;margin:0}
.timeline li{display:flex;gap:12px;padding:8px 0;border-bottom:1px solid var(--border)}
.timeline-dot{width:12px;height:12px;border-radius:50%;background:var(--accent);margin-top:4px;flex-shrink:0}
.timeline-content{flex:1}
.timeline-date{font-size:0.75rem;color:var(--text3)}
.timeline-text{font-size:0.83rem;color:var(--text)}
.stats-row{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:12px}
.stat-box{background:var(--bg2);border:1px solid var(--border);padding:8px 14px;border-radius:1px;flex:1;min-width:100px;text-align:center}
.stat-box .stat-val{font-size:1.1rem;font-weight:700;color:var(--accent)}
.stat-box .stat-lbl{font-size:0.72rem;color:var(--text2);text-transform:uppercase}
.sidebar-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:90}
.print-btn-wrap{margin-bottom:10px}
.select2-container {
    width: 100% !important;
    min-width: 180px !important;
}
.select2-container--default .select2-selection--single {
    background-color: var(--input-bg) !important;
    border: 1px solid var(--input-border) !important;
    border-radius: 1px !important;
    height: 34px !important;
    display: flex !important;
    align-items: center !important;
    font-size: 0.87rem !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: var(--input-text) !important;
    line-height: 32px !important;
    padding-left: 8px !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 32px !important;
    right: 5px !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow b {
    border-color: var(--input-text) transparent transparent transparent !important;
}
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: var(--accent) !important;
}
.select2-container--open {
    z-index: 99999 !important;
}
.select2-dropdown {
    background-color: var(--input-bg) !important;
    border: 1px solid var(--accent) !important;
    border-radius: 1px !important;
}
.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable {
    background-color: var(--accent) !important;
    color: #fff !important;
}
.select2-container--default .select2-results__option--selectable {
    color: var(--input-text) !important;
}
.select2-search input {
    background-color: var(--bg) !important;
    color: var(--text) !important;
    border: 1px solid var(--border) !important;
}
.dataTables_wrapper .dataTables_filter input, .dataTables_wrapper .dataTables_length select {
    background-color: var(--input-bg);
    color: var(--input-text);
    border: 1px solid var(--input-border);
    padding: 7px 9px;
    border-radius: 1px;
    font-size: 0.87rem;
    outline: none;
    transition: border-color 0.15s;
    margin-left: 0.5em;
    margin-right: 0.5em;
}
.dataTables_wrapper .dataTables_filter label, .dataTables_wrapper .dataTables_length label, .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_paginate {
    color: var(--text) !important;
    font-size: 0.85rem;
    padding: 10px 0;
}
.dataTables_wrapper select {
    background-color: var(--input-bg) !important;
    color: var(--input-text) !important;
    border: 1px solid var(--input-border) !important;
    padding: 4px !important;
    border-radius: 2px !important;
    outline: none !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.5em 1em;
    margin-left: 2px;
    border: 1px solid var(--border);
    background-color: var(--surface);
    color: var(--text) !important;
    border-radius: 1px;
    cursor: pointer;
    font-size: 0.8rem;
    min-width: 44px; 
    min-height: 34px; 
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background-color: var(--accent);
    color: #fff !important;
    border-color: var(--accent-h);
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current, .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
    background-color: var(--accent);
    color: #fff !important;
    border-color: var(--accent-h);
}
.dataTables_wrapper .dataTables_processing {
    background-color: var(--bg2);
    color: var(--text);
    border: 1px solid var(--border);
}
.validation-error {
    color: var(--danger);
    font-size: 0.75rem;
    margin-top: 2px;
    padding-left: 2px;
}
.just-validate-error-field {
    border-color: var(--danger) !important;
}
.just-validate-error-label {
    color: var(--danger) !important;
}
@media print{
.sidebar,.topbar,.filter-bar,.pagination,.btn,.actions-col,.print-btn-wrap,.no-print,.dataTables_filter,.dataTables_length,.dataTables_info,.dataTables_paginate{display:none!important}
.main-wrap{margin-left:0!important}
.content{padding:0!important}
body{background:#fff!important;color:#111!important}
.data-table th,.data-table td{color:#111!important;background:#fff!important;border-color:#666!important;white-space:normal!important;word-wrap:break-word!important}
.data-table-wrap{overflow:visible!important;overflow-x:visible!important}
.a4-invoice { border: none !important; box-shadow: none !important; width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; }
.thermal-receipt { margin: 0 !important; padding: 0 !important; width: 80mm !important; }
}
@media(max-width:900px){
.card-grid{grid-template-columns:repeat(2,1fr)}
.split-grid-3{grid-template-columns:1fr 1fr}
}
@media(max-width:600px){
.page-title .title-text{display:none}
.card-grid, .split-grid, .split-grid-3{grid-template-columns:1fr}
.sidebar{transform:translateX(-100%);z-index:200}
.sidebar.open{transform:translateX(0)}
.sidebar-overlay.open{display:block}
.main-wrap{margin-left:0;padding-bottom:60px}
.form-row{flex-direction:column}
.form-group{min-width:0}
.filter-bar{flex-direction:column;align-items:stretch}
.filter-bar .form-group{width:100%}
.filter-bar .btn, .filter-bar button{width:100%;justify-content:center;margin-top:4px}
.filter-actions{width:100%;display:flex;flex-direction:column;gap:8px}
.data-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}
.data-table th,.data-table td{font-size:0.75rem;padding:4px 6px}
.btn{font-size:0.78rem;padding:6px 10px}
.modal{width:98%;padding:12px}
.stats-row{flex-direction:column}
.dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_length {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
.dataTables_wrapper .dataTables_filter input, .dataTables_wrapper .dataTables_length select {
    width: 100%;
    margin-left: 0;
}
.dataTables_wrapper .dataTables_info {
    text-align: center;
}
.dataTables_wrapper .dataTables_paginate {
    justify-content: center;
    display: flex;
    flex-wrap: wrap;
}
}
.bottom-nav{display:none;position:fixed;bottom:0;left:0;right:0;background:var(--bg2);border-top:2px solid var(--border);z-index:150;padding:4px 0 calc(4px + env(safe-area-inset-bottom));transform:translateY(0);transition:transform 0.25s ease}
.bottom-nav.hide{transform:translateY(100%)}
.bottom-nav-scroll{display:flex;overflow-x:auto;gap:2px;padding:0 4px;-webkit-overflow-scrolling:touch;scrollbar-width:none}
.bottom-nav-scroll::-webkit-scrollbar{display:none}
.bottom-nav-scroll a{display:flex;flex-direction:column;align-items:center;justify-content:center;min-width:54px;min-height:48px;padding:4px 6px;color:var(--text2);text-decoration:none;font-size:0.6rem;text-align:center;border-radius:4px;flex-shrink:0;transition:background 0.15s,color 0.15s;gap:2px}
.bottom-nav-scroll a:active{background:var(--surface)}
.bottom-nav-scroll a.active{color:var(--accent);font-weight:700}
.bottom-nav-scroll a .bnav-icon{font-size:1.3rem;line-height:1}
.bottom-nav-scroll a .bnav-label{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:58px;line-height:1.2}
@media print{
.bottom-nav{display:none!important}
}
a.paginate_button.current {
    background: #322727 !important;
}
button#sidebarToggle {
    display: none !important;
}
</style>
<link rel="icon" type="image/png" href="favicon-96x96.png" sizes="96x96" />
<link rel="icon" type="image/svg+xml" href="favicon.svg" />
<link rel="shortcut icon" href="favicon.ico" />
<link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png" />
</head>
<body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://unpkg.com/just-validate@4.3.0/dist/just-validate.production.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.11.3/r-2.2.9/datatables.min.js"></script>
<script>
if (localStorage.getItem('sidebarCollapsed') === '1' && window.innerWidth > 600) {
    document.body.classList.add('sidebar-collapsed');
}
document.addEventListener('DOMContentLoaded', function() {
    if (document.body.classList.contains('sidebar-collapsed')) {
        document.querySelectorAll('#sidebarToggle .toggle-label').forEach(function(el) { el.style.display = 'none'; });
    } else {
        document.querySelectorAll('#sidebarToggle .toggle-label').forEach(function(el) { el.style.display = ''; });
    }
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('msg')) Swal.fire({ title: 'Success!', text: urlParams.get('msg'), icon: 'success', timer: 3000, showConfirmButton: false });
    if (urlParams.get('err')) Swal.fire({ title: 'Error!', text: urlParams.get('err'), icon: 'error', confirmButtonColor: '#d33' });
    if (urlParams.has('msg') || urlParams.has('err')) {
        urlParams.delete('msg');
        urlParams.delete('err');
        window.history.replaceState(null, '', window.location.pathname + '?' + urlParams.toString());
    }
    try {
        $('table.data-table:not(.no-dt)').DataTable({
            responsive: true,
            pagingType: 'full_numbers',
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            pageLength: 100,
            stateSave: true,
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search records...",
                lengthMenu: "_MENU_",
                paginate: {
                    first: "«",
                    last: "»",
                    next: "›",
                    previous: "‹"
                }
            },
            columnDefs: [
                { targets: 'no-sort', orderable: false }
            ]
        });
    } catch (e) { console.warn('DataTable init error:', e); }
    $('select:not([name$="_length"]):not(.swal2-select)').select2({
        minimumResultsForSearch: 10, 
        placeholder: '-- Select --',
        allowClear: false,
        theme: 'default'
    });
    var csrfToken = "<?= $_SESSION['csrf_token'] ?? '' ?>";
    const originalSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function() {
        if (this.method && this.method.toUpperCase() === 'POST' && !this.querySelector('input[name="csrf_token"]')) {
            const c = document.createElement('input'); c.type = 'hidden'; c.name = 'csrf_token'; c.value = typeof csrfToken !== 'undefined' ? csrfToken : ''; this.appendChild(c);
        }
        originalSubmit.call(this);
    };
    function injectCsrf(container) {
        $(container || document).find('form[method="POST"]').each(function() {
            if ($(this).find('input[name="csrf_token"]').length === 0) {
                $(this).append('<input type="hidden" name="csrf_token" value="' + csrfToken + '">');
            }
        });
    }
    injectCsrf();
    document.addEventListener('submit', function(e) {
        let form = e.target;
        if (form && form.tagName === 'FORM' && form.method && form.method.toUpperCase() === 'POST') {
            if (!form.querySelector('input[name="csrf_token"]')) {
                let c = document.createElement('input'); c.type = 'hidden'; c.name = 'csrf_token'; c.value = typeof csrfToken !== 'undefined' ? csrfToken : ''; form.appendChild(c);
            }
        }
    }, true);
    ['click', 'change'].forEach(evt => {
        document.addEventListener(evt, function(e) {
            let form = e.target.closest ? e.target.closest('form') : null;
            if (form && form.method && form.method.toUpperCase() === 'POST') {
                if (!form.querySelector('input[name="csrf_token"]')) {
                    let c = document.createElement('input'); c.type = 'hidden'; c.name = 'csrf_token'; c.value = typeof csrfToken !== 'undefined' ? csrfToken : ''; form.appendChild(c);
                }
            }
        }, true);
    });
    $(document).on('draw.dt responsive-display.dt', function() { injectCsrf(); });
    $(document).on('DOMNodeInserted', function(e) {
        if (e.target && e.target.nodeType === 1 && !$(e.target).closest('.dataTable, .select2-container').length) {
            $(e.target).find('select:not([name$="_length"]):not(.swal2-select)').each(function() {
                if (!$(this).data('select2')) {
                    $(this).select2({ minimumResultsForSearch: 10, placeholder: '-- Select --', allowClear: false, theme: 'default' });
                }
            });
        }
    });
    $(document).on('change', 'select[name^="bike_link"]', function() {
        var m = this.name.match(/\[(\d+)\]/);
        if (m) updateDepBikeRem(this, parseInt(m[1]));
    });
    $(document).on('input', 'input[name^="bike_link"][name$="[amount]"]', function() {
        var m = this.name.match(/\[(\d+)\]/);
        if (m) updateDepBikeRemFromAmount(this, parseInt(m[1]));
    });
    window.originalAlert = window.alert;
    window.alert = function(message) {
        Swal.fire({
            title: 'Alert',
            text: message,
            icon: 'info',
            confirmButtonText: 'OK',
            customClass: {
                popup: 'animate__animated animate__fadeInUp animate__faster'
            }
        });
    };
    var forms = document.querySelectorAll('form');
    forms.forEach(function(form) {
        if (!form.hasAttribute('novalidate')) { 
            var validator = new JustValidate(form, {
                errorFieldCssClass: 'just-validate-error-field',
                errorLabelCssClass: 'just-validate-error-label',
                focusInvalidField: true,
                lockForm: true,
            });
            form.querySelectorAll('input[required], select[required], textarea[required]').forEach(function(input) {
                validator.addField(input, [{ rule: 'required', errorMessage: 'This field is required' }]);
            });
            form.querySelectorAll('input[type="email"]').forEach(function(input) {
                validator.addField(input, [{ rule: 'email', errorMessage: 'Enter a valid email' }]);
            });
            form.querySelectorAll('input[type="number"]').forEach(function(input) {
                validator.addField(input, [{ rule: 'number', errorMessage: 'Must be a number' }]);
                if (input.min) validator.addField(input, [{ rule: 'minNumber', value: parseFloat(input.min), errorMessage: 'Must be at least ' + input.min }]);
                if (input.max) validator.addField(input, [{ rule: 'maxNumber', value: parseFloat(input.max), errorMessage: 'Must be at most ' + input.max }]);
            });
            form.querySelectorAll('input[minlength]').forEach(function(input) {
                validator.addField(input, [{ rule: 'minLength', value: parseInt(input.minlength), errorMessage: 'Minimum ' + input.minlength + ' characters' }]);
            });
            form.querySelectorAll('input[maxlength]').forEach(function(input) {
                validator.addField(input, [{ rule: 'maxLength', value: parseInt(input.maxlength), errorMessage: 'Maximum ' + input.maxlength + ' characters' }]);
            });
            if (form.querySelector('input[name="password"]') || form.querySelector('input[name="new_password"]')) {
                validator.addField(form.querySelector('input[name="password"]') || form.querySelector('input[name="new_password"]'), [
                    { rule: 'minLength', value: 8, errorMessage: 'Minimum 8 characters' },
                    { rule: 'customRegexp', value: /^(?=.*[!@#$%^&*-])(?=.*[0-9])(?=.*[A-Za-z]).{8,}$/, errorMessage: 'Must include uppercase, lowercase, number, and special character' }
                ]);
            }
            validator.onSuccess((event) => {
                const form = event.target;
                const btn = form.querySelector('button[type="submit"][name], input[type="submit"][name]');
                if (btn && btn.name && !form.querySelector('input[name="' + btn.name + '"]')) {
                    const hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = btn.name;
                    hidden.value = btn.value || '1';
                    form.appendChild(hidden);
                }
                if (!form.querySelector('input[name="csrf_token"]')) {
                    const csrfInput = document.createElement('input');
                    csrfInput.type = 'hidden';
                    csrfInput.name = 'csrf_token';
                    csrfInput.value = csrfToken;
                    form.appendChild(csrfInput);
                }
                if (form.classList.contains('ajax-form')) {
                    const enteredNameInput = form.querySelector('[name="name"]') || form.querySelector('[name="model_name"]');
                    const enteredName = enteredNameInput ? enteredNameInput.value : null;
                    $.ajax({
                        type: form.method || 'POST',
                        url: form.action,
                        data: new FormData(form),
                        processData: false,
                        contentType: false,
                        success: function() {
                            if (form.id === 'supplierForm') closeSupplierModal(enteredName);
                            else if (form.id === 'modelForm') closeModelModal(enteredName);
                            else if (form.id === 'customerForm') closeCustomerModal(enteredName);
                            form.reset();
                            Swal.fire({ title: 'Success', text: 'Added successfully!', icon: 'success', timer: 1500, showConfirmButton: false });
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to add. Please try again.', 'error');
                        }
                    });
                } else {
                    form.submit();
                }
            });
        }
    });
    document.querySelectorAll('[onclick*="return confirm"]').forEach(button => {
        let match = button.getAttribute('onclick').match(/confirm\(['"]([^'"]+)['"]\)/);
        let message = match ? match[1] : 'Are you sure?';
        button.removeAttribute('onclick');
        button.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Confirm Action',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, proceed!',
                customClass: { popup: 'animate__animated animate__shakeX animate__faster' }
            }).then((result) => {
                if (result.isConfirmed) {
                    let form = button.closest('form');
                    if (form) {
                        if (button.name) {
                            let hidden = document.createElement('input');
                            hidden.type = 'hidden';
                            hidden.name = button.name;
                            hidden.value = button.value || '1';
                            form.appendChild(hidden);
                        }
                        if (!form.querySelector('input[name="csrf_token"]')) {
                            let csrfInput = document.createElement('input');
                            csrfInput.type = 'hidden';
                            csrfInput.name = 'csrf_token';
                            csrfInput.value = csrfToken;
                            form.appendChild(csrfInput);
                        }
                        form.submit();
                    } else if (button.tagName === 'A') {
                        window.location.href = button.href;
                    }
                }
            });
        });
    });
    document.querySelectorAll('form[onsubmit*="confirm"]').forEach(form => {
        let match = form.getAttribute('onsubmit').match(/confirm\(['"]([^'"]+)['"]\)/);
        let message = match ? match[1] : 'Are you sure?';
        form.removeAttribute('onsubmit'); 
        form.addEventListener('submit', function(e) {
            if (this.hasAttribute('data-swal-passed')) return;
            e.preventDefault();
            const submitter = e.submitter;
            Swal.fire({
                title: 'Confirm Action',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, proceed!',
                customClass: { popup: 'animate__animated animate__shakeX animate__faster' }
            }).then((result) => {
                if (result.isConfirmed) {
                    this.setAttribute('data-swal-passed', 'true');
                    if (submitter && submitter.name) {
                        let hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = submitter.name;
                        hidden.value = submitter.value || '1';
                        this.appendChild(hidden);
                    }
                    if (!this.querySelector('input[name="csrf_token"]')) {
                        let csrfInput = document.createElement('input');
                        csrfInput.type = 'hidden';
                        csrfInput.name = 'csrf_token';
                        csrfInput.value = csrfToken;
                        this.appendChild(csrfInput);
                    }
                    this.submit();
                }
            });
        });
    });
});
</script>
<?php if (!$db_exists): ?>
<div class="install-wrap">
<div class="install-box animate__animated animate__fadeInDown">
<div style="font-size:2.5rem;margin-bottom:10px">⚡</div>
<h2>BNI Enterprises Setup</h2>
<p>Welcome! The database needs to be installed. Click the button below to create the database and all required tables automatically.</p>
<?php if (isset($_GET['db_error'])): ?>
<div class="login-err animate__animated animate__shakeX">Database connection failed. Please check your credentials in index.php.</div>
<?php endif; ?>
<form method="POST">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<button type="submit" name="do_install" class="btn btn-primary animate__animated animate__pulse animate__infinite" style="width:100%;font-size:0.95rem;padding:10px">⚡ Install Database</button>
</form>
<p style="margin-top:14px;font-size:0.75rem;color:var(--text3)">Created by: <?= $author ?> | v<?= $app_version ?> | WhatsApp: 03361593533</p>
</div>
</div>
<?php elseif (!isset($_SESSION['user_id'])): ?>
<div class="login-wrap">
<div class="login-box animate__animated animate__fadeInUp">
<div style="font-size:2.5rem;text-align:center;margin-bottom:8px">⚡</div>
<h2>BNI Enterprises</h2>
<div class="login-sub"><?= sanitize(get_setting('branch_name') ?? 'Dera (Ahmed Metro)') ?></div>
<?php if (isset($_GET['msg'])): ?>
<div class="login-err animate__animated animate__shakeX"><?= $_GET['msg'] === 'idle_logout' ? 'Session expired due to inactivity.' : 'Your session has expired. Please login again.' ?></div>
<?php endif; ?>
<?php if (isset($login_error)): ?>
<div class="login-err animate__animated animate__shakeX"><?= $login_error ?></div>
<?php endif; ?>
<form method="POST" id="loginForm">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<div class="form-group"><label>Username <span class="req">*</span></label><input type="text" name="username" required autocomplete="username" placeholder="admin"></div>
<div class="form-group" style="margin-top:10px"><label>Password <span class="req">*</span></label><input type="password" name="password" required autocomplete="current-password" placeholder="••••••••"></div>
<div class="form-group" style="margin-top:10px">
    <label>CAPTCHA <span class="req">*</span></label>
    <div style="display:flex;align-items:center;gap:5px;">
        <input type="text" name="captcha_code" required placeholder="Enter result" style="flex:1;">
        <img src="index.php?captcha=1&amp;<?= time() ?>" alt="CAPTCHA" style="height:34px;width:100px;border:1px solid var(--border);border-radius:2px;cursor:pointer" onclick="this.src='index.php?captcha=1&amp;'+Date.now()">
    </div>
</div>
<button type="submit" name="do_login" class="login-btn animate__animated animate__pulse animate__infinite" style="margin-top:14px">🔐 Login</button>
</form>
<p style="margin-top:14px;font-size:0.75rem;color:var(--text3);text-align:center">Created by: <?= $author ?> | v<?= $app_version ?> | WhatsApp: 03361593533</p>
</div>
</div>
<script>
    const loginValidator = new JustValidate('#loginForm', {
        errorFieldCssClass: 'just-validate-error-field',
        errorLabelCssClass: 'just-validate-error-label',
        focusInvalidField: true,
        lockForm: true,
    });
    loginValidator
        .addField('input[name="username"]', [{ rule: 'required', errorMessage: 'Username is required' }])
        .addField('input[name="password"]', [{ rule: 'required', errorMessage: 'Password is required' }])
        .addField('input[name="captcha_code"]', [{ rule: 'required', errorMessage: 'Captcha is required' }])
        .onSuccess((event) => {
            const form = event.target;
            const btn = form.querySelector('[type="submit"]');
            if (btn && btn.name) {
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = btn.name;
                hidden.value = btn.value || '1';
                form.appendChild(hidden);
            }
            form.submit();
        });
</script>
<?php
else:
    $company_name = get_setting('company_name') ?? 'BNI Enterprises';
    $branch_name = get_setting('branch_name') ?? 'Dera (Ahmed Metro)';
    $currency = get_setting('currency') ?? 'Rs.';
    $all_nav = [
        ['dashboard', '🏠', 'Dashboard'],
        ['purchase', '📦', 'Purchase Entry'],
        ['inventory', '📋', 'Inventory / Stock'],
        ['sale', '🛒', 'Sales Entry'],
        ['returns', '↩', 'Returns'],
        ['payments', '💳', 'Payments Register'],
        ['installments', '🗓️', 'Installments'],
        ['money_destinations', '🏦', 'Money Destinations'],
        ['money_tracking', '💸', 'Money Tracking'],
        ['bank_deposits', '🏧', 'Bank Deposits'],
        ['quotations', '📝', 'Quotations'],
        ['income_expense', '💰', 'Income/Expense'],
        ['customer_ledger', '👤', 'Customer Ledger'],
        ['supplier_ledger', '🏭', 'Supplier Ledger'],
        ['reports', '📊', 'Reports'],
        ['models', '🚲', 'Models'],
        ['accessories', '🛠️', 'Accessories'],
        ['customers', '👥', 'Customers'],
        ['suppliers', '🏢', 'Suppliers'],
        ['users', '👨‍💼', 'Users'],
        ['roles', '🔑', 'Roles & Permissions'],
        ['settings', '⚙', 'Settings'],
        ['landing_page', '🌐', 'Landing Page'],
    ];
    $pages_nav = [];
    foreach ($all_nav as $nav) {
        if (has_permission($conn, $nav[0], 'view')) {
            $pages_nav[] = $nav;
        }
    }
?>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<div class="sidebar" id="sidebar">
<div class="sidebar-header">
<img src="logo.png" alt="Logo" class="logo">
<div class="header-text">
<div class="company">⚡ <?= sanitize($company_name) ?></div>
<div class="branch"><?= sanitize($branch_name) ?></div>
</div>
</div>
<button class="sidebar-toggle" id="sidebarToggle" onclick="toggleSidebar()" title="Toggle sidebar"><span class="toggle-label">◀</span> ☰ <span class="toggle-label">Collapse</span></button>
<nav>
<ul>
<?php foreach ($pages_nav as $pn): ?>
<li><a href="index.php?page=<?= $pn[0] ?>" class="<?= $page === $pn[0] ? 'active' : '' ?> animate__animated animate__fadeInLeft"><span class="icon"><?= $pn[1] ?></span><span class="nav-label"><?= $pn[2] ?></span></a></li>
<?php endforeach; ?>
</ul>
</nav>
<div class="sidebar-footer">
<p style="margin-top:14px;font-size:0.75rem;color:var(--text3);text-align:center">Created by: <?= $author ?><br>WhatsApp: 03361593533</p>
<form method="POST" action="index.php"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><button type="submit" name="do_logout" value="1">🚪 Logout</button></form>
</div>
</div>
<nav class="bottom-nav" id="bottomNav">
<div class="bottom-nav-scroll">
<?php foreach ($pages_nav as $pn): ?>
<a href="index.php?page=<?= $pn[0] ?>" class="<?= $page === $pn[0] ? 'active' : '' ?>"><span class="bnav-icon"><?= $pn[1] ?></span><span class="bnav-label"><?= $pn[2] ?></span></a>
<?php endforeach; ?>
<a href="index.php?logout=1&amp;logout_token=<?= urlencode($_SESSION['csrf_token']) ?>"><span class="bnav-icon">🚪</span><span class="bnav-label">Logout</span></a>
</div>
</nav>
<div class="main-wrap">
<div class="topbar">
<button class="hamburger" id="hamburgerBtn" onclick="toggleSidebar()">☰</button>
<div class="page-title">
<?php foreach ($pages_nav as $pn) { if ($pn[0] === $page) echo $pn[1] . ' <span class="title-text">' . $pn[2] . '</span>'; } ?>
</div>
<div class="topbar-actions">
<?php $cu = current_user($conn);
    if ($cu): ?><span style="font-size:0.8rem;color:var(--text2);margin-right:10px">👤 <?= sanitize($cu['full_name'] ?: $cu['username']) ?> (<?= sanitize($cu['role_name']) ?>)</span><?php endif; ?>
<form method="POST" action="index.php?<?= http_build_query(array_merge($_GET, [])) ?>">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<button type="submit" name="toggle_theme" title="Toggle Theme"><?= ($theme ?? 'dark') === 'dark' ? '☀' : '🌙' ?></button>
</form>
<span style="font-size:0.75rem;color:var(--text3)"><?= date('d/m/Y H:i') ?></span>
</div>
</div>
<div class="content">
<?php
    $per_page = 20;
    $current_pg = max(1, (int) ($_GET['pg'] ?? 1));
    $offset = ($current_pg - 1) * $per_page;
    if ($page === 'dashboard'):
        require_permission($conn, 'dashboard', 'view');
        $total_stock = $conn->query("SELECT COUNT(*) as c FROM bikes WHERE status='in_stock'")->fetch_assoc()['c'];
        $total_sold = $conn->query("SELECT COUNT(*) as c FROM bikes WHERE status='sold'")->fetch_assoc()['c'];
        $total_returned = $conn->query("SELECT COUNT(*) as c FROM bikes WHERE status='returned'")->fetch_assoc()['c'];
        $total_damaged = $conn->query("SELECT COUNT(*) as c FROM bikes WHERE status='damaged_lost'")->fetch_assoc()['c'];
        $total_purchase_val = $conn->query('SELECT SUM(purchase_price) as s FROM bikes')->fetch_assoc()['s'] ?? 0;
        $total_sales_val = $conn->query("SELECT COALESCE(SUM(b.selling_price + COALESCE((SELECT SUM(sa.final_price) FROM sale_accessories sa WHERE sa.bike_id=b.id),0)),0) as s FROM bikes b WHERE b.status='sold'")->fetch_assoc()['s'] ?? 0;
        $total_tax = $conn->query("SELECT SUM(tax_amount) as s FROM bikes WHERE status='sold'")->fetch_assoc()['s'] ?? 0;
        $total_margin = $conn->query("SELECT SUM(margin) as s FROM bikes WHERE status='sold'")->fetch_assoc()['s'] ?? 0;
        $pending_payments = $conn->query("SELECT COUNT(*) as c, SUM(amount) as s FROM payments WHERE payment_type='cheque' AND (status='pending' OR status IS NULL)")->fetch_assoc();
        $todays_sales = $conn->query("SELECT COUNT(*) as c, COALESCE(SUM(b.selling_price + COALESCE((SELECT SUM(sa.final_price) FROM sale_accessories sa WHERE sa.bike_id=b.id),0)),0) as s FROM bikes b WHERE b.status='sold' AND b.selling_date = CURDATE()")->fetch_assoc();
        $total_customers = $conn->query('SELECT COUNT(*) as c FROM customers')->fetch_assoc()['c'];
        $total_suppliers = $conn->query('SELECT COUNT(*) as c FROM suppliers')->fetch_assoc()['c'];
        $total_expenses = $conn->query("SELECT SUM(amount) as s FROM income_expenses WHERE type='expense'")->fetch_assoc()['s'] ?? 0;
        $overdue_installments = $conn->query("SELECT COUNT(*) as c, SUM((installment_amount - amount_paid) + (penalty_fee - COALESCE(penalty_paid,0))) as s FROM installments WHERE status IN ('pending','overdue') AND due_date < CURDATE()")->fetch_assoc();
        $sales_trend = $conn->query("SELECT DATE_FORMAT(b.selling_date,'%Y-%m') as ym, SUM(b.selling_price + COALESCE((SELECT SUM(sa.final_price) FROM sale_accessories sa WHERE sa.bike_id=b.id),0)) as total FROM bikes b WHERE b.status='sold' AND b.selling_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY ym ORDER BY ym");
        $chart_labels = [];
        $chart_sales = [];
        while ($r = $sales_trend->fetch_assoc()) {
            $chart_labels[] = $r['ym'];
            $chart_sales[] = $r['total'];
        }
        $model_stock = $conn->query("SELECT m.model_name, COUNT(b.id) as cnt FROM models m LEFT JOIN bikes b ON m.id=b.model_id WHERE b.status='in_stock' GROUP BY m.id HAVING cnt > 0");
        $ms_labels = [];
        $ms_data = [];
        while ($r = $model_stock->fetch_assoc()) {
            $ms_labels[] = $r['model_name'];
            $ms_data[] = $r['cnt'];
        }
        $ie_summary = $conn->query('SELECT type, SUM(amount) as total FROM income_expenses GROUP BY type');
        $ie_data = ['income' => 0, 'expense' => 0];
        while ($r = $ie_summary->fetch_assoc()) {
            $ie_data[$r['type']] = $r['total'];
        }
        $total_allocated_dash = $conn->query("SELECT COALESCE(SUM(sma.amount),0) FROM sale_money_allocations sma JOIN money_destinations md ON md.id=sma.destination_id WHERE md.type='bank'")->fetch_row()[0];
        $total_deposited_dash = $conn->query('SELECT COALESCE(SUM(amount),0) FROM deposit_allocations')->fetch_row()[0];
        $undeposited_dash = max(0, $total_allocated_dash - $total_deposited_dash);
        ?>
<div class="card-grid">
<div class="card accent"><div class="card-icon">📦</div><div class="card-body"><div class="card-label">In Stock</div><div class="card-value"><?= number_format($total_stock) ?></div><div class="card-sub">bikes</div></div></div>
<div class="card success"><div class="card-icon">✅</div><div class="card-body"><div class="card-label">Total Sold</div><div class="card-value"><?= number_format($total_sold) ?></div><div class="card-sub">bikes</div></div></div>
<div class="card danger"><div class="card-icon">↩</div><div class="card-body"><div class="card-label">Returned</div><div class="card-value"><?= number_format($total_returned) ?></div><div class="card-sub">bikes</div></div></div>
<div class="card" style="border-color:#444"><div class="card-icon">🚨</div><div class="card-body"><div class="card-label">Damaged/Lost</div><div class="card-value"><?= number_format($total_damaged) ?></div><div class="card-sub">bikes</div></div></div>
<div class="card warning"><div class="card-icon">💰</div><div class="card-body"><div class="card-label">Purchase Value</div><div class="card-value" style="font-size:1rem"><?= $currency ?> <?= number_format($total_purchase_val) ?></div></div></div>
<div class="card success"><div class="card-icon">💵</div><div class="card-body"><div class="card-label">Sales Value</div><div class="card-value" style="font-size:1rem"><?= $currency ?> <?= number_format($total_sales_val) ?></div></div></div>
<div class="card"><div class="card-icon">🧾</div><div class="card-body"><div class="card-label">Total Tax Paid</div><div class="card-value" style="font-size:1rem"><?= $currency ?> <?= number_format($total_tax, 2) ?></div></div></div>
<div class="card success"><div class="card-icon">📈</div><div class="card-body"><div class="card-label">Total Profit</div><div class="card-value" style="font-size:1rem;color:var(--success)"><?= $currency ?> <?= number_format($total_margin) ?></div></div></div>
<div class="card"><div class="card-icon">💳</div><div class="card-body"><div class="card-label">Pending Cheques</div><div class="card-value" style="font-size:1rem;color:var(--warning)"><?= number_format($pending_payments['c'] ?? 0) ?></div><div class="card-sub"><?= $currency ?> <?= number_format($pending_payments['s'] ?? 0) ?></div></div></div>
<div class="card success"><div class="card-icon">🔥</div><div class="card-body"><div class="card-label">Today's Sales</div><div class="card-value"><?= number_format($todays_sales['c']) ?></div><div class="card-sub"><?= $currency ?> <?= number_format($todays_sales['s'] ?? 0) ?></div></div></div>
<div class="card <?= $undeposited_dash > 0 ? 'warning' : 'success' ?>"><div class="card-icon">🏧</div><div class="card-body"><div class="card-label">Pending Bank Deposit</div><div class="card-value" style="font-size:1rem;color:<?= $undeposited_dash > 0 ? 'var(--warning)' : 'var(--success)' ?>"><?= $currency ?> <?= number_format($undeposited_dash) ?></div><div class="card-sub"><?= fmt_money($total_deposited_dash) ?> deposited</div></div></div>
<div class="card danger"><div class="card-icon">💸</div><div class="card-body"><div class="card-label">Total Expenses</div><div class="card-value" style="font-size:1rem;color:var(--danger)"><?= $currency ?> <?= number_format($total_expenses) ?></div></div></div>
<div class="card accent"><div class="card-icon">👥</div><div class="card-body"><div class="card-label">Customers</div><div class="card-value"><?= number_format($total_customers) ?></div></div></div>
<div class="card warning"><div class="card-icon">🏭</div><div class="card-body"><div class="card-label">Suppliers</div><div class="card-value"><?= number_format($total_suppliers) ?></div></div></div>
</div>
<style>.quick-actions-wrap .btn { flex: 1 1 auto; justify-content: center; min-width: 140px; text-align: center; }</style>
<fieldset class="fieldset"><legend>⚡ Quick Actions</legend>
<div class="quick-actions-wrap" style="display:flex;gap:8px;flex-wrap:wrap">
<?php if (has_permission($conn, 'sale', 'add')): ?><a href="index.php?page=sale" class="btn btn-success animate__animated animate__pulse">🛒 New Sale</a><?php endif; ?>
<?php if (has_permission($conn, 'purchase', 'add')): ?><a href="index.php?page=purchase" class="btn btn-primary animate__animated animate__pulse">📦 New Purchase</a><?php endif; ?>
<?php if (has_permission($conn, 'customers', 'add')): ?><a href="index.php?page=customers" class="btn btn-default">👥 Add Customer</a><?php endif; ?>
<?php if (has_permission($conn, 'income_expense', 'add')): ?><a href="index.php?page=income_expense" class="btn btn-default">💰 Add Expense</a><?php endif; ?>
<?php if (has_permission($conn, 'returns', 'add')): ?><a href="index.php?page=returns" class="btn btn-warning">↩ Process Return</a><?php endif; ?>
<?php if (has_permission($conn, 'inventory', 'view')): ?><a href="index.php?page=inventory" class="btn btn-default" style="background:#1a2d4d;color:#8ab8f0;border-color:#1a2d4d">📋 View Inventory</a><?php endif; ?>
<?php if (has_permission($conn, 'quotations', 'add')): ?><a href="index.php?page=quotations" class="btn btn-info">📝 New Quotation</a><?php endif; ?>
<?php if (has_permission($conn, 'installments', 'view')): ?><a href="index.php?page=installments" class="btn btn-default">🗓️ Installments</a><?php endif; ?>
<?php if (has_permission($conn, 'reports', 'view')): ?>
<a href="index.php?page=reports&sub=daily" class="btn btn-default">📆 Daily Report</a>
<a href="index.php?page=reports&sub=profit" class="btn btn-default">📈 Profit Report</a>
<?php endif; ?>
<?php if (has_permission($conn, 'payments', 'view')): ?><a href="index.php?page=payments" class="btn btn-default">💳 Payments</a><?php endif; ?>
<?php if (has_permission($conn, 'bank_deposits', 'add')): ?><a href="index.php?page=bank_deposits" class="btn btn-default">🏧 New Deposit</a><?php endif; ?>
<?php if (has_permission($conn, 'settings', 'view')): ?><a href="index.php?page=settings" class="btn btn-default">⚙ Settings</a><?php endif; ?>
</div>
</fieldset>
<div class="split-grid" style="margin-bottom:16px;">
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📈 Sales Trend (Last 6 Months)</legend><div style="position:relative;height:250px;width:100%"><canvas id="salesChart"></canvas></div></fieldset>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📊 Model-wise Stock</legend><div style="position:relative;height:250px;width:100%"><canvas id="stockChart"></canvas></div></fieldset>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>💰 Income vs Expense</legend><div style="position:relative;height:250px;width:100%"><canvas id="ieChart"></canvas></div></fieldset>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>🚲 Inventory Status</legend><div style="position:relative;height:250px;width:100%"><canvas id="statusChart"></canvas></div></fieldset>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.color = 'var(--text2)';
    const commonOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { color: 'var(--border)' } }, y: { grid: { color: 'var(--border)' } } } };
    const pieOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: 'var(--text)' } } } };
    new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: { labels: <?= json_encode($chart_labels) ?>, datasets: [{ label: 'Sales (<?= $currency ?>)', data: <?= json_encode($chart_sales) ?>, borderColor: '#4ec94e', tension: 0.3, fill: true, backgroundColor: 'rgba(78, 201, 78, 0.1)' }] },
        options: commonOptions
    });
    new Chart(document.getElementById('stockChart'), {
        type: 'doughnut',
        data: { labels: <?= json_encode($ms_labels) ?>, datasets: [{ data: <?= json_encode($ms_data) ?>, backgroundColor: ['#4a9eff','#4ec94e','#e74c3c','#e0a800','#9b59b6','#34495e','#16a085'] }] },
        options: pieOptions
    });
    new Chart(document.getElementById('ieChart'), {
        type: 'bar',
        data: { labels: ['Income', 'Expense'], datasets: [{ label: 'Amount (<?= $currency ?>)', data: [<?= $ie_data['income'] ?? 0 ?>, <?= $ie_data['expense'] ?? 0 ?>], backgroundColor: ['#4ec94e', '#e74c3c'] }] },
        options: commonOptions
    });
    new Chart(document.getElementById('statusChart'), {
        type: 'pie',
        data: { labels: ['In Stock', 'Sold', 'Returned'], datasets: [{ data: [<?= $total_stock ?>, <?= $total_sold ?>, <?= $total_returned ?>], backgroundColor: ['#4a9eff', '#4ec94e', '#e74c3c'] }] },
        options: pieOptions
    });
});
</script>
<?php if ($pending_payments['c'] > 0): ?>
<div style="background:#3d2a00;border:1px solid var(--warning);padding:8px 14px;margin-bottom:12px;border-radius:2px;font-size:0.82rem;color:#f0c858" class="animate__animated animate__headShake">
⚠ <strong><?= $pending_payments['c'] ?> pending cheque payment(s)</strong> totaling <?= $currency ?> <?= number_format($pending_payments['s'] ?? 0) ?> — <a href="index.php?page=payments">View Payments →</a>
</div>
<?php endif; ?>
<?php if ($overdue_installments['c'] > 0): ?>
<div style="background:#4d1e1e;border:1px solid var(--danger);padding:8px 14px;margin-bottom:12px;border-radius:2px;font-size:0.82rem;color:#f0b8b8" class="animate__animated animate__headShake">
🚨 <strong><?= $overdue_installments['c'] ?> overdue installment(s)</strong> totaling <?= $currency ?> <?= number_format($overdue_installments['s'] ?? 0) ?> — <a href="index.php?page=installments&status_f=overdue">View Overdue Installments →</a>
</div>
<?php endif; ?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📊 Model-wise Stock Summary</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Model</th><th>Category</th><th>Inventory</th><th>Sold</th><th>Returned</th><th>Available</th></tr></thead>
<tbody>
<?php
        $model_summary = $conn->query("SELECT m.model_name, m.category,
    SUM(CASE WHEN 1=1 THEN 1 ELSE 0 END) as total_inv,
    SUM(CASE WHEN b.status='sold' THEN 1 ELSE 0 END) as sold_cnt,
    SUM(CASE WHEN b.status='returned' THEN 1 ELSE 0 END) as ret_cnt,
    SUM(CASE WHEN b.status='damaged_lost' THEN 1 ELSE 0 END) as dmg_cnt,
    SUM(CASE WHEN b.status='in_stock' THEN 1 ELSE 0 END) as avail_cnt
    FROM models m LEFT JOIN bikes b ON m.id=b.model_id
    GROUP BY m.id, m.model_name, m.category ORDER BY m.model_name");
        $ms_totals = [0, 0, 0, 0];
        while ($ms = $model_summary->fetch_assoc()):
            $ms_totals[0] += $ms['total_inv'];
            $ms_totals[1] += $ms['sold_cnt'];
            $ms_totals[2] += $ms['ret_cnt'];
            $ms_totals[3] += $ms['avail_cnt'];
            ?>
<tr>
<td><?= sanitize($ms['model_name']) ?></td>
<td><?= sanitize($ms['category']) ?></td>
<td><?= $ms['total_inv'] ?></td>
<td><span class="badge badge-success"><?= $ms['sold_cnt'] ?></span></td>
<td><span class="badge badge-danger"><?= $ms['ret_cnt'] ?></span></td>
<td><span class="badge badge-info"><?= $ms['avail_cnt'] ?></span></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td><strong>TOTAL</strong></td><td></td><td><strong><?= $ms_totals[0] ?></strong></td><td><strong><?= $ms_totals[1] ?></strong></td><td><strong><?= $ms_totals[2] ?></strong></td><td><strong><?= $ms_totals[3] ?></strong></td></tr></tfoot>
</table>
</div>
</fieldset>
<div class="split-grid">
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>🛒 Recent 10 Sales</legend>
<div class="data-table-wrap">
<table class="data-table no-dt">
<thead><tr><th>Date</th><th>Chassis</th><th>Model</th><th>Price</th><th>Margin</th></tr></thead>
<tbody>
<?php
        $recent_sales = $conn->query("SELECT b.chassis_number, b.selling_date, b.selling_price, b.margin, m.model_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.status='sold' ORDER BY b.selling_date DESC LIMIT 10");
        while ($rs = $recent_sales->fetch_assoc()):
            ?>
<tr class="row-sold">
<td><?= fmt_date($rs['selling_date']) ?></td>
<td><?= sanitize($rs['chassis_number']) ?></td>
<td><?= sanitize($rs['model_name']) ?></td>
<td><?= $currency ?> <?= number_format($rs['selling_price']) ?></td>
<td style="color:<?= $rs['margin'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= $currency ?> <?= number_format($rs['margin']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</fieldset>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📦 Recent 10 Purchases</legend>
<div class="data-table-wrap">
<table class="data-table no-dt">
<thead><tr><th>Date</th><th>Chassis</th><th>Model</th><th>Price</th><th>Status</th></tr></thead>
<tbody>
<?php
        $recent_purch = $conn->query('SELECT b.chassis_number, b.inventory_date, b.purchase_price, b.status, m.model_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id ORDER BY b.created_at DESC LIMIT 10');
        while ($rp = $recent_purch->fetch_assoc()):
            $st_badge = $rp['status'] === 'sold' ? 'badge-success' : ($rp['status'] === 'returned' ? 'badge-danger' : ($rp['status'] === 'reserved' ? 'badge-warning' : 'badge-info'));
            ?>
<tr class="row-<?= $rp['status'] ?>">
<td><?= fmt_date($rp['inventory_date']) ?></td>
<td><?= sanitize($rp['chassis_number']) ?></td>
<td><?= sanitize($rp['model_name']) ?></td>
<td><?= $currency ?> <?= number_format($rp['purchase_price']) ?></td>
<td><span class="badge <?= $st_badge ?>"><?= strtoupper($rp['status']) ?></span></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</fieldset>
</div>
<?php elseif ($page === 'purchase'): ?>
<?php
        $suppliers_list = $conn->query('SELECT id, name FROM suppliers ORDER BY name');
        $models_list = $conn->query('SELECT id, model_code, model_name FROM models ORDER BY model_name');
?>
<form method="POST" id="purchaseForm" enctype="multipart/form-data" class="animate__animated animate__fadeIn">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="save_purchase" value="1">
<fieldset class="fieldset"><legend>📦 Purchase Order Details</legend>
<div class="form-row">
<div class="form-group"><label>Order Date <span class="req">*</span></label><input type="date" name="order_date" value="<?= date('Y-m-d') ?>" required></div>
<div class="form-group"><label>Inventory Date <span class="req">*</span></label><input type="date" name="inventory_date" value="<?= date('Y-m-d') ?>" required></div>
<div class="form-group">
<label>Supplier <span class="req">*</span></label>
<div style="display:flex;gap:4px">
<select name="supplier_id" required style="flex:1">
<option value="">-- Select Supplier --</option>
<?php $suppliers_list->data_seek(0);
        while ($sup = $suppliers_list->fetch_assoc()): ?>
<option value="<?= $sup['id'] ?>"><?= sanitize($sup['name']) ?></option>
<?php endwhile; ?>
</select>
<button type="button" class="btn btn-default btn-sm" onclick="openSupplierModal()">+</button>
</div>
</div>
</div>
<div class="form-row">
<div class="form-group"><label>Notes</label><textarea name="po_notes" rows="2" placeholder="Any additional notes..."></textarea></div>
</div>
</fieldset>
<fieldset class="fieldset"><legend>💵 Payments for this Purchase</legend>
<div id="paymentsList"></div>
<button type="button" class="btn btn-primary btn-sm" onclick="addPaymentRow()" style="margin-top:6px">+ Add Payment</button>
</fieldset>
<fieldset class="fieldset"><legend>🚲 Bike Units</legend>
<div id="bikesList"></div>
<button type="button" class="btn btn-success" onclick="addBikeRow()" style="margin-top:6px">+ Add Bike</button>
</fieldset>
<div id="purchaseSummaryBox" style="background:var(--bg3);padding:12px;border-radius:2px;margin-bottom:14px;display:flex;gap:15px;align-items:center;border:1px solid var(--border);flex-wrap:wrap;">
    <div style="flex:1;min-width:140px"><strong style="color:var(--text2);display:block;font-size:0.75rem;text-transform:uppercase">Total Payment</strong> <span id="sumPay" style="font-size:1.3rem;font-weight:bold;color:var(--success)">0.00</span></div>
    <div style="flex:1;min-width:140px"><strong style="color:var(--text2);display:block;font-size:0.75rem;text-transform:uppercase">Total Purchase</strong> <span id="sumPurch" style="font-size:1.3rem;font-weight:bold;color:var(--warning)">0.00</span></div>
    <div style="flex:1;min-width:140px"><strong style="color:var(--text2);display:block;font-size:0.75rem;text-transform:uppercase">Difference</strong> <span id="sumDiff" style="font-size:1.3rem;font-weight:bold">0.00</span></div>
    <div style="flex:1;min-width:140px"><label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:bold;color:var(--accent);font-size:0.85rem"><input type="checkbox" id="autoDivideCb" checked onchange="updatePurchaseTotals(true)" style="width:16px;height:16px"> Auto-Divide Payment</label></div>
</div>
<div style="display:flex;gap:8px;flex-wrap:wrap">
<button type="submit" name="save_purchase" class="btn btn-primary">💾 Save Purchase Order</button>
<a href="index.php?page=inventory" class="btn btn-default">← Back to Inventory</a>
</div>
</form>
<?php
        $last_purchase_id = $_SESSION['last_purchase_id'] ?? 0;
        unset($_SESSION['last_purchase_id']);
        if ($last_purchase_id):
            ?>
<div style="margin-top:16px; display:flex; gap:10px;">
<a href="index.php?page=purchase&print_po=<?= $last_purchase_id ?>&format=a4" class="btn btn-primary" target="_blank">🖨 Print Letterhead (A4)</a>
<a href="index.php?page=purchase&print_po=<?= $last_purchase_id ?>&format=thermal" class="btn btn-warning" target="_blank">🧾 Print Thermal (POS)</a>
</div>
<?php endif; ?>
<?php
        $print_po_id = (int) ($_GET['print_po'] ?? 0);
        if ($print_po_id):
            echo '<style>.sidebar, .topbar { display: none !important; } .main-wrap { margin-left: 0 !important; } .content > *:not(#receiptArea):not(.no-print) { display: none !important; } .content { padding: 40px !important; background: #333 !important; } body { background: #333 !important; } @media print { .content, body { padding: 0 !important; background: #fff !important; } }</style>';
            $po_r = $conn->query("SELECT po.*, s.name as sup_name, s.contact, s.address FROM purchase_orders po LEFT JOIN suppliers s ON po.supplier_id=s.id WHERE po.id=$print_po_id");
            $po = $po_r ? $po_r->fetch_assoc() : null;
            $po_no = 'PO-' . date('Ymd') . '-' . str_pad($print_po_id, 3, '0', STR_PAD_LEFT);
            $format = $_GET['format'] ?? 'a4';
            $container_class = $format === 'thermal' ? 'thermal-receipt' : 'a4-invoice';
            if ($po):
                ?>
<div class="<?= $container_class ?>" id="receiptArea">
    <div class="invoice-header">
        <h1>⚡ <?= sanitize(get_setting('company_name') ?? 'BNI Enterprises') ?></h1>
        <h2><?= sanitize(get_setting('branch_name') ?? 'Dera (Ahmed Metro)') ?></h2>
        <?php
        $raw_wa = get_setting('company_whatsapp') ?? '';
        $wa_numbers = array_filter(array_map('trim', explode(',', $raw_wa)));
        if (!empty($wa_numbers)):
            ?>
        <div style="font-size:0.85rem;margin-top:2px;font-weight:normal;">WhatsApp: <?= sanitize(implode(', ', $wa_numbers)) ?></div>
        <?php endif; ?>
        <div style="font-size:0.9rem;margin-top:4px"><strong>PURCHASE RECEIPT</strong></div>
    </div>
    <div class="invoice-meta">
        <div><strong>PO #:</strong> <?= $po_no ?><br><strong>Date:</strong> <?= fmt_date($po['order_date']) ?></div>
        <div class="<?= $format === 'thermal' ? '' : 'text-right' ?>" style="<?= $format === 'thermal' ? 'margin-top:10px;' : 'text-align:right;' ?>">
            <strong>Supplier:</strong> <?= sanitize($po['sup_name'] ?? 'Unknown') ?><br>
            <?php if ($po['contact']): ?><strong>Contact:</strong> <?= sanitize($po['contact']) ?><br><?php endif; ?>
        </div>
    </div>
    <div class="invoice-section">
        <h3>Bikes Purchased</h3>
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Chassis</th>
                    <?= $format === 'a4' ? '<th>Model</th><th>Color</th>' : '' ?>
                    <th style="text-align:right">Price</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $bikes_po = $conn->query("SELECT b.chassis_number, b.color, b.purchase_price, m.model_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.purchase_order_id=$print_po_id");
                while ($bpo = $bikes_po->fetch_assoc()):
                    ?>
                <tr>
                    <td style="font-family:Consolas,monospace"><?= sanitize($bpo['chassis_number']) ?><?= $format === 'thermal' ? '<br><small>' . sanitize($bpo['model_name']) . '</small>' : '' ?></td>
                    <?= $format === 'a4' ? '<td>' . sanitize($bpo['model_name']) . '</td><td>' . sanitize($bpo['color']) . '</td>' : '' ?>
                    <td style="text-align:right"><?= fmt_money($bpo['purchase_price']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <div class="invoice-section">
        <h3>Summary</h3>
        <table class="invoice-table" style="width:<?= $format === 'thermal' ? '100%' : '50%' ?>; margin-left:<?= $format === 'thermal' ? '0' : 'auto' ?>;">
            <tbody>
                <tr><td><strong>Total Units</strong></td><td style="text-align:right"><?= $po['total_units'] ?></td></tr>
                <tr><td><strong>Total Amount</strong></td><td style="text-align:right"><?= fmt_money($po['total_amount']) ?></td></tr>
            </tbody>
        </table>
    </div>
    <div class="invoice-footer">
        Generated by: Yasin Ullah – BSS<br>WhatsApp: 03361593533
    </div>
</div>
<div class="no-print" style="margin-top:10px">
    <button onclick="window.print()" class="btn btn-success">🖨 Print Receipt</button>
    <a href="index.php?page=inventory" class="btn btn-default">Back to Inventory</a>
</div>
<?php endif;
        endif; ?>
<div class="modal-overlay" id="addSupplierModal">
<div class="modal">
<div class="modal-header"><h3>Add New Supplier</h3><button class="modal-close" onclick="closeSupplierModal()">✕</button></div>
<form id="supplierForm" class="ajax-form" method="POST" action="index.php?page=suppliers&action=add">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<div class="form-group" style="margin-bottom:8px"><label>Name <span class="req">*</span></label><input type="text" name="name" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Contact</label><input type="text" name="contact"></div>
<div class="form-group" style="margin-bottom:12px"><label>Address</label><textarea name="address" rows="2"></textarea></div>
<button type="submit" class="btn btn-primary">Save Supplier</button>
</form>
</div>
</div>
<div class="modal-overlay" id="addModelModal">
<div class="modal">
<div class="modal-header"><h3>Add New Model</h3><button class="modal-close" onclick="closeModelModal()">✕</button></div>
<form id="modelForm" class="ajax-form" method="POST" enctype="multipart/form-data" action="index.php?page=models&action=add">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<div class="form-group" style="margin-bottom:8px"><label>Model Code <span class="req">*</span></label><input type="text" name="model_code" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Model Name <span class="req">*</span></label><input type="text" name="model_name" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Category</label><input type="text" name="category" value="Electric Bike"></div>
<div class="form-group" style="margin-bottom:8px"><label>Short Code</label><input type="text" name="short_code"></div>
<div class="form-group" style="margin-bottom:12px"><label>Image</label><input type="file" name="image" accept="image/*" style="padding:4px"></div>
<button type="submit" class="btn btn-primary">Save Model</button>
</form>
</div>
</div>
<script>
var prefillModelId = <?= (int) ($_GET['model_id'] ?? 0) ?>;
var bikeCount = 0;
var paymentCount = 0;
var modelsOptions = `<?php $models_list->data_seek(0);
        $mo = '';
        while ($m = $models_list->fetch_assoc())
            $mo .= '<option value="' . $m['id'] . '">' . $m['model_code'] . ' - ' . $m['model_name'] . '</option>';
        echo $mo; ?>`;
var allSuppliers = <?= json_encode($conn->query('SELECT id, name FROM suppliers ORDER BY name')->fetch_all(MYSQLI_ASSOC)) ?>;
function updatePurchaseTotals(triggeredByGlobalChange = false) {
    let autoDivideCb = document.getElementById('autoDivideCb');
    if (!triggeredByGlobalChange && autoDivideCb) {
        autoDivideCb.checked = false;
    }
    let totalPay = 0;
    document.querySelectorAll('.pay-amount-input').forEach(inp => totalPay += parseFloat(inp.value) || 0);
    let bikeInputs = document.querySelectorAll('.bike-price-input');
    if (triggeredByGlobalChange && autoDivideCb && autoDivideCb.checked && bikeInputs.length > 0) {
        let divided = (totalPay / bikeInputs.length).toFixed(2);
        bikeInputs.forEach(inp => inp.value = divided);
    }
    let totalPurch = 0;
    bikeInputs.forEach(inp => totalPurch += parseFloat(inp.value) || 0);
    let diff = totalPay - totalPurch;
    let sumPayEl = document.getElementById('sumPay');
    let sumPurchEl = document.getElementById('sumPurch');
    let sumDiffEl = document.getElementById('sumDiff');
    if (sumPayEl) sumPayEl.innerText = totalPay.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (sumPurchEl) sumPurchEl.innerText = totalPurch.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    if (sumDiffEl) {
        sumDiffEl.innerText = Math.abs(diff).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        sumDiffEl.style.color = diff === 0 ? 'var(--text)' : (diff > 0 ? 'var(--success)' : 'var(--danger)');
    }
}
function addBikeRow() {
    bikeCount++;
    var d = document.createElement('div');
    d.className = 'bike-row animate__animated animate__fadeInDown';
    d.id = 'bikeRow_'+bikeCount;
    d.innerHTML = `<div class="bike-row-header"><span class="bike-row-num">🚲 Bike #${bikeCount}</span><button type="button" class="bike-row-del" onclick="removeBikeRow(${bikeCount})">✕ Remove</button></div>
    <div class="form-row">
    <div class="form-group"><label>Chassis Number <span class="req">*</span></label><input type="text" name="bikes[${bikeCount}][chassis]" required placeholder="e.g. KIU-2024-001" onblur="checkChassis(this)"></div>
    <div class="form-group"><label>Motor Number</label><input type="text" name="bikes[${bikeCount}][motor]" placeholder="e.g. MT-001"></div>
    <div class="form-group"><label>Model <span class="req">*</span></label>
    <div style="display:flex;gap:4px"><select name="bikes[${bikeCount}][model_id]" required class="select2-enable" style="flex:1"><option value="">-- Model --</option>${modelsOptions}</select>
    <button type="button" class="btn btn-default btn-sm" onclick="openModelModal()">+</button></div></div>
    </div>
    <div class="form-row">
    <div class="form-group"><label>Color</label><input type="text" name="bikes[${bikeCount}][color]" placeholder="Red, Black, White..."></div>
    <div class="form-group"><label>Purchase Price (Rs.) <span class="req">*</span></label><input type="number" name="bikes[${bikeCount}][purchase_price]" class="bike-price-input" step="0.01" min="0" required placeholder="0.00" oninput="updatePurchaseTotals(false)"></div>
    <div class="form-group"><label>Safeguard Notes</label><input type="text" name="bikes[${bikeCount}][safeguard_notes]" placeholder="Helmet, Tyre, Warranty..."></div>
    </div>
    <div class="form-row">
    <div class="form-group"><label>Notes</label><input type="text" name="bikes[${bikeCount}][notes]" placeholder="Any notes..."></div>
    <div class="form-group"><label>Image (Optional)</label><input type="file" name="bikes[${bikeCount}][image]" accept="image/*" style="padding:4px"></div>
    </div>`;
    document.getElementById('bikesList').appendChild(d);
    if (prefillModelId && bikeCount === 1) {
        $(d).find(`select[name="bikes[${bikeCount}][model_id]"]`).val(prefillModelId).trigger('change');
    }
    $(d).find('.select2-enable').select2({
        minimumResultsForSearch: 10,
        placeholder: '-- Select --',
        allowClear: false,
        theme: 'default'
    });
    if (typeof updatePurchaseTotals === 'function') updatePurchaseTotals(true);
}
function addPaymentRow() {
    paymentCount++;
    var d = document.createElement('div');
    d.className = 'bike-row animate__animated animate__fadeInDown'; 
    d.id = 'paymentRow_'+paymentCount;
    d.innerHTML = `<div class="bike-row-header"><span class="bike-row-num">💵 Payment #${paymentCount}</span><button type="button" class="bike-row-del" onclick="removePaymentRow(${paymentCount})">✕ Remove</button></div>
    <div class="form-row">
    <div class="form-group"><label>Payment Type <span class="req">*</span></label>
        <select name="payments[${paymentCount}][payment_type]" onchange="togglePaymentFields(this, ${paymentCount})" required>
            <option value="cash">Cash</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="cheque">Cheque</option>
            <option value="online">Online</option>
        </select>
    </div>
    <div class="form-group"><label>Amount (Rs.) <span class="req">*</span></label><input type="number" name="payments[${paymentCount}][amount]" class="pay-amount-input" step="0.01" min="0" required placeholder="0.00" oninput="updatePurchaseTotals(true)"></div>
    </div>
    <div id="paymentChequeFields_${paymentCount}" style="display:none" class="form-row">
        <div class="form-group"><label>Cheque Number</label><input type="text" name="payments[${paymentCount}][cheque_number]" placeholder="CHQ-001"></div>
        <div class="form-group"><label>Bank Name</label><input type="text" name="payments[${paymentCount}][bank_name]" placeholder="HBL, MCB..."></div>
        <div class="form-group"><label>Cheque Date</label><input type="date" name="payments[${paymentCount}][cheque_date]"></div>
    </div>`;
    document.getElementById('paymentsList').appendChild(d);
    $(d).find('select').select2({ minimumResultsForSearch: 10, placeholder: '-- Select --', allowClear: false, theme: 'default' });
    if (typeof updatePurchaseTotals === 'function') updatePurchaseTotals(true);
}
function togglePaymentFields(selectElement, index) {
    var chequeFields = document.getElementById('paymentChequeFields_' + index);
    if (selectElement.value === 'cheque') {
        chequeFields.style.display = 'flex';
        $(chequeFields).find('input').attr('required', true);
    } else {
        chequeFields.style.display = 'none';
        $(chequeFields).find('input').removeAttr('required');
    }
}
function removeBikeRow(n) {
    var el = document.getElementById('bikeRow_'+n);
    if (el) el.remove();
    if (typeof updatePurchaseTotals === 'function') updatePurchaseTotals(true);
}
function removePaymentRow(n) {
    var el = document.getElementById('paymentRow_'+n);
    if (el) el.remove();
    if (typeof updatePurchaseTotals === 'function') updatePurchaseTotals(true);
}
function checkChassis(inp) {
    var val = inp.value.trim();
    if (!val) {
        inp.style.borderColor = '';
        inp.title = '';
        return;
    }
    $.ajax({
        url: 'index.php?ajax=check_chassis&chassis='+encodeURIComponent(val),
        type: 'GET',
        success: function(response) {
            if (response === '1') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Chassis Number Exists!',
                    text: 'WARNING: Chassis number "' + val + '" already exists in the system!',
                    customClass: {
                        popup: 'animate__animated animate__shakeX'
                    }
                });
                inp.classList.add('just-validate-error-field');
                inp.title = 'WARNING: This chassis number already exists!';
            } else {
                inp.classList.remove('just-validate-error-field');
                inp.style.borderColor = 'var(--success)';
                inp.title = 'Chassis number is unique.';
            }
        },
        error: function() {
            Swal.fire('Error', 'Could not check chassis number uniqueness.', 'error');
        }
    });
}
function openSupplierModal() {
    document.getElementById('addSupplierModal').classList.add('open');
}
function closeSupplierModal(selectName) {
    document.getElementById('addSupplierModal').classList.remove('open');
    $.ajax({
        url: 'index.php?ajax=get_suppliers',
        type: 'GET',
        cache: false,
        success: function(response) {
            var newOptions = JSON.parse(response);
            var supplierSelect = $('select[name="supplier_id"]');
            var currentVal = supplierSelect.val();
            supplierSelect.empty();
            supplierSelect.append('<option value="">-- Select Supplier --</option>');
            var newValToSelect = currentVal;
            var sName = selectName ? selectName.trim().toLowerCase() : null;
            newOptions.forEach(function(sup) {
                supplierSelect.append(`<option value="${sup.id}">${sup.name}</option>`);
                if (sName && sup.name.toLowerCase() === sName) newValToSelect = sup.id;
            });
            supplierSelect.val(newValToSelect).trigger('change');
        }
    });
}
function openModelModal() {
    document.getElementById('addModelModal').classList.add('open');
}
function closeModelModal(selectName) {
    document.getElementById('addModelModal').classList.remove('open');
    $.ajax({
        url: 'index.php?ajax=get_models',
        type: 'GET',
        cache: false,
        success: function(response) {
            var models = JSON.parse(response);
            var newModelId = null;
            var sName = selectName ? selectName.trim().toLowerCase() : null;
            if (sName) {
                var found = models.find(m => m.model_name.toLowerCase() === sName || m.model_code.toLowerCase() === sName);
                if (found) newModelId = found.id;
            }
            modelsOptions = models.map(m => `<option value="${m.id}">${m.model_code} - ${m.model_name}</option>`).join('');
            $('select[name$="[model_id]"]').each(function() {
                var currentVal = $(this).val();
                $(this).empty().append('<option value="">-- Model --</option>' + modelsOptions);
                if (!currentVal && newModelId) {
                    $(this).val(newModelId).trigger('change');
                } else {
                    $(this).val(currentVal).trigger('change');
                }
            });
        }
    });
}
addBikeRow();
addPaymentRow();
$(document).ready(function() {
    $('select[name="supplier_id"]').select2({
        minimumResultsForSearch: 10,
        placeholder: '-- Select Supplier --',
        allowClear: false,
        theme: 'default'
    });
});
</script>
<?php
    elseif ($page === 'inventory'):
        if (isset($_GET['reset_filters'])) {
            unset($_SESSION['inv_filters']);
            setcookie('inv_filters', '', time() - 3600, '/');
            echo '<script>window.location.href="index.php?page=inventory";</script>';
            exit;
        }
        if (isset($_GET['search_f']) || isset($_GET['status_f']) || isset($_GET['model_f']) || isset($_GET['color_f']) || isset($_GET['date_from']) || isset($_GET['date_to'])) {
            $_SESSION['inv_filters'] = [
                'status_f' => $_GET['status_f'] ?? '',
                'model_f' => $_GET['model_f'] ?? 0,
                'color_f' => $_GET['color_f'] ?? '',
                'search_f' => $_GET['search_f'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? ''
            ];
            setcookie('inv_filters', json_encode($_SESSION['inv_filters']), time() + (86400 * 30), '/');
        } elseif (!isset($_SESSION['inv_filters']) && isset($_COOKIE['inv_filters'])) {
            $cookie_filters = json_decode($_COOKIE['inv_filters'], true);
            if (is_array($cookie_filters)) {
                $_SESSION['inv_filters'] = $cookie_filters;
            }
        }
        $status_f = sanitize($_SESSION['inv_filters']['status_f'] ?? '');
        $model_f = (int) ($_SESSION['inv_filters']['model_f'] ?? 0);
        $color_f = sanitize($_SESSION['inv_filters']['color_f'] ?? '');
        $search_f = sanitize($_SESSION['inv_filters']['search_f'] ?? '');
        $date_from = $_SESSION['inv_filters']['date_from'] ?? '';
        $date_to = $_SESSION['inv_filters']['date_to'] ?? '';
        $where_parts = ['1=1'];
        if ($status_f && in_array($status_f, ['in_stock', 'sold', 'returned', 'returned_to_supplier', 'reserved', 'damaged_lost']))
            $where_parts[] = "b.status='$status_f'";
        if ($model_f)
            $where_parts[] = "b.model_id=$model_f";
        if ($color_f)
            $where_parts[] = "b.color LIKE '%" . mysqli_real_escape_string($conn, $color_f) . "%'";
        if ($search_f)
            $where_parts[] = "(b.chassis_number LIKE '%" . mysqli_real_escape_string($conn, $search_f) . "%' OR b.motor_number LIKE '%" . mysqli_real_escape_string($conn, $search_f) . "%' OR m.model_name LIKE '%" . mysqli_real_escape_string($conn, $search_f) . "%' OR b.color LIKE '%" . mysqli_real_escape_string($conn, $search_f) . "%')";
        if ($date_from)
            $where_parts[] = "b.inventory_date >= '" . mysqli_real_escape_string($conn, $date_from) . "'";
        if ($date_to)
            $where_parts[] = "b.inventory_date <= '" . mysqli_real_escape_string($conn, $date_to) . "'";
        $where = implode(' AND ', $where_parts);
        $bikes_result = $conn->query("SELECT b.*, m.model_name, m.model_code, c.name as cust_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id WHERE $where ORDER BY b.created_at DESC");
        $models_filter_list = $conn->query('SELECT id, model_code, model_name FROM models ORDER BY model_name');
        $edit_bike_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_bike = null;
        if ($edit_bike_id) {
            $er = $conn->query("SELECT * FROM bikes WHERE id=$edit_bike_id");
            $edit_bike = $er ? $er->fetch_assoc() : null;
        }
        $view_bike_id = (int) ($_GET['view_id'] ?? 0);
        $view_bike = null;
        if ($view_bike_id) {
            $vr = $conn->query("SELECT b.*, m.model_name, m.model_code, m.category, c.name as cust_name, c.phone as cust_phone, c.cnic as cust_cnic, s.name as sup_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id LEFT JOIN purchase_orders po ON b.purchase_order_id=po.id LEFT JOIN suppliers s ON po.supplier_id=s.id WHERE b.id=$view_bike_id");
            $view_bike = $vr ? $vr->fetch_assoc() : null;
        }
?>
<?php if ($view_bike): ?>
<div class="print-btn-wrap no-print"><button onclick="window.print()" class="btn btn-default btn-sm">🖨 Print</button> <a href="index.php?page=inventory" class="btn btn-default btn-sm">← Back</a></div>
<fieldset class="fieldset animate__animated animate__fadeIn"><legend>🚲 Bike Details — <?= sanitize($view_bike['chassis_number']) ?></legend>
<?php if (!empty($view_bike['image'])): ?>
<div style="margin-bottom:14px"><img src="<?= sanitize($view_bike['image']) ?>" style="max-height:150px;border-radius:2px;border:1px solid var(--border)"></div>
<?php endif; ?>
<div class="split-grid-3" style="margin-bottom:12px">
<?php
            $detail_fields = [
                ['Chassis Number', $view_bike['chassis_number']],
                ['Motor Number', $view_bike['motor_number']],
                ['Model', $view_bike['model_name'] . ' (' . $view_bike['model_code'] . ')'],
                ['Category', $view_bike['category']],
                ['Color', $view_bike['color']],
                ['Status', strtoupper($view_bike['status'])],
                ['Purchase Price', fmt_money($view_bike['purchase_price'])],
                ['Selling Price', $view_bike['selling_price'] ? fmt_money($view_bike['selling_price']) : '-'],
                ['Tax Amount', fmt_money($view_bike['tax_amount'])],
                ['Tax Rate', $view_bike['tax_rate_applied'] !== null ? ($view_bike['tax_rate_applied'] * 100) . '%' : '-'],
                ['Tax Basis', $view_bike['tax_basis'] ?? '-'],
                ['Margin', $view_bike['margin'] ? fmt_money($view_bike['margin']) : '-'],
                ['Order Date', fmt_date($view_bike['order_date'])],
                ['Inventory Date', fmt_date($view_bike['inventory_date'])],
                ['Selling Date', fmt_date($view_bike['selling_date'])],
                ['Customer', $view_bike['cust_name'] ?? '-'],
                ['Customer Phone', $view_bike['cust_phone'] ?? '-'],
                ['Supplier', $view_bike['sup_name'] ?? '-'],
                ['Safeguard Notes', $view_bike['safeguard_notes'] ?? '-'],
            ];
            foreach ($detail_fields as $df):
                ?>
<div style="background:var(--bg2);border:1px solid var(--border);padding:8px 10px;border-radius:1px">
<div style="font-size:0.72rem;color:var(--text2);text-transform:uppercase;font-weight:700;margin-bottom:3px"><?= $df[0] ?></div>
<div style="font-size:0.87rem;color:var(--text)"><?= sanitize($df[1] ?? '-') ?></div>
</div>
<?php endforeach; ?>
</div>
<?php if ($view_bike['notes']): ?>
<div style="margin-top:8px"><strong style="font-size:0.78rem;color:var(--text2)">NOTES:</strong> <span style="font-size:0.85rem"><?= sanitize($view_bike['notes']) ?></span></div>
<?php endif; ?>
<hr style="border-color:var(--border);margin:14px 0">
<h4 style="font-size:0.82rem;color:var(--accent);text-transform:uppercase;margin-bottom:10px">📅 Bike History Timeline</h4>
<ul class="timeline">
<li><div class="timeline-dot" style="background:#4a9eff"></div><div class="timeline-content"><div class="timeline-date"><?= fmt_date($view_bike['order_date']) ?></div><div class="timeline-text">📦 <strong>Purchased</strong> — <?= sanitize($view_bike['sup_name'] ?? 'Unknown Supplier') ?> | <?= fmt_money($view_bike['purchase_price']) ?></div></div></li>
<li><div class="timeline-dot" style="background:#4ec94e"></div><div class="timeline-content"><div class="timeline-date"><?= fmt_date($view_bike['inventory_date']) ?></div><div class="timeline-text">📋 <strong>Added to Inventory</strong> — Status: IN STOCK</div></div></li>
<?php if ($view_bike['status'] === 'sold' || $view_bike['selling_date']): ?>
<li><div class="timeline-dot" style="background:#4ec94e"></div><div class="timeline-content"><div class="timeline-date"><?= fmt_date($view_bike['selling_date']) ?></div><div class="timeline-text">🛒 <strong>Sold</strong> to <?= sanitize($view_bike['cust_name'] ?? 'Cash Customer') ?> — <?= fmt_money($view_bike['selling_price']) ?> | Margin: <?= fmt_money($view_bike['margin']) ?></div></div></li>
<?php endif; ?>
<?php if ($view_bike['status'] === 'damaged_lost'): ?>
<li><div class="timeline-dot" style="background:#444"></div><div class="timeline-content"><div class="timeline-text">🚨 <strong>Marked as Damaged / Lost</strong></div></div></li>
<?php endif; ?>
<?php if ($view_bike['status'] === 'returned_to_supplier'): ?>
<li><div class="timeline-dot" style="background:#e74c3c"></div><div class="timeline-content"><div class="timeline-date"><?= fmt_date($view_bike['return_date']) ?></div><div class="timeline-text">📤 <strong>Returned to Supplier</strong> — Refund: <?= fmt_money($view_bike['return_amount']) ?> | Notes: <?= sanitize($view_bike['return_notes'] ?? '-') ?></div></div></li>
<?php elseif ($view_bike['status'] === 'returned' || $view_bike['return_date']): ?>
<li><div class="timeline-dot" style="background:#e74c3c"></div><div class="timeline-content"><div class="timeline-date"><?= fmt_date($view_bike['return_date']) ?></div><div class="timeline-text">↩ <strong>Returned by Customer</strong> — Refund: <?= fmt_money($view_bike['return_amount']) ?> | Notes: <?= sanitize($view_bike['return_notes'] ?? '-') ?></div></div></li>
<?php endif; ?>
</ul>
<?php
            if ($view_bike['status'] === 'sold' && has_permission($conn, 'money_tracking', 'view')):
                $alloc_result = $conn->query("SELECT sma.*, md.name as dest_name, md.type as dest_type, md.details as dest_details,
                    COALESCE((SELECT SUM(da.amount) FROM deposit_allocations da WHERE da.allocation_id=sma.id),0) as deposited_amount,
                    u.full_name as created_by_name FROM sale_money_allocations sma JOIN money_destinations md ON sma.destination_id=md.id LEFT JOIN users u ON sma.created_by=u.id WHERE sma.bike_id=$view_bike_id ORDER BY sma.allocation_date");
                $acc_total_q = $conn->query("SELECT COALESCE(SUM(sa.final_price),0) as t FROM sale_accessories sa WHERE sa.bike_id=$view_bike_id");
                $acc_total_row = $acc_total_q ? $acc_total_q->fetch_assoc() : ['t' => 0];
                $sale_total = $view_bike['selling_price'] + $acc_total_row['t'];
                $alloc_total = 0;
                $alloc_rows = [];
                if ($alloc_result) {
                    while ($ar = $alloc_result->fetch_assoc()) {
                        $alloc_rows[] = $ar;
                        $alloc_total += $ar['amount'];
                    }
                }
                $remaining = $sale_total - $alloc_total;
                ?>
<hr style="border-color:var(--border);margin:14px 0">
<h4 style="font-size:0.82rem;color:var(--accent);text-transform:uppercase;margin-bottom:10px">💸 Money Destination Tracking</h4>
<div class="stats-cards" style="margin-bottom:12px">
<div class="stat-card"><div class="stat-value"><?= fmt_money($sale_total) ?></div><div class="stat-label">Sale Total</div></div>
<div class="stat-card" style="border-left:3px solid var(--success)"><div class="stat-value" style="color:var(--success)"><?= fmt_money($alloc_total) ?></div><div class="stat-label">Allocated</div></div>
<div class="stat-card" style="border-left:3px solid <?= $remaining > 0 ? 'var(--warning)' : 'var(--success)' ?>"><div class="stat-value" style="color:<?= $remaining > 0 ? 'var(--warning)' : 'var(--success)' ?>"><?= fmt_money($remaining) ?></div><div class="stat-label">Remaining</div></div>
</div>
<?php if (count($alloc_rows) > 0): ?>
<div class="data-table-wrap">
<table class="data-table" style="font-size:0.82rem">
<thead><tr><th>Sr#</th><th>Type</th><th>Destination</th><th>Amount</th><th>Date</th><th>Notes</th><th>By</th></tr></thead>
<tbody>
<?php
                    $sr = 1;
                    foreach ($alloc_rows as $ar):
                        $dti = ['bank' => '🏦', 'person' => '👤', 'wallet' => '💳'][$ar['dest_type']] ?? '📌';
                        ?>
<tr>
<td><?= $sr++ ?></td>
<td><span class="badge badge-<?= $ar['dest_type'] === 'bank' ? 'info' : ($ar['dest_type'] === 'person' ? 'success' : 'warning') ?>"><?= $dti ?> <?= strtoupper($ar['dest_type']) ?></span></td>
<td><strong><?= sanitize($ar['dest_name']) ?></strong><br><small style="color:var(--text3)"><?= sanitize($ar['dest_details'] ?: '') ?></small></td>
<td><strong><?= fmt_money($ar['amount']) ?></strong></td>
<td><?= fmt_date($ar['allocation_date']) ?></td>
<td><?= sanitize($ar['notes'] ?: '-') ?></td>
<td style="font-size:0.75rem;color:var(--text3)"><?= sanitize($ar['created_by_name'] ?? '-') ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php else: ?>
<div style="background:var(--bg3);padding:12px;border-radius:2px;text-align:center;color:var(--text3);font-size:0.85rem">
⚠️ No money allocations recorded for this sale yet.
</div>
<?php endif; ?>
<div class="no-print" style="margin-top:10px">
<a href="index.php?page=money_tracking&filter_bike=<?= $view_bike_id ?>" class="btn btn-primary btn-sm">💸 Manage Allocations</a>
</div>
<?php endif; ?>
</fieldset>
<?php else: ?>
<?php if ($status_f || $model_f || $color_f || $search_f || $date_from || $date_to): ?>
<div style="background:var(--bg3); border-left:4px solid var(--warning); padding:10px 14px; border-radius:2px; margin-bottom:12px; font-size:0.85rem; display:flex; justify-content:space-between; align-items:center;" class="no-print animate__animated animate__fadeIn">
    <span style="color:var(--text)"><strong>ℹ️ Active Filters Applied:</strong> You are currently viewing a filtered list of inventory.</span>
    <a href="index.php?page=inventory&reset_filters=1" class="btn btn-sm btn-danger" style="text-decoration:none;">✖ Remove Filters</a>
</div>
<?php endif; ?>
<div class="filter-bar no-print">
<form method="GET" id="filterForm" action="index.php" style="display:contents">
<input type="hidden" name="page" value="inventory">
<div class="form-group"><label>Search</label><input type="text" name="search_f" value="<?= sanitize($search_f) ?>" placeholder="Chassis, Motor, Model, Color"></div>
<div class="form-group"><label>Status</label>
<select name="status_f">
<option value="">All</option>
<option value="in_stock" <?= $status_f === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
<option value="sold" <?= $status_f === 'sold' ? 'selected' : '' ?>>Sold</option>
<option value="returned" <?= $status_f === 'returned' ? 'selected' : '' ?>>Returned (Sales)</option>
<option value="returned_to_supplier" <?= $status_f === 'returned_to_supplier' ? 'selected' : '' ?>>Returned (Purchase)</option>
<option value="reserved" <?= $status_f === 'reserved' ? 'selected' : '' ?>>Reserved</option>
<option value="damaged_lost" <?= $status_f === 'damaged_lost' ? 'selected' : '' ?>>Damaged / Lost</option>
</select>
</div>
<div class="form-group"><label>Model</label>
<select name="model_f">
<option value="0">All Models</option>
<?php $models_filter_list->data_seek(0);
            while ($mf = $models_filter_list->fetch_assoc()): ?>
<option value="<?= $mf['id'] ?>" <?= $model_f == $mf['id'] ? 'selected' : '' ?>><?= sanitize($mf['model_code']) ?></option>
<?php endwhile; ?>
</select>
</div>
<div class="form-group"><label>Color</label><input type="text" name="color_f" value="<?= sanitize($color_f) ?>" placeholder="Color"></div>
<div class="form-group"><label>From</label><input type="date" name="date_from" value="<?= $date_from ?>"></div>
<div class="form-group"><label>To</label><input type="date" name="date_to" value="<?= $date_to ?>"></div>
<div class="filter-actions">
<button type="submit" class="btn btn-primary btn-sm">🔍 Apply Filters</button>
<a href="index.php?page=inventory&reset_filters=1" class="btn btn-default btn-sm">Reset</a>
<a href="index.php?page=inventory&export_csv=1&status_f=<?= urlencode($status_f) ?>&model_f=<?= $model_f ?>&color_f=<?= urlencode($color_f) ?>&search_f=<?= urlencode($search_f) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>" class="btn btn-default btn-sm">⬇ CSV</a>
</div>
</form>
</div>
<form method="POST" id="bulkForm" action="index.php?page=inventory&action=bulk_delete">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<div style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;align-items:center" class="no-print animate__animated animate__fadeInLeft">
<span style="font-size:0.8rem;color:var(--text2)">Total: <?= $bikes_result->num_rows ?> record(s)</span>
<?php if (has_permission($conn, 'purchase', 'add')): ?><a href="index.php?page=purchase" class="btn btn-success btn-sm">+ New Purchase</a><?php endif; ?>
<?php if (has_permission($conn, 'inventory', 'delete')): ?><button type="submit" name="bulk_action" value="bulk_delete" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete Selected?', text: 'Are you sure you want to delete selected bikes? This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete them!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑 Delete Selected</button><?php endif; ?>
<button type="submit" form="bulkExportForm" class="btn btn-default btn-sm">⬇ Export Selected</button>
<button onclick="window.print()" type="button" class="btn btn-default btn-sm">🖨 Print</button>
<button type="button" class="btn btn-default btn-sm" onclick="toggleSelectAll()">☑ Select All</button>
</div>
<div class="data-table-wrap">
<table class="data-table" id="invTable">
<thead>
<tr>
<th style="width:30px"><input type="checkbox" id="selectAll" onchange="toggleSelectAll()" class="no-sort"></th>
<th>Sr#</th>
<th class="no-sort">Pic</th>
<th>Chassis</th>
<th>Motor#</th>
<th>Model</th>
<th>Color</th>
<th>Purchase Price</th>
<th>Tax</th>
<th>Status</th>
<th>Selling Price</th>
<th>Selling Date</th>
<th>Margin</th>
<th class="no-sort">Actions</th>
</tr>
</thead>
<tbody>
<?php
            $sr = 1;
            $total_pp = 0;
            $total_sp = 0;
            $total_mg = 0;
            $total_tax = 0;
            while ($bike = $bikes_result->fetch_assoc()):
                $st_badge = $bike['status'] === 'sold' ? 'badge-success' : (in_array($bike['status'], ['returned', 'returned_to_supplier']) ? 'badge-danger' : ($bike['status'] === 'damaged_lost' ? 'badge-dark' : ($bike['status'] === 'reserved' ? 'badge-warning' : 'badge-info')));
                $total_pp += $bike['purchase_price'];
                $total_sp += $bike['selling_price'] ?? 0;
                $total_mg += $bike['margin'] ?? 0;
                $total_tax += $bike['tax_amount'] ?? 0;
                ?>
<tr class="row-<?= $bike['status'] ?>">
<td><input type="checkbox" name="selected_bikes[]" value="<?= $bike['id'] ?>" class="bike-check"></td>
<td><?= $sr++ ?></td>
<td><?php if (!empty($bike['image'])): ?><img src="<?= sanitize($bike['image']) ?>" style="height:24px;width:auto;border-radius:2px;cursor:pointer" onclick="window.open(this.src)"><?php else: ?>-<?php endif; ?></td>
<td style="font-family:Consolas,monospace;font-size:0.8rem"><?= sanitize($bike['chassis_number']) ?></td>
<td style="font-family:Consolas,monospace;font-size:0.8rem"><?= sanitize($bike['motor_number'] ?? '-') ?></td>
<td><?= sanitize($bike['model_name'] ?? '-') ?></td>
<td><?= sanitize($bike['color'] ?? '-') ?></td>
<td><?= fmt_money($bike['purchase_price']) ?></td>
<td><?= $bike['tax_amount'] > 0 ? fmt_money($bike['tax_amount']) : '-' ?></td>
<td><span class="badge <?= $st_badge ?>"><?= strtoupper($bike['status']) ?></span></td>
<td><?= $bike['selling_price'] ? fmt_money($bike['selling_price']) : '-' ?></td>
<td><?= fmt_date($bike['selling_date']) ?></td>
<td style="color:<?= ($bike['margin'] ?? 0) >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= $bike['status'] === 'sold' ? fmt_money($bike['margin']) : '-' ?></td>
<td>
<div class="actions-col">
<a href="index.php?page=inventory&view_id=<?= $bike['id'] ?>" class="btn btn-default btn-sm" title="View">👁</a>
<?php if ($bike['status'] === 'in_stock' && has_permission($conn, 'sale', 'add')): ?>
<a href="index.php?page=sale&bike_id=<?= $bike['id'] ?>" class="btn btn-success btn-sm" title="Sell">🛒</a>
<?php endif; ?>
<?php if ($bike['status'] === 'sold' && has_permission($conn, 'returns', 'add')): ?>
<a href="index.php?page=returns&bike_id=<?= $bike['id'] ?>" class="btn btn-warning btn-sm" title="Return">↩</a>
<?php endif; ?>
<?php if ($bike['status'] === 'sold' && has_permission($conn, 'sale', 'view')): ?>
<a href="index.php?page=sale&print_invoice=<?= $bike['id'] ?>&format=a4" class="btn btn-primary btn-sm" title="A4 Invoice" target="_blank">📄</a>
<a href="index.php?page=sale&print_invoice=<?= $bike['id'] ?>&format=thermal" class="btn btn-warning btn-sm" title="Thermal POS" target="_blank">🧾</a>
<?php endif; ?>
<?php if ($bike['status'] === 'sold' && has_permission($conn, 'money_tracking', 'view')): ?>
<a href="index.php?page=money_tracking&filter_bike=<?= $bike['id'] ?>" class="btn btn-default btn-sm" title="Track Money Destination" style="background:var(--bg3)">💸</a>
<?php endif; ?>
<?php if ($bike['purchase_order_id'] && has_permission($conn, 'purchase', 'view')): ?>
<a href="index.php?page=purchase&print_po=<?= $bike['purchase_order_id'] ?>&format=a4" class="btn btn-primary btn-sm" title="A4 Purchase Receipt" target="_blank">📄</a>
<a href="index.php?page=purchase&print_po=<?= $bike['purchase_order_id'] ?>&format=thermal" class="btn btn-default btn-sm" title="Thermal Purchase Receipt" target="_blank">📦</a>
<?php endif; ?>
<?php if (has_permission($conn, 'inventory', 'edit')): ?>
<a href="index.php?page=inventory&edit_id=<?= $bike['id'] ?>" class="btn btn-primary btn-sm" title="Edit">✏</a>
<?php endif; ?>
<?php if (has_permission($conn, 'inventory', 'delete')): ?>
<form method="POST" action="index.php?page=inventory&action=delete" style="display:inline">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="id" value="<?= $bike['id'] ?>">
<button type="submit" class="btn btn-danger btn-sm" title="Delete" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete this bike?', text: 'Are you sure you want to delete this bike? This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot>
<tr>
<td colspan="7"><strong>PAGE TOTAL</strong></td>
<td style="white-space:nowrap"><strong><?= fmt_money($total_pp) ?></strong></td>
<td style="white-space:nowrap"><strong><?= fmt_money($total_tax) ?></strong></td>
<td></td>
<td style="white-space:nowrap"><strong><?= fmt_money($total_sp) ?></strong></td>
<td></td>
<td style="white-space:nowrap;color:<?= $total_mg >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><strong><?= fmt_money($total_mg) ?></strong></td>
<td></td>
</tr>
</tfoot>
</table>
</div>
</form>
<form method="POST" id="bulkExportForm" action="index.php?page=inventory&action=bulk_export">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<div id="hiddenBikeIds"></div>
</form>
<?php if ($edit_bike): ?>
<div class="modal-overlay open" id="editBikeModal">
<div class="modal animate__animated animate__zoomIn">
<div class="modal-header"><h3>✏ Edit Bike — <?= sanitize($edit_bike['chassis_number']) ?></h3><a href="index.php?page=inventory" class="modal-close">✕</a></div>
<form id="editBikeForm" method="POST" enctype="multipart/form-data" action="index.php?page=inventory&action=edit">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="id" value="<?= $edit_bike['id'] ?>">
<div class="form-group" style="margin-bottom:8px"><label>Model</label>
<select name="model_id" required>
<?php
                $models_filter_list->data_seek(0);
                while ($m = $models_filter_list->fetch_assoc()):
                    ?>
<option value="<?= $m['id'] ?>" <?= $edit_bike['model_id'] == $m['id'] ? 'selected' : '' ?>><?= sanitize($m['model_code'] . ' - ' . $m['model_name']) ?></option>
<?php endwhile; ?>
</select>
</div>
<div class="form-group" style="margin-bottom:8px"><label>Color</label><input type="text" name="color" value="<?= sanitize($edit_bike['color']) ?>"></div>
<div class="form-group" style="margin-bottom:8px"><label>Chassis Number</label><input type="text" name="chassis_number" value="<?= sanitize($edit_bike['chassis_number']) ?>"></div>
<div class="form-group" style="margin-bottom:8px"><label>Motor Number</label><input type="text" name="motor_number" value="<?= sanitize($edit_bike['motor_number'] ?? '') ?>"></div>
<div class="form-group" style="margin-bottom:8px"><label>Order Date</label><input type="date" name="order_date" value="<?= $edit_bike['order_date'] ?? '' ?>"></div>
<div class="form-group" style="margin-bottom:8px"><label>Inventory Date</label><input type="date" name="inventory_date" value="<?= $edit_bike['inventory_date'] ?? '' ?>"></div>
<div class="form-group" style="margin-bottom:8px"><label>Purchase Price</label><input type="number" name="purchase_price" step="0.01" value="<?= $edit_bike['purchase_price'] ?>"></div>
<div class="form-group" style="margin-bottom:8px;font-size:0.85rem">
<label><input type="checkbox" name="recalc_tax" value="1"> Recalculate tax with current settings (<?= $tax_rate * 100 ?>% on <?= $tax_on ?>)</label>
<small style="display:block;color:var(--text3)">Stored: <?= $edit_bike['tax_rate_applied'] !== null ? ($edit_bike['tax_rate_applied'] * 100) . '%' : 'N/A' ?> on <?= sanitize($edit_bike['tax_basis'] ?? 'N/A') ?></small>
</div>
<div class="form-group" style="margin-bottom:8px"><label>Status</label>
<select name="status">
<?php
$allowed_statuses = [
    'in_stock' => ['in_stock' => 'In Stock', 'reserved' => 'Reserved', 'damaged_lost' => 'Damaged / Lost'],
    'reserved' => ['reserved' => 'Reserved', 'in_stock' => 'In Stock', 'damaged_lost' => 'Damaged / Lost'],
    'sold' => ['sold' => 'Sold'],
    'returned' => ['returned' => 'Returned by Customer'],
    'returned_to_supplier' => ['returned_to_supplier' => 'Returned to Supplier'],
    'damaged_lost' => ['damaged_lost' => 'Damaged / Lost', 'in_stock' => 'In Stock'],
];
$current_status = $edit_bike['status'] ?? 'in_stock';
$opts = $allowed_statuses[$current_status] ?? $allowed_statuses['in_stock'];
foreach ($opts as $val => $label):
    $sel = $val === $current_status ? 'selected' : '';
?>
<option value="<?= $val ?>" <?= $sel ?>><?= $label ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="form-group" style="margin-bottom:8px"><label>Safeguard Notes</label><input type="text" name="safeguard_notes" value="<?= sanitize($edit_bike['safeguard_notes'] ?? '') ?>"></div>
<div class="form-group" style="margin-bottom:8px"><label>Notes</label><textarea name="notes" rows="2"><?= sanitize($edit_bike['notes'] ?? '') ?></textarea></div>
<div class="form-group" style="margin-bottom:12px"><label>Image (Optional)</label><input type="file" name="image" accept="image/*" style="padding:4px"></div>
<button type="submit" class="btn btn-primary">💾 Save Changes</button>
</form>
</div>
</div>
<?php endif; ?>
<script>
function toggleSelectAll() {
    var chk = document.getElementById('selectAll').checked;
    document.querySelectorAll('.bike-check').forEach(function(c){ c.checked = chk; });
}
document.getElementById('bulkExportForm').addEventListener('submit', function(){
    var hidden = document.getElementById('hiddenBikeIds');
    hidden.innerHTML = '';
    document.querySelectorAll('.bike-check:checked').forEach(function(c){
        var inp = document.createElement('input');
        inp.type='hidden'; inp.name='selected_bikes[]'; inp.value=c.value;
        hidden.appendChild(inp);
    });
});
</script>
<?php endif; ?>
<?php
    elseif ($page === 'sale'):
        $prefill_bike_id = (int) ($_GET['bike_id'] ?? 0);
        $prefill_bike = null;
        if ($prefill_bike_id) {
            $pr = $conn->query("SELECT b.*, m.model_name, m.model_code FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.id=$prefill_bike_id AND b.status='in_stock'");
            $prefill_bike = $pr ? $pr->fetch_assoc() : null;
        }
        $sale_model_id = (int) ($_GET['model_id'] ?? 0);
        $sale_where = "b.status='in_stock'";
        if ($sale_model_id)
            $sale_where .= " AND b.model_id=$sale_model_id";
        $bikes_instock = $conn->query("SELECT b.id, b.chassis_number, b.color, b.purchase_price, m.model_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE $sale_where ORDER BY b.created_at DESC");
        $customers_list = $conn->query("SELECT c.id, c.name, c.phone, c.cnic, c.is_filer,
            COALESCE((SELECT SUM(CASE WHEN entry_type='credit' THEN amount ELSE 0 END) - SUM(CASE WHEN entry_type='debit' THEN amount ELSE 0 END) FROM ledger WHERE party_type='customer' AND party_id=c.id),0)
            as advance_balance FROM customers c ORDER BY c.name");
        $accessories_list = $conn->query('SELECT id, name, selling_price, current_stock FROM accessories WHERE current_stock > 0 ORDER BY name');
        $last_sale_bike_id = $_SESSION['last_sale_bike_id'] ?? 0;
        unset($_SESSION['last_sale_bike_id']);
?>
<form method="POST" id="saleForm" class="animate__animated animate__fadeIn">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="save_sale" value="1">
<fieldset class="fieldset"><legend>🛒 Sale Details</legend>
<div class="form-row">
<div class="form-group">
<label>Select Bike <span class="req">*</span></label>
<select name="bike_id" id="bikeSelect" required onchange="fillBikeDetails(this)">
<option value="">-- Select Bike (Chassis / Model / Color) --</option>
<?php
        $auto_sel = false;
        while ($bs = $bikes_instock->fetch_assoc()):
            $sel_attr = '';
            if ($prefill_bike_id == $bs['id']) {
                $sel_attr = 'selected';
            } elseif (isset($sale_model_id) && $sale_model_id > 0 && !$auto_sel) {
                $sel_attr = 'selected';
                $auto_sel = true;
            }
            ?>
<option value="<?= $bs['id'] ?>" data-pp="<?= $bs['purchase_price'] ?>" <?= $sel_attr ?>>
<?= sanitize($bs['chassis_number']) ?> | <?= sanitize($bs['model_name']) ?> | <?= sanitize($bs['color']) ?> | Pp: <?= fmt_money($bs['purchase_price']) ?>
</option>
<?php endwhile; ?>
</select>
</div>
<div class="form-group"><label>Selling Date <span class="req">*</span></label><input type="date" name="selling_date" value="<?= date('Y-m-d') ?>" required></div>
</div>
<div class="form-row">
<div class="form-group"><label>Selling Price (<?= $currency ?>) <span class="req">*</span></label><input type="number" name="selling_price" id="sellingPrice" step="0.01" min="0" required placeholder="0.00" oninput="calcMargin()"></div>
<div class="form-group"><label>Purchase Price</label><input type="text" id="purchasePriceDisplay" readonly style="background:var(--bg3);color:var(--text2)" placeholder="Auto-filled"></div>
<div class="form-group"><label>Tax Amount (<?= $tax_rate * 100 ?>% of <?= $tax_on === 'selling_price' ? 'Selling' : 'Purchase' ?> Price)</label><input type="text" id="taxDisplay" readonly style="background:var(--bg3);color:var(--text2)" placeholder="Auto-calculated"></div>
<div class="form-group"><label>Margin / Profit</label><input type="text" id="marginDisplay" readonly style="background:var(--bg3);font-weight:700" placeholder="Auto-calculated"></div>
</div>
<div class="form-row">
<div class="form-group" style="min-width: 350px; flex: 2;">
<label>Customer <span class="req">*</span></label>
<div style="display:flex;gap:4px">
<select name="customer_id" id="customerSel" class="select2-enable" required style="flex:1" onchange="updateFilerStatus(this); updateAdvanceDisplay();">
<option value="0" data-is-filer="1" data-advance="0">-- Walk-in / Cash Customer --</option>
<?php $customers_list->data_seek(0);
        while ($cl = $customers_list->fetch_assoc()):
            $adv_bal = (float) $cl['advance_balance']; ?>
<option value="<?= $cl['id'] ?>" data-is-filer="<?= $cl['is_filer'] ?>" data-advance="<?= $adv_bal ?>"><?= sanitize($cl['name']) ?> — <?= sanitize($cl['phone']) ?></option>
<?php endwhile; ?>
</select>
<button type="button" class="btn btn-default btn-sm" onclick="openCustomerModal()">+</button>
</div>
<span id="advanceDisplay" style="font-size:0.8rem;color:var(--text3);display:block;margin-top:2px"></span>
</div>
<div class="form-group"><label>Customer Filer Status</label><input type="text" id="filerStatusDisplay" readonly style="background:var(--bg3);color:var(--text2)" value="Filer"></div>
<div class="form-group"><label>Down Payment (<?= $currency ?>) <span class="req">*</span></label><input type="number" name="down_payment" id="downPayment" step="0.01" min="0" value="0.00" oninput="calcRemainingBalance()" required></div>
<div class="form-group"><label>Payment Method (Down Payment) <span class="req">*</span></label>
<select name="payment_method_dp" id="payTypeDp" onchange="toggleChequeFieldsDp(this.value)">
<option value="cash">Cash</option>
<option value="cheque">Cheque</option>
<option value="bank_transfer">Bank Transfer</option>
<option value="online">Online</option>
</select>
</div>
</div>
<div id="chequeFieldsDp" style="display:none" class="form-row">
<div class="form-group"><label>Cheque Number</label><input type="text" name="cheque_number_dp" placeholder="CHQ-001"></div>
<div class="form-group"><label>Bank Name</label><input type="text" name="bank_name_dp" placeholder="HBL, MCB..."></div>
<div class="form-group"><label>Cheque Date</label><input type="date" name="cheque_date_dp"></div>
</div>
<div class="form-row">
    <div class="form-group"><label>Total Amount Due</label><input type="text" id="totalAmountDue" readonly style="background:var(--bg3);color:var(--text2)"></div>
    <div class="form-group"><label>Remaining Balance</label><input type="text" id="remainingBalance" readonly style="background:var(--bg3);color:var(--text2)"></div>
</div>
<div class="form-row">
    <div class="form-group"><label>Total Installments</label><input type="number" name="total_installments" id="totalInstallments" min="0" value="0" oninput="calcInstallments()"></div>
    <div class="form-group"><label>Installment Amount</label><input type="number" name="installment_amount" id="installmentAmount" step="0.01" min="0" value="0.00" readonly style="background:var(--bg3);color:var(--text2)"></div>
    <div class="form-group"><label>First Due Date</label><input type="date" name="first_due_date" id="firstDueDate" value="<?= date('Y-m-d', strtotime('+1 month')) ?>"></div>
</div>
</fieldset>
<fieldset class="fieldset"><legend>🛠️ Accessories Sold</legend>
    <div id="accessoriesList"></div>
    <button type="button" class="btn btn-default btn-sm" onclick="addAccessoryRow()" style="margin-top:6px">+ Add Accessory</button>
</fieldset>
<div class="form-row">
    <div class="form-group"><label>Sale Notes</label><textarea name="sale_notes" rows="2" placeholder="Any notes..."></textarea></div>
</div>
<?php
        $dest_list_sale = $conn->query('SELECT id, type, name FROM money_destinations WHERE is_active=1 ORDER BY type, name');
        $dest_options_sale = [];
        if ($dest_list_sale) {
            while ($dd = $dest_list_sale->fetch_assoc())
                $dest_options_sale[] = $dd;
        }
?>
<fieldset class="fieldset" style="border-color:var(--accent);background:var(--surface)">
<legend style="cursor:pointer" onclick="document.getElementById('moneyAllocArea').style.display=document.getElementById('moneyAllocArea').style.display==='none'?'block':'none'">💸 Track Money Destination <small style="color:var(--text3)">(Optional — click to expand)</small></legend>
<div id="moneyAllocArea" style="display:none">
    <div id="moneyAllocRows"></div>
    <button type="button" class="btn btn-default btn-sm" onclick="addMoneyAllocRow()" style="margin-top:6px">+ Add Destination</button>
</div>
</fieldset>
<script>
var moneyDestOptions = <?= json_encode($dest_options_sale) ?>;
var moneyAllocIdx = 0;
function addMoneyAllocRow() {
    var container = document.getElementById('moneyAllocRows');
    var idx = moneyAllocIdx++;
    var typeIcons = {bank:'🏦',person:'👤',wallet:'💳'};
    var opts = '<option value="">-- Select Destination --</option>';
    moneyDestOptions.forEach(function(d) {
        opts += '<option value="'+d.id+'">'+(typeIcons[d.type]||'')+' '+d.name+' ('+d.type+')</option>';
    });
    var row = document.createElement('div');
    row.className = 'form-row';
    row.style.alignItems = 'flex-end';
    row.id = 'moneyAllocRow_'+idx;
    row.innerHTML = '<div class="form-group"><label>Destination</label><select name="money_alloc['+idx+'][destination_id]">'+opts+'</select></div>'
        + '<div class="form-group"><label>Amount (<?= $currency ?>)</label><input type="number" name="money_alloc['+idx+'][amount]" step="0.01" min="0" placeholder="0.00"></div>'
        + '<div class="form-group"><label>Notes</label><input type="text" name="money_alloc['+idx+'][notes]" placeholder="Optional note"></div>'
        + '<div class="form-group"><button type="button" class="btn btn-danger btn-sm" onclick="document.getElementById(\'moneyAllocRow_'+idx+'\').remove()">✕</button></div>';
    container.appendChild(row);
    $(row).find('select').select2({ minimumResultsForSearch: 10, placeholder: '-- Select --', allowClear: false, theme: 'default' });
}
</script>
<div style="display:flex;gap:8px;flex-wrap:wrap">
<button type="submit" name="save_sale" class="btn btn-success">💾 Record Sale</button>
<a href="index.php?page=inventory" class="btn btn-default">← Back to Inventory</a>
</div>
</form>
<?php if ($last_sale_bike_id): ?>
<div style="margin-top:16px; display:flex; gap:10px;">
<a href="index.php?page=sale&print_invoice=<?= $last_sale_bike_id ?>&format=a4" class="btn btn-primary" target="_blank">🖨 Print Letterhead (A4)</a>
<a href="index.php?page=sale&print_invoice=<?= $last_sale_bike_id ?>&format=thermal" class="btn btn-warning" target="_blank">🧾 Print Thermal (POS)</a>
</div>
<?php endif; ?>
<?php
        $print_inv_id = (int) ($_GET['print_invoice'] ?? 0);
        if ($print_inv_id):
            echo '<style>.sidebar, .topbar { display: none !important; } .main-wrap { margin-left: 0 !important; } .content > *:not(#receiptArea):not(.no-print) { display: none !important; } .content { padding: 40px !important; background: #333 !important; } body { background: #333 !important; } @media print { .content, body { padding: 0 !important; background: #fff !important; } }</style>';
            $inv_r = $conn->query("SELECT b.*, m.model_name, m.model_code, m.category, c.name as cust_name, c.phone as cust_phone, c.cnic as cust_cnic, c.address as cust_addr, c.is_filer FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id WHERE b.id=$print_inv_id");
            $inv = $inv_r ? $inv_r->fetch_assoc() : null;
            $show_pp = get_setting('show_purchase_on_invoice') == '1';
            $inv_no = 'INV-' . date('Ymd') . '-' . str_pad($print_inv_id, 3, '0', STR_PAD_LEFT);
            $format = $_GET['format'] ?? 'a4';
            $container_class = $format === 'thermal' ? 'thermal-receipt' : 'a4-invoice';
            if ($inv):
                $sold_acc_r = $conn->query('SELECT sa.*, a.name FROM sale_accessories sa JOIN accessories a ON sa.accessory_id=a.id WHERE sa.bike_id=' . $inv['id']);
                $invoice_acc_total = (float) $conn->query('SELECT COALESCE(SUM(final_price),0) FROM sale_accessories WHERE bike_id=' . $inv['id'])->fetch_row()[0];
                $dp_credit = (float) ($conn->query("SELECT COALESCE(SUM(amount),0) FROM ledger WHERE entry_type='credit' AND reference_type='down_payment' AND reference_id=" . $inv['id'])->fetch_row()[0] ?? 0);
                $dp_bounced = (float) ($conn->query("SELECT COALESCE(SUM(l.amount),0) FROM ledger l JOIN payments p ON p.id=l.reference_id WHERE l.entry_type='debit' AND l.reference_type='cheque_bounce' AND p.transaction_type='sale' AND p.reference_id=" . $inv['id'])->fetch_row()[0] ?? 0);
                $dp_amount = max(0, $dp_credit - $dp_bounced);
                $installments_r = $conn->query('SELECT installment_amount, amount_paid, penalty_fee, COALESCE(penalty_paid,0) AS penalty_paid FROM installments WHERE bike_id=' . $inv['id']);
                $total_installments = 0;
                $total_paid_installments = 0;
                $total_penalty = 0;
                $total_penalty_paid = 0;
                while ($inst = $installments_r->fetch_assoc()) {
                    $total_installments += $inst['installment_amount'];
                    $total_paid_installments += $inst['amount_paid'];
                    $total_penalty += $inst['penalty_fee'];
                    $total_penalty_paid += $inst['penalty_paid'];
                }
                ?>
<div class="<?= $container_class ?>" id="receiptArea">
    <div class="invoice-header">
        <h1>⚡ <?= sanitize(get_setting('company_name') ?? 'BNI Enterprises') ?></h1>
        <h2><?= sanitize(get_setting('branch_name') ?? 'Dera (Ahmed Metro)') ?></h2>
        <?php
        $raw_wa = get_setting('company_whatsapp') ?? '';
        $wa_numbers = array_filter(array_map('trim', explode(',', $raw_wa)));
        if (!empty($wa_numbers)):
            ?>
        <div style="font-size:0.85rem;margin-top:2px;font-weight:normal;">WhatsApp: <?= sanitize(implode(', ', $wa_numbers)) ?></div>
        <?php endif; ?>
        <div style="font-size:0.9rem;margin-top:4px"><strong>SALE RECEIPT</strong></div>
    </div>
    <div class="invoice-meta">
        <div><strong>Invoice #:</strong> <?= $inv_no ?><br><strong>Date:</strong> <?= fmt_date($inv['selling_date']) ?></div>
        <div class="<?= $format === 'thermal' ? '' : 'text-right' ?>" style="<?= $format === 'thermal' ? 'margin-top:10px;' : 'text-align:right;' ?>">
            <strong>Customer:</strong> <?= sanitize($inv['cust_name'] ?? 'Walk-in Customer') ?><br>
            <?php if ($inv['cust_phone']): ?><strong>Phone:</strong> <?= sanitize($inv['cust_phone']) ?><br><?php endif; ?>
            <?php if ($inv['cust_cnic']): ?><strong>CNIC:</strong> <?= sanitize($inv['cust_cnic']) ?> (<?= $inv['is_filer'] ? 'Filer' : 'Non-Filer' ?>)<br><?php endif; ?>
        </div>
    </div>
    <div class="invoice-section">
        <h3>Items Details</h3>
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <?= $format === 'a4' ? '<th>Category</th>' : '' ?>
                    <th style="text-align:right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?= sanitize($inv['model_name']) ?> (<?= sanitize($inv['model_code']) ?>)</strong><br>
                        <small>Chassis: <span style="font-family:Consolas"><?= sanitize($inv['chassis_number']) ?></span> | Color: <?= sanitize($inv['color']) ?></small>
                    </td>
                    <?= $format === 'a4' ? '<td>' . sanitize($inv['category']) . '</td>' : '' ?>
                    <td style="text-align:right"><?= fmt_money($inv['selling_price']) ?></td>
                </tr>
                <?php if ($sold_acc_r->num_rows > 0): ?>
                    <?php while ($sa = $sold_acc_r->fetch_assoc()): ?>
                    <tr>
                        <td><small>+ <?= sanitize($sa['name']) ?> (Qty: <?= $sa['quantity'] ?>)</small></td>
                        <?= $format === 'a4' ? '<td>Accessory</td>' : '' ?>
                        <td style="text-align:right"><?= fmt_money($sa['final_price']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="invoice-section">
        <h3>Payment Summary</h3>
        <table class="invoice-table" style="width:<?= $format === 'thermal' ? '100%' : '60%' ?>; margin-left:<?= $format === 'thermal' ? '0' : 'auto' ?>;">
            <tbody>
                <?php if ($show_pp): ?><tr><td>Purchase Price</td><td style="text-align:right"><?= fmt_money($inv['purchase_price']) ?></td></tr><?php endif; ?>
                <tr><td><strong>Total Sale Price</strong></td><td style="text-align:right"><strong><?= fmt_money($inv['selling_price'] + $invoice_acc_total) ?></strong></td></tr>
                <tr><td>Tax (<?= number_format(((float) ($inv['tax_rate_applied'] ?? 0)) * 100, 2) ?>%)</td><td style="text-align:right"><?= fmt_money($inv['tax_amount']) ?></td></tr>
                <?php if ($dp_amount > 0): ?><tr><td>Down Payment Received</td><td style="text-align:right; color:green;">- <?= fmt_money($dp_amount) ?></td></tr><?php endif; ?>
                <?php if ($total_installments > 0): ?><tr><td>Total Installments Plan</td><td style="text-align:right"><?= fmt_money($total_installments) ?></td></tr><?php endif; ?>
                <?php if ($total_paid_installments > 0): ?><tr><td>Installments Paid</td><td style="text-align:right; color:green;">- <?= fmt_money($total_paid_installments) ?></td></tr><?php endif; ?>
                <?php if ($total_penalty > 0): ?><tr><td>Total Penalty</td><td style="text-align:right">+ <?= fmt_money($total_penalty) ?></td></tr><?php endif; ?>
            </tbody>
        </table>
        <div class="invoice-total">Net Total: <?= fmt_money($inv['selling_price'] + $invoice_acc_total + $total_penalty) ?></div>
        <div class="invoice-total" style="background:#fff; border-color:#000;">Amount Due: <?= fmt_money(max(0, ($inv['selling_price'] + $invoice_acc_total + $total_penalty) - ($dp_amount + $total_paid_installments + $total_penalty_paid))) ?></div>
    </div>
    <div class="invoice-footer">
        Generated by: Yasin Ullah – BSS<br>WhatsApp: 03361593533
    </div>
</div>
<div class="no-print" style="margin-top:10px">
    <button onclick="window.print()" class="btn btn-success">🖨 Print Receipt</button>
</div>
<?php endif;
        endif; ?>
<div class="modal-overlay" id="addCustModal">
<div class="modal">
<div class="modal-header"><h3>Add New Customer</h3><button class="modal-close" onclick="closeCustomerModal()">✕</button></div>
<form id="customerForm" class="ajax-form" method="POST" action="index.php?page=customers&action=add">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<div class="form-group" style="margin-bottom:8px"><label>Name <span class="req">*</span></label><input type="text" name="name" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Phone</label><input type="text" name="phone"></div>
<div class="form-group" style="margin-bottom:8px"><label>CNIC</label><input type="text" name="cnic" placeholder="XXXXX-XXXXXXX-X"></div>
<div class="form-group" style="margin-bottom:8px"><label style="display:flex;align-items:center;gap:6px;"><input type="checkbox" name="is_filer" value="1" checked> Is Filer?</label></div>
<div class="form-group" style="margin-bottom:12px"><label>Address</label><textarea name="address" rows="2"></textarea></div>
<button type="submit" class="btn btn-primary">Save Customer</button>
</form>
</div>
</div>
<script>
var taxRate = <?= $tax_rate ?>;
var taxOn = '<?= $tax_on ?>';
var accessoryPrices = {};
var accessoriesCount = 0;
var allAccessories = <?= json_encode($conn->query('SELECT id, name, selling_price, current_stock FROM accessories')->fetch_all(MYSQLI_ASSOC)) ?>;
allAccessories.forEach(function(acc) {
    accessoryPrices[acc.id] = acc;
});
function fillBikeDetails(sel) {
    var opt = sel.options[sel.selectedIndex];
    var pp = opt.dataset.pp || 0;
    document.getElementById('purchasePriceDisplay').value = pp ? parseFloat(pp).toLocaleString('en-PK',{minimumFractionDigits:2}) : '';
    calcMargin();
    calcRemainingBalance();
}
function updateFilerStatus(selectElement) {
    var selectedOption = selectElement.options[selectElement.selectedIndex];
    var isFiler = selectedOption.dataset.isFiler;
    document.getElementById('filerStatusDisplay').value = isFiler == '1' ? 'Filer' : 'Non-Filer';
    calcMargin(); 
}
function updateAdvanceDisplay() {
    var sel = document.getElementById('customerSel');
    var opt = sel.options[sel.selectedIndex];
    var adv = opt ? parseFloat(opt.dataset.advance || 0) : 0;
    var display = document.getElementById('advanceDisplay');
    if (opt && opt.value && opt.value != '0') {
        if (adv > 0) {
            display.innerHTML = '💰 Advance Available: <strong style="color:var(--success)"><?= $currency ?> ' + adv.toLocaleString() + '</strong> <small style="color:var(--text3)">(can be applied to down payment)</small>';
        } else if (adv < 0) {
            display.innerHTML = '⚠️ Customer Due: <strong style="color:var(--danger)"><?= $currency ?> ' + Math.abs(adv).toLocaleString() + '</strong> <small style="color:var(--text3)">(outstanding balance)</small>';
        } else {
            display.innerHTML = '✅ No outstanding balance';
        }
    } else {
        display.innerHTML = '';
    }
}
function calcMargin() {
    var sp = parseFloat(document.getElementById('sellingPrice').value) || 0;
    var pp = parseFloat(document.getElementById('purchasePriceDisplay').value.replace(/,/g,'')) || 0;
    var base = taxOn === 'selling_price' ? sp : pp;
    var tax = (base * taxRate); 
    var margin = sp - pp - tax;
    document.getElementById('taxDisplay').value = '<?= $currency ?> ' + tax.toFixed(2);
    var md = document.getElementById('marginDisplay');
    md.value = '<?= $currency ?> ' + margin.toFixed(2);
    md.style.color = margin >= 0 ? 'var(--success)' : 'var(--danger)';
    calcRemainingBalance();
}
function calcRemainingBalance() {
    var sellingPrice = parseFloat(document.getElementById('sellingPrice').value) || 0;
    var totalAccessoriesPrice = 0;
    document.querySelectorAll('[name$="[final_price]"]').forEach(function(input) {
        totalAccessoriesPrice += parseFloat(input.value) || 0;
    });
    var totalAmountDue = sellingPrice + totalAccessoriesPrice;
    var custSel = document.getElementById('customerSel');
    if (custSel && custSel.value == '0') {
        document.getElementById('downPayment').value = totalAmountDue.toFixed(2);
        document.getElementById('downPayment').readOnly = true;
        document.getElementById('totalInstallments').value = '0';
        document.getElementById('totalInstallments').readOnly = true;
    } else {
        var dpInput = document.getElementById('downPayment');
        if (dpInput.readOnly) {
            dpInput.readOnly = false;
        }
        var instInput = document.getElementById('totalInstallments');
        if (instInput.readOnly) {
            instInput.readOnly = false;
        }
    }
    var downPayment = parseFloat(document.getElementById('downPayment').value) || 0;
    var remainingBalance = totalAmountDue - downPayment;
    document.getElementById('totalAmountDue').value = '<?= $currency ?> ' + totalAmountDue.toFixed(2);
    document.getElementById('remainingBalance').value = '<?= $currency ?> ' + remainingBalance.toFixed(2);
    calcInstallments();
}
function calcInstallments() {
    var remainingBalanceStr = document.getElementById('remainingBalance').value.replace('<?= $currency ?> ', '');
    var remainingBalance = parseFloat(remainingBalanceStr.replace(/[^0-9.-]/g, '')) || 0;
    var totalInstallments = parseInt(document.getElementById('totalInstallments').value) || 0;
    var installmentAmount = 0;
    if (totalInstallments > 0) {
        installmentAmount = remainingBalance / totalInstallments;
    }
    document.getElementById('installmentAmount').value = installmentAmount.toFixed(2);
}
function toggleChequeFieldsDp(val) {
    var chequeFields = document.getElementById('chequeFieldsDp');
    if (val === 'cheque') {
        chequeFields.style.display = 'flex';
        $(chequeFields).find('input').attr('required', true);
    } else {
        chequeFields.style.display = 'none';
        $(chequeFields).find('input').removeAttr('required');
    }
}
function addAccessoryRow() {
    accessoriesCount++;
    var d = document.createElement('div');
    d.className = 'bike-row animate__animated animate__fadeInDown';
    d.id = 'accessoryRow_' + accessoriesCount;
    var optionsHtml = '<option value="">-- Select Accessory --</option>';
    allAccessories.forEach(function(acc) {
        optionsHtml += `<option value="${acc.id}" data-price="${acc.selling_price}" data-stock="${acc.current_stock}">${acc.name} (Stock: ${acc.current_stock})</option>`;
    });
    d.innerHTML = `<div class="bike-row-header"><span class="bike-row-num">🛠️ Accessory #${accessoriesCount}</span><button type="button" class="bike-row-del" onclick="removeAccessoryRow(${accessoriesCount})">✕ Remove</button></div>
    <div class="form-row">
        <div class="form-group" style="flex:2"><label>Accessory <span class="req">*</span></label>
            <select name="selected_accessories[${accessoriesCount}][id]" required class="select2-enable" onchange="updateAccessoryDetails(this, ${accessoriesCount})">
                ${optionsHtml}
            </select>
            <span id="accStock_${accessoriesCount}" style="font-size:0.75rem;color:var(--text3)"></span>
        </div>
        <div class="form-group"><label>Quantity <span class="req">*</span></label><input type="number" name="selected_accessories[${accessoriesCount}][quantity]" value="1" min="1" required oninput="calculateAccessoryPrice(${accessoriesCount})"></div>
        <div class="form-group"><label>Unit Price</label><input type="number" name="selected_accessories[${accessoriesCount}][unit_price]" step="0.01" min="0" oninput="calculateAccessoryPrice(${accessoriesCount})"></div>
        <div class="form-group"><label>Discount</label><input type="number" name="selected_accessories[${accessoriesCount}][discount]" value="0.00" step="0.01" min="0" oninput="calculateAccessoryPrice(${accessoriesCount})"></div>
        <div class="form-group"><label>Final Price</label><input type="number" name="selected_accessories[${accessoriesCount}][final_price]" step="0.01" min="0" readonly style="background:var(--bg3);color:var(--text2)"></div>
    </div>`;
    document.getElementById('accessoriesList').appendChild(d);
    $(d).find('.select2-enable').select2({
        minimumResultsForSearch: 0,
        placeholder: '-- Select Accessory --',
        allowClear: false,
        tags: true,
        theme: 'default'
    });
}
function removeAccessoryRow(n) {
    document.getElementById('accessoryRow_' + n).remove();
    calcRemainingBalance();
}
function updateAccessoryDetails(selectElement, index) {
    var selectedOption = selectElement.options[selectElement.selectedIndex];
    var price = selectedOption && selectedOption.dataset ? (selectedOption.dataset.price || 0) : 0;
    var stock = selectedOption && selectedOption.dataset ? (selectedOption.dataset.stock || 0) : 0;
    document.querySelector(`#accessoryRow_${index} input[name="selected_accessories[${index}][unit_price]"]`).value = price;
    document.querySelector(`#accStock_${index}`).innerText = `Available: ${stock}`;
    calculateAccessoryPrice(index);
}
function calculateAccessoryPrice(index) {
    var quantity = parseInt(document.querySelector(`#accessoryRow_${index} input[name="selected_accessories[${index}][quantity]"]`).value) || 0;
    var unitPrice = parseFloat(document.querySelector(`#accessoryRow_${index} input[name="selected_accessories[${index}][unit_price]"]`).value) || 0;
    var discount = parseFloat(document.querySelector(`#accessoryRow_${index} input[name="selected_accessories[${index}][discount]"]`).value) || 0;
    var finalPrice = (quantity * unitPrice) - discount;
    document.querySelector(`#accessoryRow_${index} input[name="selected_accessories[${index}][final_price]"]`).value = finalPrice.toFixed(2);
    calcRemainingBalance();
}
function openCustomerModal() {
    document.getElementById('addCustModal').classList.add('open');
}
function closeCustomerModal(selectName) {
    document.getElementById('addCustModal').classList.remove('open');
    $.ajax({
        url: 'index.php?ajax=get_customers',
        type: 'GET',
        cache: false,
        success: function(response) {
            var newOptions = JSON.parse(response);
            var customerSelect = $('#customerSel');
            var currentVal = customerSelect.val();
            customerSelect.empty();
            customerSelect.append('<option value="0" data-is-filer="1">-- Walk-in / Cash Customer --</option>');
            var newValToSelect = currentVal;
            var sName = selectName ? selectName.trim().toLowerCase() : null;
            newOptions.forEach(function(cust) {
                customerSelect.append(`<option value="${cust.id}" data-is-filer="${cust.is_filer}">${cust.name} — ${cust.phone}</option>`);
                if (sName && cust.name.toLowerCase() === sName) newValToSelect = cust.id;
            });
            customerSelect.val(newValToSelect).trigger('change');
        }
    });
}
window.onload = function() {
    var sel = document.getElementById('bikeSelect');
    if (sel.value) fillBikeDetails(sel);
    updateFilerStatus(document.getElementById('customerSel'));
    calcRemainingBalance(); 
};
</script>
<?php
    elseif ($page === 'returns'):
        $sub = sanitize($_GET['sub'] ?? 'sale');
        $prefill_ret_id = (int) ($_GET['bike_id'] ?? 0);
?>
<div class="sub-tabs no-print animate__animated animate__fadeInDown">
<a href="index.php?page=returns&sub=sale" class="sub-tab <?= $sub === 'sale' ? 'active' : '' ?>">🛒 Sales Returns</a>
<a href="index.php?page=returns&sub=purchase" class="sub-tab <?= $sub === 'purchase' ? 'active' : '' ?>">📤 Purchase Returns</a>
</div>
<?php
        if ($sub === 'sale'):
            $sold_bikes = $conn->query("SELECT b.id, b.chassis_number, b.color, b.selling_price, b.purchase_price, m.model_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.status='sold' ORDER BY b.selling_date DESC");
            ?>
<form method="POST" id="returnForm" class="animate__animated animate__fadeIn">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="save_return" value="1">
<fieldset class="fieldset"><legend>↩ Sales Return / Adjustment</legend>
<div class="form-row">
<div class="form-group">
<label>Select Sold Bike <span class="req">*</span></label>
<select name="bike_id" id="returnBikeSelect" required>
<option value="">-- Select Bike --</option>
<?php while ($sb = $sold_bikes->fetch_assoc()): ?>
<option value="<?= $sb['id'] ?>" <?= $prefill_ret_id == $sb['id'] ? 'selected' : '' ?>><?= sanitize($sb['chassis_number']) ?> | <?= sanitize($sb['model_name']) ?> | <?= sanitize($sb['color']) ?> | Sold: <?= fmt_money($sb['selling_price']) ?></option>
<?php endwhile; ?>
</select>
</div>
<div class="form-group"><label>Return Date <span class="req">*</span></label><input type="date" name="return_date" value="<?= date('Y-m-d') ?>" required></div>
<div class="form-group"><label>Refund Amount (<?= $currency ?>) <span class="req">*</span></label><input type="number" name="return_amount" step="0.01" min="0" required placeholder="0.00"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Refund Method <span class="req">*</span></label>
<select name="refund_method" id="refundMethod" onchange="toggleRetCheque(this.value, 'retChequeFields')">
<option value="cash">Cash</option>
<option value="bank_transfer">Bank Transfer</option>
<option value="cheque">Cheque</option>
<option value="online">Online</option>
</select>
</div>
</div>
<div id="retChequeFields" style="display:none" class="form-row">
<div class="form-group"><label>Cheque Number</label><input type="text" name="cheque_number" placeholder="CHQ-001"></div>
<div class="form-group"><label>Bank Name</label><input type="text" name="bank_name" placeholder="HBL, MCB..."></div>
<div class="form-group"><label>Cheque Date</label><input type="date" name="cheque_date"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Return Notes</label><textarea name="return_notes" rows="3" placeholder="Reason for return, cheque details, account number, etc."></textarea></div>
</div>
</fieldset>
<button type="submit" name="save_return" class="btn btn-warning">↩ Process Sales Return</button>
<a href="index.php?page=inventory" class="btn btn-default">← Cancel</a>
</form>
<?php
        elseif ($sub === 'purchase'):
            $purchased_bikes = $conn->query("SELECT b.id, b.chassis_number, b.color, b.purchase_price, m.model_name, s.name AS sup_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN purchase_orders po ON b.purchase_order_id=po.id LEFT JOIN suppliers s ON po.supplier_id=s.id WHERE b.status='in_stock' ORDER BY b.inventory_date DESC");
?>
<form method="POST" id="purchaseReturnForm" class="animate__animated animate__fadeIn">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="save_purchase_return" value="1">
<fieldset class="fieldset"><legend>📤 Purchase Return (To Supplier)</legend>
<div class="form-row">
<div class="form-group">
<label>Select In-Stock Bike <span class="req">*</span></label>
<select name="bike_id" id="purchReturnBikeSelect" required onchange="fillPurchReturnAmount(this)">
<option value="">-- Select Bike --</option>
<?php while ($pb = $purchased_bikes->fetch_assoc()): ?>
<option value="<?= $pb['id'] ?>" data-pp="<?= $pb['purchase_price'] ?>"><?= sanitize($pb['chassis_number']) ?> | <?= sanitize($pb['model_name']) ?> | <?= sanitize($pb['sup_name'] ?? 'Unknown Supplier') ?> | PP: <?= fmt_money($pb['purchase_price']) ?></option>
<?php endwhile; ?>
</select>
</div>
<div class="form-group"><label>Return Date <span class="req">*</span></label><input type="date" name="return_date" value="<?= date('Y-m-d') ?>" required></div>
<div class="form-group"><label>Refund Received (<?= $currency ?>) <span class="req">*</span></label><input type="number" name="return_amount" id="purchReturnAmount" step="0.01" min="0" required placeholder="0.00"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Refund Receipt Method <span class="req">*</span></label>
<select name="refund_method" id="purchRefundMethod" onchange="toggleRetCheque(this.value, 'purchRetChequeFields')">
<option value="cash">Cash</option>
<option value="bank_transfer">Bank Transfer</option>
<option value="cheque">Cheque</option>
<option value="online">Online</option>
</select>
</div>
</div>
<div id="purchRetChequeFields" style="display:none" class="form-row">
<div class="form-group"><label>Cheque Number</label><input type="text" name="cheque_number" placeholder="CHQ-001"></div>
<div class="form-group"><label>Bank Name</label><input type="text" name="bank_name" placeholder="HBL, MCB..."></div>
<div class="form-group"><label>Cheque Date</label><input type="date" name="cheque_date"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Return Notes</label><textarea name="return_notes" rows="3" placeholder="Reason for return, replacement info, etc."></textarea></div>
</div>
</fieldset>
<button type="submit" name="save_purchase_return" class="btn btn-danger">📤 Process Purchase Return</button>
<a href="index.php?page=inventory" class="btn btn-default">← Cancel</a>
</form>
<?php endif; ?>
<script>
function toggleRetCheque(v, targetId) {
    var chequeFields = document.getElementById(targetId);
    if (v === 'cheque') {
        chequeFields.style.display = 'flex';
        $(chequeFields).find('input').attr('required', true);
    } else {
        chequeFields.style.display = 'none';
        $(chequeFields).find('input').removeAttr('required');
    }
}
function fillPurchReturnAmount(sel) {
    var opt = sel.options[sel.selectedIndex];
    var pp = opt.dataset.pp || '';
    document.getElementById('purchReturnAmount').value = pp;
}
$(document).ready(function() {
    $('#returnBikeSelect, #refundMethod, #purchReturnBikeSelect, #purchRefundMethod').select2({
        minimumResultsForSearch: 10,
        placeholder: '-- Select --',
        allowClear: false,
        theme: 'default'
    });
});
</script>
<?php
    elseif ($page === 'payments'):
        $chq_status_f = sanitize($_GET['chq_status'] ?? '');
        $chq_type_f = sanitize($_GET['chq_type'] ?? '');
        $chq_bank_f = sanitize($_GET['chq_bank'] ?? '');
        $chq_from = valid_date($_GET['chq_from'] ?? '', true) ? ($_GET['chq_from'] ?? '') : '';
        $chq_to = valid_date($_GET['chq_to'] ?? '', true) ? ($_GET['chq_to'] ?? '') : '';
        $chq_where = ['1=1'];
        if ($chq_status_f && in_array($chq_status_f, ['pending', 'cleared', 'bounced', 'cancelled'])) {
            if ($chq_status_f === 'pending') {
                $chq_where[] = "p.payment_type='cheque' AND (p.status='pending' OR p.status IS NULL)";
            } else {
                $chq_where[] = "p.payment_type='cheque' AND p.status='$chq_status_f'";
            }
        }
        if ($chq_type_f && in_array($chq_type_f, ['purchase', 'sale', 'installment', 'expense_payment', 'supplier_payment', 'customer_refund']))
            $chq_where[] = "p.transaction_type='$chq_type_f'";
        if ($chq_bank_f)
            $chq_where[] = "p.bank_name LIKE '%" . mysqli_real_escape_string($conn, $chq_bank_f) . "%'";
        if ($chq_from)
            $chq_where[] = "p.payment_date >= '" . mysqli_real_escape_string($conn, $chq_from) . "'";
        if ($chq_to)
            $chq_where[] = "p.payment_date <= '" . mysqli_real_escape_string($conn, $chq_to) . "'";
        $chq_wstr = implode(' AND ', $chq_where);
        $payments_result = $conn->query("SELECT p.*, (CASE WHEN p.payment_type='cheque' THEN IFNULL(p.status, 'pending') ELSE NULL END) as status_display FROM payments p WHERE $chq_wstr ORDER BY p.payment_date DESC, p.id DESC");
        $chq_summary = $conn->query("SELECT (CASE WHEN payment_type='cheque' THEN IFNULL(status, 'pending') ELSE 'N/A' END) as status_group, COUNT(*) as cnt, SUM(amount) as total FROM payments GROUP BY status_group");
        $chq_sum_data = [];
        while ($cs = $chq_summary->fetch_assoc())
            $chq_sum_data[$cs['status_group']] = $cs;
?>
<div class="stats-row no-print animate__animated animate__fadeInUp">
<div class="stat-box"><div class="stat-val" style="color:var(--warning)"><?= number_format($chq_sum_data['pending']['cnt'] ?? 0) ?></div><div class="stat-lbl">Pending</div><div style="font-size:0.75rem;color:var(--text2)"><?= fmt_money($chq_sum_data['pending']['total'] ?? 0) ?></div></div>
<div class="stat-box"><div class="stat-val" style="color:var(--success)"><?= number_format($chq_sum_data['cleared']['cnt'] ?? 0) ?></div><div class="stat-lbl">Cleared</div><div style="font-size:0.75rem;color:var(--text2)"><?= fmt_money($chq_sum_data['cleared']['total'] ?? 0) ?></div></div>
<div class="stat-box"><div class="stat-val" style="color:var(--danger)"><?= number_format($chq_sum_data['bounced']['cnt'] ?? 0) ?></div><div class="stat-lbl">Bounced</div><div style="font-size:0.75rem;color:var(--text2)"><?= fmt_money($chq_sum_data['bounced']['total'] ?? 0) ?></div></div>
<div class="stat-box"><div class="stat-val"><?= number_format($chq_sum_data['cancelled']['cnt'] ?? 0) ?></div><div class="stat-lbl">Cancelled</div></div>
</div>
<div class="filter-bar no-print animate__animated animate__fadeInLeft">
<form method="GET" action="index.php" style="display:contents">
<input type="hidden" name="page" value="payments">
<div class="form-group"><label>Status</label>
<select name="chq_status">
<option value="">All Status</option>
<option value="pending" <?= $chq_status_f === 'pending' ? 'selected' : '' ?>>Pending</option>
<option value="cleared" <?= $chq_status_f === 'cleared' ? 'selected' : '' ?>>Cleared</option>
<option value="bounced" <?= $chq_status_f === 'bounced' ? 'selected' : '' ?>>Bounced</option>
<option value="cancelled" <?= $chq_status_f === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
</select>
</div>
<div class="form-group"><label>Type</label>
<select name="chq_type">
<option value="">All Types</option>
<option value="purchase" <?= $chq_type_f === 'purchase' ? 'selected' : '' ?>>Purchase Payment</option>
<option value="sale" <?= $chq_type_f === 'sale' ? 'selected' : '' ?>>Sale Receipt</option>
<option value="installment" <?= $chq_type_f === 'installment' ? 'selected' : '' ?>>Installment Receipt</option>
<option value="expense_payment" <?= $chq_type_f === 'expense_payment' ? 'selected' : '' ?>>Expense Payment</option>
<option value="supplier_payment" <?= $chq_type_f === 'supplier_payment' ? 'selected' : '' ?>>Supplier Payment</option>
<option value="customer_refund" <?= $chq_type_f === 'customer_refund' ? 'selected' : '' ?>>Customer Refund</option>
</select>
</div>
<div class="form-group"><label>Bank</label><input type="text" name="chq_bank" value="<?= sanitize($chq_bank_f) ?>" placeholder="Bank name"></div>
<div class="form-group"><label>From</label><input type="date" name="chq_from" value="<?= $chq_from ?>"></div>
<div class="form-group"><label>To</label><input type="date" name="chq_to" value="<?= $chq_to ?>"></div>
<button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-end">🔍 Filter</button>
<a href="index.php?page=payments" class="btn btn-default btn-sm" style="align-self:flex-end">Reset</a>
</form>
</div>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Date</th><th>Type</th><th>Method</th><th>Amount</th><th>Cheque #</th><th>Bank</th><th>Chq Date</th><th>Status</th><th>Party</th><th>Ref</th><th class="no-print">Actions</th></tr></thead>
<tbody>
<?php
        $sr = 1;
        $payments_total_amt = 0;
        while ($pay = $payments_result->fetch_assoc()):
            $payments_total_amt += $pay['amount'];
            $st_badge = '';
            if ($pay['payment_type'] === 'cheque') {
                $st_badge = $pay['status_display'] === 'cleared' ? 'badge-success' : ($pay['status_display'] === 'bounced' ? 'badge-danger' : ($pay['status_display'] === 'cancelled' ? 'badge-default' : 'badge-warning'));
            } else {
                $st_badge = 'badge-info';
            }
            $type_badge = '';
            switch ($pay['transaction_type']) {
                case 'sale':
                case 'installment':
                    $type_badge = 'badge-success';
                    break;
                case 'purchase':
                case 'supplier_payment':
                case 'expense_payment':
                    $type_badge = 'badge-danger';
                    break;
                case 'customer_refund':
                    $type_badge = 'badge-warning';
                    break;
                default:
                    $type_badge = 'badge-default';
                    break;
            }
            ?>
<tr>
<td><?= $sr++ ?></td>
<td><?= fmt_date($pay['payment_date']) ?></td>
<td><span class="badge <?= $type_badge ?>"><?= strtoupper(str_replace('_', ' ', $pay['transaction_type'])) ?></span></td>
<td><?= sanitize($pay['payment_type']) ?></td>
<td><?= fmt_money($pay['amount']) ?></td>
<td style="font-family:Consolas,monospace"><?= sanitize($pay['cheque_number'] ?? '-') ?></td>
<td><?= sanitize($pay['bank_name'] ?? '-') ?></td>
<td><?= fmt_date($pay['cheque_date']) ?></td>
<td><span class="badge <?= $st_badge ?>"><?= $pay['payment_type'] === 'cheque' ? strtoupper($pay['status_display']) : 'N/A' ?></span></td>
<td><?= sanitize($pay['party_name']) ?></td>
<td><?= sanitize($pay['reference_id'] ?? '-') ?></td>
<td class="no-print">
<div class="actions-col">
<?php if ($pay['payment_type'] === 'cheque' && $pay['status_display'] === 'pending' && has_permission($conn, 'payments', 'edit')): ?>
<form method="POST" action="index.php?page=payments&action=status" style="display:inline">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="id" value="<?= $pay['id'] ?>">
<input type="hidden" name="status" value="cleared">
<button type="submit" class="btn btn-success btn-sm" title="Mark Cleared">✓ Clear</button>
</form>
<form method="POST" action="index.php?page=payments&action=status" style="display:inline">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="id" value="<?= $pay['id'] ?>">
<input type="hidden" name="status" value="bounced">
<button type="submit" class="btn btn-danger btn-sm" title="Mark Bounced" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Mark as Bounced?', text: 'Are you sure you want to mark this cheque as bounced?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, mark bounced!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">✗ Bounce</button>
</form>
<?php endif; ?>
<?php if (has_permission($conn, 'payments', 'delete')): ?>
<form method="POST" action="index.php?page=payments&action=delete" style="display:inline">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="id" value="<?= $pay['id'] ?>">
<button type="submit" class="btn btn-danger btn-sm" title="Delete" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete this payment?', text: 'Are you sure you want to delete this payment entry? This cannot be undone.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="4"><strong>TOTAL</strong></td><td><strong><?= fmt_money($payments_total_amt) ?></strong></td><td colspan="7"></td></tr></tfoot>
</table>
</div>
<?php
    elseif ($page === 'installments'):
        $status_f = sanitize($_GET['status_f'] ?? '');
        $customer_f = (int) ($_GET['customer_f'] ?? 0);
        $due_from = valid_date($_GET['due_from'] ?? '', true) ? ($_GET['due_from'] ?? '') : '';
        $due_to = valid_date($_GET['due_to'] ?? '', true) ? ($_GET['due_to'] ?? '') : '';
        $where = ['1=1'];
        if ($status_f)
            $where[] = "i.status='$status_f'";
        if ($customer_f)
            $where[] = "i.customer_id=$customer_f";
        if ($due_from)
            $where[] = "i.due_date >= '$due_from'";
        if ($due_to)
            $where[] = "i.due_date <= '$due_to'";
        $where_str = implode(' AND ', $where);
        $installments_r = $conn->query("SELECT i.*, b.chassis_number, b.model_id, m.model_name, c.name as customer_name, c.phone as customer_phone FROM installments i LEFT JOIN bikes b ON i.bike_id=b.id LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON i.customer_id=c.id WHERE $where_str ORDER BY i.due_date ASC");
        $customers_list = $conn->query('SELECT id, name, phone FROM customers ORDER BY name');
?>
<div class="filter-bar no-print animate__animated animate__fadeInLeft">
<form method="GET" action="index.php" style="display:contents">
<input type="hidden" name="page" value="installments">
<div class="form-group"><label>Status</label>
<select name="status_f">
<option value="">All Status</option>
<option value="pending" <?= $status_f === 'pending' ? 'selected' : '' ?>>Pending</option>
<option value="paid" <?= $status_f === 'paid' ? 'selected' : '' ?>>Paid</option>
<option value="overdue" <?= $status_f === 'overdue' ? 'selected' : '' ?>>Overdue</option>
<option value="cancelled" <?= $status_f === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
</select>
</div>
<div class="form-group" style="min-width: 350px;"><label>Customer</label>
<select name="customer_f">
<option value="0">All Customers</option>
<?php while ($cl = $customers_list->fetch_assoc()): ?>
<option value="<?= $cl['id'] ?>" <?= $customer_f == $cl['id'] ? 'selected' : '' ?>><?= sanitize($cl['name']) ?> - <?= sanitize($cl['phone']) ?></option>
<?php endwhile; ?>
</select>
</div>
<div class="form-group"><label>Due From</label><input type="date" name="due_from" value="<?= $due_from ?>"></div>
<div class="form-group"><label>Due To</label><input type="date" name="due_to" value="<?= $due_to ?>"></div>
<button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-end">🔍 Filter</button>
<a href="index.php?page=installments" class="btn btn-default btn-sm" style="align-self:flex-end">Reset</a>
</form>
</div>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Due Date</th><th>Customer</th><th>Chassis</th><th>Model</th><th>Installment Amount</th><th>Amount Paid</th><th>Penalty</th><th>Status</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php
        $sr = 1;
        $total_installments_amt = 0;
        $total_amount_paid = 0;
        $total_penalty = 0;
        while ($inst = $installments_r->fetch_assoc()):
            $total_installments_amt += $inst['installment_amount'];
            $total_amount_paid += $inst['amount_paid'];
            $total_penalty += $inst['penalty_fee'];
            $status_badge = '';
            $row_class = '';
            switch ($inst['status']) {
                case 'paid':
                    $status_badge = 'badge-success';
                    $row_class = 'row-sold';
                    break;
                case 'overdue':
                    $status_badge = 'badge-danger';
                    $row_class = 'row-returned';
                    break;
                case 'cancelled':
                    $status_badge = 'badge-default';
                    break;
                default:
                    $status_badge = 'badge-warning';
                    $row_class = 'row-reserved';
                    break;
            }
            ?>
<tr class="<?= $row_class ?>">
<td><?= $sr++ ?></td>
<td><?= fmt_date($inst['due_date']) ?></td>
<td><?= sanitize($inst['customer_name'] ?? '-') ?></td>
<td><?= sanitize($inst['chassis_number'] ?? '-') ?></td>
<td><?= sanitize($inst['model_name'] ?? '-') ?></td>
<td><?= fmt_money($inst['installment_amount']) ?></td>
<td><?= fmt_money($inst['amount_paid']) ?></td>
<td><?= fmt_money($inst['penalty_fee']) ?><br><small><?= fmt_money($inst['penalty_paid'] ?? 0) ?> paid</small></td>
<td><span class="badge <?= $status_badge ?>"><?= strtoupper($inst['status']) ?></span></td>
<td class="no-print">
<div class="actions-col">
<?php if (($inst['status'] === 'pending' || $inst['status'] === 'overdue') && has_permission($conn, 'installments', 'edit')): ?>
<button type="button" class="btn btn-success btn-sm" onclick="openPayInstallmentModal(<?= $inst['id'] ?>, '<?= fmt_date($inst['due_date']) ?>', <?= $inst['installment_amount'] ?>, <?= $inst['amount_paid'] ?>, <?= $inst['penalty_fee'] ?>, <?= $inst['penalty_paid'] ?? 0 ?>)">💵 Pay</button>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot>
<tr>
<td colspan="5"><strong>TOTAL</strong></td>
<td><strong><?= fmt_money($total_installments_amt) ?></strong></td>
<td><strong><?= fmt_money($total_amount_paid) ?></strong></td>
<td><strong><?= fmt_money($total_penalty) ?></strong></td>
<td colspan="2"></td>
</tr>
</tfoot>
</table>
</div>
<div class="modal-overlay" id="payInstallmentModal">
<div class="modal">
<div class="modal-header"><h3>Pay Installment</h3><button class="modal-close" onclick="closePayInstallmentModal()">✕</button></div>
<form id="payInstallmentForm" method="POST" action="index.php?page=installments&action=pay_installment">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="installment_id" id="modalInstallmentId">
<div class="form-group" style="margin-bottom:8px"><label>Installment Due Date</label><input type="text" id="modalDueDate" readonly style="background:var(--bg3);color:var(--text2)"></div>
<div class="form-group" style="margin-bottom:8px"><label>Installment Amount</label><input type="text" id="modalInstallmentAmount" readonly style="background:var(--bg3);color:var(--text2)"></div>
<div class="form-group" style="margin-bottom:8px"><label>Already Paid</label><input type="text" id="modalAmountPaidPrev" readonly style="background:var(--bg3);color:var(--text2)"></div>
<div class="form-group" style="margin-bottom:8px"><label>Amount to Pay <span class="req">*</span></label><input type="number" name="amount_paid" step="0.01" min="0" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Penalty Payment / New Penalty</label><input type="number" name="penalty_fee" step="0.01" min="0" value="0.00"><small>Outstanding penalty is filled automatically; any excess is treated as a new penalty.</small></div>
<div class="form-group" style="margin-bottom:8px"><label>Payment Date <span class="req">*</span></label><input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Payment Type <span class="req">*</span></label>
    <select name="payment_type" onchange="togglePayInstCheque(this.value)">
        <option value="cash">Cash</option>
        <option value="bank_transfer">Bank Transfer</option>
        <option value="cheque">Cheque</option>
        <option value="online">Online</option>
    </select>
</div>
<div id="payInstChequeFields" style="display:none">
    <div class="form-group" style="margin-bottom:8px"><label>Cheque Number</label><input type="text" name="cheque_number"></div>
    <div class="form-group" style="margin-bottom:8px"><label>Bank Name</label><input type="text" name="bank_name"></div>
    <div class="form-group" style="margin-bottom:12px"><label>Cheque Date</label><input type="date" name="cheque_date"></div>
</div>
<button type="submit" class="btn btn-primary">Save Payment</button>
</form>
</div>
</div>
<script>
function openPayInstallmentModal(id, dueDate, installmentAmount, amountPaidPrev, penaltyAssessed, penaltyPaidPrev) {
    document.getElementById('modalInstallmentId').value = id;
    document.getElementById('modalDueDate').value = dueDate;
    document.getElementById('modalInstallmentAmount').value = '<?= $currency ?> ' + parseFloat(installmentAmount).toFixed(2);
    document.getElementById('modalAmountPaidPrev').value = '<?= $currency ?> ' + parseFloat(amountPaidPrev).toFixed(2);
    document.querySelector('#payInstallmentModal input[name="amount_paid"]').value = (installmentAmount - amountPaidPrev).toFixed(2);
    document.querySelector('#payInstallmentModal input[name="penalty_fee"]').value = Math.max(0, penaltyAssessed - penaltyPaidPrev).toFixed(2);
    document.getElementById('payInstallmentModal').classList.add('open');
    togglePayInstCheque('cash'); 
}
function closePayInstallmentModal() {
    document.getElementById('payInstallmentModal').classList.remove('open');
}
function togglePayInstCheque(val) {
    var chequeFields = document.getElementById('payInstChequeFields');
    if (val === 'cheque') {
        chequeFields.style.display = 'block';
        $(chequeFields).find('input').attr('required', true);
    } else {
        chequeFields.style.display = 'none';
        $(chequeFields).find('input').removeAttr('required');
    }
}
$(document).ready(function() {
    $('select[name="status_f"], select[name="customer_f"], #payInstallmentModal select[name="payment_type"]').select2({
        minimumResultsForSearch: 10,
        placeholder: '-- Select --',
        allowClear: false,
        theme: 'default'
    });
});
</script>
<?php
    elseif ($page === 'customer_ledger'):
        $sel_cust = (int) ($_GET['cust_id'] ?? 0);
        $customers_for_led = $conn->query('SELECT id, name, phone FROM customers ORDER BY name');
?>
<div class="filter-bar no-print animate__animated animate__fadeInLeft">
<form method="GET" action="index.php" style="display:contents">
<input type="hidden" name="page" value="customer_ledger">
<div class="form-group" style="min-width: 350px;"><label>Select Customer <span class="req">*</span></label>
<select name="cust_id" required onchange="this.form.submit()">
<option value="0">-- Select Customer --</option>
<?php while ($cl = $customers_for_led->fetch_assoc()): ?>
<option value="<?= $cl['id'] ?>" <?= $sel_cust == $cl['id'] ? 'selected' : '' ?>><?= sanitize($cl['name']) ?> — <?= sanitize($cl['phone']) ?></option>
<?php endwhile; ?>
</select>
</div>
</form>
</div>
<?php
        if ($sel_cust > 0):
            $cust_info = $conn->query("SELECT * FROM customers WHERE id=$sel_cust")->fetch_assoc();
            $ledger_entries = $conn->query("SELECT * FROM ledger WHERE party_type='customer' AND party_id=$sel_cust ORDER BY entry_date ASC, id ASC");
            $running_bal = 0;
            $sums = $conn->query("SELECT 
                SUM(CASE WHEN reference_type IN ('sale', 'penalty') THEN amount ELSE 0 END) - SUM(CASE WHEN reference_type='return_reversal' THEN amount ELSE 0 END) as total_billed, 
                SUM(CASE WHEN reference_type IN ('payment','down_payment','installment') THEN amount ELSE 0 END) - SUM(CASE WHEN reference_type IN ('return_refund', 'cheque_bounce') THEN amount ELSE 0 END) as total_paid,
                SUM(CASE WHEN entry_type='debit' THEN amount ELSE 0 END) as total_dr, 
                SUM(CASE WHEN entry_type='credit' THEN amount ELSE 0 END) as total_cr 
                FROM ledger WHERE party_type='customer' AND party_id=$sel_cust")->fetch_assoc();
            $total_dr_summary = $sums['total_billed'] ?? 0;
            $total_cr_summary = $sums['total_paid'] ?? 0;
            $bal_summary = ($sums['total_dr'] ?? 0) - ($sums['total_cr'] ?? 0);
            $advance_total = $conn->query("SELECT COALESCE(SUM(amount),0) FROM ledger WHERE party_type='customer' AND party_id=$sel_cust AND reference_type='advance_given'")->fetch_row()[0];
            ?>
<div class="split-grid-3 animate__animated animate__fadeInDown" style="margin-bottom:14px">
    <div class="card danger"><div class="card-icon">🛒</div><div class="card-body"><div class="card-label">Total Billed (Sales)</div><div class="card-value"><?= fmt_money($total_dr_summary) ?></div></div></div>
    <div class="card success"><div class="card-icon">💵</div><div class="card-body"><div class="card-label">Total Paid</div><div class="card-value"><?= fmt_money($total_cr_summary) ?></div></div></div>
    <div class="card <?= $bal_summary > 0 ? 'warning' : 'info' ?>"><div class="card-icon">⚖️</div><div class="card-body"><div class="card-label"><?= $bal_summary > 0 ? 'Due (Customer owes you)' : 'Advance (You owe customer)' ?></div><div class="card-value" style="color:<?= $bal_summary > 0 ? 'var(--danger)' : 'var(--success)' ?>;font-size:1.1rem"><?= fmt_money(abs($bal_summary)) ?></div><div class="card-sub"><?= $bal_summary > 0 ? 'Outstanding receivable' : 'Advance balance available' ?></div></div></div>
</div>
<div class="print-btn-wrap no-print animate__animated animate__fadeInRight" style="display:flex;gap:8px;">
<button onclick="document.getElementById('receivePaymentModal').classList.add('open')" class="btn btn-success btn-sm">+ Receive Payment</button>
<button onclick="document.getElementById('makePaymentCustModal').classList.add('open')" class="btn btn-warning btn-sm">💸 Make Payment (Advance)</button>
<button onclick="window.print()" class="btn btn-default btn-sm">🖨 Print Ledger</button>
</div>
<div class="modal-overlay" id="receivePaymentModal">
<div class="modal">
<div class="modal-header"><h3>Receive Payment</h3><button class="modal-close" onclick="document.getElementById('receivePaymentModal').classList.remove('open')">✕</button></div>
<form method="POST">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="add_payment" value="1">
<div class="form-group" style="margin-bottom:8px"><label>Date <span class="req">*</span></label><input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Amount <span class="req">*</span></label><input type="number" name="amount" step="0.01" min="0.01" required value="<?= $bal_summary > 0 ? $bal_summary : '' ?>"></div>
<div class="form-group" style="margin-bottom:8px"><label>Method</label><select name="payment_method"><option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option><option value="cheque">Cheque</option><option value="online">Online</option></select></div>
<div class="form-group" style="margin-bottom:12px"><label>Notes</label><textarea name="notes" rows="2" placeholder="Cheque number, bank details, etc..."></textarea></div>
<button type="submit" class="btn btn-primary">Save Payment</button>
</form>
</div>
</div>
<div class="modal-overlay" id="makePaymentCustModal">
<div class="modal">
<div class="modal-header"><h3>Make Payment (Advance/Loan)</h3><button class="modal-close" onclick="document.getElementById('makePaymentCustModal').classList.remove('open')">✕</button></div>
<form method="POST">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="make_payment_cust" value="1">
<div class="form-group" style="margin-bottom:8px"><label>Date <span class="req">*</span></label><input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Amount <span class="req">*</span></label><input type="number" name="amount" step="0.01" min="0.01" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Method</label><select name="payment_method"><option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option><option value="cheque">Cheque</option><option value="online">Online</option></select></div>
<div class="form-group" style="margin-bottom:12px"><label>Notes</label><textarea name="notes" rows="2" placeholder="Reason for advance, cheque details, etc..."></textarea></div>
<button type="submit" class="btn btn-primary">Save Advance Payment</button>
</form>
</div>
</div>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>👤 Customer Ledger — <?= sanitize($cust_info['name']) ?></legend>
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px;font-size:0.83rem">
<span><strong>Phone:</strong> <?= sanitize($cust_info['phone'] ?? '-') ?></span>
<span><strong>CNIC:</strong> <?= sanitize($cust_info['cnic'] ?? '-') ?></span>
<span><strong>Address:</strong> <?= sanitize($cust_info['address'] ?? '-') ?></span>
<span><strong>Filer Status:</strong> <?= $cust_info['is_filer'] ? 'Filer' : 'Non-Filer' ?></span>
</div>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Date</th><th>Description</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead>
<tbody>
<?php
            $sr = 1;
            $total_dr = 0;
            $total_cr = 0;
            while ($le = $ledger_entries->fetch_assoc()):
                if ($le['entry_type'] === 'debit') {
                    $running_bal -= $le['amount'];
                    $total_dr += $le['amount'];
                } else {
                    $running_bal += $le['amount'];
                    $total_cr += $le['amount'];
                }
                ?>
<tr>
<td><?= $sr++ ?></td>
<td><?= fmt_date($le['entry_date']) ?></td>
<td><?= sanitize($le['description']) ?></td>
<td><?= $le['entry_type'] === 'debit' ? fmt_money($le['amount']) : '-' ?></td>
<td><?= $le['entry_type'] === 'credit' ? fmt_money($le['amount']) : '-' ?></td>
<td style="color:<?= $running_bal >= 0 ? 'var(--success)' : 'var(--danger)' ?>;font-weight:700"><?= fmt_money(abs($running_bal)) ?> <?= $running_bal >= 0 ? 'Cr' : 'Dr' ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot>
<tr>
<td colspan="3"><strong>TOTAL</strong></td>
<td><strong><?= fmt_money($total_dr) ?></strong></td>
<td><strong><?= fmt_money($total_cr) ?></strong></td>
<td style="color:<?= $running_bal >= 0 ? 'var(--success)' : 'var(--danger)' ?>;font-weight:700"><strong><?= fmt_money(abs($running_bal)) ?> <?= $running_bal >= 0 ? 'Cr' : 'Dr' ?></strong></td>
</tr>
</tfoot>
</table>
</div>
<h4 style="font-size:0.82rem;color:var(--accent);margin:14px 0 8px;text-transform:uppercase">🚲 Purchase History</h4>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Date</th><th>Chassis</th><th>Model</th><th>Color</th><th>Selling Price</th><th>Status</th></tr></thead>
<tbody>
<?php
            $cust_bikes = $conn->query("SELECT b.*, m.model_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.customer_id=$sel_cust ORDER BY b.selling_date DESC");
            while ($cb = $cust_bikes->fetch_assoc()):
                ?>
<tr class="row-<?= $cb['status'] ?>">
<td><?= fmt_date($cb['selling_date']) ?></td>
<td><?= sanitize($cb['chassis_number']) ?></td>
<td><?= sanitize($cb['model_name']) ?></td>
<td><?= sanitize($cb['color']) ?></td>
<td><?= fmt_money($cb['selling_price']) ?></td>
<td><span class="badge badge-<?= $cb['status'] === 'sold' ? 'success' : ($cb['status'] === 'returned' ? 'danger' : 'info') ?>"><?= strtoupper($cb['status']) ?></span></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</fieldset>
<script>
$(document).ready(function() {
    $('select[name="cust_id"]').select2({
        minimumResultsForSearch: 10,
        placeholder: '-- Select Customer --',
        allowClear: false,
        theme: 'default'
    });
});
</script>
<?php endif; ?>
<?php
    elseif ($page === 'supplier_ledger'):
        $sel_sup = (int) ($_GET['sup_id'] ?? 0);
        $suppliers_for_led = $conn->query('SELECT id, name FROM suppliers ORDER BY name');
?>
<div class="filter-bar no-print animate__animated animate__fadeInLeft">
<form method="GET" action="index.php" style="display:contents">
<input type="hidden" name="page" value="supplier_ledger">
<div class="form-group"><label>Select Supplier</label>
<select name="sup_id" required onchange="this.form.submit()">
<option value="0">-- Select Supplier --</option>
<?php while ($sl = $suppliers_for_led->fetch_assoc()): ?>
<option value="<?= $sl['id'] ?>" <?= $sel_sup == $sl['id'] ? 'selected' : '' ?>><?= sanitize($sl['name']) ?></option>
<?php endwhile; ?>
</select>
</div>
</form>
</div>
<?php
        if ($sel_sup > 0):
            $sup_info = $conn->query("SELECT * FROM suppliers WHERE id=$sel_sup")->fetch_assoc();
            $sup_orders = $conn->query("SELECT po.*, COALESCE(SUM(CASE WHEN b.status!='returned_to_supplier' THEN b.purchase_price ELSE 0 END), po.total_amount) as bikes_total, SUM(CASE WHEN b.status!='returned_to_supplier' THEN 1 ELSE 0 END) as bike_count FROM purchase_orders po LEFT JOIN bikes b ON po.id=b.purchase_order_id WHERE po.supplier_id=$sel_sup GROUP BY po.id ORDER BY po.order_date ASC");
            $supplier_payments = $conn->query("SELECT * FROM payments WHERE transaction_type IN ('supplier_payment', 'supplier_refund') AND status!='bounced' AND ((transaction_type='supplier_payment' AND reference_id IN (SELECT id FROM purchase_orders WHERE supplier_id=$sel_sup)) OR (transaction_type='supplier_refund' AND reference_id IN (SELECT b.id FROM bikes b JOIN purchase_orders po2 ON po2.id=b.purchase_order_id WHERE po2.supplier_id=$sel_sup)) OR (reference_id=0 AND supplier_id=$sel_sup)) ORDER BY payment_date ASC");
            $running_bal = 0;
            $purchase_total_sum = 0;
            $payment_total_sum = 0;
            while ($order = $sup_orders->fetch_assoc())
                $purchase_total_sum += $order['bikes_total'];
            while ($payment = $supplier_payments->fetch_assoc()) {
                if ($payment['transaction_type'] === 'supplier_refund') {
                    $payment_total_sum -= $payment['amount'];
                } else {
                    $payment_total_sum += $payment['amount'];
                }
            }
            $bal_summary = $purchase_total_sum - $payment_total_sum;
            ?>
<div class="split-grid-3 animate__animated animate__fadeInDown" style="margin-bottom:14px">
    <div class="card danger"><div class="card-icon">📦</div><div class="card-body"><div class="card-label">Total Purchased</div><div class="card-value"><?= fmt_money($purchase_total_sum) ?></div></div></div>
    <div class="card success"><div class="card-icon">💵</div><div class="card-body"><div class="card-label">Total Paid</div><div class="card-value"><?= fmt_money($payment_total_sum) ?></div></div></div>
    <div class="card <?= $bal_summary > 0 ? 'warning' : 'info' ?>"><div class="card-icon">⚖️</div><div class="card-body"><div class="card-label"><?= $bal_summary > 0 ? 'Payable (You owe supplier)' : 'Advance (Supplier owes you)' ?></div><div class="card-value" style="color:<?= $bal_summary > 0 ? 'var(--danger)' : 'var(--success)' ?>;font-size:1.1rem"><?= fmt_money(abs($bal_summary)) ?></div></div></div>
</div>
<div class="print-btn-wrap no-print animate__animated animate__fadeInRight" style="display:flex;gap:8px;">
<button onclick="document.getElementById('makePaymentModal').classList.add('open')" class="btn btn-success btn-sm">+ Make Payment</button>
<button onclick="document.getElementById('receiveRefundSupModal').classList.add('open')" class="btn btn-warning btn-sm">💸 Receive Refund</button>
<button onclick="window.print()" class="btn btn-default btn-sm">🖨 Print Ledger</button>
</div>
<div class="modal-overlay" id="makePaymentModal">
<div class="modal">
<div class="modal-header"><h3>Make Payment to Supplier</h3><button class="modal-close" onclick="document.getElementById('makePaymentModal').classList.remove('open')">✕</button></div>
<form method="POST">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="add_sup_payment" value="1">
<div class="form-group" style="margin-bottom:8px"><label>Date <span class="req">*</span></label><input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Amount <span class="req">*</span></label><input type="number" name="amount" step="0.01" min="0.01" required value="<?= $bal_summary > 0 ? $bal_summary : '' ?>"></div>
<div class="form-group" style="margin-bottom:8px"><label>Method</label><select name="payment_method"><option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option><option value="cheque">Cheque</option><option value="online">Online</option></select></div>
<div class="form-group" style="margin-bottom:12px"><label>Notes</label><textarea name="notes" rows="2" placeholder="Cheque number, bank details, etc..."></textarea></div>
<button type="submit" class="btn btn-primary">Save Payment</button>
</form>
</div>
</div>
<div class="modal-overlay" id="receiveRefundSupModal">
<div class="modal">
<div class="modal-header"><h3>Receive Refund from Supplier</h3><button class="modal-close" onclick="document.getElementById('receiveRefundSupModal').classList.remove('open')">✕</button></div>
<form method="POST">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="receive_sup_payment" value="1">
<div class="form-group" style="margin-bottom:8px"><label>Date <span class="req">*</span></label><input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Amount <span class="req">*</span></label><input type="number" name="amount" step="0.01" min="0.01" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Method</label><select name="payment_method"><option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option><option value="cheque">Cheque</option><option value="online">Online</option></select></div>
<div class="form-group" style="margin-bottom:12px"><label>Notes</label><textarea name="notes" rows="2" placeholder="Reason for refund, cheque details, etc..."></textarea></div>
<button type="submit" class="btn btn-primary">Save Refund</button>
</form>
</div>
</div>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>🏭 Supplier Ledger — <?= sanitize($sup_info['name']) ?></legend>
<div style="margin-bottom:10px;font-size:0.83rem">
<strong>Contact:</strong> <?= sanitize($sup_info['contact'] ?? '-') ?> | <strong>Address:</strong> <?= sanitize($sup_info['address'] ?? '-') ?>
</div>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Date</th><th>Description</th><th>Debit (Dr)</th><th>Credit (Cr)</th><th>Balance</th></tr></thead>
<tbody>
<?php
            $sr = 1;
            $purchase_total = 0;
            $payment_total = 0;
            $transactions = [];
            $sup_orders->data_seek(0);
            while ($order = $sup_orders->fetch_assoc()) {
                $transactions[] = [
                    'date' => $order['order_date'],
                    'type' => 'purchase',
                    'amount' => $order['bikes_total'],
                    'description' => "Purchase Order #{$order['id']} ({$order['bike_count']} bikes)",
                    'id' => $order['id']
                ];
            }
            $supplier_payments->data_seek(0);
            while ($payment = $supplier_payments->fetch_assoc()) {
                if ($payment['transaction_type'] === 'supplier_refund') {
                    $transactions[] = [
                        'date' => $payment['payment_date'],
                        'type' => 'refund',
                        'amount' => $payment['amount'],
                        'description' => "Refund Received #{$payment['id']} ({$payment['payment_type']} - " . ($payment['cheque_number'] ?? '-') . ')',
                        'id' => $payment['id']
                    ];
                } else {
                    $transactions[] = [
                        'date' => $payment['payment_date'],
                        'type' => 'payment',
                        'amount' => $payment['amount'],
                        'description' => "Payment #{$payment['id']} ({$payment['payment_type']} - " . ($payment['cheque_number'] ?? '-') . ')',
                        'id' => $payment['id']
                    ];
                }
            }
            usort($transactions, function ($a, $b) {
                if ($a['date'] == $b['date']) {
                    return $a['id'] - $b['id'];
                }
                return strtotime($a['date']) - strtotime($b['date']);
            });
            foreach ($transactions as $trans):
                $debit = 0;
                $credit = 0;
                if ($trans['type'] === 'purchase') {
                    $debit = $trans['amount'];
                    $running_bal -= $debit;
                    $purchase_total += $debit;
                } elseif ($trans['type'] === 'refund') {
                    $debit = $trans['amount'];
                    $running_bal -= $debit;
                } else {
                    $credit = $trans['amount'];
                    $running_bal += $credit;
                    $payment_total += $credit;
                }
                ?>
<tr>
<td><?= $sr++ ?></td>
<td><?= fmt_date($trans['date']) ?></td>
<td><?= sanitize($trans['description']) ?></td>
<td><?= $debit > 0 ? fmt_money($debit) : '-' ?></td>
<td><?= $credit > 0 ? fmt_money($credit) : '-' ?></td>
<td style="color:<?= $running_bal >= 0 ? 'var(--success)' : 'var(--danger)' ?>;font-weight:700"><?= fmt_money(abs($running_bal)) ?> <?= $running_bal >= 0 ? 'Cr' : 'Dr' ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
<tr>
<td colspan="3"><strong>TOTAL</strong></td>
<td><strong><?= fmt_money($purchase_total) ?></strong></td>
<td><strong><?= fmt_money($payment_total) ?></strong></td>
<td style="color:<?= $running_bal >= 0 ? 'var(--success)' : 'var(--danger)' ?>;font-weight:700"><strong><?= fmt_money(abs($running_bal)) ?> <?= $running_bal >= 0 ? 'Cr' : 'Dr' ?></strong></td>
</tr>
</tfoot>
</table>
</div>
</fieldset>
<script>
$(document).ready(function() {
    $('select[name="sup_id"]').select2({
        minimumResultsForSearch: 10,
        placeholder: '-- Select Supplier --',
        allowClear: false,
        theme: 'default'
    });
});
</script>
<?php endif; ?>
<?php
    elseif ($page === 'reports'):
        $sub = sanitize($_GET['sub'] ?? 'stock');
        $rep_from = (!empty($_GET['rep_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['rep_from'])) ? $_GET['rep_from'] : date('Y-01-01');
        $rep_to = (!empty($_GET['rep_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['rep_to'])) ? $_GET['rep_to'] : date('Y-12-31');
        $rep_year = !empty($_GET['rep_year']) ? (int) $_GET['rep_year'] : (int) date('Y');
        $rep_month = !empty($_GET['rep_month']) ? (int) $_GET['rep_month'] : (int) date('n');
        $dep_type = sanitize($_GET['dep_type'] ?? '');
        $dest_id = (int) ($_GET['dest_id'] ?? 0);
        $deposited_by = sanitize($_GET['deposited_by'] ?? '');
        $ref_no = sanitize($_GET['ref_no'] ?? '');
?>
<div class="sub-tabs no-print animate__animated animate__fadeInDown">
<?php
        $sub_items = [
            ['stock', '📦 Current Stock'],
            ['sold', '✅ Sold Bikes'],
            ['model_wise', '📊 Model-wise'],
            ['tax', '🧾 Tax Report'],
            ['profit', '📈 Profit/Margin'],
            ['bank', '💳 Bank/Cheque'],
            ['monthly', '📅 Monthly Summary'],
            ['daily', '📆 Daily Ledger'],
            ['purchase_vs_sales', '🔄 Purchase vs Sales'],
            ['accessory_stock', '🛠️ Accessory Stock'],
            ['installments_summary', '🗓️ Installments Summary'],
            ['money_by_destination', '🏦 Money by Destination'],
            ['money_by_sale', '🧾 Money by Sale'],
            ['money_untracked', '⚠️ Untracked Sales'],
            ['money_flow', '📊 Money Flow'],
            ['bank_deposits_report', '🏧 Bank Deposits'],
        ];
        foreach ($sub_items as $si):
            ?>
<a href="index.php?page=reports&sub=<?= $si[0] ?>&rep_from=<?= $rep_from ?>&rep_to=<?= $rep_to ?>&rep_year=<?= $rep_year ?>&rep_month=<?= $rep_month ?>&dep_type=<?= $dep_type ?>&dest_id=<?= $dest_id ?>&deposited_by=<?= urlencode($deposited_by) ?>&ref_no=<?= urlencode($ref_no) ?>" class="sub-tab <?= $sub === $si[0] ? 'active' : '' ?>"><?= $si[1] ?></a>
<?php endforeach; ?>
</div>
<div class="filter-bar no-print animate__animated animate__fadeInLeft">
<form method="GET" action="index.php" style="display:contents">
<input type="hidden" name="page" value="reports">
<input type="hidden" name="sub" value="<?= $sub ?>">
<div class="form-group"><label>From Date</label><input type="date" name="rep_from" value="<?= $rep_from ?>"></div>
<div class="form-group"><label>To Date</label><input type="date" name="rep_to" value="<?= $rep_to ?>"></div>
<div class="form-group"><label>Year</label><input type="number" name="rep_year" value="<?= $rep_year ?>" min="2000" max="2100" style="width:90px"></div>
<div class="form-group"><label>Month</label>
<select name="rep_month">
<?php for ($m = 1; $m <= 12; $m++): ?>
<option value="<?= $m ?>" <?= $rep_month == $m ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
<?php endfor; ?>
</select>
</div>
<?php if ($sub === 'bank_deposits_report'): ?>
<div class="form-group"><label>Type</label>
<select name="dep_type">
<option value="">All Types</option>
<option value="cash" <?= $dep_type === 'cash' ? 'selected' : '' ?>>Cash</option>
<option value="cheque" <?= $dep_type === 'cheque' ? 'selected' : '' ?>>Cheque</option>
<option value="transfer" <?= $dep_type === 'transfer' ? 'selected' : '' ?>>Transfer</option>
<option value="online" <?= $dep_type === 'online' ? 'selected' : '' ?>>Online</option>
<option value="other" <?= $dep_type === 'other' ? 'selected' : '' ?>>Other</option>
</select>
</div>
<div class="form-group"><label>Destination</label>
<select name="dest_id">
<option value="0">All Destinations</option>
<?php
$dest_q = $conn->query("SELECT id, name, account_no FROM money_destinations WHERE (is_active=1 OR is_active IS NULL) ORDER BY name");
while ($d = $dest_q->fetch_assoc()):
?><option value="<?= $d['id'] ?>" <?= $dest_id == $d['id'] ? 'selected' : '' ?>><?= sanitize($d['name']) ?> (<?= sanitize($d['account_no'] ?: 'N/A') ?>)</option>
<?php endwhile; ?>
</select>
</div>
<div class="form-group"><label>Deposited By</label><input type="text" name="deposited_by" value="<?= sanitize($deposited_by) ?>" placeholder="Name" style="min-width:120px"></div>
<div class="form-group"><label>Ref #</label><input type="text" name="ref_no" value="<?= sanitize($ref_no) ?>" placeholder="Reference" style="min-width:120px"></div>
<?php endif; ?>
<button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-end">🔍 Filter</button>
</form>
<button onclick="window.print()" class="btn btn-default btn-sm" style="align-self:flex-end">🖨 Print</button>
</div>
<?php
        if ($sub === 'stock'):
            $stock_bikes = $conn->query("SELECT b.*, m.model_name, m.category, m.short_code FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.status='in_stock' ORDER BY m.model_name, b.inventory_date");
            $stk_total = 0;
            ?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📦 Current Stock Report</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Chassis</th><th>Motor#</th><th>Model</th><th>Category</th><th>Color</th><th>Purchase Price</th><th>Inventory Date</th><th>Days in Stock</th></tr></thead>
<tbody>
<?php
            $sr = 1;
            while ($bk = $stock_bikes->fetch_assoc()):
                $days = (int) ((time() - strtotime($bk['inventory_date'])) / 86400);
                $stk_total += $bk['purchase_price'];
                ?>
<tr>
<td><?= $sr++ ?></td>
<td style="font-family:Consolas,monospace"><?= sanitize($bk['chassis_number']) ?></td>
<td style="font-family:Consolas,monospace"><?= sanitize($bk['motor_number'] ?? '-') ?></td>
<td><?= sanitize($bk['model_name']) ?></td>
<td><?= sanitize($bk['category']) ?></td>
<td><?= sanitize($bk['color']) ?></td>
<td><?= fmt_money($bk['purchase_price']) ?></td>
<td><?= fmt_date($bk['inventory_date']) ?></td>
<td><?= $days ?> days</td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="6"><strong>TOTAL</strong></td><td><strong><?= fmt_money($stk_total) ?></strong></td><td colspan="2"></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'sold'):
            $sold_bikes_r = $conn->query("SELECT b.*, m.model_name, m.short_code, c.name as cust_name, COALESCE((SELECT SUM(sa.final_price) FROM sale_accessories sa WHERE sa.bike_id=b.id),0) AS acc_total FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id WHERE b.status='sold' AND b.selling_date BETWEEN '" . mysqli_real_escape_string($conn, $rep_from) . "' AND '" . mysqli_real_escape_string($conn, $rep_to) . "' ORDER BY b.selling_date DESC");
            $sold_total_sp = 0;
            $sold_total_pp = 0;
            $sold_total_mg = 0;
            $sold_total_tax = 0;
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>✅ Sold Bikes Report (<?= fmt_date($rep_from) ?> - <?= fmt_date($rep_to) ?>)</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Chassis</th><th>Model</th><th>Color</th><th>Customer</th><th>Selling Date</th><th>Purchase Price</th><th>Selling Price</th><th>Tax</th><th>Margin</th></tr></thead>
<tbody>
<?php
            $sr = 1;
            while ($sb = $sold_bikes_r->fetch_assoc()):
                $sold_total_sp += $sb['selling_price'] + $sb['acc_total'];
                $sold_total_pp += $sb['purchase_price'];
                $sold_total_mg += $sb['margin'];
                $sold_total_tax += $sb['tax_amount'];
                ?>
<tr class="row-sold">
<td><?= $sr++ ?></td>
<td style="font-family:Consolas,monospace"><?= sanitize($sb['chassis_number']) ?></td>
<td><?= sanitize($sb['model_name']) ?></td>
<td><?= sanitize($sb['color']) ?></td>
<td><?= sanitize($sb['cust_name'] ?? 'Walk-in') ?></td>
<td><?= fmt_date($sb['selling_date']) ?></td>
<td><?= fmt_money($sb['purchase_price']) ?></td>
<td><?= fmt_money($sb['selling_price'] + $sb['acc_total']) ?></td>
<td><?= fmt_money($sb['tax_amount']) ?></td>
<td style="color:<?= $sb['margin'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= fmt_money($sb['margin']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot>
<tr>
<td colspan="6"><strong>TOTAL</strong></td>
<td><strong><?= fmt_money($sold_total_pp) ?></strong></td>
<td><strong><?= fmt_money($sold_total_sp) ?></strong></td>
<td><strong><?= fmt_money($sold_total_tax) ?></strong></td>
<td style="color:<?= $sold_total_mg >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><strong><?= fmt_money($sold_total_mg) ?></strong></td>
</tr>
</tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'model_wise'):
            $mw_result = $conn->query("SELECT m.model_name, m.short_code, m.category,
    COUNT(b.id) as total_inv,
    SUM(CASE WHEN b.status='sold' THEN 1 ELSE 0 END) as sold_cnt,
    SUM(CASE WHEN b.status='in_stock' THEN 1 ELSE 0 END) as avail_cnt,
    SUM(CASE WHEN b.status IN ('returned', 'returned_to_supplier') THEN 1 ELSE 0 END) as ret_cnt,
    SUM(CASE WHEN b.status='damaged_lost' THEN 1 ELSE 0 END) as dmg_cnt,
    SUM(b.purchase_price) as total_pp,
    SUM(CASE WHEN b.status='sold' THEN b.selling_price + COALESCE((SELECT SUM(sa.final_price) FROM sale_accessories sa WHERE sa.bike_id=b.id),0) ELSE 0 END) as total_sp,
    SUM(CASE WHEN b.status='sold' THEN b.margin ELSE 0 END) as total_mg
    FROM models m LEFT JOIN bikes b ON m.id=b.model_id
    GROUP BY m.id ORDER BY m.model_name");
            $mw_t = [0, 0, 0, 0, 0, 0, 0];
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📊 Model-wise Sales Report</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Model</th><th>Short Code</th><th>Category</th><th>Inventory</th><th>Sold</th><th>Available</th><th>Returned</th><th>Damaged/Lost</th><th>Total Purchase</th><th>Total Sales</th><th>Total Margin</th></tr></thead>
<tbody>
<?php
            while ($mw = $mw_result->fetch_assoc()):
                $mw_t[0] += $mw['total_inv'];
                $mw_t[1] += $mw['sold_cnt'];
                $mw_t[2] += $mw['avail_cnt'];
                $mw_t[3] += $mw['ret_cnt'];
                $mw_t[7] += $mw['dmg_cnt'];
                $mw_t[4] += $mw['total_pp'];
                $mw_t[5] += $mw['total_sp'];
                $mw_t[6] += $mw['total_mg'];
                ?>
<tr>
<td><strong><?= sanitize($mw['model_name']) ?></strong></td>
<td><?= sanitize($mw['short_code']) ?></td>
<td><?= sanitize($mw['category']) ?></td>
<td><?= $mw['total_inv'] ?></td>
<td><span class="badge badge-success"><?= $mw['sold_cnt'] ?></span></td>
<td><span class="badge badge-info"><?= $mw['avail_cnt'] ?></span></td>
<td><span class="badge badge-danger"><?= $mw['ret_cnt'] ?></span></td>
<td><span class="badge badge-dark"><?= $mw['dmg_cnt'] ?></span></td>
<td><?= fmt_money($mw['total_pp']) ?></td>
<td><?= fmt_money($mw['total_sp']) ?></td>
<td style="color:<?= $mw['total_mg'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= fmt_money($mw['total_mg']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td><strong>TOTAL</strong></td><td colspan="3"><strong><?= $mw_t[0] ?></strong></td><td><strong><?= $mw_t[1] ?></strong></td><td><strong><?= $mw_t[2] ?></strong></td><td><strong><?= $mw_t[3] ?></strong></td><td><strong><?= $mw_t[7] ?? 0 ?></strong></td><td><strong><?= fmt_money($mw_t[4]) ?></strong></td><td><strong><?= fmt_money($mw_t[5]) ?></strong></td><td style="color:var(--success)"><strong><?= fmt_money($mw_t[6]) ?></strong></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'tax'):
            $tax_result = $conn->query("SELECT DATE_FORMAT(selling_date,'%Y-%m') as ym, COUNT(*) as cnt, SUM(tax_amount) as total_tax, SUM(purchase_price) as total_pp FROM bikes WHERE status='sold' AND selling_date BETWEEN '" . mysqli_real_escape_string($conn, $rep_from) . "' AND '" . mysqli_real_escape_string($conn, $rep_to) . "' GROUP BY ym ORDER BY ym DESC");
            $tax_total = 0;
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>🧾 Tax Report by Month</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Month</th><th>Bikes Sold</th><th>Total Purchase Value</th><th>Tax Amount (<?= (get_setting('tax_rate') ?? 0.1) * 100 ?>%)</th></tr></thead>
<tbody>
<?php
            while ($tr = $tax_result->fetch_assoc()):
                $tax_total += $tr['total_tax'];
                ?>
<tr>
<td><?= date('F Y', strtotime($tr['ym'] . '-01')) ?></td>
<td><?= $tr['cnt'] ?></td>
<td><?= fmt_money($tr['total_pp']) ?></td>
<td><?= fmt_money($tr['total_tax']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="3"><strong>TOTAL TAX</strong></td><td><strong><?= fmt_money($tax_total) ?></strong></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'profit'):
            $profit_monthly = $conn->query("SELECT DATE_FORMAT(b.selling_date,'%Y-%m') as ym, COUNT(*) as cnt, SUM(b.selling_price + COALESCE((SELECT SUM(sa.final_price) FROM sale_accessories sa WHERE sa.bike_id=b.id),0)) as total_sp, SUM(b.purchase_price) as total_pp, SUM(b.margin) as total_margin, SUM(b.tax_amount) as total_tax FROM bikes b WHERE b.status='sold' AND b.selling_date BETWEEN '" . mysqli_real_escape_string($conn, $rep_from) . "' AND '" . mysqli_real_escape_string($conn, $rep_to) . "' GROUP BY ym ORDER BY ym DESC");
            $profit_t = [0, 0, 0, 0, 0];
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📈 Profit / Margin Report</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Month</th><th>Bikes Sold</th><th>Total Purchase</th><th>Total Sales</th><th>Total Tax</th><th>Net Profit</th><th>Avg Margin</th></tr></thead>
<tbody>
<?php
            while ($pm = $profit_monthly->fetch_assoc()):
                $profit_t[0] += $pm['cnt'];
                $profit_t[1] += $pm['total_pp'];
                $profit_t[2] += $pm['total_sp'];
                $profit_t[3] += $pm['total_tax'];
                $profit_t[4] += $pm['total_margin'];
                $avg_margin = $pm['cnt'] > 0 ? $pm['total_margin'] / $pm['cnt'] : 0;
                ?>
<tr>
<td><?= date('F Y', strtotime($pm['ym'] . '-01')) ?></td>
<td><?= $pm['cnt'] ?></td>
<td><?= fmt_money($pm['total_pp']) ?></td>
<td><?= fmt_money($pm['total_sp']) ?></td>
<td><?= fmt_money($pm['total_tax']) ?></td>
<td style="color:<?= $pm['total_margin'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>;font-weight:700"><?= fmt_money($pm['total_margin']) ?></td>
<td><?= fmt_money($avg_margin) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot>
<tr>
<td><strong>TOTAL</strong></td>
<td><strong><?= $profit_t[0] ?></strong></td>
<td><strong><?= fmt_money($profit_t[1]) ?></strong></td>
<td><strong><?= fmt_money($profit_t[2]) ?></strong></td>
<td><strong><?= fmt_money($profit_t[3]) ?></strong></td>
<td style="color:var(--success)"><strong><?= fmt_money($profit_t[4]) ?></strong></td>
<td><strong><?= $profit_t[0] > 0 ? fmt_money($profit_t[4] / $profit_t[0]) : fmt_money(0) ?></strong></td>
</tr>
</tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'bank'):
            $bank_result = $conn->query("SELECT bank_name, payment_type, transaction_type, COUNT(*) as cnt, SUM(amount) as total FROM payments WHERE payment_type = 'cheque' AND (status IS NULL OR status IN ('pending', 'cleared')) GROUP BY bank_name, payment_type, transaction_type ORDER BY bank_name, transaction_type");
            $bank_data = [];
            while ($br2 = $bank_result->fetch_assoc()) {
                $bank_data[$br2['bank_name']][$br2['transaction_type']]['count'] = $br2['cnt'];
                $bank_data[$br2['bank_name']][$br2['transaction_type']]['total'] = $br2['total'];
            }
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>💳 Bank / Cheque Report</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Bank</th><th>Transaction Type</th><th>Total Cheques</th><th>Total Amount</th></tr></thead>
<tbody>
<?php
            $bank_totals = [0, 0];
            foreach ($bank_data as $bank => $types):
                foreach ($types as $type => $data):
                    $cnt = $data['count'] ?? 0;
                    $ttl = $data['total'] ?? 0;
                    $bank_totals[0] += $cnt;
                    $bank_totals[1] += $ttl;
                    ?>
<tr>
<td><?= sanitize($bank) ?></td>
<td><span class="badge badge-<?= ($type === 'sale' || $type === 'installment') ? 'success' : ($type === 'customer_refund' ? 'warning' : 'info') ?>"><?= strtoupper(str_replace('_', ' ', $type)) ?></span></td>
<td><?= $cnt ?></td>
<td><?= fmt_money($ttl) ?></td>
</tr>
<?php endforeach;
            endforeach; ?>
</tbody>
<tfoot><tr><td colspan="2"><strong>TOTAL</strong></td><td><strong><?= $bank_totals[0] ?></strong></td><td><strong><?= fmt_money($bank_totals[1]) ?></strong></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'monthly'):
            $monthly_r = $conn->query("SELECT DATE_FORMAT(order_date,'%Y-%m') as ym, COUNT(*) as purchased, SUM(purchase_price) as pp_total FROM bikes WHERE YEAR(order_date)=$rep_year GROUP BY ym");
            $sold_monthly_r = $conn->query("SELECT DATE_FORMAT(b.selling_date,'%Y-%m') as ym, COUNT(*) as sold_cnt, SUM(b.selling_price + COALESCE((SELECT SUM(sa.final_price) FROM sale_accessories sa WHERE sa.bike_id=b.id),0)) as sp_total, SUM(b.margin) as mg_total FROM bikes b WHERE b.status='sold' AND YEAR(b.selling_date)=$rep_year GROUP BY ym");
            $monthly_purch = [];
            $monthly_sales = [];
            while ($mr = $monthly_r->fetch_assoc())
                $monthly_purch[$mr['ym']] = $mr;
            while ($mr2 = $sold_monthly_r->fetch_assoc())
                $monthly_sales[$mr2['ym']] = $mr2;
            $all_months = array_unique(array_merge(array_keys($monthly_purch), array_keys($monthly_sales)));
            sort($all_months);
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📅 Monthly Summary — <?= $rep_year ?></legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Month</th><th>Purchased Units</th><th>Purchase Value</th><th>Sold Units</th><th>Sales Value</th><th>Profit</th></tr></thead>
<tbody>
<?php
            $mt = [0, 0, 0, 0, 0];
            foreach ($all_months as $ym):
                $p = $monthly_purch[$ym] ?? null;
                $s = $monthly_sales[$ym] ?? null;
                $mt[0] += $p['purchased'] ?? 0;
                $mt[1] += $p['pp_total'] ?? 0;
                $mt[2] += $s['sold_cnt'] ?? 0;
                $mt[3] += $s['sp_total'] ?? 0;
                $mt[4] += $s['mg_total'] ?? 0;
                ?>
<tr>
<td><?= date('F Y', strtotime($ym . '-01')) ?></td>
<td><?= $p['purchased'] ?? 0 ?></td>
<td><?= fmt_money($p['pp_total'] ?? 0) ?></td>
<td><?= $s['sold_cnt'] ?? 0 ?></td>
<td><?= fmt_money($s['sp_total'] ?? 0) ?></td>
<td style="color:<?= ($s['mg_total'] ?? 0) >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= fmt_money($s['mg_total'] ?? 0) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr><td><strong>TOTAL</strong></td><td><strong><?= $mt[0] ?></strong></td><td><strong><?= fmt_money($mt[1]) ?></strong></td><td><strong><?= $mt[2] ?></strong></td><td><strong><?= fmt_money($mt[3]) ?></strong></td><td style="color:var(--success)"><strong><?= fmt_money($mt[4]) ?></strong></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'daily'):
            $daily_date = valid_date($_GET['daily_date'] ?? '', true) && !empty($_GET['daily_date']) ? $_GET['daily_date'] : date('Y-m-d');
            $daily_sales = $conn->query("SELECT b.*, m.model_name, c.name as cust_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id WHERE b.selling_date='" . mysqli_real_escape_string($conn, $daily_date) . "' AND b.status='sold'");
            $daily_purch = $conn->query("SELECT b.*, m.model_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.inventory_date='" . mysqli_real_escape_string($conn, $daily_date) . "'");
            $daily_expenses = $conn->query("SELECT * FROM income_expenses WHERE entry_date='" . mysqli_real_escape_string($conn, $daily_date) . "' AND type='expense'");
            $daily_income_other = $conn->query("SELECT * FROM income_expenses WHERE entry_date='" . mysqli_real_escape_string($conn, $daily_date) . "' AND type='income'");
?>
<div class="filter-bar no-print" style="margin-bottom:8px">
<form method="GET" action="index.php" style="display:contents">
<input type="hidden" name="page" value="reports">
<input type="hidden" name="sub" value="daily">
<div class="form-group"><label>Select Date</label><input type="date" name="daily_date" value="<?= $daily_date ?>"></div>
<button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-end">🔍 View</button>
</form>
</div>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📆 Daily Ledger — <?= fmt_date($daily_date) ?></legend>
<h4 style="font-size:0.82rem;color:var(--success);margin-bottom:6px">Sales</h4>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Chassis</th><th>Model</th><th>Customer</th><th>Selling Price</th><th>Tax</th><th>Margin</th></tr></thead>
<tbody>
<?php $d_sp = 0;
            $d_mg = 0;
            $d_tax = 0;
            while ($ds = $daily_sales->fetch_assoc()):
                $d_sp += $ds['selling_price'];
                $d_mg += $ds['margin'];
                $d_tax += $ds['tax_amount']; ?>
<tr class="row-sold"><td><?= sanitize($ds['chassis_number']) ?></td><td><?= sanitize($ds['model_name']) ?></td><td><?= sanitize($ds['cust_name'] ?? 'Walk-in') ?></td><td><?= fmt_money($ds['selling_price']) ?></td><td><?= fmt_money($ds['tax_amount']) ?></td><td style="color:var(--success)"><?= fmt_money($ds['margin']) ?></td></tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="3"><strong>TOTAL</strong></td><td><strong><?= fmt_money($d_sp) ?></strong></td><td><strong><?= fmt_money($d_tax) ?></strong></td><td style="color:var(--success)"><strong><?= fmt_money($d_mg) ?></strong></td></tr></tfoot>
</table>
</div>
<h4 style="font-size:0.82rem;color:var(--accent);margin:10px 0 6px">Inventory Added</h4>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Chassis</th><th>Motor#</th><th>Model</th><th>Color</th><th>Purchase Price</th><th>Status</th></tr></thead>
<tbody>
<?php $d_pp = 0;
            while ($dp = $daily_purch->fetch_assoc()):
                $d_pp += $dp['purchase_price']; ?>
<tr><td><?= sanitize($dp['chassis_number']) ?></td><td><?= sanitize($dp['motor_number'] ?? '-') ?></td><td><?= sanitize($dp['model_name']) ?></td><td><?= sanitize($dp['color']) ?></td><td><?= fmt_money($dp['purchase_price']) ?></td><td><span class="badge badge-<?= $dp['status'] === 'in_stock' ? 'info' : ($dp['status'] === 'sold' ? 'success' : 'danger') ?>"><?= strtoupper($dp['status']) ?></span></td></tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="4"><strong>TOTAL</strong></td><td><strong><?= fmt_money($d_pp) ?></strong></td><td></td></tr></tfoot>
</table>
</div>
<h4 style="font-size:0.82rem;color:var(--danger);margin:10px 0 6px">Expenses</h4>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Category</th><th>Amount</th><th>Method</th><th>Notes</th></tr></thead>
<tbody>
<?php $d_exp_total = 0;
            while ($exp = $daily_expenses->fetch_assoc()):
                $d_exp_total += $exp['amount']; ?>
<tr>
<td><?= sanitize($exp['category']) ?></td>
<td><?= fmt_money($exp['amount']) ?></td>
<td><?= sanitize($exp['payment_method']) ?></td>
<td><?= sanitize($exp['notes']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td><strong>TOTAL</strong></td><td><strong><?= fmt_money($d_exp_total) ?></strong></td><td colspan="2"></td></tr></tfoot>
</table>
</div>
<h4 style="font-size:0.82rem;color:var(--success);margin:10px 0 6px">Other Income</h4>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Category</th><th>Amount</th><th>Method</th><th>Notes</th></tr></thead>
<tbody>
<?php $d_inc_total = 0;
            while ($inc = $daily_income_other->fetch_assoc()):
                $d_inc_total += $inc['amount']; ?>
<tr>
<td><?= sanitize($inc['category']) ?></td>
<td><?= fmt_money($inc['amount']) ?></td>
<td><?= sanitize($inc['payment_method']) ?></td>
<td><?= sanitize($inc['notes']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td><strong>TOTAL</strong></td><td><strong><?= fmt_money($d_inc_total) ?></strong></td><td colspan="2"></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'purchase_vs_sales'):
            $pvs = $conn->query("SELECT DATE_FORMAT(order_date,'%Y-%m') as ym, COUNT(*) as p_cnt, SUM(purchase_price) as p_val FROM bikes WHERE YEAR(order_date)=$rep_year GROUP BY ym");
            $svp = $conn->query("SELECT DATE_FORMAT(b.selling_date,'%Y-%m') as ym, COUNT(*) as s_cnt, SUM(b.selling_price + COALESCE((SELECT SUM(sa.final_price) FROM sale_accessories sa WHERE sa.bike_id=b.id),0)) as s_val FROM bikes b WHERE b.status='sold' AND YEAR(b.selling_date)=$rep_year GROUP BY ym");
            $pvs_data = [];
            $svp_data = [];
            while ($r = $pvs->fetch_assoc())
                $pvs_data[$r['ym']] = $r;
            while ($r = $svp->fetch_assoc())
                $svp_data[$r['ym']] = $r;
            $all_m = array_unique(array_merge(array_keys($pvs_data), array_keys($svp_data)));
            sort($all_m);
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>🔄 Purchase vs Sales — <?= $rep_year ?></legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Month</th><th>Purchased</th><th>Purchase Value</th><th>Sold</th><th>Sales Value</th><th>Difference</th></tr></thead>
<tbody>
<?php
            $pt = [0, 0, 0, 0];
            foreach ($all_m as $ym):
                $p = $pvs_data[$ym] ?? null;
                $s = $svp_data[$ym] ?? null;
                $diff = ($s['s_val'] ?? 0) - ($p['p_val'] ?? 0);
                $pt[0] += $p['p_cnt'] ?? 0;
                $pt[1] += $p['p_val'] ?? 0;
                $pt[2] += $s['s_cnt'] ?? 0;
                $pt[3] += $s['s_val'] ?? 0;
                ?>
<tr>
<td><?= date('F Y', strtotime($ym . '-01')) ?></td>
<td><?= $p['p_cnt'] ?? 0 ?></td>
<td><?= fmt_money($p['p_val'] ?? 0) ?></td>
<td><?= $s['s_cnt'] ?? 0 ?></td>
<td><?= fmt_money($s['s_val'] ?? 0) ?></td>
<td style="color:<?= $diff >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= fmt_money($diff) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr><td><strong>TOTAL</strong></td><td><strong><?= $pt[0] ?></strong></td><td><strong><?= fmt_money($pt[1]) ?></strong></td><td><strong><?= $pt[2] ?></strong></td><td><strong><?= fmt_money($pt[3]) ?></strong></td><td style="color:<?= ($pt[3] - $pt[1]) >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><strong><?= fmt_money($pt[3] - $pt[1]) ?></strong></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'accessory_stock'):
            $acc_stock_r = $conn->query('SELECT * FROM accessories ORDER BY name');
            $acc_total_stock = 0;
            $acc_total_value_pp = 0;
            $acc_total_value_sp = 0;
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>🛠️ Accessory Stock Report</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Accessory Name</th><th>SKU</th><th>Purchase Price</th><th>Selling Price</th><th>Current Stock</th><th>Total Purchase Value</th><th>Total Selling Value</th></tr></thead>
<tbody>
<?php
            $sr = 1;
            while ($acc = $acc_stock_r->fetch_assoc()):
                $total_pp_val = $acc['purchase_price'] * $acc['current_stock'];
                $total_sp_val = $acc['selling_price'] * $acc['current_stock'];
                $acc_total_stock += $acc['current_stock'];
                $acc_total_value_pp += $total_pp_val;
                $acc_total_value_sp += $total_sp_val;
                ?>
<tr>
<td><?= $sr++ ?></td>
<td><?= sanitize($acc['name']) ?></td>
<td><?= sanitize($acc['sku']) ?></td>
<td><?= fmt_money($acc['purchase_price']) ?></td>
<td><?= fmt_money($acc['selling_price']) ?></td>
<td><?= $acc['current_stock'] ?></td>
<td><?= fmt_money($total_pp_val) ?></td>
<td><?= fmt_money($total_sp_val) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot>
<tr>
<td colspan="5"><strong>TOTAL</strong></td>
<td><strong><?= $acc_total_stock ?></strong></td>
<td><strong><?= fmt_money($acc_total_value_pp) ?></strong></td>
<td><strong><?= fmt_money($acc_total_value_sp) ?></strong></td>
</tr>
</tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'installments_summary'):
            $inst_sum_r = $conn->query("SELECT
                c.name AS customer_name,
                COUNT(i.id) AS total_installments,
                SUM(i.installment_amount) AS total_due_amount,
                SUM(i.amount_paid) AS total_paid_amount,
                SUM(i.penalty_fee) AS total_penalty,
                SUM(CASE WHEN i.status IN ('pending','overdue') AND i.due_date < CURDATE() THEN (i.installment_amount - i.amount_paid + i.penalty_fee - COALESCE(i.penalty_paid,0)) ELSE 0 END) AS overdue_balance,
                SUM(CASE WHEN i.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN i.status = 'overdue' THEN 1 ELSE 0 END) AS overdue_count
            FROM installments i
            LEFT JOIN customers c ON i.customer_id = c.id
            GROUP BY c.id
            ORDER BY c.name");
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>🗓️ Installments Summary Report</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Customer</th><th>Total Installments</th><th>Total Due Amount</th><th>Total Paid</th><th>Total Penalty</th><th>Overdue Balance</th><th>Pending Count</th><th>Overdue Count</th></tr></thead>
<tbody>
<?php
            $sr = 1;
            $overall_totals = ['installments' => 0, 'due' => 0, 'paid' => 0, 'penalty' => 0, 'overdue_bal' => 0, 'pending_cnt' => 0, 'overdue_cnt' => 0];
            while ($sum = $inst_sum_r->fetch_assoc()):
                $overall_totals['installments'] += $sum['total_installments'];
                $overall_totals['due'] += $sum['total_due_amount'];
                $overall_totals['paid'] += $sum['total_paid_amount'];
                $overall_totals['penalty'] += $sum['total_penalty'];
                $overall_totals['overdue_bal'] += $sum['overdue_balance'];
                $overall_totals['pending_cnt'] += $sum['pending_count'];
                $overall_totals['overdue_cnt'] += $sum['overdue_count'];
                ?>
<tr>
<td><?= $sr++ ?></td>
<td><?= sanitize($sum['customer_name']) ?></td>
<td><?= $sum['total_installments'] ?></td>
<td><?= fmt_money($sum['total_due_amount']) ?></td>
<td><?= fmt_money($sum['total_paid_amount']) ?></td>
<td><?= fmt_money($sum['total_penalty']) ?></td>
<td style="color:<?= $sum['overdue_balance'] > 0 ? 'var(--danger)' : 'var(--success)' ?>"><?= fmt_money($sum['overdue_balance']) ?></td>
<td><?= $sum['pending_count'] ?></td>
<td style="color:var(--danger)"><?= $sum['overdue_count'] ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot>
<tr>
<td colspan="2"><strong>TOTAL</strong></td>
<td><strong><?= $overall_totals['installments'] ?></strong></td>
<td><strong><?= fmt_money($overall_totals['due']) ?></strong></td>
<td><strong><?= fmt_money($overall_totals['paid']) ?></strong></td>
<td><strong><?= fmt_money($overall_totals['penalty']) ?></strong></td>
<td style="color:<?= $overall_totals['overdue_bal'] > 0 ? 'var(--danger)' : 'var(--success)' ?>"><strong><?= fmt_money($overall_totals['overdue_bal']) ?></strong></td>
<td><strong><?= $overall_totals['pending_cnt'] ?></strong></td>
<td><strong><?= $overall_totals['overdue_cnt'] ?></strong></td>
</tr>
</tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'money_by_destination'):
            $mbd_result = $conn->query("SELECT md.id, md.name, md.type, md.details,
                COUNT(sma.id) as alloc_count,
                COALESCE(SUM(sma.amount),0) as total_allocated
                FROM money_destinations md
                LEFT JOIN sale_money_allocations sma ON md.id=sma.destination_id AND sma.allocation_date BETWEEN '" . mysqli_real_escape_string($conn, $rep_from) . "' AND '" . mysqli_real_escape_string($conn, $rep_to) . "'
                GROUP BY md.id ORDER BY total_allocated DESC");
            $mbd_grand = 0;
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>🏦 Money by Destination (<?= fmt_date($rep_from) ?> - <?= fmt_date($rep_to) ?>)</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Type</th><th>Destination</th><th>Details</th><th>Allocations</th><th>Total Amount</th></tr></thead>
<tbody>
<?php
            $sr = 1;
            while ($mbd = $mbd_result->fetch_assoc()):
                $mbd_grand += $mbd['total_allocated'];
                $ti = ['bank' => '🏦', 'person' => '👤', 'wallet' => '💳'][$mbd['type']] ?? '📌';
                ?>
<tr>
<td><?= $sr++ ?></td>
<td><span class="badge badge-<?= $mbd['type'] === 'bank' ? 'info' : ($mbd['type'] === 'person' ? 'success' : 'warning') ?>"><?= $ti ?> <?= strtoupper($mbd['type']) ?></span></td>
<td><strong><?= sanitize($mbd['name']) ?></strong></td>
<td><?= sanitize($mbd['details'] ?: '-') ?></td>
<td><?= $mbd['alloc_count'] ?></td>
<td><strong><?= fmt_money($mbd['total_allocated']) ?></strong></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="4"><strong>GRAND TOTAL</strong></td><td></td><td><strong><?= fmt_money($mbd_grand) ?></strong></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'money_by_sale'):
            $mbs_result = $conn->query("SELECT b.id as bike_id, b.chassis_number, b.selling_price, b.selling_date,
                m.model_name, c.name as cust_name,
                COALESCE((SELECT SUM(sa2.final_price) FROM sale_accessories sa2 WHERE sa2.bike_id=b.id),0) as acc_total,
                COALESCE((SELECT SUM(sma.amount) FROM sale_money_allocations sma WHERE sma.bike_id=b.id),0) as total_allocated
                FROM bikes b
                LEFT JOIN models m ON b.model_id=m.id
                LEFT JOIN customers c ON b.customer_id=c.id
                WHERE b.status='sold' AND b.selling_date BETWEEN '" . mysqli_real_escape_string($conn, $rep_from) . "' AND '" . mysqli_real_escape_string($conn, $rep_to) . "'
                ORDER BY b.selling_date DESC");
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>🧾 Money by Sale (<?= fmt_date($rep_from) ?> - <?= fmt_date($rep_to) ?>)</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Chassis</th><th>Model</th><th>Customer</th><th>Sale Date</th><th>Sale Total</th><th>Allocated</th><th>Remaining</th><th>Destinations</th></tr></thead>
<tbody>
<?php
            $sr = 1;
            $mbs_total_sale = 0;
            $mbs_total_alloc = 0;
            while ($mbs = $mbs_result->fetch_assoc()):
                $sale_total = $mbs['selling_price'] + $mbs['acc_total'];
                $remaining = $sale_total - $mbs['total_allocated'];
                $mbs_total_sale += $sale_total;
                $mbs_total_alloc += $mbs['total_allocated'];
                $dest_details = $conn->query('SELECT md.name, md.type, sma.amount FROM sale_money_allocations sma JOIN money_destinations md ON sma.destination_id=md.id WHERE sma.bike_id=' . $mbs['bike_id']);
                $dest_chips = '';
                if ($dest_details) {
                    while ($dd = $dest_details->fetch_assoc()) {
                        $dti = ['bank' => '🏦', 'person' => '👤', 'wallet' => '💳'][$dd['type']] ?? '';
                        $dest_chips .= '<span class="badge badge-' . ($dd['type'] === 'bank' ? 'info' : ($dd['type'] === 'person' ? 'success' : 'warning')) . '" style="margin:1px">' . $dti . ' ' . sanitize($dd['name']) . ': ' . fmt_money($dd['amount']) . '</span> ';
                    }
                }
                ?>
<tr>
<td><?= $sr++ ?></td>
<td style="font-family:Consolas,monospace"><?= sanitize($mbs['chassis_number']) ?></td>
<td><?= sanitize($mbs['model_name']) ?></td>
<td><?= sanitize($mbs['cust_name'] ?? 'Walk-in') ?></td>
<td><?= fmt_date($mbs['selling_date']) ?></td>
<td><?= fmt_money($sale_total) ?></td>
<td><?= fmt_money($mbs['total_allocated']) ?></td>
<td style="color:<?= $remaining > 0 ? 'var(--warning)' : 'var(--success)' ?>;font-weight:700"><?= fmt_money($remaining) ?></td>
<td><?= $dest_chips ?: '<span style="color:var(--text3)">None</span>' ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="5"><strong>TOTAL</strong></td><td><strong><?= fmt_money($mbs_total_sale) ?></strong></td><td><strong><?= fmt_money($mbs_total_alloc) ?></strong></td><td style="color:<?= ($mbs_total_sale - $mbs_total_alloc) > 0 ? 'var(--warning)' : 'var(--success)' ?>"><strong><?= fmt_money($mbs_total_sale - $mbs_total_alloc) ?></strong></td><td></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'money_untracked'):
            $mu_result = $conn->query("SELECT b.id, b.chassis_number, b.selling_price, b.selling_date,
                m.model_name, c.name as cust_name,
                COALESCE((SELECT SUM(sa2.final_price) FROM sale_accessories sa2 WHERE sa2.bike_id=b.id),0) as acc_total,
                COALESCE((SELECT SUM(sma.amount) FROM sale_money_allocations sma WHERE sma.bike_id=b.id),0) as total_allocated
                FROM bikes b
                LEFT JOIN models m ON b.model_id=m.id
                LEFT JOIN customers c ON b.customer_id=c.id
                WHERE b.status='sold'
                HAVING (b.selling_price + acc_total) > total_allocated
                ORDER BY (b.selling_price + acc_total - total_allocated) DESC");
            $mu_total_gap = 0;
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>⚠️ Untracked / Partially Tracked Sales</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Chassis</th><th>Model</th><th>Customer</th><th>Sale Date</th><th>Sale Total</th><th>Allocated</th><th>Untracked Amount</th><th class="no-sort">Action</th></tr></thead>
<tbody>
<?php
            $sr = 1;
            while ($mu = $mu_result->fetch_assoc()):
                $sale_total = $mu['selling_price'] + $mu['acc_total'];
                $gap = $sale_total - $mu['total_allocated'];
                $mu_total_gap += $gap;
                ?>
<tr>
<td><?= $sr++ ?></td>
<td style="font-family:Consolas,monospace"><?= sanitize($mu['chassis_number']) ?></td>
<td><?= sanitize($mu['model_name']) ?></td>
<td><?= sanitize($mu['cust_name'] ?? 'Walk-in') ?></td>
<td><?= fmt_date($mu['selling_date']) ?></td>
<td><?= fmt_money($sale_total) ?></td>
<td><?= fmt_money($mu['total_allocated']) ?></td>
<td style="color:var(--danger);font-weight:700"><?= fmt_money($gap) ?></td>
<td class="no-print"><a href="index.php?page=money_tracking&filter_bike=<?= $mu['id'] ?>" class="btn btn-primary btn-sm">💸 Track</a></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="7"><strong>TOTAL UNTRACKED</strong></td><td style="color:var(--danger)"><strong><?= fmt_money($mu_total_gap) ?></strong></td><td></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'money_flow'):
            $mf_result = $conn->query("SELECT DATE_FORMAT(sma.allocation_date,'%Y-%m') as ym, md.type,
                COUNT(sma.id) as alloc_count, SUM(sma.amount) as total_amount
                FROM sale_money_allocations sma
                JOIN money_destinations md ON sma.destination_id=md.id
                WHERE YEAR(sma.allocation_date)=$rep_year
                GROUP BY ym, md.type ORDER BY ym, md.type");
            $mf_data = [];
            $mf_months = [];
            while ($mf = $mf_result->fetch_assoc()) {
                $mf_data[$mf['ym']][$mf['type']] = ['count' => $mf['alloc_count'], 'amount' => $mf['total_amount']];
                $mf_months[$mf['ym']] = true;
            }
            ksort($mf_months);
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📊 Monthly Money Flow — <?= $rep_year ?></legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Month</th><th>🏦 Bank</th><th>👤 Person</th><th>💳 Wallet</th><th>Total</th></tr></thead>
<tbody>
<?php
            $mf_totals = ['bank' => 0, 'person' => 0, 'wallet' => 0];
            foreach ($mf_months as $ym => $_v):
                $bank_amt = $mf_data[$ym]['bank']['amount'] ?? 0;
                $person_amt = $mf_data[$ym]['person']['amount'] ?? 0;
                $wallet_amt = $mf_data[$ym]['wallet']['amount'] ?? 0;
                $month_total = $bank_amt + $person_amt + $wallet_amt;
                $mf_totals['bank'] += $bank_amt;
                $mf_totals['person'] += $person_amt;
                $mf_totals['wallet'] += $wallet_amt;
                ?>
<tr>
<td><?= date('F Y', strtotime($ym . '-01')) ?></td>
<td><?= fmt_money($bank_amt) ?></td>
<td><?= fmt_money($person_amt) ?></td>
<td><?= fmt_money($wallet_amt) ?></td>
<td><strong><?= fmt_money($month_total) ?></strong></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr><td><strong>TOTAL</strong></td><td><strong><?= fmt_money($mf_totals['bank']) ?></strong></td><td><strong><?= fmt_money($mf_totals['person']) ?></strong></td><td><strong><?= fmt_money($mf_totals['wallet']) ?></strong></td><td><strong><?= fmt_money($mf_totals['bank'] + $mf_totals['person'] + $mf_totals['wallet']) ?></strong></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        elseif ($sub === 'bank_deposits_report'):
            $dep_where = ["bd.deposit_date BETWEEN '" . mysqli_real_escape_string($conn, $rep_from) . "' AND '" . mysqli_real_escape_string($conn, $rep_to) . "'"];
            if (!empty($dep_type)) {
                $dep_where[] = "bd.deposit_type = '" . mysqli_real_escape_string($conn, $dep_type) . "'";
            }
            if ($dest_id > 0) {
                $dep_where[] = "bd.destination_id = $dest_id";
            }
            if (!empty($deposited_by)) {
                $dep_where[] = "bd.deposited_by LIKE '%" . mysqli_real_escape_string($conn, $deposited_by) . "%'";
            }
            if (!empty($ref_no)) {
                $dep_where[] = "bd.reference_no LIKE '%" . mysqli_real_escape_string($conn, $ref_no) . "%'";
            }
            $dep_report_q = $conn->query("SELECT bd.*, md.name as dest_name, md.account_no,
                COUNT(da.id) as bike_link_count,
                GROUP_CONCAT(DISTINCT CONCAT(b.chassis_number, ' (', COALESCE(da.amount,0), ')') SEPARATOR ', ') as bike_details
                FROM bank_deposits bd
                LEFT JOIN money_destinations md ON bd.destination_id=md.id
                LEFT JOIN deposit_allocations da ON da.deposit_id=bd.id
                LEFT JOIN bikes b ON da.bike_id=b.id
                WHERE " . implode(" AND ", $dep_where) . "
                GROUP BY bd.id ORDER BY bd.deposit_date DESC");
            $dep_grand = 0;
            $dep_counts = ['cash' => 0, 'cheque' => 0, 'transfer' => 0, 'online' => 0, 'other' => 0];
            $dep_totals = ['cash' => 0, 'cheque' => 0, 'transfer' => 0, 'online' => 0, 'other' => 0];
?>
<fieldset class="fieldset animate__animated animate__fadeInUp"><legend>🏧 Bank Deposits Report (<?= fmt_date($rep_from) ?> - <?= fmt_date($rep_to) ?>)</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Date</th><th>Destination</th><th>Amount</th><th>Type</th><th>Reference</th><th>Linked Bikes</th><th>Deposited By</th></tr></thead>
<tbody>
<?php
            $sr = 1;
            while ($dr = $dep_report_q->fetch_assoc()):
                $dep_grand += $dr['amount'];
                $dep_counts[$dr['deposit_type']]++;
                $dep_totals[$dr['deposit_type']] += $dr['amount'];
                ?>
<tr>
<td><?= $sr++ ?></td>
<td><?= fmt_date($dr['deposit_date']) ?></td>
<td><strong><?= sanitize($dr['dest_name']) ?></strong><br><small style="color:var(--text3)"><?= sanitize($dr['account_no'] ?: '') ?></small></td>
<td><strong><?= fmt_money($dr['amount']) ?></strong></td>
<td><span class="badge badge-<?= $dr['deposit_type'] === 'cash' ? 'success' : ($dr['deposit_type'] === 'cheque' ? 'warning' : 'info') ?>"><?= strtoupper($dr['deposit_type']) ?></span></td>
<td><?= sanitize($dr['reference_no'] ?: '-') ?></td>
<td><?= $dr['bike_link_count'] > 0 ? '<small>' . sanitize($dr['bike_details']) . '</small>' : '<span style="color:var(--text3)">—</span>' ?></td>
<td><?= sanitize($dr['deposited_by'] ?: '-') ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="3"><strong>GRAND TOTAL</strong></td><td><strong><?= fmt_money($dep_grand) ?></strong></td><td colspan="4"></td></tr></tfoot>
</table>
</div>
</fieldset>
<fieldset class="fieldset animate__animated animate__fadeInUp" style="margin-top:12px"><legend>📊 Deposit Summary by Type</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Type</th><th>Count</th><th>Total Amount</th></tr></thead>
<tbody>
<?php foreach (['cash' => 'Cash', 'cheque' => 'Cheque', 'transfer' => 'Transfer', 'online' => 'Online', 'other' => 'Other'] as $dk => $dl): ?>
<tr><td><span class="badge badge-<?= $dk === 'cash' ? 'success' : ($dk === 'cheque' ? 'warning' : 'info') ?>"><?= strtoupper($dk) ?></span></td><td><?= $dep_counts[$dk] ?></td><td><strong><?= fmt_money($dep_totals[$dk]) ?></strong></td></tr>
<?php endforeach; ?>
</tbody>
<tfoot><tr><td><strong>TOTAL</strong></td><td><strong><?= array_sum($dep_counts) ?></strong></td><td><strong><?= fmt_money($dep_grand) ?></strong></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php
        endif;
    elseif ($page === 'models'):
        $models_result = $conn->query("SELECT m.*, COUNT(b.id) as bike_count, SUM(CASE WHEN b.status='in_stock' THEN 1 ELSE 0 END) as in_stock, SUM(CASE WHEN b.status='sold' THEN 1 ELSE 0 END) as sold_cnt FROM models m LEFT JOIN bikes b ON m.id=b.model_id GROUP BY m.id ORDER BY m.model_name");
        $edit_model_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_model = null;
        if ($edit_model_id) {
            $em = $conn->query("SELECT * FROM models WHERE id=$edit_model_id");
            $edit_model = $em ? $em->fetch_assoc() : null;
        }
        ?>
<div style="display:flex;gap:8px;margin-bottom:10px" class="no-print animate__animated animate__fadeInLeft">
<?php if (has_permission($conn, 'models', 'add')): ?>
<button class="btn btn-success" onclick="document.getElementById('addModelFormArea').style.display='block';document.getElementById('addModelFormArea').scrollIntoView()">+ Add Model</button>
<?php endif; ?>
</div>
<div id="addModelFormArea" style="display:<?= $edit_model ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_model ? '✏ Edit Model' : '+ Add New Model' ?></legend>
<form id="modelForm" method="POST" enctype="multipart/form-data" action="index.php?page=models&action=<?= $edit_model ? 'edit' : 'add' ?>">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<?php if ($edit_model): ?><input type="hidden" name="id" value="<?= $edit_model['id'] ?>"><?php endif; ?>
<div class="form-row">
<div class="form-group"><label>Model Code <span class="req">*</span></label><input type="text" name="model_code" value="<?= sanitize($edit_model['model_code'] ?? '') ?>" required></div>
<div class="form-group"><label>Model Name <span class="req">*</span></label><input type="text" name="model_name" value="<?= sanitize($edit_model['model_name'] ?? '') ?>" required></div>
<div class="form-group"><label>Category</label><input type="text" name="category" value="<?= sanitize($edit_model['category'] ?? 'Electric Bike') ?>"></div>
<div class="form-group"><label>Short Code</label><input type="text" name="short_code" value="<?= sanitize($edit_model['short_code'] ?? '') ?>"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Top Speed (km/h)</label><input type="text" name="top_speed" value="<?= sanitize($edit_model['top_speed'] ?? '') ?>" placeholder="e.g. 100km/h"></div>
<div class="form-group"><label>Max Range (km)</label><input type="text" name="max_range" value="<?= sanitize($edit_model['max_range'] ?? '') ?>" placeholder="e.g. 80km Range"></div>
<div class="form-group"><label>Image</label><input type="file" name="image" accept="image/*" style="padding:4px"></div>
</div>
<button type="submit" class="btn btn-primary">💾 Save</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('addModelFormArea').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Image</th><th>Model Code</th><th>Model Name</th><th>Category</th><th>Short Code</th><th>Total Inventory</th><th>In Stock</th><th>Sold</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php $sr = 1;
        while ($mdl = $models_result->fetch_assoc()): ?>
<tr>
<td><?= $sr++ ?></td>
<td><?php if (!empty($mdl['image'])): ?><img src="<?= sanitize($mdl['image']) ?>" style="height:30px;width:auto;border-radius:2px"><?php else: ?>-<?php endif; ?></td>
<td><strong><?= sanitize($mdl['model_code']) ?></strong></td>
<td><?= sanitize($mdl['model_name']) ?></td>
<td><?= sanitize($mdl['category']) ?></td>
<td><code><?= sanitize($mdl['short_code']) ?></code></td>
<td><?= $mdl['bike_count'] ?></td>
<td><span class="badge badge-info"><?= $mdl['in_stock'] ?></span></td>
<td><span class="badge badge-success"><?= $mdl['sold_cnt'] ?></span></td>
<td class="no-print">
<div class="actions-col">
<?php if (has_permission($conn, 'purchase', 'add')): ?><a href="index.php?page=purchase&model_id=<?= $mdl['id'] ?>" class="btn btn-success btn-sm" title="Purchase">📦</a><?php endif; ?>
<?php if (has_permission($conn, 'sale', 'add')): ?><a href="index.php?page=sale&model_id=<?= $mdl['id'] ?>" class="btn btn-warning btn-sm" title="Sell">🛒</a><?php endif; ?>
<?php if (has_permission($conn, 'models', 'edit')): ?><a href="index.php?page=models&edit_id=<?= $mdl['id'] ?>" class="btn btn-primary btn-sm">✏ Edit</a><?php endif; ?>
<?php if (has_permission($conn, 'models', 'delete')): ?>
<form method="POST" action="index.php?page=models&action=delete" style="display:inline">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="id" value="<?= $mdl['id'] ?>">
<button type="submit" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete this model?', text: 'Are you sure you want to delete this model? Only possible if no bikes are linked.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php
    elseif ($page === 'accessories'):
        $accessories_result = $conn->query('SELECT * FROM accessories ORDER BY name');
        $edit_acc_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_acc = null;
        if ($edit_acc_id) {
            $ea = $conn->query("SELECT * FROM accessories WHERE id=$edit_acc_id");
            $edit_acc = $ea ? $ea->fetch_assoc() : null;
        }
        $acc_stats = $conn->query('SELECT COUNT(id) as total_items, SUM(current_stock) as total_stock, SUM(current_stock * purchase_price) as total_pp_val, SUM(current_stock * selling_price) as total_sp_val FROM accessories')->fetch_assoc();
        $sold_stats = $conn->query('SELECT SUM(sa.quantity) as total_sold_qty, SUM(sa.final_price) as total_revenue, SUM(sa.discount_amount) as total_discount, SUM(sa.final_price - (sa.quantity * a.purchase_price)) as total_profit FROM sale_accessories sa JOIN accessories a ON sa.accessory_id = a.id')->fetch_assoc();
        $top_sold = $conn->query('SELECT a.name, SUM(sa.quantity) as qty FROM sale_accessories sa JOIN accessories a ON sa.accessory_id = a.id GROUP BY sa.accessory_id ORDER BY qty DESC LIMIT 5');
        $ts_labels = [];
        $ts_data = [];
        if ($top_sold) {
            while ($r = $top_sold->fetch_assoc()) {
                $ts_labels[] = $r['name'];
                $ts_data[] = $r['qty'];
            }
        }
?>
<div class="card-grid animate__animated animate__fadeInDown">
    <div class="card accent"><div class="card-icon">🛠️</div><div class="card-body"><div class="card-label">Unique Items</div><div class="card-value"><?= number_format((float) ($acc_stats['total_items'] ?? 0)) ?></div></div></div>
    <div class="card success"><div class="card-icon">📦</div><div class="card-body"><div class="card-label">Total Stock Qty</div><div class="card-value"><?= number_format((float) ($acc_stats['total_stock'] ?? 0)) ?></div></div></div>
    <div class="card warning"><div class="card-icon">💰</div><div class="card-body"><div class="card-label">Stock Value (PP)</div><div class="card-value" style="font-size:1rem"><?= $currency ?> <?= number_format((float) ($acc_stats['total_pp_val'] ?? 0)) ?></div></div></div>
    <div class="card danger"><div class="card-icon">🛒</div><div class="card-body"><div class="card-label">Qty Sold</div><div class="card-value"><?= number_format((float) ($sold_stats['total_sold_qty'] ?? 0)) ?></div></div></div>
    <div class="card success"><div class="card-icon">💵</div><div class="card-body"><div class="card-label">Total Revenue</div><div class="card-value" style="font-size:1rem"><?= $currency ?> <?= number_format((float) ($sold_stats['total_revenue'] ?? 0)) ?></div></div></div>
    <div class="card accent"><div class="card-icon">📈</div><div class="card-body"><div class="card-label">Total Profit</div><div class="card-value" style="font-size:1rem;color:var(--success)"><?= $currency ?> <?= number_format((float) ($sold_stats['total_profit'] ?? 0)) ?></div></div></div>
    <div class="card warning"><div class="card-icon">💸</div><div class="card-body"><div class="card-label">Discounts Given</div><div class="card-value" style="font-size:1rem"><?= $currency ?> <?= number_format((float) ($sold_stats['total_discount'] ?? 0)) ?></div></div></div>
    <div class="card"><div class="card-icon">🎯</div><div class="card-body"><div class="card-label">Stock Value (SP)</div><div class="card-value" style="font-size:1rem"><?= $currency ?> <?= number_format((float) ($acc_stats['total_sp_val'] ?? 0)) ?></div></div></div>
</div>
<div class="split-grid" style="margin-bottom:16px;">
    <fieldset class="fieldset animate__animated animate__fadeInUp"><legend>🏆 Top 5 Sold Accessories</legend><div style="position:relative;height:250px;width:100%"><canvas id="accTopSoldChart"></canvas></div></fieldset>
    <fieldset class="fieldset animate__animated animate__fadeInUp"><legend>📊 Stock & Sales Overview</legend>
        <div style="display:flex;flex-direction:column;gap:10px;margin-top:10px">
            <div style="background:var(--bg2);border:1px solid var(--border);padding:10px;border-radius:2px;"><strong>Revenue to Cost Ratio:</strong> <?= ($sold_stats['total_revenue'] ?? 0) > 0 && ($sold_stats['total_revenue'] - $sold_stats['total_profit']) > 0 ? round(($sold_stats['total_revenue'] / ($sold_stats['total_revenue'] - $sold_stats['total_profit'])), 2) . 'x' : 'N/A' ?></div>
            <div style="background:var(--bg2);border:1px solid var(--border);padding:10px;border-radius:2px;"><strong>Avg Profit per Item:</strong> <?= ($sold_stats['total_sold_qty'] ?? 0) > 0 ? $currency . ' ' . number_format($sold_stats['total_profit'] / $sold_stats['total_sold_qty'], 2) : 'N/A' ?></div>
            <div style="background:var(--bg2);border:1px solid var(--border);padding:10px;border-radius:2px;"><strong>Estimated Future Profit (Current Stock):</strong> <?= $currency ?> <?= number_format((float) ($acc_stats['total_sp_val'] ?? 0) - (float) ($acc_stats['total_pp_val'] ?? 0)) ?></div>
        </div>
    </fieldset>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if(document.getElementById('accTopSoldChart')) {
        new Chart(document.getElementById('accTopSoldChart'), {
            type: 'bar',
            data: { labels: <?= json_encode($ts_labels) ?>, datasets: [{ label: 'Quantity Sold', data: <?= json_encode($ts_data) ?>, backgroundColor: '#4a9eff', borderRadius: 2 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: 'var(--border)' } }, x: { grid: { color: 'var(--border)' } } } }
        });
    }
});
</script>
<div style="display:flex;gap:8px;margin-bottom:10px" class="no-print animate__animated animate__fadeInLeft">
<?php if (has_permission($conn, 'accessories', 'add')): ?>
<button class="btn btn-success" onclick="document.getElementById('addAccFormArea').style.display='block';document.getElementById('addAccFormArea').scrollIntoView()">+ Add Accessory</button>
<?php endif; ?>
</div>
<div id="addAccFormArea" style="display:<?= $edit_acc ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_acc ? '✏ Edit Accessory' : '+ Add New Accessory' ?></legend>
<form id="accessoryForm" method="POST" action="index.php?page=accessories&action=<?= $edit_acc ? 'edit' : 'add' ?>">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<?php if ($edit_acc): ?><input type="hidden" name="id" value="<?= $edit_acc['id'] ?>"><?php endif; ?>
<div class="form-row">
<div class="form-group"><label>Accessory Name <span class="req">*</span></label><input type="text" name="name" value="<?= sanitize($edit_acc['name'] ?? '') ?>" required></div>
<div class="form-group"><label>SKU <span class="req">*</span></label><input type="text" name="sku" value="<?= sanitize($edit_acc['sku'] ?? '') ?>" required></div>
</div>
<div class="form-row">
<div class="form-group"><label>Purchase Price (<?= $currency ?>) <span class="req">*</span></label><input type="number" name="purchase_price" step="0.01" min="0" value="<?= $edit_acc['purchase_price'] ?? '0.00' ?>" required></div>
<div class="form-group"><label>Selling Price (<?= $currency ?>) <span class="req">*</span></label><input type="number" name="selling_price" step="0.01" min="0" value="<?= $edit_acc['selling_price'] ?? '0.00' ?>" required></div>
<div class="form-group"><label>Current Stock <span class="req">*</span></label><input type="number" name="current_stock" min="0" value="<?= $edit_acc['current_stock'] ?? '0' ?>" required></div>
</div>
<button type="submit" class="btn btn-primary">💾 Save</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('addAccFormArea').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Name</th><th>SKU</th><th>Purchase Price</th><th>Selling Price</th><th>Current Stock</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php $sr = 1;
        while ($acc = $accessories_result->fetch_assoc()): ?>
<tr>
<td><?= $sr++ ?></td>
<td><strong><?= sanitize($acc['name']) ?></strong></td>
<td><code><?= sanitize($acc['sku']) ?></code></td>
<td><?= fmt_money($acc['purchase_price']) ?></td>
<td><?= fmt_money($acc['selling_price']) ?></td>
<td><?= $acc['current_stock'] ?></td>
<td class="no-print">
<div class="actions-col">
<?php if (has_permission($conn, 'accessories', 'edit')): ?><a href="index.php?page=accessories&edit_id=<?= $acc['id'] ?>" class="btn btn-primary btn-sm">✏ Edit</a><?php endif; ?>
<?php if (has_permission($conn, 'accessories', 'delete')): ?>
<form method="POST" action="index.php?page=accessories&action=delete" style="display:inline">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="id" value="<?= $acc['id'] ?>">
<button type="submit" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete this accessory?', text: 'Are you sure you want to delete this accessory? Only possible if no sales are linked.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php
    elseif ($page === 'quotations'):
        $quotes_r = $conn->query('SELECT q.*, b.chassis_number, m.model_name, c.name AS customer_name, u.username AS created_by_user FROM quotations q LEFT JOIN bikes b ON q.bike_id=b.id LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON q.customer_id=c.id LEFT JOIN users u ON q.created_by=u.id ORDER BY q.id DESC');
        $edit_quote_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_quote = null;
        if ($edit_quote_id) {
            $eq = $conn->query("SELECT * FROM quotations WHERE id=$edit_quote_id");
            $edit_quote = $eq ? $eq->fetch_assoc() : null;
        }
        $customers_list_q = $conn->query('SELECT id, name, phone, cnic, is_filer, address FROM customers ORDER BY name');
        $bikes_list_q = $conn->query("SELECT b.id, b.chassis_number, m.model_name, b.color, b.purchase_price, m.category FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.status='in_stock' ORDER BY b.chassis_number");
        $accessories_list_q = $conn->query('SELECT id, name, selling_price, current_stock FROM accessories WHERE current_stock > 0 ORDER BY name');
?>
<div style="display:flex;gap:8px;margin-bottom:10px" class="no-print animate__animated animate__fadeInLeft">
<?php if (has_permission($conn, 'quotations', 'add')): ?>
<button class="btn btn-success" onclick="document.getElementById('addQuoteFormArea').style.display='block';document.getElementById('addQuoteFormArea').scrollIntoView()">+ Create Quotation</button>
<?php endif; ?>
</div>
<div id="addQuoteFormArea" style="display:<?= $edit_quote ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_quote ? '✏ Edit Quotation' : '+ Create New Quotation' ?></legend>
<form id="quotationForm" method="POST" action="index.php?page=quotations&action=<?= $edit_quote ? 'edit' : 'add' ?>">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<?php if ($edit_quote): ?><input type="hidden" name="id" value="<?= $edit_quote['id'] ?>"><?php endif; ?>
<input type="hidden" name="save_quote" value="1">
<div class="form-row">
<div class="form-group"><label>Quote Date <span class="req">*</span></label><input type="date" name="quote_date" value="<?= $edit_quote['quote_date'] ?? date('Y-m-d') ?>" required></div>
<div class="form-group"><label>Valid Until <span class="req">*</span></label><input type="date" name="valid_until" value="<?= $edit_quote['valid_until'] ?? date('Y-m-d', strtotime('+7 days')) ?>" required></div>
</div>
<div class="form-row">
<div class="form-group" style="min-width: 350px; flex: 2;">
    <label>Customer <span class="req">*</span></label>
    <select name="customer_id" id="quoteCustomerSel" required class="select2-enable" onchange="showQuoteCustomerDetails(this)">
        <option value="">-- Select Customer --</option>
        <?php $customers_list_q->data_seek(0);
        while ($cust = $customers_list_q->fetch_assoc()): ?>
            <option value="<?= $cust['id'] ?>" data-phone="<?= sanitize($cust['phone']) ?>" data-cnic="<?= sanitize($cust['cnic']) ?>" data-filer="<?= $cust['is_filer'] ? 'Filer' : 'Non-Filer' ?>" data-address="<?= sanitize($cust['address']) ?>" <?= (($edit_quote['customer_id'] ?? '') == $cust['id']) ? 'selected' : '' ?>><?= sanitize($cust['name']) ?> (<?= sanitize($cust['phone']) ?>)</option>
        <?php endwhile; ?>
    </select>
    <div id="quoteCustomerDetails" style="margin-top:8px;font-size:0.8rem;color:var(--text);display:none;background:var(--bg2);padding:10px;border-radius:2px;border:1px solid var(--border);line-height:1.4"></div>
</div>
<div class="form-group" style="flex:1">
    <label>Bike <span class="req">*</span></label>
    <select name="bike_id" id="quoteBikeSel" required class="select2-enable" onchange="showQuoteBikeDetails(this)">
        <option value="">-- Select Bike --</option>
        <?php $bikes_list_q->data_seek(0);
        while ($bike = $bikes_list_q->fetch_assoc()): ?>
            <option value="<?= $bike['id'] ?>" data-model="<?= sanitize($bike['model_name']) ?>" data-color="<?= sanitize($bike['color']) ?>" data-pp="<?= fmt_money($bike['purchase_price']) ?>" data-cat="<?= sanitize($bike['category']) ?>" <?= (($edit_quote['bike_id'] ?? '') == $bike['id']) ? 'selected' : '' ?>><?= sanitize($bike['chassis_number']) ?> (<?= sanitize($bike['model_name']) ?>)</option>
        <?php endwhile; ?>
    </select>
    <div id="quoteBikeDetails" style="margin-top:8px;font-size:0.8rem;color:var(--text);display:none;background:var(--bg2);padding:10px;border-radius:2px;border:1px solid var(--border);line-height:1.4"></div>
</div>
</div>
<div class="form-row">
    <div class="form-group"><label>Quoted Price (<?= $currency ?>) <span class="req">*</span></label><input type="number" id="quotePrice" name="quoted_price" step="0.01" min="0" value="<?= $edit_quote['quoted_price'] ?? '0.00' ?>" required oninput="calcQuoteInstallment()"></div>
</div>
<div class="form-row">
    <div class="form-group" style="flex:0 0 100%;"><label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:bold;color:var(--accent)"><input type="checkbox" name="is_installment" id="quoteIsInstallment" value="1" <?= ($edit_quote['is_installment'] ?? 0) ? 'checked' : '' ?> onchange="toggleQuoteInstallments()" style="width:16px;height:16px"> Calculate Installment Plan?</label></div>
</div>
<div class="form-row animate__animated animate__fadeIn" id="quoteInstallmentFields" style="display:<?= ($edit_quote['is_installment'] ?? 0) ? 'flex' : 'none' ?>; background:var(--bg3); padding:12px; border-radius:2px; border:1px dashed var(--accent); margin-bottom:10px;">
    <div class="form-group"><label>Down Payment (<?= $currency ?>)</label><input type="number" name="down_payment" id="quoteDownPayment" step="0.01" min="0" value="<?= $edit_quote['down_payment'] ?? '0.00' ?>" oninput="calcQuoteInstallment()"></div>
    <div class="form-group"><label>Total Installments (Months)</label><input type="number" name="total_installments" id="quoteTotalInst" min="1" value="<?= $edit_quote['total_installments'] ?? '0' ?>" oninput="calcQuoteInstallment()"></div>
    <div class="form-group"><label>Monthly Installment</label><input type="number" name="installment_amount" id="quoteInstAmount" step="0.01" min="0" value="<?= $edit_quote['installment_amount'] ?? '0.00' ?>" readonly style="background:var(--bg);color:var(--text2)"></div>
</div>
<fieldset class="fieldset" style="margin-top:10px;"><legend>Accessories Included</legend>
    <div id="quoteAccessoriesList">
        <?php
        $q_acc_data = $edit_quote ? json_decode($edit_quote['accessories_json'], true) : [];
        if (!empty($q_acc_data)) {
            $q_acc_count = 0;
            foreach ($q_acc_data as $q_acc_idx => $q_acc_item) {
                $q_acc_count++;
                $selected_acc_id = $q_acc_item['id'] ?? 0;
                $qty = $q_acc_item['quantity'] ?? 1;
                $unit_p = $q_acc_item['unit_price'] ?? 0;
                $disc = $q_acc_item['discount'] ?? 0;
                $final_p = $q_acc_item['final_price'] ?? 0;
                ?>
            <div class="bike-row animate__animated animate__fadeInDown" id="quoteAccessoryRow_<?= $q_acc_count ?>">
                <div class="bike-row-header">
                    <span class="bike-row-num">🛠️ Accessory #<?= $q_acc_count ?></span>
                    <button type="button" class="bike-row-del" onclick="removeQuoteAccessoryRow(<?= $q_acc_count ?>)">✕ Remove</button>
                </div>
                <div class="form-row">
                    <div class="form-group" style="flex:2"><label>Accessory <span class="req">*</span></label>
                        <select name="accessories[<?= $q_acc_count ?>][id]" required class="select2-enable" onchange="updateQuoteAccessoryDetails(this, <?= $q_acc_count ?>)">
                            <option value="">-- Select Accessory --</option>
                            <?php $accessories_list_q->data_seek(0);
                            while ($acc_opt = $accessories_list_q->fetch_assoc()): ?>
                                <option value="<?= $acc_opt['id'] ?>" data-price="<?= $acc_opt['selling_price'] ?>" data-stock="<?= $acc_opt['current_stock'] ?>" <?= ($selected_acc_id == $acc_opt['id']) ? 'selected' : '' ?>>
                                    <?= sanitize($acc_opt['name']) ?> (Stock: <?= $acc_opt['current_stock'] ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <span id="quoteAccStock_<?= $q_acc_count ?>" style="font-size:0.75rem;color:var(--text3)"></span>
                    </div>
                    <div class="form-group"><label>Quantity <span class="req">*</span></label><input type="number" name="accessories[<?= $q_acc_count ?>][quantity]" value="<?= $qty ?>" min="1" required oninput="calculateQuoteAccessoryPrice(<?= $q_acc_count ?>)"></div>
                    <div class="form-group"><label>Unit Price</label><input type="number" name="accessories[<?= $q_acc_count ?>][unit_price]" step="0.01" min="0" value="<?= $unit_p ?>" oninput="calculateQuoteAccessoryPrice(<?= $q_acc_count ?>)"></div>
                    <div class="form-group"><label>Discount</label><input type="number" name="accessories[<?= $q_acc_count ?>][discount]" value="<?= $disc ?>" step="0.01" min="0" oninput="calculateQuoteAccessoryPrice(<?= $q_acc_count ?>)"></div>
                    <div class="form-group"><label>Final Price</label><input type="number" name="accessories[<?= $q_acc_count ?>][final_price]" step="0.01" min="0" value="<?= $final_p ?>" readonly style="background:var(--bg3);color:var(--text2)"></div>
                </div>
            </div>
        <?php
            }
        }
        ?>
    </div>
    <button type="button" class="btn btn-default btn-sm" onclick="addQuoteAccessoryRow()" style="margin-top:6px">+ Add Accessory</button>
</fieldset>
<div class="form-group"><label>Notes</label><textarea name="notes" rows="2"><?= sanitize($edit_quote['notes'] ?? '') ?></textarea></div>
<button type="submit" class="btn btn-primary">💾 Save Quotation</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('addQuoteFormArea').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Quote #</th><th>Date</th><th>Valid Until</th><th>Customer</th><th>Bike Chassis</th><th>Quoted Price</th><th>Status</th><th>Created By</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php $sr = 1;
        while ($quote = $quotes_r->fetch_assoc()): ?>
<tr>
<td><?= $sr++ ?></td>
<td>QT-<?= $quote['id'] ?></td>
<td><?= fmt_date($quote['quote_date']) ?></td>
<td><?= fmt_date($quote['valid_until']) ?></td>
<td><?= sanitize($quote['customer_name'] ?? '-') ?></td>
<td><?= sanitize($quote['chassis_number'] ?? '-') ?></td>
<td><?= fmt_money($quote['quoted_price']) ?></td>
<td><span class="badge badge-<?= ($quote['status'] == 'converted') ? 'success' : (($quote['status'] == 'rejected') ? 'danger' : 'info') ?>"><?= strtoupper($quote['status']) ?></span></td>
<td><?= sanitize($quote['created_by_user'] ?? '-') ?></td>
<td class="no-print">
<div class="actions-col">
<a href="index.php?page=quotations&print_quote=<?= $quote['id'] ?>" class="btn btn-primary btn-sm" title="Print Quotation" target="_blank">📄</a>    
<?php if ($quote['status'] == 'pending' && has_permission($conn, 'quotations', 'edit')): ?>
<form method="POST" action="index.php?page=quotations&action=convert_quote_to_sale" style="display:inline">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="quote_id" value="<?= $quote['id'] ?>">
<input type="hidden" name="convert_quote_to_sale" value="1">
<button type="submit" class="btn btn-success btn-sm" title="Convert to Sale" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Convert to Sale?', text: 'This will create a new sale entry and mark this quotation as converted. Are you sure?', icon: 'info', showCancelButton: true, confirmButtonText: 'Yes, convert it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🛒</button>
</form>
<a href="index.php?page=quotations&edit_id=<?= $quote['id'] ?>" class="btn btn-primary btn-sm" title="Edit">✏</a>
<?php endif; ?>
<?php if (has_permission($conn, 'quotations', 'delete')): ?>
<form method="POST" action="index.php?page=quotations&action=delete" style="display:inline">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="id" value="<?= $quote['id'] ?>">
<input type="hidden" name="delete_quote" value="1">
<button type="submit" class="btn btn-danger btn-sm" title="Delete" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete this quotation?', text: 'Are you sure you want to delete this quotation?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php
        $print_quote_id = (int) ($_GET['print_quote'] ?? 0);
        if ($print_quote_id):
            echo '<style>.sidebar, .topbar { display: none !important; } .main-wrap { margin-left: 0 !important; } .content > *:not(#receiptArea):not(.no-print) { display: none !important; } .content { padding: 40px !important; background: #333 !important; } body { background: #333 !important; } @media print { .content, body { padding: 0 !important; background: #fff !important; } }</style>';
            $q_r = $conn->query("SELECT q.*, b.chassis_number, b.color, m.model_name, m.model_code, m.category, c.name as cust_name, c.phone as cust_phone, c.cnic as cust_cnic, c.address as cust_addr FROM quotations q LEFT JOIN bikes b ON q.bike_id=b.id LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON q.customer_id=c.id WHERE q.id=$print_quote_id");
            $q_data = $q_r ? $q_r->fetch_assoc() : null;
            if ($q_data):
                $q_no = 'QT-' . date('Ymd') . '-' . str_pad($print_quote_id, 3, '0', STR_PAD_LEFT);
                $q_accs = json_decode($q_data['accessories_json'], true) ?: [];
                $acc_total = 0;
                ?>
<div class="a4-invoice" id="receiptArea">
    <div class="invoice-header">
        <h1>⚡ <?= sanitize(get_setting('company_name') ?? 'BNI Enterprises') ?></h1>
        <h2><?= sanitize(get_setting('branch_name') ?? 'Dera (Ahmed Metro)') ?></h2>
        <?php
        $raw_wa = get_setting('company_whatsapp') ?? '';
        $wa_numbers = array_filter(array_map('trim', explode(',', $raw_wa)));
        if (!empty($wa_numbers)):
            ?>
        <div style="font-size:0.85rem;margin-top:2px;font-weight:normal;">WhatsApp: <?= sanitize(implode(', ', $wa_numbers)) ?></div>
        <?php endif; ?>
        <div style="font-size:1.1rem;margin-top:8px;font-weight:700;letter-spacing:2px;color:#333;">OFFICIAL QUOTATION</div>
    </div>
    <div class="invoice-meta">
        <div>
            <strong>Quotation #:</strong> <?= $q_no ?><br>
            <strong>Date Generated:</strong> <?= fmt_date($q_data['quote_date']) ?><br>
            <strong>Valid Until:</strong> <span style="color:#c0392b;font-weight:bold;"><?= fmt_date($q_data['valid_until']) ?></span>
        </div>
        <div style="text-align:right;">
            <strong>Customer:</strong> <?= sanitize($q_data['cust_name'] ?? 'Walk-in') ?><br>
            <?php if ($q_data['cust_phone']): ?><strong>Phone:</strong> <?= sanitize($q_data['cust_phone']) ?><br><?php endif; ?>
            <?php if ($q_data['cust_addr']): ?><strong>Address:</strong> <?= sanitize($q_data['cust_addr']) ?><br><?php endif; ?>
            <div style="margin-top:10px; padding:5px; border:1px solid #ddd; background:#f9f9f9; display:inline-block; border-radius:3px;">
                <strong>Status:</strong> <span style="font-weight:bold;color:<?= $q_data['status'] == 'converted' ? '#27ae60' : ($q_data['status'] == 'rejected' ? '#c0392b' : '#f39c12') ?>;text-transform:uppercase;"><?= $q_data['status'] ?></span>
            </div>
        </div>
    </div>
    <div class="invoice-section">
        <h3>Proposed Items</h3>
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Category</th>
                    <th style="text-align:right">Quoted Price</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong><?= sanitize($q_data['model_name']) ?> (<?= sanitize($q_data['model_code']) ?>)</strong><br>
                        <small>Chassis: <span style="font-family:Consolas"><?= sanitize($q_data['chassis_number']) ?></span> | Color: <?= sanitize($q_data['color']) ?></small>
                    </td>
                    <td><?= sanitize($q_data['category']) ?></td>
                    <td style="text-align:right"><?= fmt_money($q_data['quoted_price']) ?></td>
                </tr>
                <?php if (!empty($q_accs)): ?>
                    <?php
                    foreach ($q_accs as $acc):
                        $acc_name_r = $conn->query('SELECT name FROM accessories WHERE id=' . (int) $acc['id']);
                        $acc_name = $acc_name_r && $acc_name_r->num_rows ? $acc_name_r->fetch_row()[0] : 'Custom Accessory';
                        $acc_total += $acc['final_price'];
                        ?>
                    <tr>
                        <td><small>+ <?= sanitize($acc_name) ?> (Qty: <?= $acc['quantity'] ?>)</small></td>
                        <td><small>Accessory</small></td>
                        <td style="text-align:right"><small><?= fmt_money($acc['final_price']) ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="invoice-section">
        <h3>Financial Summary</h3>
        <table class="invoice-table" style="width:50%; margin-left:auto;">
            <tbody>
                <tr><td>Bike Price</td><td style="text-align:right"><?= fmt_money($q_data['quoted_price']) ?></td></tr>
                <?php if ($acc_total > 0): ?><tr><td>Accessories Total</td><td style="text-align:right">+ <?= fmt_money($acc_total) ?></td></tr><?php endif; ?>
                <tr style="background:#eef2f5;font-weight:bold;font-size:1.1rem;border-top:2px solid #ccc;">
                    <td>Grand Total</td><td style="text-align:right;color:#1a6fc4;"><?= fmt_money($q_data['quoted_price'] + $acc_total) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php if ($q_data['is_installment']): ?>
    <div class="invoice-section">
        <h3>Proposed Installment Plan</h3>
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Grand Total</th>
                    <th>Down Payment</th>
                    <th>Remaining Balance</th>
                    <th>Total Months</th>
                    <th>Monthly Installment</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= fmt_money($q_data['quoted_price'] + $acc_total) ?></td>
                    <td><?= fmt_money($q_data['down_payment']) ?></td>
                    <td><?= fmt_money(($q_data['quoted_price'] + $acc_total) - $q_data['down_payment']) ?></td>
                    <td><?= $q_data['total_installments'] ?> Months</td>
                    <td style="font-weight:bold;color:#c0392b;"><?= fmt_money($q_data['installment_amount']) ?></td>
                </tr>
            </tbody>
        </table>
        <p style="font-size:0.8rem;color:#555;margin-top:5px;">* Installment plan is subject to final verification and approval of documents at the time of sale.</p>
    </div>
    <?php endif; ?>
    <?php if ($q_data['notes']): ?>
    <div class="invoice-section">
        <h3>Terms & Additional Notes</h3>
        <p style="font-size:0.9rem;border:1px solid #ddd;padding:10px;background:#f9f9f9;"><?= nl2br(sanitize($q_data['notes'])) ?></p>
    </div>
    <?php endif; ?>
    <div style="margin-top:60px; display:flex; justify-content:space-between; border-top:1px solid #ddd; padding-top:50px; font-weight:bold; color:#444;">
        <div style="text-align:center; width:250px; border-top:2px solid #333; padding-top:5px;">Authorized Signature</div>
        <div style="text-align:center; width:250px; border-top:2px solid #333; padding-top:5px;">Customer Signature (Acceptance)</div>
    </div>
    <div class="invoice-footer">
        This is a system generated quotation. Valid until <?= fmt_date($q_data['valid_until']) ?>.<br>
        Generated by: Yasin Ullah – BSS | WhatsApp: 03361593533
    </div>
</div>
<div class="no-print" style="margin-top:10px">
    <button onclick="window.print()" class="btn btn-success">🖨 Print Quotation</button>
    <a href="index.php?page=quotations" class="btn btn-default">Back to List</a>
</div>
<?php endif;
        endif; ?>
<script>
var quoteAccessoriesCount = <?= $q_acc_count ?? 0 ?>;
var allAvailableAccessories = <?= json_encode($conn->query('SELECT id, name, selling_price, current_stock FROM accessories WHERE current_stock > 0 ORDER BY name')->fetch_all(MYSQLI_ASSOC)) ?>;
function addQuoteAccessoryRow() {
    quoteAccessoriesCount++;
    var d = document.createElement('div');
    d.className = 'bike-row animate__animated animate__fadeInDown';
    d.id = 'quoteAccessoryRow_' + quoteAccessoriesCount;
    var optionsHtml = '<option value="">-- Select Accessory --</option>';
    allAvailableAccessories.forEach(function(acc) {
        optionsHtml += `<option value="${acc.id}" data-price="${acc.selling_price}" data-stock="${acc.current_stock}">${acc.name} (Stock: ${acc.current_stock})</option>`;
    });
    d.innerHTML = `<div class="bike-row-header"><span class="bike-row-num">🛠️ Accessory #${quoteAccessoriesCount}</span><button type="button" class="bike-row-del" onclick="removeQuoteAccessoryRow(${quoteAccessoriesCount})">✕ Remove</button></div>
    <div class="form-row">
        <div class="form-group" style="flex:2"><label>Accessory <span class="req">*</span></label>
            <select name="accessories[${quoteAccessoriesCount}][id]" required class="select2-enable" onchange="updateQuoteAccessoryDetails(this, ${quoteAccessoriesCount})">
                ${optionsHtml}
            </select>
            <span id="quoteAccStock_${quoteAccessoriesCount}" style="font-size:0.75rem;color:var(--text3)"></span>
        </div>
        <div class="form-group"><label>Quantity <span class="req">*</span></label><input type="number" name="accessories[${quoteAccessoriesCount}][quantity]" value="1" min="1" required oninput="calculateQuoteAccessoryPrice(${quoteAccessoriesCount})"></div>
        <div class="form-group"><label>Unit Price</label><input type="number" name="accessories[${quoteAccessoriesCount}][unit_price]" step="0.01" min="0" oninput="calculateQuoteAccessoryPrice(${quoteAccessoriesCount})"></div>
        <div class="form-group"><label>Discount</label><input type="number" name="accessories[${quoteAccessoriesCount}][discount]" value="0.00" step="0.01" min="0" oninput="calculateQuoteAccessoryPrice(${quoteAccessoriesCount})"></div>
        <div class="form-group"><label>Final Price</label><input type="number" name="accessories[${quoteAccessoriesCount}][final_price]" step="0.01" min="0" readonly style="background:var(--bg3);color:var(--text2)"></div>
    </div>`;
    document.getElementById('quoteAccessoriesList').appendChild(d);
    $(d).find('.select2-enable').select2({
        minimumResultsForSearch: 0,
        placeholder: '-- Select Accessory --',
        allowClear: false,
        tags: true,
        theme: 'default'
    });
}
function removeQuoteAccessoryRow(n) {
    document.getElementById('quoteAccessoryRow_' + n).remove();
    if(typeof calcQuoteInstallment === 'function') calcQuoteInstallment();
}
function updateQuoteAccessoryDetails(selectElement, index) {
    var selectedOption = selectElement.options[selectElement.selectedIndex];
    var price = selectedOption && selectedOption.dataset ? (selectedOption.dataset.price || 0) : 0;
    var stock = selectedOption && selectedOption.dataset ? (selectedOption.dataset.stock || 0) : 0;
    document.querySelector(`#quoteAccessoryRow_${index} input[name="accessories[${index}][unit_price]"]`).value = price;
    document.querySelector(`#quoteAccStock_${index}`).innerText = `Available: ${stock}`;
    calculateQuoteAccessoryPrice(index);
}
function calculateQuoteAccessoryPrice(index) {
    var quantity = parseInt(document.querySelector(`#quoteAccessoryRow_${index} input[name="accessories[${index}][quantity]"]`).value) || 0;
    var unitPrice = parseFloat(document.querySelector(`#quoteAccessoryRow_${index} input[name="accessories[${index}][unit_price]"]`).value) || 0;
    var discount = parseFloat(document.querySelector(`#quoteAccessoryRow_${index} input[name="accessories[${index}][discount]"]`).value) || 0;
    var finalPrice = (quantity * unitPrice) - discount;
    document.querySelector(`#quoteAccessoryRow_${index} input[name="accessories[${index}][final_price]"]`).value = finalPrice.toFixed(2);
    calcQuoteInstallment();
}
function toggleQuoteInstallments() {
    document.getElementById('quoteInstallmentFields').style.display = document.getElementById('quoteIsInstallment').checked ? 'flex' : 'none';
    calcQuoteInstallment();
}
function calcQuoteInstallment() {
    var quotedPrice = parseFloat(document.getElementById('quotePrice').value) || 0;
    var accTotal = 0;
    document.querySelectorAll('#quoteAccessoriesList input[name$="[final_price]"]').forEach(function(inp) {
        accTotal += parseFloat(inp.value) || 0;
    });
    var total = quotedPrice + accTotal;
    var down = parseFloat(document.getElementById('quoteDownPayment').value) || 0;
    var months = parseInt(document.getElementById('quoteTotalInst').value) || 0;
    var instAmount = months > 0 ? (total - down) / months : 0;
    document.getElementById('quoteInstAmount').value = instAmount > 0 ? instAmount.toFixed(2) : '0.00';
}
$(document).ready(function() {
    $('#quotationForm select.select2-enable').select2({
        minimumResultsForSearch: 10,
        placeholder: '-- Select --',
        allowClear: false,
        theme: 'default'
    });
    $('#quoteAccessoriesList .select2-enable').select2({
        minimumResultsForSearch: 0,
        placeholder: '-- Select Accessory --',
        allowClear: false,
        tags: true,
        theme: 'default'
    });
    $('#quoteCustomerSel').on('change', function() { showQuoteCustomerDetails(this); });
    $('#quoteBikeSel').on('change', function() { showQuoteBikeDetails(this); });
    if ($('#quoteCustomerSel').val()) showQuoteCustomerDetails(document.getElementById('quoteCustomerSel'));
    if ($('#quoteBikeSel').val()) showQuoteBikeDetails(document.getElementById('quoteBikeSel'));
});
function showQuoteCustomerDetails(sel) {
    var opt = sel.options[sel.selectedIndex];
    var detailsDiv = document.getElementById('quoteCustomerDetails');
    if (sel.value && opt) {
        var phone = opt.getAttribute('data-phone') || '-';
        var cnic = opt.getAttribute('data-cnic') || '-';
        var filer = opt.getAttribute('data-filer') || '-';
        var addr = opt.getAttribute('data-address') || '-';
        var filerBadge = filer === 'Filer' ? 'success' : 'danger';
        detailsDiv.innerHTML = '<strong>Phone:</strong> ' + phone + '<br><strong>CNIC:</strong> ' + cnic + '<br><strong>Status:</strong> <span class="badge badge-' + filerBadge + '">' + filer + '</span><br><strong>Address:</strong> ' + addr;
        detailsDiv.style.display = 'block';
    } else {
        detailsDiv.style.display = 'none';
        detailsDiv.innerHTML = '';
    }
}
function showQuoteBikeDetails(sel) {
    var opt = sel.options[sel.selectedIndex];
    var detailsDiv = document.getElementById('quoteBikeDetails');
    if (sel.value && opt) {
        var model = opt.getAttribute('data-model') || '-';
        var color = opt.getAttribute('data-color') || '-';
        var cat = opt.getAttribute('data-cat') || '-';
        var pp = opt.getAttribute('data-pp') || '-';
        detailsDiv.innerHTML = '<strong>Model:</strong> ' + model + '<br><strong>Category:</strong> ' + cat + '<br><strong>Color:</strong> ' + color + '<br><strong>Purchase Price:</strong> ' + pp;
        detailsDiv.style.display = 'block';
    } else {
        detailsDiv.style.display = 'none';
        detailsDiv.innerHTML = '';
    }
}
</script>
<?php
    elseif ($page === 'customers'):
        $cust_result = $conn->query("SELECT c.*, COUNT(b.id) as bike_count, SUM(CASE WHEN b.status='sold' THEN b.selling_price + COALESCE((SELECT SUM(sa.final_price) FROM sale_accessories sa WHERE sa.bike_id=b.id),0) ELSE 0 END) as total_purchases FROM customers c LEFT JOIN bikes b ON c.id=b.customer_id GROUP BY c.id ORDER BY c.name");
        $edit_cust_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_cust = null;
        if ($edit_cust_id) {
            $ec = $conn->query("SELECT * FROM customers WHERE id=$edit_cust_id");
            $edit_cust = $ec ? $ec->fetch_assoc() : null;
        }
        $search_cust = sanitize($_GET['search_cust'] ?? '');
        $where_cust = '1=1';
        if ($search_cust)
            $where_cust = "(c.name LIKE '%" . mysqli_real_escape_string($conn, $search_cust) . "%' OR c.phone LIKE '%" . mysqli_real_escape_string($conn, $search_cust) . "%' OR c.cnic LIKE '%" . mysqli_real_escape_string($conn, $search_cust) . "%')";
        $cust_result = $conn->query("SELECT c.*, COUNT(b.id) as bike_count, SUM(CASE WHEN b.status='sold' THEN b.selling_price + COALESCE((SELECT SUM(sa.final_price) FROM sale_accessories sa WHERE sa.bike_id=b.id),0) ELSE 0 END) as total_purchases FROM customers c LEFT JOIN bikes b ON c.id=b.customer_id WHERE $where_cust GROUP BY c.id ORDER BY c.name");
?>
<div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap;align-items:center" class="no-print animate__animated animate__fadeInLeft">
<form method="GET" action="index.php" style="display:flex;gap:6px;align-items:center">
<input type="hidden" name="page" value="customers">
<input type="text" name="search_cust" value="<?= $search_cust ?>" placeholder="Search by name, phone, CNIC..." style="padding:6px 10px;border:1px solid var(--input-border);background:var(--input-bg);color:var(--input-text);border-radius:1px">
<button type="submit" class="btn btn-default btn-sm">🔍</button>
</form>
<?php if (has_permission($conn, 'customers', 'add')): ?>
<button class="btn btn-success" onclick="document.getElementById('addCustFormArea').style.display='block';document.getElementById('addCustFormArea').scrollIntoView()">+ Add Customer</button>
<?php endif; ?>
</div>
<div id="addCustFormArea" style="display:<?= $edit_cust ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_cust ? '✏ Edit Customer' : '+ Add New Customer' ?></legend>
<form id="customerForm" method="POST" action="index.php?page=customers&action=<?= $edit_cust ? 'edit' : 'add' ?>">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<?php if ($edit_cust): ?><input type="hidden" name="id" value="<?= $edit_cust['id'] ?>"><?php endif; ?>
<div class="form-row">
<div class="form-group"><label>Name <span class="req">*</span></label><input type="text" name="name" value="<?= sanitize($edit_cust['name'] ?? '') ?>" required></div>
<div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= sanitize($edit_cust['phone'] ?? '') ?>"></div>
<div class="form-group"><label>CNIC</label><input type="text" name="cnic" value="<?= sanitize($edit_cust['cnic'] ?? '') ?>" placeholder="XXXXX-XXXXXXX-X"></div>
</div>
<div class="form-row">
<div class="form-group"><label style="display:flex;align-items:center;gap:6px;"><input type="checkbox" name="is_filer" value="1" <?= ($edit_cust['is_filer'] ?? 1) ? 'checked' : '' ?>> Is Filer?</label></div>
</div>
<div class="form-row">
<div class="form-group"><label>Address</label><textarea name="address" rows="2"><?= sanitize($edit_cust['address'] ?? '') ?></textarea></div>
</div>
<button type="submit" class="btn btn-primary">💾 Save</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('addCustFormArea').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Name</th><th>Phone</th><th>CNIC</th><th>Filer Status</th><th>Address</th><th>Bikes Purchased</th><th>Total Amount</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php $sr = 1;
        while ($cu = $cust_result->fetch_assoc()): ?>
<tr>
<td><?= $sr++ ?></td>
<td><strong><?= sanitize($cu['name']) ?></strong></td>
<td><?= sanitize($cu['phone'] ?? '-') ?></td>
<td style="font-family:Consolas,monospace"><?= sanitize($cu['cnic'] ?? '-') ?></td>
<td><span class="badge badge-<?= $cu['is_filer'] ? 'success' : 'danger' ?>"><?= $cu['is_filer'] ? 'FILER' : 'NON-FILER' ?></span></td>
<td><?= sanitize($cu['address'] ?? '-') ?></td>
<td><?= $cu['bike_count'] ?></td>
<td><?= fmt_money($cu['total_purchases'] ?? 0) ?></td>
<td class="no-print">
<div class="actions-col">
<?php if (has_permission($conn, 'customer_ledger', 'view')): ?><a href="index.php?page=customer_ledger&cust_id=<?= $cu['id'] ?>" class="btn btn-default btn-sm" title="Ledger">📒</a><?php endif; ?>
<?php if (has_permission($conn, 'customers', 'edit')): ?><a href="index.php?page=customers&edit_id=<?= $cu['id'] ?>" class="btn btn-primary btn-sm">✏</a><?php endif; ?>
<?php if (has_permission($conn, 'customers', 'delete')): ?>
<form method="POST" action="index.php?page=customers&action=delete" style="display:inline">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="id" value="<?= $cu['id'] ?>">
<button type="submit" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete customer?', text: 'Are you sure you want to delete this customer? Only possible if no bikes are linked.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php
    elseif ($page === 'suppliers'):
        $sup_result = $conn->query('SELECT s.*, COUNT(po.id) as order_count, SUM(po.total_amount) as total_purchase_value FROM suppliers s LEFT JOIN purchase_orders po ON s.id=po.supplier_id GROUP BY s.id ORDER BY s.name');
        $edit_sup_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_sup = null;
        if ($edit_sup_id) {
            $es = $conn->query("SELECT * FROM suppliers WHERE id=$edit_sup_id");
            $edit_sup = $es ? $es->fetch_assoc() : null;
        }
?>
<div style="display:flex;gap:8px;margin-bottom:10px" class="no-print animate__animated animate__fadeInLeft">
<?php if (has_permission($conn, 'suppliers', 'add')): ?>
<button class="btn btn-success" onclick="document.getElementById('addSupFormArea').style.display='block';document.getElementById('addSupFormArea').scrollIntoView()">+ Add Supplier</button>
<?php endif; ?>
</div>
<div id="addSupFormArea" style="display:<?= $edit_sup ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_sup ? '✏ Edit Supplier' : '+ Add New Supplier' ?></legend>
<form id="supplierForm" method="POST" action="index.php?page=suppliers&action=<?= $edit_sup ? 'edit' : 'add' ?>">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<?php if ($edit_sup): ?><input type="hidden" name="id" value="<?= $edit_sup['id'] ?>"><?php endif; ?>
<div class="form-row">
<div class="form-group"><label>Name <span class="req">*</span></label><input type="text" name="name" value="<?= sanitize($edit_sup['name'] ?? '') ?>" required></div>
<div class="form-group"><label>Contact</label><input type="text" name="contact" value="<?= sanitize($edit_sup['contact'] ?? '') ?>"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Address</label><textarea name="address" rows="2"><?= sanitize($edit_sup['address'] ?? '') ?></textarea></div>
</div>
<button type="submit" class="btn btn-primary">💾 Save</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('addSupFormArea').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Name</th><th>Contact</th><th>Address</th><th>Orders</th><th>Total Purchase Value</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php $sr = 1;
        while ($sv = $sup_result->fetch_assoc()): ?>
<tr>
<td><?= $sr++ ?></td>
<td><strong><?= sanitize($sv['name']) ?></strong></td>
<td><?= sanitize($sv['contact'] ?? '-') ?></td>
<td><?= sanitize($sv['address'] ?? '-') ?></td>
<td><?= $sv['order_count'] ?></td>
<td><?= fmt_money($sv['total_purchase_value'] ?? 0) ?></td>
<td class="no-print">
<div class="actions-col">
<?php if (has_permission($conn, 'supplier_ledger', 'view')): ?><a href="index.php?page=supplier_ledger&sup_id=<?= $sv['id'] ?>" class="btn btn-default btn-sm" title="Ledger">📒</a><?php endif; ?>
<?php if (has_permission($conn, 'suppliers', 'edit')): ?><a href="index.php?page=suppliers&edit_id=<?= $sv['id'] ?>" class="btn btn-primary btn-sm">✏</a><?php endif; ?>
<?php if (has_permission($conn, 'suppliers', 'delete')): ?>
<form method="POST" action="index.php?page=suppliers&action=delete" style="display:inline">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="id" value="<?= $sv['id'] ?>">
<button type="submit" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete supplier?', text: 'Are you sure you want to delete this supplier? Only possible if no purchase orders are linked.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php
    elseif ($page === 'roles'):
        require_permission($conn, 'roles', 'view');
        $roles = $conn->query('SELECT * FROM roles ORDER BY id');
        $edit_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_role = null;
        $perms = [];
        if ($edit_id) {
            $er = $conn->query("SELECT * FROM roles WHERE id=$edit_id");
            $edit_role = $er->fetch_assoc();
            $pr = $conn->query("SELECT * FROM role_permissions WHERE role_id=$edit_id");
            while ($p = $pr->fetch_assoc())
                $perms[$p['page']] = $p;
        }
        $all_pages = [
            'dashboard' => 'Dashboard', 'inventory' => 'Inventory', 'purchase' => 'Purchase Orders', 'sale' => 'Sales', 'customers' => 'Customers', 'suppliers' => 'Suppliers', 'models' => 'Models', 'reports' => 'Reports', 'returns' => 'Returns', 'payments' => 'Payments Register', 'settings' => 'Settings', 'roles' => 'Roles', 'users' => 'Users', 'income_expense' => 'Income/Expense', 'accessories' => 'Accessories', 'quotations' => 'Quotations', 'installments' => 'Installments',
            'customer_ledger' => 'Customer Ledger', 'supplier_ledger' => 'Supplier Ledger', 'money_destinations' => 'Money Destinations',
            'money_tracking' => 'Money Tracking', 'bank_deposits' => 'Bank Deposits', 'landing_page' => 'Landing Page'
        ];
?>
<div style="display:flex;gap:8px;margin-bottom:10px" class="no-print animate__animated animate__fadeInLeft">
    <?php if (has_permission($conn, 'roles', 'add')): ?>
    <button class="btn btn-success" onclick="document.getElementById('roleForm').style.display='block';document.getElementById('roleForm').scrollIntoView()">+ Add Role</button>
    <?php endif; ?>
</div>
<div id="roleForm" style="display:<?= $edit_role ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_role ? '✏ Edit Role' : ' + Add New Role' ?></legend>
<form id="editRoleForm" method="POST">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="id" value="<?= $edit_role['id'] ?? 0 ?>">
<input type="hidden" name="save_role" value="1">
<div class="form-row">
<div class="form-group"><label>Role Name *</label><input type="text" name="name" value="<?= sanitize($edit_role['name'] ?? '') ?>" required></div>
<div class="form-group"><label>Description</label><input type="text" name="description" value="<?= sanitize($edit_role['description'] ?? '') ?>"></div>
</div>
<h4 style="margin:12px 0 6px">Permissions (check to allow)</h4>
<div style="overflow:auto;max-height:400px;border:1px solid var(--border);padding:8px">
<table class="data-table">
<thead><tr><th>Page</th><th>View</th><th>Add</th><th>Edit</th><th>Delete</th></tr></thead>
<tbody>
<?php foreach ($all_pages as $k => $label):
            $p = $perms[$k] ?? []; ?>
<tr>
<td><strong><?= $label ?></strong></td>
<td style="text-align:center"><input type="checkbox" name="perm[<?= $k ?>][view]" <?= ($p['can_view'] ?? 0) ? 'checked' : '' ?>></td>
<td style="text-align:center"><input type="checkbox" name="perm[<?= $k ?>][add]" <?= ($p['can_add'] ?? 0) ? 'checked' : '' ?>></td>
<td style="text-align:center"><input type="checkbox" name="perm[<?= $k ?>][edit]" <?= ($p['can_edit'] ?? 0) ? 'checked' : '' ?>></td>
<td style="text-align:center"><input type="checkbox" name="perm[<?= $k ?>][delete]" <?= ($p['can_delete'] ?? 0) ? 'checked' : '' ?>></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<button type="submit" class="btn btn-primary" style="margin-top:10px">💾 Save Role</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('roleForm').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>ID</th><th>Role</th><th>Description</th><th>Users</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php while ($r = $roles->fetch_assoc()):
            $uc = $conn->query('SELECT COUNT(*) c FROM users WHERE role_id=' . $r['id'])->fetch_assoc()['c']; ?>
<tr>
<td><?= $r['id'] ?></td>
<td><strong><?= sanitize($r['name']) ?></strong></td>
<td><?= sanitize($r['description']) ?></td>
<td><?= $uc ?></td>
<td class="no-print">
<?php if (has_permission($conn, 'roles', 'edit')): ?>
<a href="index.php?page=roles&edit_id=<?= $r['id'] ?>" class="btn btn-primary btn-sm">✏</a>
<?php endif; ?>
<?php if ($r['id'] != 1 && has_permission($conn, 'roles', 'delete')): ?>
<form method="POST" style="display:inline">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="id" value="<?= $r['id'] ?>">
<button name="delete_role" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete role?', text: 'Are you sure you want to delete this role? Only possible if no users are linked.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php
    elseif ($page === 'users'):
        require_permission($conn, 'users', 'view');
        $users = $conn->query('SELECT u.*, r.name role_name FROM users u LEFT JOIN roles r ON u.role_id=r.id ORDER BY u.id');
        $roles = $conn->query('SELECT * FROM roles ORDER BY name');
        $edit_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_user = null;
        if ($edit_id) {
            $eu = $conn->query("SELECT * FROM users WHERE id=$edit_id");
            $edit_user = $eu->fetch_assoc();
        }
?>
<div style="display:flex;gap:8px;margin-bottom:10px" class="no-print animate__animated animate__fadeInLeft">
    <?php if (has_permission($conn, 'users', 'add')): ?>
    <button class="btn btn-success" onclick="document.getElementById('userForm').style.display='block';document.getElementById('userForm').scrollIntoView()">+ Add User</button>
    <?php endif; ?>
</div>
<div id="userForm" style="display:<?= $edit_user ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_user ? '✏ Edit User' : ' + Add New User' ?></legend>
<form id="editUserForm" method="POST">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="id" value="<?= $edit_user['id'] ?? 0 ?>">
<input type="hidden" name="save_user" value="1">
<div class="form-row">
<div class="form-group"><label>Username *</label><input type="text" name="username" value="<?= sanitize($edit_user['username'] ?? '') ?>" required pattern="[a-zA-Z0-9_]{3,}" title="3+ chars, letters/numbers/_"></div>
<div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?= sanitize($edit_user['full_name'] ?? '') ?>"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Role *</label>
<select name="role_id" required>
<?php $roles2 = $conn->query('SELECT * FROM roles ORDER BY name');
        while ($rl = $roles2->fetch_assoc()): ?>
<option value="<?= $rl['id'] ?>" <?= ($edit_user['role_id'] ?? 2) == $rl['id'] ? 'selected' : '' ?>><?= sanitize($rl['name']) ?></option>
<?php endwhile; ?>
</select></div>
<div class="form-group"><label>Password <?= $edit_user ? '(leave blank to keep)' : '(min 8 chars, incl. special, number, letter)' ?></label>
<input type="password" name="password" <?= $edit_user ? '' : 'required' ?> minlength="8" placeholder="Strong password"></div>
<div class="form-group"><label style="display:flex;align-items:center;gap:6px;margin-top:24px"><input type="checkbox" name="is_active" <?= ($edit_user['is_active'] ?? 1) ? 'checked' : '' ?>> Active</label></div>
</div>
<div style="font-size:0.78rem;color:var(--text2)">Strong password: min 8 chars, must include at least one uppercase letter, one lowercase letter, one number, and one special character. Leave empty to keep the current password unchanged.</div>
<button type="submit" class="btn btn-primary" style="margin-top:10px">💾 Save User</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('userForm').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Status</th><th>Created</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php while ($u = $users->fetch_assoc()): ?>
<tr>
<td><?= $u['id'] ?></td>
<td><strong><?= sanitize($u['username']) ?></strong></td>
<td><?= sanitize($u['full_name']) ?></td>
<td><?= sanitize($u['role_name']) ?></td>
<td><?= $u['is_active'] ? '<span style="color:var(--success)">Active</span>' : '<span style="color:var(--danger)">Disabled</span>' ?></td>
<td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
<td class="no-print">
<?php if (has_permission($conn, 'users', 'edit')): ?>
<a href="index.php?page=users&edit_id=<?= $u['id'] ?>" class="btn btn-primary btn-sm">✏</a>
<?php endif; ?>
<?php if ($u['id'] != 1 && $u['id'] != $_SESSION['user_id'] && has_permission($conn, 'users', 'delete')): ?>
<form method="POST" style="display:inline">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="id" value="<?= $u['id'] ?>">
<button name="delete_user" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete user?', text: 'Are you sure you want to delete this user?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php
    elseif ($page === 'income_expense'):
        require_permission($conn, 'income_expense', 'view');
        $filter_type = in_array($_GET['type'] ?? '', ['income', 'expense'], true) ? $_GET['type'] : '';
        $filter_from = valid_date($_GET['from'] ?? '', true) ? ($_GET['from'] ?? '') : '';
        $filter_to = valid_date($_GET['to'] ?? '', true) ? ($_GET['to'] ?? '') : '';
        $filter_cat = clean_text($_GET['category'] ?? '', 255);
        $where = 'WHERE 1=1';
        if ($filter_from && $filter_to)
            $where .= " AND entry_date BETWEEN '$filter_from' AND '$filter_to'";
        elseif ($filter_from)
            $where .= " AND entry_date >= '$filter_from'";
        elseif ($filter_to)
            $where .= " AND entry_date <= '$filter_to'";
        if ($filter_type)
            $where .= " AND type='$filter_type'";
        if ($filter_cat)
            $where .= " AND category='" . mysqli_real_escape_string($conn, $filter_cat) . "'";
        $entries = $conn->query("SELECT ie.*, u.full_name FROM income_expenses ie LEFT JOIN users u ON ie.created_by=u.id $where ORDER BY entry_date DESC, id DESC");
        $cats = $conn->query('SELECT DISTINCT category FROM income_expenses ORDER BY category');
        $totals = $conn->query("SELECT type, COUNT(*) as cnt, SUM(amount) total FROM income_expenses $where GROUP BY type");
        $sum_income = 0;
        $sum_expense = 0;
        $cnt_income = 0;
        $cnt_expense = 0;
        while ($t = $totals->fetch_assoc()) {
            if ($t['type'] == 'income') {
                $sum_income = $t['total'];
                $cnt_income = $t['cnt'];
            } else {
                $sum_expense = $t['total'];
                $cnt_expense = $t['cnt'];
            }
        }
        $cat_stats = $conn->query("SELECT type, category, SUM(amount) as total FROM income_expenses $where GROUP BY type, category ORDER BY total DESC");
        $inc_cats = [];
        $exp_cats = [];
        $top_inc_cat = ['name' => 'None', 'amount' => 0];
        $top_exp_cat = ['name' => 'None', 'amount' => 0];
        while ($cs = $cat_stats->fetch_assoc()) {
            if ($cs['type'] == 'income') {
                $inc_cats[] = $cs;
                if ($cs['total'] > $top_inc_cat['amount'])
                    $top_inc_cat = ['name' => $cs['category'], 'amount' => $cs['total']];
            } else {
                $exp_cats[] = $cs;
                if ($cs['total'] > $top_exp_cat['amount'])
                    $top_exp_cat = ['name' => $cs['category'], 'amount' => $cs['total']];
            }
        }
        $trend_stats = $conn->query("SELECT entry_date, type, SUM(amount) as total FROM income_expenses $where GROUP BY entry_date, type ORDER BY entry_date ASC");
        $trend_labels = [];
        $trend_inc = [];
        $trend_exp = [];
        $trend_map = [];
        while ($ts = $trend_stats->fetch_assoc()) {
            $date = $ts['entry_date'];
            if (!isset($trend_map[$date]))
                $trend_map[$date] = ['inc' => 0, 'exp' => 0];
            if ($ts['type'] == 'income')
                $trend_map[$date]['inc'] = $ts['total'];
            else
                $trend_map[$date]['exp'] = $ts['total'];
        }
        foreach ($trend_map as $d => $v) {
            $trend_labels[] = date('d M', strtotime($d));
            $trend_inc[] = $v['inc'];
            $trend_exp[] = $v['exp'];
        }
        $edit_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_entry = null;
        if ($edit_id) {
            $ee = $conn->query("SELECT * FROM income_expenses WHERE id=$edit_id");
            $edit_entry = $ee->fetch_assoc();
        }
?>
<div class="no-print animate__animated animate__fadeInLeft" style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap;align-items:end">
    <form method="GET" style="display:flex;gap:6px;align-items:end;flex-wrap:wrap">
    <input type="hidden" name="page" value="income_expense">
    <div class="form-group"><label>From</label><input type="date" name="from" value="<?= $filter_from ?>"></div>
    <div class="form-group"><label>To</label><input type="date" name="to" value="<?= $filter_to ?>"></div>
    <div class="form-group"><label>Type</label>
    <select name="type"><option value="">All</option><option value="income" <?= $filter_type == 'income' ? 'selected' : '' ?>>Income</option><option value="expense" <?= $filter_type == 'expense' ? 'selected' : '' ?>>Expense</option></select></div>
    <div class="form-group"><label>Category</label>
    <select name="category"><option value="">All</option><?php $cats2 = $conn->query('SELECT DISTINCT category FROM income_expenses ORDER BY category');
        while ($c = $cats2->fetch_assoc()): ?><option value="<?= sanitize($c['category']) ?>" <?= $filter_cat == $c['category'] ? 'selected' : '' ?>><?= sanitize($c['category']) ?></option><?php endwhile; ?></select></div>
    <button class="btn btn-default">Filter</button>
    <a href="index.php?page=income_expense" class="btn btn-default">Reset</a>
    </form>
    <?php if (has_permission($conn, 'income_expense', 'add')): ?>
    <button class="btn btn-success" onclick="document.getElementById('ieForm').style.display='block';document.getElementById('ieForm').scrollIntoView()">+ Add Entry</button>
    <?php endif; ?>
</div>
<div class="card-grid animate__animated animate__fadeInDown" style="margin-bottom:12px">
    <div class="card success"><div class="card-icon">💰</div><div class="card-body"><div class="card-label">Total Income</div><div class="card-value" style="font-size:1.1rem"><?= fmt_money($sum_income) ?></div><div class="card-sub"><?= $cnt_income ?> transactions</div></div></div>
    <div class="card danger"><div class="card-icon">💸</div><div class="card-body"><div class="card-label">Total Expense</div><div class="card-value" style="font-size:1.1rem"><?= fmt_money($sum_expense) ?></div><div class="card-sub"><?= $cnt_expense ?> transactions</div></div></div>
    <div class="card"><div class="card-icon">⚖️</div><div class="card-body"><div class="card-label">Net Balance</div><div class="card-value" style="font-size:1.1rem;color:<?= ($sum_income - $sum_expense) >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= fmt_money($sum_income - $sum_expense) ?></div></div></div>
    <div class="card"><div class="card-icon">📊</div><div class="card-body"><div class="card-label">Avg Transaction</div><div class="card-value" style="font-size:1.1rem"><?= fmt_money((($cnt_income + $cnt_expense) > 0) ? ($sum_income + $sum_expense) / ($cnt_income + $cnt_expense) : 0) ?></div></div></div>
    <div class="card success"><div class="card-icon">📈</div><div class="card-body"><div class="card-label">Top Income Cat.</div><div class="card-value" style="font-size:1.1rem"><?= sanitize($top_inc_cat['name']) ?></div><div class="card-sub"><?= fmt_money($top_inc_cat['amount']) ?></div></div></div>
    <div class="card danger"><div class="card-icon">📉</div><div class="card-body"><div class="card-label">Top Expense Cat.</div><div class="card-value" style="font-size:1.1rem"><?= sanitize($top_exp_cat['name']) ?></div><div class="card-sub"><?= fmt_money($top_exp_cat['amount']) ?></div></div></div>
</div>
<div class="split-grid-3 animate__animated animate__fadeInUp" style="margin-bottom:16px;">
    <fieldset class="fieldset" style="margin-bottom:0"><legend>💰 Income by Category</legend><div style="position:relative;height:200px;width:100%"><canvas id="incCatChart"></canvas></div></fieldset>
    <fieldset class="fieldset" style="margin-bottom:0"><legend>💸 Expense by Category</legend><div style="position:relative;height:200px;width:100%"><canvas id="expCatChart"></canvas></div></fieldset>
    <fieldset class="fieldset" style="margin-bottom:0"><legend>📈 Daily Trend</legend><div style="position:relative;height:200px;width:100%"><canvas id="ieTrendChart"></canvas></div></fieldset>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if(typeof Chart !== 'undefined') {
        const commonPieOpts = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: 'var(--text)' } } } };
        <?php if (!empty($inc_cats)): ?>
        new Chart(document.getElementById('incCatChart'), {
            type: 'doughnut',
            data: { labels: <?= json_encode(array_column($inc_cats, 'category')) ?>, datasets: [{ data: <?= json_encode(array_column($inc_cats, 'total')) ?>, backgroundColor: ['#4ec94e','#4a9eff','#e0a800','#9b59b6','#16a085'] }] },
            options: commonPieOpts
        });
        <?php endif; ?>
        <?php if (!empty($exp_cats)): ?>
        new Chart(document.getElementById('expCatChart'), {
            type: 'doughnut',
            data: { labels: <?= json_encode(array_column($exp_cats, 'category')) ?>, datasets: [{ data: <?= json_encode(array_column($exp_cats, 'total')) ?>, backgroundColor: ['#e74c3c','#e67e22','#d35400','#c0392b','#bdc3c7'] }] },
            options: commonPieOpts
        });
        <?php endif; ?>
        <?php if (!empty($trend_labels)): ?>
        new Chart(document.getElementById('ieTrendChart'), {
            type: 'line',
            data: { 
                labels: <?= json_encode($trend_labels) ?>, 
                datasets: [
                    { label: 'Income', data: <?= json_encode($trend_inc) ?>, borderColor: '#4ec94e', tension: 0.3 },
                    { label: 'Expense', data: <?= json_encode($trend_exp) ?>, borderColor: '#e74c3c', tension: 0.3 }
                ] 
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { color: 'var(--border)' } }, y: { grid: { color: 'var(--border)' } } } }
        });
        <?php endif; ?>
    }
});
</script>
<div id="ieForm" style="display:<?= $edit_entry ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_entry ? '✏ Edit' : ' + Add' ?> Income/Expense</legend>
<form id="ieEntryForm" method="POST">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="id" value="<?= $edit_entry['id'] ?? 0 ?>">
<input type="hidden" name="save_entry" value="1">
<div class="form-row">
<div class="form-group"><label>Date *</label><input type="date" name="entry_date" value="<?= $edit_entry['entry_date'] ?? date('Y-m-d') ?>" required></div>
<div class="form-group"><label>Type *</label><select name="type" required><option value="income" <?= ($edit_entry['type'] ?? '') == 'income' ? 'selected' : '' ?>>Income</option><option value="expense" <?= ($edit_entry['type'] ?? 'expense') == 'expense' ? 'selected' : '' ?>>Expense</option></select></div>
<div class="form-group"><label>Category *</label><input type="text" name="category" list="catlist" value="<?= sanitize($edit_entry['category'] ?? '') ?>" required placeholder="e.g. Fuel, Salary, Sales"><datalist id="catlist"><?php while ($c = $cats->fetch_assoc()): ?><option value="<?= sanitize($c['category']) ?>"><?php endwhile; ?></datalist></div>
<div class="form-group"><label>Amount *</label><input type="number" step="0.01" name="amount" value="<?= $edit_entry['amount'] ?? '' ?>" required></div>
</div>
<div class="form-row">
<div class="form-group"><label>Payment Method</label><select name="payment_method">
    <option value="cash" <?= ($edit_entry['payment_method'] ?? '') == 'cash' ? 'selected' : '' ?>>cash</option>
    <option value="bank_transfer" <?= ($edit_entry['payment_method'] ?? '') == 'bank_transfer' ? 'selected' : '' ?>>bank_transfer</option>
    <option value="cheque" <?= ($edit_entry['payment_method'] ?? '') == 'cheque' ? 'selected' : '' ?>>cheque</option>
    <option value="online" <?= ($edit_entry['payment_method'] ?? '') == 'online' ? 'selected' : '' ?>>online</option>
    <option value="other" <?= ($edit_entry['payment_method'] ?? '') == 'other' ? 'selected' : '' ?>>other</option>
</select></div>
<div class="form-group"><label>Reference</label><input type="text" name="reference" value="<?= sanitize($edit_entry['reference'] ?? '') ?>"></div>
</div>
<div class="form-row"><div class="form-group"><label>Notes</label><textarea name="notes" rows="2"><?= sanitize($edit_entry['notes'] ?? '') ?></textarea></div></div>
<button type="submit" class="btn btn-primary">💾 Save</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('ieForm').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>Date</th><th>Type</th><th>Category</th><th>Amount</th><th>Method</th><th>Reference</th><th>By</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php while ($e = $entries->fetch_assoc()): ?>
<tr>
<td><?= date('d/m/Y', strtotime($e['entry_date'])) ?></td>
<td><?= $e['type'] == 'income' ? '<span style="color:var(--success)">Income</span>' : '<span style="color:var(--danger)">Expense</span>' ?></td>
<td><?= sanitize($e['category']) ?></td>
<td><?= fmt_money($e['amount']) ?></td>
<td><?= $e['payment_method'] ?></td>
<td><?= sanitize($e['reference']) ?></td>
<td><?= sanitize($e['full_name'] ?? '-') ?></td>
<td class="no-print">
<?php if (has_permission($conn, 'income_expense', 'edit')): ?><a href="index.php?page=income_expense&edit_id=<?= $e['id'] ?>&from=<?= $filter_from ?>&to=<?= $filter_to ?>" class="btn btn-primary btn-sm">✏</a><?php endif; ?>
<?php if (has_permission($conn, 'income_expense', 'delete')): ?><form method="POST" style="display:inline"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="id" value="<?= $e['id'] ?>"><button name="delete_entry" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete this entry?', text: 'Are you sure you want to delete this income/expense entry?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button></form><?php endif; ?>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php
    elseif ($page === 'landing_page'):
        require_permission($conn, 'landing_page', 'view');
        $sub = sanitize($_GET['sub'] ?? 'general');
?>
<div class="sub-tabs no-print animate__animated animate__fadeInDown">
    <a href="index.php?page=landing_page&sub=general" class="sub-tab <?= $sub === 'general' ? 'active' : '' ?>">⚙️ General</a>
    <a href="index.php?page=landing_page&sub=leadership" class="sub-tab <?= $sub === 'leadership' ? 'active' : '' ?>">👨‍💼 Leadership</a>
    <a href="index.php?page=landing_page&sub=gallery" class="sub-tab <?= $sub === 'gallery' ? 'active' : '' ?>">🖼️ Gallery</a>
    <a href="index.php?page=landing_page&sub=requests" class="sub-tab <?= $sub === 'requests' ? 'active' : '' ?>">📩 Requests</a>
</div>
<?php if ($sub === 'general'): ?>
<form method="POST" class="animate__animated animate__fadeIn">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="save_landing_settings" value="1">
<input type="hidden" name="sub" value="general">
<fieldset class="fieldset"><legend>🌐 Hero Section</legend>
<div class="form-row">
<div class="form-group"><label>Hero Title</label><input type="text" name="landing_hero_title" value="<?= sanitize(get_setting('landing_hero_title') ?? '') ?>"></div>
<div class="form-group"><label>Hero Subtitle</label><input type="text" name="landing_hero_subtitle" value="<?= sanitize(get_setting('landing_hero_subtitle') ?? '') ?>"></div>
</div>
</fieldset>
<fieldset class="fieldset"><legend>🏢 About Us (Mission/Vision)</legend>
<div class="form-row">
<div class="form-group"><label>Vision Statement</label><textarea name="vision_statement" rows="2"><?= sanitize(get_setting('vision_statement') ?? '') ?></textarea></div>
<div class="form-group"><label>Mission Statement</label><textarea name="mission_statement" rows="2"><?= sanitize(get_setting('mission_statement') ?? '') ?></textarea></div>
</div>
</fieldset>
<fieldset class="fieldset"><legend>📍 Contact & Socials</legend>
<div class="form-row">
<div class="form-group"><label>Company Address</label><input type="text" name="company_address" value="<?= sanitize(get_setting('company_address') ?? '') ?>"></div>
<div class="form-group"><label>Google Map Iframe (src only)</label><input type="text" name="company_map_iframe" value="<?= sanitize(get_setting('company_map_iframe') ?? '') ?>"></div>
</div>
<div class="form-row">
<div class="form-group"><label>WhatsApp Numbers (comma separated)</label><input type="text" name="company_whatsapp" value="<?= sanitize(get_setting('company_whatsapp') ?? '') ?>" placeholder="923000000000, 923111111111"></div>
<div class="form-group"><label>Company Email</label><input type="email" name="company_email" value="<?= sanitize(get_setting('company_email') ?? '') ?>"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Facebook URL</label><input type="text" name="social_facebook" value="<?= sanitize(get_setting('social_facebook') ?? '') ?>"></div>
<div class="form-group"><label>Instagram URL</label><input type="text" name="social_instagram" value="<?= sanitize(get_setting('social_instagram') ?? '') ?>"></div>
<div class="form-group"><label>Twitter URL</label><input type="text" name="social_twitter" value="<?= sanitize(get_setting('social_twitter') ?? '') ?>"></div>
</div>
</fieldset>
<button type="submit" class="btn btn-primary">💾 Save Landing Settings</button>
</form>
<?php
        elseif ($sub === 'leadership'):
            $leadership = $conn->query('SELECT * FROM leadership ORDER BY sort_order ASC, id DESC');
            $edit_lid = (int) ($_GET['edit_lid'] ?? 0);
            $edit_l = null;
            if ($edit_lid) {
                $edit_l = $conn->query("SELECT * FROM leadership WHERE id=$edit_lid")->fetch_assoc();
            }
?>
<div id="leadershipFormArea" style="display:<?= $edit_l ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_l ? '✏ Edit Leadership' : '+ Add Leader' ?></legend>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="save_leadership" value="1">
<input type="hidden" name="sub" value="leadership">
<?php if ($edit_l): ?><input type="hidden" name="id" value="<?= $edit_l['id'] ?>"><?php endif; ?>
<div class="form-row">
<div class="form-group"><label>Name *</label><input type="text" name="name" value="<?= sanitize($edit_l['name'] ?? '') ?>" required></div>
<div class="form-group"><label>Position</label><input type="text" name="position" value="<?= sanitize($edit_l['position'] ?? '') ?>"></div>
<div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" value="<?= $edit_l['sort_order'] ?? '0' ?>"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Message</label><textarea name="message" rows="3"><?= sanitize($edit_l['message'] ?? '') ?></textarea></div>
<div class="form-group"><label>Image (Optional)</label><input type="file" name="image" accept="image/*"></div>
</div>
<button type="submit" class="btn btn-primary">💾 Save</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('leadershipFormArea').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div style="margin-bottom:10px" class="no-print"><button class="btn btn-success" onclick="document.getElementById('leadershipFormArea').style.display='block'">+ Add Leader</button></div>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sort</th><th>Image</th><th>Name</th><th>Position</th><th>Message</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php while ($l = $leadership->fetch_assoc()): ?>
<tr>
<td><?= $l['sort_order'] ?></td>
<td><?php if ($l['image']): ?><img src="<?= $l['image'] ?>" style="height:40px;width:40px;object-fit:cover;border-radius:50%"><?php else: ?>-<?php endif; ?></td>
<td><strong><?= sanitize($l['name']) ?></strong></td>
<td><?= sanitize($l['position']) ?></td>
<td><small><?= sanitize(substr($l['message'], 0, 50)) ?>...</small></td>
<td>
<div class="actions-col">
<a href="index.php?page=landing_page&sub=leadership&edit_lid=<?= $l['id'] ?>" class="btn btn-primary btn-sm">✏</a>
<form method="POST" style="display:inline"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="id" value="<?= $l['id'] ?>"><input type="hidden" name="sub" value="leadership"><button name="delete_leadership" class="btn btn-danger btn-sm" onclick="return confirm('Delete this leader?')">🗑</button></form>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php
        elseif ($sub === 'gallery'):
            $gallery = $conn->query('SELECT * FROM gallery ORDER BY sort_order ASC, id DESC');
            $edit_gid = (int) ($_GET['edit_gid'] ?? 0);
            $edit_g = null;
            if ($edit_gid) {
                $edit_g = $conn->query("SELECT * FROM gallery WHERE id=$edit_gid")->fetch_assoc();
            }
?>
<div id="galleryFormArea" style="display:<?= $edit_g ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_g ? '✏ Edit Gallery Item' : '+ Add Gallery Item' ?></legend>
<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="save_gallery" value="1">
<input type="hidden" name="sub" value="gallery">
<?php if ($edit_g): ?><input type="hidden" name="id" value="<?= $edit_g['id'] ?>"><?php endif; ?>
<div class="form-row">
<div class="form-group"><label>Title</label><input type="text" name="title" value="<?= sanitize($edit_g['title'] ?? '') ?>"></div>
<div class="form-group"><label>Sort Order</label><input type="number" name="sort_order" value="<?= $edit_g['sort_order'] ?? '0' ?>"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Description</label><textarea name="description" rows="2"><?= sanitize($edit_g['description'] ?? '') ?></textarea></div>
<div class="form-group"><label>Image *</label><input type="file" name="image" accept="image/*" <?= $edit_g ? '' : 'required' ?>></div>
</div>
<button type="submit" class="btn btn-primary">💾 Save</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('galleryFormArea').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div style="margin-bottom:10px" class="no-print"><button class="btn btn-success" onclick="document.getElementById('galleryFormArea').style.display='block'">+ Add Item</button></div>
<div class="card-grid">
<?php while ($g = $gallery->fetch_assoc()): ?>
<div class="card" style="flex-direction:column;align-items:flex-start;gap:8px;padding:10px">
<img src="<?= $g['image'] ?>" style="width:100%;height:120px;object-fit:cover;border-radius:2px">
<div style="font-weight:700;font-size:0.85rem"><?= sanitize($g['title'] ?: 'Untitled') ?></div>
<div style="font-size:0.75rem;color:var(--text2)"><?= sanitize(substr($g['description'], 0, 40)) ?>...</div>
<div style="display:flex;gap:5px;margin-top:auto">
<a href="index.php?page=landing_page&sub=gallery&edit_gid=<?= $g['id'] ?>" class="btn btn-default btn-sm">✏</a>
<form method="POST" style="display:inline"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="id" value="<?= $g['id'] ?>"><input type="hidden" name="sub" value="gallery"><button name="delete_gallery" class="btn btn-danger btn-sm" onclick="return confirm('Delete item?')">🗑</button></form>
</div>
</div>
<?php endwhile; ?>
</div>
<?php
        elseif ($sub === 'requests'):
            $bike_reqs = $conn->query('SELECT * FROM bike_requests ORDER BY created_at DESC');
            $quote_reqs = $conn->query('SELECT qr.*, b.chassis_number, m.model_name FROM quote_requests qr LEFT JOIN bikes b ON qr.bike_id=b.id LEFT JOIN models m ON b.model_id=m.id ORDER BY qr.created_at DESC');
?>
<fieldset class="fieldset"><legend>🚲 Bike Requests (Not in stock)</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Date</th><th>Customer</th><th>Phone</th><th>Bike Details</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php while ($r = $bike_reqs->fetch_assoc()): ?>
<tr>
<td><?= fmt_date($r['created_at']) ?></td>
<td><strong><?= defang_spam($r['customer_name']) ?></strong></td>
<td><?= defang_spam($r['customer_phone']) ?></td>
<td><small><?= defang_spam($r['bike_details']) ?></small></td>
<td><span class="badge badge-<?= ($r['status'] === 'fulfilled') ? 'success' : (($r['status'] === 'cancelled') ? 'danger' : 'warning') ?>"><?= strtoupper($r['status']) ?></span></td>
<td>
<form method="POST" style="display:inline"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="update_request_status" value="1"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="type" value="bike"><input type="hidden" name="sub" value="requests"><select name="status" onchange="this.form.submit()"><option value="pending" <?= $r['status'] === 'pending' ? 'selected' : '' ?>>Pending</option><option value="contacted" <?= $r['status'] === 'contacted' ? 'selected' : '' ?>>Contacted</option><option value="fulfilled" <?= $r['status'] === 'fulfilled' ? 'selected' : '' ?>>Fulfilled</option><option value="cancelled" <?= $r['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option></select></form>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</fieldset>
<fieldset class="fieldset" style="margin-top:20px"><legend>📝 Quote Requests (From Landing Page)</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Date</th><th>Customer</th><th>Phone</th><th>Bike Interested</th><th>Details</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php while ($r = $quote_reqs->fetch_assoc()): ?>
<tr>
<td><?= fmt_date($r['created_at']) ?></td>
<td><strong><?= defang_spam($r['customer_name']) ?></strong></td>
<td><?= defang_spam($r['customer_phone']) ?></td>
<td><?= $r['model_name'] ? defang_spam($r['model_name'] . ' - ' . $r['chassis_number']) : 'General' ?></td>
<td><small><?= defang_spam($r['details']) ?></small></td>
<td><span class="badge badge-<?= ($r['status'] === 'accepted') ? 'success' : (($r['status'] === 'rejected') ? 'danger' : 'warning') ?>"><?= strtoupper($r['status']) ?></span></td>
<td>
<form method="POST" style="display:inline"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="update_request_status" value="1"><input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="type" value="quote"><input type="hidden" name="sub" value="requests"><select name="status" onchange="this.form.submit()"><option value="pending" <?= $r['status'] === 'pending' ? 'selected' : '' ?>>Pending</option><option value="sent" <?= $r['status'] === 'sent' ? 'selected' : '' ?>>Sent</option><option value="accepted" <?= $r['status'] === 'accepted' ? 'selected' : '' ?>>Accepted</option><option value="rejected" <?= $r['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option></select></form>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</fieldset>
<?php endif; ?>
<?php
    elseif ($page === 'money_destinations'):
        $dest_result = $conn->query('SELECT * FROM money_destinations ORDER BY type, name');
        $edit_dest_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_dest = null;
        if ($edit_dest_id) {
            $ed = $conn->query("SELECT * FROM money_destinations WHERE id=$edit_dest_id");
            $edit_dest = $ed ? $ed->fetch_assoc() : null;
        }
        $dest_stats = $conn->query('SELECT type, COUNT(*) as cnt FROM money_destinations GROUP BY type');
        $type_counts = ['bank' => 0, 'person' => 0, 'wallet' => 0];
        while ($ds = $dest_stats->fetch_assoc()) {
            $type_counts[$ds['type']] = $ds['cnt'];
        }
        $total_bank_deposited = $conn->query('SELECT COALESCE(SUM(amount),0) FROM bank_deposits')->fetch_row()[0];
?>
<div class="card-grid animate__animated animate__fadeInDown">
    <div class="card accent"><div class="card-icon">🏦</div><div class="card-body"><div class="card-label">Banks</div><div class="card-value"><?= $type_counts['bank'] ?></div><div class="card-sub">Deposited: <?= fmt_money($total_bank_deposited) ?></div></div></div>
    <div class="card success"><div class="card-icon">👤</div><div class="card-body"><div class="card-label">Persons</div><div class="card-value"><?= $type_counts['person'] ?></div></div></div>
    <div class="card warning"><div class="card-icon">💳</div><div class="card-body"><div class="card-label">Wallets</div><div class="card-value"><?= $type_counts['wallet'] ?></div></div></div>
</div>
<div style="display:flex;gap:8px;margin-bottom:10px" class="no-print animate__animated animate__fadeInLeft">
<?php if (has_permission($conn, 'money_destinations', 'add')): ?>
<button class="btn btn-success" onclick="document.getElementById('addDestFormArea').style.display='block';document.getElementById('addDestFormArea').scrollIntoView()">+ Add Destination</button>
<?php endif; ?>
</div>
<div id="addDestFormArea" style="display:<?= $edit_dest ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_dest ? '✏ Edit Destination' : '+ Add New Destination' ?></legend>
<form method="POST" action="index.php?page=money_destinations">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<?php if ($edit_dest): ?><input type="hidden" name="id" value="<?= $edit_dest['id'] ?>"><?php endif; ?>
<div class="form-row">
<div class="form-group"><label>Type <span class="req">*</span></label>
<select name="type" required>
<option value="bank" <?= ($edit_dest['type'] ?? '') === 'bank' ? 'selected' : '' ?>>🏦 Bank</option>
<option value="person" <?= ($edit_dest['type'] ?? '') === 'person' ? 'selected' : '' ?>>👤 Person</option>
<option value="wallet" <?= ($edit_dest['type'] ?? '') === 'wallet' ? 'selected' : '' ?>>💳 Wallet</option>
</select>
</div>
<div class="form-group"><label>Name <span class="req">*</span></label><input type="text" name="name" value="<?= sanitize($edit_dest['name'] ?? '') ?>" required placeholder="e.g. HBL Main Branch"></div>
<div class="form-group"><label>Details</label><input type="text" name="details" value="<?= sanitize($edit_dest['details'] ?? '') ?>" placeholder="Account #, phone, etc."></div>
</div>
<div class="form-row" id="bankFieldsRow">
<div class="form-group"><label>Account Title</label><input type="text" name="account_title" value="<?= sanitize($edit_dest['account_title'] ?? '') ?>" placeholder="Account holder name"></div>
<div class="form-group"><label>Account No</label><input type="text" name="account_no" value="<?= sanitize($edit_dest['account_no'] ?? '') ?>" placeholder="0000-0000-0000-0000"></div>
<div class="form-group"><label>Branch</label><input type="text" name="branch" value="<?= sanitize($edit_dest['branch'] ?? '') ?>" placeholder="Branch name"></div>
</div>
<div class="form-row" id="bankFieldsRow2">
<div class="form-group"><label>Opening Balance (<?= $currency ?>)</label><input type="number" name="opening_balance" step="0.01" min="0" value="<?= $edit_dest['opening_balance'] ?? 0 ?>"></div>
<div class="form-group"><label>Contact Person</label><input type="text" name="contact_person" value="<?= sanitize($edit_dest['contact_person'] ?? '') ?>" placeholder="Bank manager/contact"></div>
<div class="form-group"><label>Contact Phone</label><input type="text" name="contact_phone" value="<?= sanitize($edit_dest['contact_phone'] ?? '') ?>" placeholder="0300-0000000"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Active</label><label style="display:flex;align-items:center;gap:6px;cursor:pointer"><input type="checkbox" name="is_active" <?= ($edit_dest['is_active'] ?? 1) ? 'checked' : '' ?>> Active</label></div>
</div>
<button type="submit" name="save_destination" class="btn btn-primary">💾 Save</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('addDestFormArea').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<script>
function toggleBankFields(sel) {
    var show = sel.value === 'bank';
    document.getElementById('bankFieldsRow').style.display = show ? '' : 'none';
    document.getElementById('bankFieldsRow2').style.display = show ? '' : 'none';
}
(function() {
    var sel = document.querySelector('select[name="type"]');
    if (sel) { toggleBankFields(sel); sel.addEventListener('change', function(){toggleBankFields(this);}); }
})();
</script>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Type</th><th>Name</th><th>Account Details</th><th>Contact</th><th>Balance</th><th>Status</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php
        $sr = 1;
        while ($dest = $dest_result->fetch_assoc()):
            $alloc_total = $conn->query('SELECT COALESCE(SUM(amount),0) FROM sale_money_allocations WHERE destination_id=' . $dest['id'])->fetch_row()[0];
            $deposited_total = $conn->query('SELECT COALESCE(SUM(amount),0) FROM bank_deposits WHERE destination_id=' . $dest['id'])->fetch_row()[0];
            $current_bal = $dest['opening_balance'] + $deposited_total;
            $type_icon = ['bank' => '🏦', 'person' => '👤', 'wallet' => '💳'][$dest['type']] ?? '📌';
            ?>
<tr>
<td><?= $sr++ ?></td>
<td><span class="badge badge-<?= $dest['type'] === 'bank' ? 'info' : ($dest['type'] === 'person' ? 'success' : 'warning') ?>"><?= $type_icon ?> <?= strtoupper($dest['type']) ?></span></td>
<td><strong><?= sanitize($dest['name']) ?></strong></td>
<td><?= $dest['type'] === 'bank' ? '<small>' . sanitize($dest['account_title'] ?: '-') . '<br><strong>' . sanitize($dest['account_no'] ?: '-') . '</strong><br>' . sanitize($dest['branch'] ?: '-') . '</small>' : sanitize($dest['details'] ?: '-') ?></td>
<td><?= $dest['type'] === 'bank' ? sanitize($dest['contact_person'] ?: '-') . '<br>' . sanitize($dest['contact_phone'] ?: '') : '-' ?></td>
<td><small>Opening: <?= fmt_money($dest['opening_balance']) ?><br>Deposited: <?= fmt_money($deposited_total) ?><br><strong>Current: <?= fmt_money($current_bal) ?></strong></small></td>
<td><span class="badge badge-<?= $dest['is_active'] ? 'success' : 'danger' ?>"><?= $dest['is_active'] ? 'Active' : 'Inactive' ?></span></td>
<td class="no-print">
<div class="actions-col">
<?php if (has_permission($conn, 'money_destinations', 'edit')): ?><a href="index.php?page=money_destinations&edit_id=<?= $dest['id'] ?>" class="btn btn-primary btn-sm">✏</a><?php endif; ?>
<?php if (has_permission($conn, 'money_destinations', 'delete')): ?>
<form method="POST" style="display:inline"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="id" value="<?= $dest['id'] ?>">
<button name="delete_destination" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete this destination?', text: 'Only possible if no allocations are linked.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
<?php
    elseif ($page === 'money_tracking'):
        $sold_bikes = $conn->query("SELECT b.id, b.chassis_number, b.selling_price, b.selling_date, m.model_name, c.name as cust_name,
            COALESCE((SELECT SUM(amount) FROM sale_money_allocations WHERE bike_id=b.id),0) as allocated,
            COALESCE((SELECT SUM(sa2.final_price) FROM sale_accessories sa2 WHERE sa2.bike_id=b.id),0) as acc_total
            FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id
            WHERE b.status='sold'
            HAVING (b.selling_price + COALESCE((SELECT SUM(sa2.final_price) FROM sale_accessories sa2 WHERE sa2.bike_id=b.id),0)) > allocated
            ORDER BY b.selling_date DESC");
        $sold_bikes_arr = [];
        while ($sb = $sold_bikes->fetch_assoc())
            $sold_bikes_arr[] = $sb;
        $active_dests = $conn->query('SELECT id, type, name FROM money_destinations WHERE is_active=1 ORDER BY type, name');
        $active_dests_arr = [];
        while ($ad = $active_dests->fetch_assoc())
            $active_dests_arr[] = $ad;
        $edit_alloc_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_alloc = null;
        if ($edit_alloc_id) {
            $ea = $conn->query("SELECT * FROM sale_money_allocations WHERE id=$edit_alloc_id");
            $edit_alloc = $ea ? $ea->fetch_assoc() : null;
        }
        $filter_bike = (int) ($_GET['filter_bike'] ?? 0);
        $filter_dest = (int) ($_GET['filter_dest'] ?? 0);
        $alloc_where = '1=1';
        if ($filter_bike)
            $alloc_where .= " AND sma.bike_id=$filter_bike";
        if ($filter_dest)
            $alloc_where .= " AND sma.destination_id=$filter_dest";
        $alloc_result = $conn->query("SELECT sma.*, md.name as dest_name, md.type as dest_type, b.chassis_number, b.selling_price, m.model_name,
            COALESCE((SELECT SUM(sa2.final_price) FROM sale_accessories sa2 WHERE sa2.bike_id=b.id),0) as acc_total,
            COALESCE((SELECT SUM(da.amount) FROM deposit_allocations da WHERE da.allocation_id=sma.id),0) as deposited_amount,
            u.full_name as created_by_name
            FROM sale_money_allocations sma
            LEFT JOIN money_destinations md ON sma.destination_id=md.id
            LEFT JOIN bikes b ON sma.bike_id=b.id
            LEFT JOIN models m ON b.model_id=m.id
            LEFT JOIN users u ON sma.created_by=u.id
            WHERE $alloc_where ORDER BY sma.allocation_date DESC, sma.id DESC");
        $total_allocated_all = $conn->query('SELECT COALESCE(SUM(amount),0) FROM sale_money_allocations')->fetch_row()[0];
        $total_sold_value = $conn->query("SELECT COALESCE(SUM(b.selling_price + COALESCE((SELECT SUM(sa2.final_price) FROM sale_accessories sa2 WHERE sa2.bike_id=b.id),0)),0) FROM bikes b WHERE b.status='sold'")->fetch_row()[0];
        $untracked = $total_sold_value - $total_allocated_all;
?>
<div class="card-grid animate__animated animate__fadeInDown">
    <div class="card success"><div class="card-icon">💰</div><div class="card-body"><div class="card-label">Total Sales Value</div><div class="card-value" style="font-size:1rem"><?= fmt_money($total_sold_value) ?></div></div></div>
    <div class="card accent"><div class="card-icon">✅</div><div class="card-body"><div class="card-label">Total Allocated</div><div class="card-value" style="font-size:1rem"><?= fmt_money($total_allocated_all) ?></div></div></div>
    <div class="card <?= $untracked > 0 ? 'warning' : 'success' ?>"><div class="card-icon"><?= $untracked > 0 ? '⚠️' : '✅' ?></div><div class="card-body"><div class="card-label">Untracked</div><div class="card-value" style="font-size:1rem"><?= fmt_money($untracked) ?></div></div></div>
</div>
<div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap" class="no-print animate__animated animate__fadeInLeft">
<?php if (has_permission($conn, 'money_tracking', 'add')): ?>
<button class="btn btn-success" onclick="document.getElementById('addAllocFormArea').style.display='block';document.getElementById('addAllocFormArea').scrollIntoView()">+ Add Allocation</button>
<?php endif; ?>
</div>
<div class="filter-bar no-print animate__animated animate__fadeInLeft" style="margin-bottom:10px">
<form method="GET" action="index.php" style="display:contents">
<input type="hidden" name="page" value="money_tracking">
<div class="form-group"><label>Filter by Sale</label>
<select name="filter_bike"><option value="0">-- All Sales --</option>
<?php foreach ($sold_bikes_arr as $sb): ?>
<option value="<?= $sb['id'] ?>" <?= $filter_bike == $sb['id'] ? 'selected' : '' ?>><?= sanitize($sb['chassis_number']) ?> | <?= sanitize($sb['model_name']) ?></option>
<?php endforeach; ?>
</select></div>
<div class="form-group"><label>Filter by Destination</label>
<select name="filter_dest"><option value="0">-- All Destinations --</option>
<?php foreach ($active_dests_arr as $ad): ?>
<option value="<?= $ad['id'] ?>" <?= $filter_dest == $ad['id'] ? 'selected' : '' ?>><?= sanitize($ad['name']) ?> (<?= $ad['type'] ?>)</option>
<?php endforeach; ?>
</select></div>
<button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-end">🔍 Filter</button>
<a href="index.php?page=money_tracking" class="btn btn-default btn-sm" style="align-self:flex-end">Clear</a>
</form>
</div>
<div id="addAllocFormArea" style="display:<?= $edit_alloc ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_alloc ? '✏ Edit Allocation' : '+ Add New Allocation' ?></legend>
<form method="POST" action="index.php?page=money_tracking">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<?php if ($edit_alloc): ?><input type="hidden" name="id" value="<?= $edit_alloc['id'] ?>"><?php endif; ?>
<div class="form-row">
<div class="form-group" style="flex:2"><label>Sold Bike <span class="req">*</span></label>
<select name="bike_id" id="allocBikeSelect" required onchange="updateAllocRemaining()">
<option value="">-- Select Sold Bike --</option>
<?php
        foreach ($sold_bikes_arr as $sb):
            $sb_total_sale = $sb['selling_price'] + $sb['acc_total'];
            $sb_remaining = $sb_total_sale - $sb['allocated'];
            ?>
<option value="<?= $sb['id'] ?>" data-total="<?= $sb_total_sale ?>" data-allocated="<?= $sb['allocated'] ?>" data-remaining="<?= $sb_remaining ?>" <?= ($edit_alloc && $edit_alloc['bike_id'] == $sb['id']) ? 'selected' : '' ?>>
<?= sanitize($sb['chassis_number']) ?> | <?= sanitize($sb['model_name']) ?> | <?= fmt_money($sb_total_sale) ?> (Remaining: <?= fmt_money($sb_remaining) ?>)
</option>
<?php endforeach; ?>
</select>
</div>
<div class="form-group"><label>Destination <span class="req">*</span></label>
<select name="destination_id" required>
<option value="">-- Select --</option>
<?php
        foreach ($active_dests_arr as $ad):
            $ti = ['bank' => '🏦', 'person' => '👤', 'wallet' => '💳'][$ad['type']] ?? '';
            ?>
<option value="<?= $ad['id'] ?>" <?= ($edit_alloc && $edit_alloc['destination_id'] == $ad['id']) ? 'selected' : '' ?>><?= $ti ?> <?= sanitize($ad['name']) ?> (<?= $ad['type'] ?>)</option>
<?php endforeach; ?>
</select>
</div>
</div>
<div class="form-row">
<div class="form-group"><label>Amount (<?= $currency ?>) <span class="req">*</span></label><input type="number" name="amount" step="0.01" min="0.01" required value="<?= $edit_alloc ? $edit_alloc['amount'] : '' ?>" placeholder="0.00"></div>
<div class="form-group"><label>Date <span class="req">*</span></label><input type="date" name="allocation_date" required value="<?= $edit_alloc ? $edit_alloc['allocation_date'] : date('Y-m-d') ?>"></div>
<div class="form-group"><label>Notes</label><input type="text" name="alloc_notes" value="<?= sanitize($edit_alloc['notes'] ?? '') ?>" placeholder="Optional note"></div>
</div>
<div id="allocRemainingInfo" style="margin-bottom:8px;font-size:0.82rem;color:var(--text2)"></div>
<button type="submit" name="save_allocation" class="btn btn-primary">💾 Save Allocation</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('addAllocFormArea').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<script>
function updateAllocRemaining() {
    var sel = document.getElementById('allocBikeSelect');
    var opt = sel.options[sel.selectedIndex];
    var info = document.getElementById('allocRemainingInfo');
    var amtInput = document.querySelector('input[name="amount"]');
    if (opt && opt.value) {
        var total = parseFloat(opt.dataset.total||0);
        var allocated = parseFloat(opt.dataset.allocated||0);
        var remaining = parseFloat(opt.dataset.remaining||0);
        info.innerHTML = '<strong>Sale Total:</strong> <?= $currency ?> '+total.toLocaleString()+' | <strong>Already Allocated:</strong> <?= $currency ?> '+allocated.toLocaleString()+' | <strong style="color:'+(remaining>0?'var(--warning)':'var(--success)')+'">Remaining:</strong> <?= $currency ?> '+remaining.toLocaleString();
        if (amtInput) amtInput.value = remaining.toFixed(2);
    } else {
        info.innerHTML = '';
        if (amtInput) amtInput.value = '';
    }
}
updateAllocRemaining();
</script>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Chassis / Model</th><th>Destination</th><th>Amount</th><th>Deposit Status</th><th>Date</th><th>Notes</th><th>By</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php
        $sr = 1;
        $page_alloc_total = 0;
        while ($al = $alloc_result->fetch_assoc()):
            $page_alloc_total += $al['amount'];
            $ti = ['bank' => '🏦', 'person' => '👤', 'wallet' => '💳'][$al['dest_type']] ?? '📌';
            $dep_pct = $al['amount'] > 0 ? round(($al['deposited_amount'] / $al['amount']) * 100) : 0;
            if ($dep_pct >= 100)
                $dep_badge = 'success';
            elseif ($dep_pct > 0)
                $dep_badge = 'warning';
            else
                $dep_badge = 'danger';
            $dep_label = $dep_pct >= 100 ? '✅ Deposited' : ($dep_pct > 0 ? $dep_pct . '% Dep.' : '⏳ Pending');
            ?>
<tr>
<td><?= $sr++ ?></td>
<td><strong style="font-family:Consolas,monospace;font-size:0.8rem"><?= sanitize($al['chassis_number']) ?></strong><br><small><?= sanitize($al['model_name']) ?></small></td>
<td><span class="badge badge-<?= $al['dest_type'] === 'bank' ? 'info' : ($al['dest_type'] === 'person' ? 'success' : 'warning') ?>"><?= $ti ?> <?= sanitize($al['dest_name']) ?></span></td>
<td><strong><?= fmt_money($al['amount']) ?></strong></td>
<td><span class="badge badge-<?= $dep_badge ?>"><?= $dep_label ?></span><br><small style="color:var(--text3)"><?= fmt_money($al['deposited_amount']) ?> deposited</small></td>
<td><?= fmt_date($al['allocation_date']) ?></td>
<td><?= sanitize($al['notes'] ?: '-') ?></td>
<td><small><?= sanitize($al['created_by_name'] ?? '-') ?></small></td>
<td class="no-print">
<div class="actions-col">
<?php if (has_permission($conn, 'money_tracking', 'edit')): ?><a href="index.php?page=money_tracking&edit_id=<?= $al['id'] ?>" class="btn btn-primary btn-sm">✏</a><?php endif; ?>
<?php if (has_permission($conn, 'bank_deposits', 'add') && $al['dest_type'] === 'bank'): ?><a href="index.php?page=bank_deposits&amp;prefill_alloc=<?= $al['id'] ?>" class="btn btn-success btn-sm" title="Create bank deposit from this allocation">🏦</a><?php endif; ?>
<?php if (has_permission($conn, 'money_tracking', 'delete')): ?>
<form method="POST" style="display:inline"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="id" value="<?= $al['id'] ?>">
<button name="delete_allocation" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete this allocation?', text: 'This will remove the money tracking record.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="3"><strong>TOTAL</strong></td><td><strong><?= fmt_money($page_alloc_total) ?></strong></td><td colspan="5"></td></tr></tfoot>
</table>
</div>
<?php
    elseif ($page === 'bank_deposits'):
        $bank_dests = $conn->query("SELECT id, name, account_title, account_no FROM money_destinations WHERE type='bank' AND is_active=1 ORDER BY name");
        $sold_bikes_deps = $conn->query("SELECT b.id, b.chassis_number, b.selling_price, b.selling_date, m.model_name, c.name as cust_name,
            COALESCE((SELECT SUM(sa2.final_price) FROM sale_accessories sa2 WHERE sa2.bike_id=b.id),0) as acc_total,
            COALESCE((SELECT SUM(sma.amount) FROM sale_money_allocations sma WHERE sma.bike_id=b.id),0) as total_allocated,
            COALESCE((SELECT SUM(da.amount) FROM deposit_allocations da WHERE da.bike_id=b.id),0) as total_deposited
            FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id
            WHERE b.status='sold'
            HAVING total_allocated > total_deposited
            ORDER BY b.selling_date DESC");
        $sold_bikes_deps_arr = [];
        while ($sbd = $sold_bikes_deps->fetch_assoc())
            $sold_bikes_deps_arr[] = $sbd;
        $edit_dep_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_dep = null;
        if ($edit_dep_id) {
            $ed = $conn->query("SELECT * FROM bank_deposits WHERE id=$edit_dep_id");
            $edit_dep = $ed ? $ed->fetch_assoc() : null;
        }
        $prefill_alloc_id = (int) ($_GET['prefill_alloc'] ?? 0);
        $prefill_alloc = null;
        $prefill_remaining_amount = 0;
        if ($prefill_alloc_id && !$edit_dep_id) {
            $pa = $conn->query("SELECT sma.*, md.type as dest_type,
                COALESCE((SELECT SUM(da.amount) FROM deposit_allocations da WHERE da.allocation_id=sma.id),0) as already_deposited
                FROM sale_money_allocations sma
                LEFT JOIN money_destinations md ON sma.destination_id=md.id
                WHERE sma.id=$prefill_alloc_id");
            $prefill_alloc = $pa ? $pa->fetch_assoc() : null;
            if ($prefill_alloc) {
                $prefill_remaining_amount = max(0, (float) $prefill_alloc['amount'] - (float) $prefill_alloc['already_deposited']);
            }
        }
        $filter_dest_dep = (int) ($_GET['filter_dest'] ?? 0);
        $filter_type_dep = sanitize($_GET['filter_type'] ?? '');
        $dep_where = '1=1';
        if ($filter_dest_dep)
            $dep_where .= " AND bd.destination_id=$filter_dest_dep";
        if ($filter_type_dep)
            $dep_where .= " AND bd.deposit_type='" . mysqli_real_escape_string($conn, $filter_type_dep) . "'";
        $dep_result = $conn->query("SELECT bd.*, md.name as dest_name, md.account_title, md.account_no
            FROM bank_deposits bd LEFT JOIN money_destinations md ON bd.destination_id=md.id
            WHERE $dep_where ORDER BY bd.deposit_date DESC, bd.id DESC");
        $total_deposited_range = $conn->query('SELECT COALESCE(SUM(amount),0) FROM deposit_allocations')->fetch_row()[0];
        $total_allocated_all = $conn->query("SELECT COALESCE(SUM(sma.amount),0) FROM sale_money_allocations sma JOIN money_destinations md ON md.id=sma.destination_id WHERE md.type='bank'")->fetch_row()[0];
        $total_sold_value = $conn->query("SELECT COALESCE(SUM(b.selling_price + COALESCE((SELECT SUM(sa2.final_price) FROM sale_accessories sa2 WHERE sa2.bike_id=b.id),0)),0) FROM bikes b WHERE b.status='sold'")->fetch_row()[0];
        $pending_deposit = $total_allocated_all - $total_deposited_range;
        if ($pending_deposit < 0)
            $pending_deposit = 0;
?>
<div class="card-grid animate__animated animate__fadeInDown">
    <div class="card success"><div class="card-icon">💰</div><div class="card-body"><div class="card-label">Total Deposited</div><div class="card-value" style="font-size:1rem"><?= fmt_money($total_deposited_range) ?></div></div></div>
    <div class="card accent"><div class="card-icon">📊</div><div class="card-body"><div class="card-label">Allocated in Destinations</div><div class="card-value" style="font-size:1rem"><?= fmt_money($total_allocated_all) ?></div></div></div>
    <div class="card <?= $pending_deposit > 0 ? 'warning' : 'success' ?>"><div class="card-icon"><?= $pending_deposit > 0 ? '⏳' : '✅' ?></div><div class="card-body"><div class="card-label">Pending Bank Deposit</div><div class="card-value" style="font-size:1rem;color:<?= $pending_deposit > 0 ? 'var(--warning)' : 'var(--success)' ?>"><?= fmt_money($pending_deposit) ?></div></div></div>
</div>
<div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap" class="no-print animate__animated animate__fadeInLeft">
<?php if (has_permission($conn, 'bank_deposits', 'add')): ?>
<button class="btn btn-success" onclick="document.getElementById('addDepFormArea').style.display='block';document.getElementById('addDepFormArea').scrollIntoView()">+ New Bank Deposit</button>
<?php endif; ?>
</div>
<div class="filter-bar no-print animate__animated animate__fadeInLeft" style="margin-bottom:10px">
<form method="GET" action="index.php" style="display:contents">
<input type="hidden" name="page" value="bank_deposits">
<div class="form-group"><label>Destination</label>
<select name="filter_dest"><option value="0">-- All Banks --</option>
<?php $bank_dests->data_seek(0);
        while ($bd_opt = $bank_dests->fetch_assoc()): ?>
<option value="<?= $bd_opt['id'] ?>" <?= $filter_dest_dep == $bd_opt['id'] ? 'selected' : '' ?>><?= sanitize($bd_opt['name']) ?></option>
<?php endwhile; ?>
</select></div>
<div class="form-group"><label>Type</label>
<select name="filter_type"><option value="">-- All Types --</option>
<option value="cash" <?= $filter_type_dep === 'cash' ? 'selected' : '' ?>>Cash</option>
<option value="cheque" <?= $filter_type_dep === 'cheque' ? 'selected' : '' ?>>Cheque</option>
<option value="transfer" <?= $filter_type_dep === 'transfer' ? 'selected' : '' ?>>Transfer</option>
<option value="online" <?= $filter_type_dep === 'online' ? 'selected' : '' ?>>Online</option>
</select></div>
<button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-end">🔍 Filter</button>
<a href="index.php?page=bank_deposits" class="btn btn-default btn-sm" style="align-self:flex-end">Clear</a>
</form>
</div>
<div id="addDepFormArea" style="display:<?= ($edit_dep || $prefill_alloc) ? 'block' : 'none' ?>;margin-bottom:14px" class="animate__animated animate__fadeIn">
<fieldset class="fieldset"><legend><?= $edit_dep ? '✏ Edit Deposit' : '+ New Bank Deposit' ?></legend>
<form method="POST" action="index.php?page=bank_deposits" enctype="multipart/form-data">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<?php if ($edit_dep): ?><input type="hidden" name="id" value="<?= $edit_dep['id'] ?>"><?php endif; ?>
<div class="form-row">
<div class="form-group" style="flex:2"><label>Destination (Bank) <span class="req">*</span></label>
<select name="destination_id" required>
<option value="">-- Select Bank --</option>
<?php $bank_dests->data_seek(0);
        while ($bd_sel = $bank_dests->fetch_assoc()): ?>
<option value="<?= $bd_sel['id'] ?>" <?= ($edit_dep && $edit_dep['destination_id'] == $bd_sel['id']) || ($prefill_alloc && $prefill_alloc['destination_id'] == $bd_sel['id']) ? 'selected' : '' ?>><?= sanitize($bd_sel['name']) ?> — <?= sanitize($bd_sel['account_no'] ?: '-') ?></option>
<?php endwhile; ?>
</select>
</div>
<div class="form-group"><label>Date <span class="req">*</span></label><input type="date" name="deposit_date" required value="<?= $edit_dep ? $edit_dep['deposit_date'] : ($prefill_alloc ? $prefill_alloc['allocation_date'] : date('Y-m-d')) ?>"></div>
<div class="form-group"><label>Amount (<?= $currency ?>) <span class="req">*</span></label><input type="number" name="amount" step="0.01" min="0.01" required value="<?= $edit_dep ? $edit_dep['amount'] : ($prefill_alloc ? $prefill_remaining_amount : '') ?>" placeholder="0.00"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Deposit Type <span class="req">*</span></label>
<select name="deposit_type" required>
<option value="cash" <?= ($edit_dep['deposit_type'] ?? '') === 'cash' ? 'selected' : '' ?>>Cash</option>
<option value="cheque" <?= ($edit_dep['deposit_type'] ?? '') === 'cheque' ? 'selected' : '' ?>>Cheque</option>
<option value="transfer" <?= ($edit_dep['deposit_type'] ?? '') === 'transfer' ? 'selected' : '' ?>>Transfer</option>
<option value="online" <?= ($edit_dep['deposit_type'] ?? '') === 'online' ? 'selected' : '' ?>>Online</option>
<option value="other" <?= ($edit_dep['deposit_type'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
</select>
</div>
<div class="form-group"><label>Reference No</label><input type="text" name="reference_no" value="<?= sanitize($edit_dep['reference_no'] ?? '') ?>" placeholder="Transaction/cheque #"></div>
<div class="form-group"><label>Deposited By</label><input type="text" name="deposited_by" value="<?= sanitize($edit_dep['deposited_by'] ?? '') ?>" placeholder="Who deposited"></div>
</div>
<div class="form-row">
<div class="form-group" style="flex:2"><label>Receipt Image</label><input type="file" name="receipt_image" accept="image/jpeg,image/png,image/webp,application/pdf"> <small style="color:var(--text3)">(optional, auto-resized to &lt;200KB)</small>
<?php if ($edit_dep && $edit_dep['receipt_image']): ?>
<br><a href="index.php?page=bank_deposits&amp;receipt_id=<?= (int) $edit_dep['id'] ?>" target="_blank" rel="noopener">View current receipt</a>
<?php endif; ?>
</div>
<div class="form-group" style="flex:3"><label>Notes</label><input type="text" name="deposit_notes" value="<?= $edit_dep ? sanitize($edit_dep['notes'] ?? '') : ($prefill_alloc ? sanitize($prefill_alloc['notes'] ?? '') : '') ?>" placeholder="Optional note"></div>
</div>
<?php if (!$edit_dep): ?>
<fieldset class="fieldset" style="border-color:var(--accent);background:var(--surface);margin-bottom:10px">
<legend style="cursor:pointer" onclick="document.getElementById('depBikeLinkArea').style.display=document.getElementById('depBikeLinkArea').style.display==='none'?'block':'none'">🔗 Link to Bike Sales <small style="color:var(--text3)">(Optional — click to expand)</small></legend>
<div id="depBikeLinkArea" style="display:<?= $prefill_alloc ? 'block' : 'none' ?>">
<div id="depBikeLinkRows"></div>
<button type="button" class="btn btn-default btn-sm" onclick="addDepBikeLinkRow()" style="margin-top:6px">+ Add Bike</button>
</div>
</fieldset>
<?php endif; ?>
<button type="submit" name="save_deposit" class="btn btn-primary">💾 Save Deposit</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('addDepFormArea').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<script>
var depBikeOptions = <?= json_encode($sold_bikes_deps_arr) ?>;
var depBikeIdx = 0;
<?php if ($prefill_alloc): ?>var prefillBikeId = <?= (int) $prefill_alloc['bike_id'] ?>;
var prefillAmount = <?= $prefill_remaining_amount ?>;
<?php else: ?>var prefillBikeId = 0;
var prefillAmount = 0;
<?php endif; ?>
function addDepBikeLinkRow() {
    var container = document.getElementById('depBikeLinkRows');
    var idx = depBikeIdx++;
    var opts = '<option value="">-- Select Sold Bike --</option>';
    depBikeOptions.forEach(function(b) {
        var saleTotal = parseFloat(b.selling_price) + parseFloat(b.acc_total || 0);
        var rem = saleTotal - parseFloat(b.total_deposited || 0);
        opts += '<option value="'+b.id+'" data-total="'+saleTotal+'" data-deposited="'+(b.total_deposited||0)+'" data-remaining="'+rem+'">'+b.chassis_number+' | '+b.model_name+' | Sale: '+saleTotal.toLocaleString()+' | Rem: '+rem.toLocaleString()+'</option>';
    });
    var row = document.createElement('div');
    row.className = 'form-row';
    row.style.alignItems = 'flex-end';
    row.id = 'depBikeLinkRow_'+idx;
    row.innerHTML = '<div class="form-group" style="flex:3"><label>Bike</label><select name="bike_link['+idx+'][bike_id]">'+opts+'</select></div>'
        + '<div class="form-group"><label>Amount (<?= $currency ?>)</label><input type="number" name="bike_link['+idx+'][amount]" step="0.01" min="0" placeholder="0.00"></div>'
        + '<div class="form-group"><label>Remaining</label><span id="depBikeRem_'+idx+'" style="font-size:0.85rem;color:var(--text3);display:block;padding-top:6px">—</span></div>'
        + '<div class="form-group"><button type="button" class="btn btn-danger btn-sm" onclick="document.getElementById(\'depBikeLinkRow_'+idx+'\').remove()">✕</button></div>';
    container.appendChild(row);
    $(row).find('select').select2({ minimumResultsForSearch: 10, placeholder: '-- Select --', allowClear: false, theme: 'default' });
}
function updateDepBikeRem(sel, idx, noFill) {
    var rem = document.getElementById('depBikeRem_'+idx);
    var row = sel.closest('.form-row');
    var amtInput = row ? row.querySelector('input[name$="[amount]"]') : null;
    if (sel.value) {
        var opt = Array.from(sel.options).find(function(o) { return o.value == sel.value; });
        if (opt) {
            var remaining = parseFloat(opt.dataset.remaining || 0);
            if (amtInput) amtInput.max = remaining;
            if (!noFill && amtInput) amtInput.value = remaining.toFixed(2);
            var amt = amtInput ? (parseFloat(amtInput.value) || 0) : 0;
            var displayRem = Math.max(0, remaining - amt);
            rem.innerHTML = '<strong style="color:'+(displayRem>0?'var(--warning)':'var(--success)')+'"><?= $currency ?> '+displayRem.toLocaleString()+'</strong>';
        } else {
            rem.innerHTML = '—';
        }
    } else {
        rem.innerHTML = '—';
    }
}
function updateDepBikeRemFromAmount(input, idx) {
    if (input.max && parseFloat(input.value) > parseFloat(input.max)) {
        input.value = input.max;
    }
    var row = input.closest('.form-row');
    var select = row ? row.querySelector('select[name$="[bike_id]"]') : null;
    if (select) {
        updateDepBikeRem(select, idx, true);
    }
}
if (prefillBikeId > 0) {
    addDepBikeLinkRow();
    setTimeout(function() {
        var container = document.getElementById('depBikeLinkRows');
        if (container) {
            var select = container.querySelector('select[name$="[bike_id]"]');
            var amtInput = container.querySelector('input[name$="[amount]"]');
            if (select) {
                $(select).val(prefillBikeId).trigger('change');
            }
            if (amtInput) {
                amtInput.value = prefillAmount.toFixed(2);
                amtInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
    }, 100);
}
</script>
<div class="data-table-wrap animate__animated animate__fadeInUp">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Date</th><th>Destination</th><th>Amount</th><th>Type</th><th>Reference</th><th>Receipt</th><th>Linked Bikes</th><th>Deposited By</th><th class="no-sort">Actions</th></tr></thead>
<tbody>
<?php
        $sr = 1;
        $page_dep_total = 0;
        while ($dep = $dep_result->fetch_assoc()):
            $page_dep_total += $dep['amount'];
            $linked_bikes = $conn->query('SELECT COUNT(DISTINCT da.bike_id) as cnt, COALESCE(SUM(da.amount),0) as tot FROM deposit_allocations da WHERE da.deposit_id=' . $dep['id']);
            $lb_row = $linked_bikes ? $linked_bikes->fetch_assoc() : ['cnt' => 0, 'tot' => 0];
            ?>
<tr>
<td><?= $sr++ ?></td>
<td><?= fmt_date($dep['deposit_date']) ?></td>
<td><strong><?= sanitize($dep['dest_name']) ?></strong><br><small style="color:var(--text3)"><?= sanitize($dep['account_no'] ?: '') ?></small></td>
<td><strong><?= fmt_money($dep['amount']) ?></strong></td>
<td><span class="badge badge-<?= $dep['deposit_type'] === 'cash' ? 'success' : ($dep['deposit_type'] === 'cheque' ? 'warning' : 'info') ?>"><?= strtoupper($dep['deposit_type']) ?></span></td>
<td><?= sanitize($dep['reference_no'] ?: '-') ?></td>
<td><?php if ($dep['receipt_image']): ?>
<a href="index.php?page=bank_deposits&amp;receipt_id=<?= (int) $dep['id'] ?>" target="_blank" rel="noopener" class="btn btn-default btn-sm" title="View Receipt">View</a>
<?php else: ?><span style="color:var(--text3)">—</span><?php endif; ?></td>
<td><?= $lb_row['cnt'] > 0 ? $lb_row['cnt'] . ' bike(s) — ' . fmt_money($lb_row['tot']) : '<span style="color:var(--text3)">—</span>' ?></td>
<td><?= sanitize($dep['deposited_by'] ?: '-') ?></td>
<td class="no-print">
<div class="actions-col">
<?php if (has_permission($conn, 'bank_deposits', 'edit')): ?><a href="index.php?page=bank_deposits&edit_id=<?= $dep['id'] ?>" class="btn btn-primary btn-sm">✏</a><?php endif; ?>
<?php if (has_permission($conn, 'bank_deposits', 'delete')): ?>
<form method="POST" style="display:inline"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>"><input type="hidden" name="id" value="<?= $dep['id'] ?>">
<button name="delete_deposit" class="btn btn-danger btn-sm" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'Delete this deposit?', text: 'This will remove the deposit and its bike links.', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, delete it!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">🗑</button>
</form>
<?php endif; ?>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="3"><strong>TOTAL</strong></td><td><strong><?= fmt_money($page_dep_total) ?></strong></td><td colspan="6"></td></tr></tfoot>
</table>
</div>
<?php
    elseif ($page === 'settings'):
        $s_company = get_setting('company_name') ?? 'BNI Enterprises';
        $s_branch = get_setting('branch_name') ?? 'Dera (Ahmed Metro)';
        $s_tax = ((float) (get_setting('tax_rate') ?? 0.1)) * 100;
        $s_curr = get_setting('currency') ?? 'Rs.';
        $s_taxon = get_setting('tax_on') ?? 'purchase_price';
        $s_show_pp = get_setting('show_purchase_on_invoice') ?? '0';
        $s_idle_timeout = get_setting('session_timeout_idle') ?? '2400';
        $s_absolute_timeout = get_setting('session_timeout_absolute') ?? '28800';
?>
<form id="settingsForm" method="POST" enctype="multipart/form-data" class="animate__animated animate__fadeIn">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
<input type="hidden" name="save_settings" value="1">
<fieldset class="fieldset"><legend>⚙ Company Settings</legend>
<div class="form-row">
<div class="form-group"><label>Company Name</label><input type="text" name="company_name" value="<?= sanitize($s_company) ?>"></div>
<div class="form-group"><label>Branch Name</label><input type="text" name="branch_name" value="<?= sanitize($s_branch) ?>"></div>
</div>
<div class="form-row">
<div class="form-group" style="flex:2">
    <label>Application Logo (Replaces all icons & logos)</label>
    <div style="display:flex;align-items:center;gap:10px;background:var(--bg2);padding:10px;border:1px solid var(--border);border-radius:2px">
        <img src="logo.png?v=<?= time() ?>" style="height:50px;width:50px;object-fit:contain;background:#fff;padding:2px;border-radius:2px">
        <input type="file" name="app_logo" accept="image/png,image/jpeg,image/webp" style="font-size:0.8rem">
    </div>
    <small style="color:var(--text3);margin-top:4px">Recommended: Square PNG with transparency. This will automatically update all app icons, favicons, and manifest files.</small>
</div>
</div>
<div class="form-row">
<div class="form-group"><label>Currency Symbol</label><input type="text" name="currency" value="<?= sanitize($s_curr) ?>" style="max-width:80px"></div>
<div class="form-group"><label>Tax Rate (%)</label><input type="number" name="tax_rate" value="<?= $s_tax ?>" step="0.01" min="0" max="100" style="max-width:100px"></div>
<div class="form-group"><label>Tax Calculated On</label>
<select name="tax_on">
<option value="purchase_price" <?= $s_taxon === 'purchase_price' ? 'selected' : '' ?>>Purchase Price</option>
<option value="selling_price" <?= $s_taxon === 'selling_price' ? 'selected' : '' ?>>Selling Price</option>
</select>
</div>
</div>
<div class="form-row">
<div class="form-group"><label>Show Purchase Price on Invoice</label>
<select name="show_purchase_on_invoice">
<option value="0" <?= $s_show_pp === '0' ? 'selected' : '' ?>>No (Hidden)</option>
<option value="1" <?= $s_show_pp === '1' ? 'selected' : '' ?>>Yes (Visible)</option>
</select>
</div>
<div class="form-group"><label>Idle Session Timeout (seconds)</label><input type="number" name="session_timeout_idle" value="<?= $s_idle_timeout ?>" min="300" max="86400" required></div>
<div class="form-group"><label>Absolute Session Timeout (seconds)</label><input type="number" name="session_timeout_absolute" value="<?= $s_absolute_timeout ?>" min="900" max="604800" required></div>
</div>
</fieldset>
<fieldset class="fieldset"><legend>🔐 Change Admin Password</legend>
<div class="form-row">
<div class="form-group"><label>Current Password</label><input type="password" name="current_password" autocomplete="current-password" placeholder="Required only when changing password"></div>
<div class="form-group"><label>New Password</label><input type="password" name="new_password" autocomplete="new-password" minlength="8" placeholder="Leave blank to keep existing password"></div>
</div>
<div style="font-size:0.78rem;color:var(--text2);margin-top:4px">⚠ Password must be at least 8 characters. Must include at least one uppercase letter, one lowercase letter, one number, and one special character. Leave empty to keep the current password unchanged.</div>
</fieldset>
<fieldset class="fieldset"><legend>💾 Database Backup & Restore</legend>
<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:14px">
<a href="index.php?page=settings&action=backup" class="btn btn-primary animate__animated animate__pulse">⬇ Download SQL Backup</a>
<span style="font-size:0.8rem;color:var(--text2)">Downloads a full SQL dump of the database.</span>
</div>
<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;border-top:1px solid var(--border);padding-top:14px">
<input type="file" name="backup_file" accept=".sql" style="font-size:0.8rem;background:var(--input-bg);color:var(--input-text);border:1px solid var(--input-border);padding:5px;border-radius:2px">
<button type="submit" name="restore_db" class="btn btn-danger" onclick="event.preventDefault(); let btn = this; let f = btn.closest('form'); Swal.fire({title: 'WARNING: Restore Database?', text: 'Restoring will OVERWRITE ALL CURRENT DATA! Are you absolutely sure?', icon: 'error', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#3085d6', confirmButtonText: 'Yes, Restore!'}).then((result) => { if(result.isConfirmed) { if(btn.name) { let h = document.createElement('input'); h.type = 'hidden'; h.name = btn.name; h.value = btn.value || '1'; f.appendChild(h); } f.submit(); } })">⬆ Restore Database</button>
<span style="font-size:0.8rem;color:var(--text2)">Upload a previously downloaded .sql backup file.</span>
</div>
</fieldset>
<fieldset class="fieldset"><legend>ℹ System Info</legend>
<div class="split-grid" style="font-size:0.82rem;color:var(--text2);gap:8px">
<div><strong>App Version:</strong> <?= $app_version ?></div>
<div><strong>Author:</strong> <?= $author ?></div>
<div><strong>PHP Version:</strong> <?= phpversion() ?></div>
<div><strong>MySQL Version:</strong> <?= $conn->server_info ?></div>
<div><strong>Database:</strong> <?= $db_name ?></div>
<div><strong>Server Time:</strong> <?= date('d/m/Y H:i:s') ?></div>
</div>
</fieldset>
<button type="submit" name="save_settings" class="btn btn-primary">💾 Save Settings</button>
</form>
<?php endif; ?>
</div>
</div>
</div>
</div>
<?php endif; ?>
<script>
function toggleSidebar() {
    if (window.innerWidth <= 600) {
        var s = document.getElementById('sidebar');
        var o = document.getElementById('sidebarOverlay');
        s.classList.toggle('open');
        o.classList.toggle('open');
    } else {
        document.body.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed') ? '1' : '0');
        var toggleBtn = document.getElementById('sidebarToggle');
        if (toggleBtn) {
            var labels = toggleBtn.querySelectorAll('.toggle-label');
            var isCollapsed = document.body.classList.contains('sidebar-collapsed');
            labels.forEach(function(el) { el.style.display = isCollapsed ? 'none' : ''; });
        }
    }
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
}
var toastWrap = document.getElementById('toastWrap');
if (toastWrap) {
    setTimeout(function() {
        toastWrap.style.opacity = '0';
        toastWrap.style.transition = 'opacity 0.5s';
        setTimeout(function(){ toastWrap.remove(); }, 500);
    }, 3500);
}
(function() {
    var bottomNav = document.getElementById('bottomNav');
    if (!bottomNav) return;
    var lastY = 0;
    var ticking = false;
    var hideThreshold = 10;
    var scrollContainer = document.querySelector('.content') || window;
    function onScroll() {
        var curY = window.pageYOffset || document.documentElement.scrollTop;
        if (curY < 0) curY = 0;
        var diff = curY - lastY;
        if (Math.abs(diff) < hideThreshold) return;
        if (diff > 0 && curY > 60) {
            bottomNav.classList.add('hide');
        } else {
            bottomNav.classList.remove('hide');
        }
        lastY = curY;
    }
    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                onScroll();
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });
})();
(function() {
    function scrollActiveIntoView(container) {
        if (!container) return;
        var active = container.querySelector('a.active');
        if (!active) return;
        var cr = container.getBoundingClientRect();
        var ar = active.getBoundingClientRect();
        var vOverflow = ar.top < cr.top || ar.bottom > cr.bottom;
        var hOverflow = ar.left < cr.left || ar.right > cr.right;
        if (vOverflow || hOverflow) {
            active.scrollIntoView({ block: 'nearest', inline: 'center' });
        }
    }
    function run() {
        scrollActiveIntoView(document.querySelector('.sidebar nav'));
        scrollActiveIntoView(document.querySelector('.bottom-nav-scroll'));
    }
    if (document.readyState === 'complete') {
        run();
    } else {
        window.addEventListener('load', run);
    }
})();
setInterval(function() {
}, 60000); 
</script>
</body>
</html>
