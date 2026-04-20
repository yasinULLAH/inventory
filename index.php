<?php
session_start();
$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'root';
$db_name = 'bni_enterprises2';
$app_version = '1.0.0';
$author = 'Yasin Ullah';

function db_connect($create_db = false)
{
    global $db_host, $db_user, $db_pass, $db_name;
    if ($create_db) {
        $conn = new mysqli($db_host, $db_user, $db_pass);
    } else {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    }
    if ($conn->connect_error) {
        return null;
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

function install_database()
{
    global $db_name;
    $conn = db_connect(true);
    if (!$conn)
        return false;
    $conn->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->select_db($db_name);

    $tables = [
        'CREATE TABLE IF NOT EXISTS `settings` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `setting_key` VARCHAR(100) UNIQUE NOT NULL,
            `setting_value` TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS `suppliers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `contact` VARCHAR(100),
            `address` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS `customers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `phone` VARCHAR(50),
            `cnic` VARCHAR(20),
            `address` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS `models` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `model_code` VARCHAR(50) NOT NULL,
            `model_name` VARCHAR(255) NOT NULL,
            `category` VARCHAR(100),
            `short_code` VARCHAR(20),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS `purchase_orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_date` DATE,
            `supplier_id` INT,
            `cheque_number` VARCHAR(50),
            `bank_name` VARCHAR(100),
            `cheque_date` DATE,
            `cheque_amount` DECIMAL(15,2),
            `total_units` INT,
            `notes` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`supplier_id`) REFERENCES `suppliers`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        "CREATE TABLE IF NOT EXISTS `bikes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `purchase_order_id` INT,
            `order_date` DATE,
            `inventory_date` DATE,
            `chassis_number` VARCHAR(100) UNIQUE NOT NULL,
            `motor_number` VARCHAR(100),
            `model_id` INT,
            `color` VARCHAR(50),
            `purchase_price` DECIMAL(15,2),
            `selling_price` DECIMAL(15,2) NULL,
            `selling_date` DATE NULL,
            `customer_id` INT NULL,
            `tax_amount` DECIMAL(15,2) DEFAULT 0,
            `margin` DECIMAL(15,2) DEFAULT 0,
            `status` ENUM('in_stock','sold','returned','reserved') DEFAULT 'in_stock',
            `return_date` DATE NULL,
            `return_amount` DECIMAL(15,2) NULL,
            `return_notes` TEXT NULL,
            `accessories` TEXT NULL,
            `safeguard_notes` TEXT NULL,
            `notes` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`model_id`) REFERENCES `models`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`) ON DELETE SET NULL,
            FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `cheque_register` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `cheque_number` VARCHAR(50),
            `bank_name` VARCHAR(100),
            `cheque_date` DATE,
            `amount` DECIMAL(15,2),
            `type` ENUM('payment','receipt','refund'),
            `status` ENUM('pending','cleared','bounced','cancelled') DEFAULT 'pending',
            `reference_type` VARCHAR(50),
            `reference_id` INT,
            `party_name` VARCHAR(255),
            `notes` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `payments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `payment_date` DATE,
            `payment_type` ENUM('cash','cheque','bank_transfer','online'),
            `amount` DECIMAL(15,2),
            `cheque_id` INT NULL,
            `reference_type` VARCHAR(50),
            `reference_id` INT,
            `party_name` VARCHAR(255),
            `notes` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`cheque_id`) REFERENCES `cheque_register`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS `ledger` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `entry_date` DATE,
            `entry_type` ENUM('debit','credit'),
            `amount` DECIMAL(15,2),
            `party_type` ENUM('customer','supplier','other'),
            `party_id` INT,
            `description` TEXT,
            `reference_type` VARCHAR(50),
            `reference_id` INT,
            `balance` DECIMAL(15,2),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    ];

    foreach ($tables as $sql) {
        if (!$conn->query($sql)) {
            $conn->close();
            return false;
        }
    }

    $defaults = [
        ['company_name', 'BNI Enterprises'],
        ['branch_name', 'Dera (Ahmed Metro)'],
        ['tax_rate', '0.1'],
        ['currency', 'Rs.'],
        ['tax_on', 'purchase_price'],
        ['theme', 'dark'],
        ['admin_password', password_hash('admin123', PASSWORD_DEFAULT)],
        ['show_purchase_on_invoice', '0'],
    ];
    $stmt = $conn->prepare('INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES (?, ?)');
    foreach ($defaults as $d) {
        $stmt->bind_param('ss', $d[0], $d[1]);
        $stmt->execute();
    }
    $stmt->close();

    $models_seed = [
        ['LY SI', 'LY SI Electric Bike', 'Electric Bike', 'LY'],
        ['T9 Sports', 'T9 Sports Electric Bike', 'Electric Bike', 'T9'],
        ['T9 Sports LFP', 'T9 Sports LFP Electric Bike', 'Electric Bike', 'T9 LFP'],
        ['T9 Eco', 'T9 Eco Electric Bike', 'Electric Bike', 'T9 Eco'],
        ['Thrill Pro', 'Thrill Pro Electric Bike', 'Electric Bike', 'TP'],
        ['Thrill Pro LFP', 'Thrill Pro LFP Electric Bike', 'Electric Bike', 'TP LFP'],
        ['E8S M2', 'E8S M2 Electric Scooter', 'Electric Scooter', 'E8S'],
        ['E8S Pro', 'E8S Pro Electric Scooter', 'Electric Scooter', 'E8S Pro'],
        ['M6 K6', 'M6 K6 Electric Bike', 'Electric Bike', 'M6'],
        ['M6 NP', 'M6 NP Electric Bike', 'Electric Bike', 'M6 NP'],
        ['M6 Lithium NP', 'M6 Lithium NP Electric Bike', 'Electric Bike', 'M6 L'],
        ['Premium', 'Premium Electric Bike', 'Electric Bike', 'Premium'],
        ['W. Bike H2', 'W. Bike H2 Electric Bike', 'Electric Bike', 'W. Bike'],
    ];
    $r = $conn->query('SELECT COUNT(*) as c FROM `models`');
    $row = $r->fetch_assoc();
    if ($row['c'] == 0) {
        $stmt = $conn->prepare('INSERT INTO `models` (`model_code`,`model_name`,`category`,`short_code`) VALUES (?,?,?,?)');
        foreach ($models_seed as $m) {
            $stmt->bind_param('ssss', $m[0], $m[1], $m[2], $m[3]);
            $stmt->execute();
        }
        $stmt->close();
    }

    $r2 = $conn->query('SELECT COUNT(*) as c FROM `suppliers`');
    $row2 = $r2->fetch_assoc();
    if ($row2['c'] == 0) {
        $conn->query("INSERT INTO `suppliers` (`name`,`contact`,`address`) VALUES ('Default Supplier','0300-0000000','Pakistan')");
    }

    $r3 = $conn->query('SELECT COUNT(*) as c FROM `customers`');
    $row3 = $r3->fetch_assoc();
    if ($row3['c'] == 0) {
        $customers_seed = [
            ['Ahmed Ali', '0321-1234567', '35201-1234567-1', 'Dera Ghazi Khan, Punjab'],
            ['Muhammad Usman', '0333-7654321', '35201-7654321-3', 'Muzaffargarh, Punjab'],
            ['Bilal Hussain', '0345-9876543', '35201-9876543-5', 'Rajanpur, Punjab'],
            ['Zafar Iqbal', '0312-4567890', '35201-4567890-7', 'Layyah, Punjab'],
        ];
        $stmt = $conn->prepare('INSERT INTO `customers` (`name`,`phone`,`cnic`,`address`) VALUES (?,?,?,?)');
        foreach ($customers_seed as $c) {
            $stmt->bind_param('ssss', $c[0], $c[1], $c[2], $c[3]);
            $stmt->execute();
        }
        $stmt->close();
    }

    $conn->close();
    return true;
}

function get_setting($key)
{
    $conn = db_connect();
    if (!$conn)
        return null;
    $stmt = $conn->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $r = $stmt->get_result();
    $row = $r->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $row ? $row['setting_value'] : null;
}

function fmt_money($val)
{
    return 'Rs. ' . number_format((float) $val, 2);
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
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

$db_exists = false;
$test_conn = db_connect(true);
if ($test_conn) {
    $r = $test_conn->query("SHOW DATABASES LIKE '$db_name'");
    if ($r && $r->num_rows > 0) {
        $test_conn->select_db($db_name);
        $r2 = $test_conn->query("SHOW TABLES LIKE 'settings'");
        if ($r2 && $r2->num_rows > 0) {
            $db_exists = true;
        }
    }
    $test_conn->close();
}

if (isset($_POST['do_install'])) {
    if (install_database()) {
        $db_exists = true;
        header('Location: index.php');
        exit;
    }
}

if ($db_exists) {
    $theme = get_setting('theme') ?? 'dark';
    if (isset($_POST['toggle_theme'])) {
        $new_theme = ($theme === 'dark') ? 'light' : 'dark';
        $conn = db_connect();
        if ($conn) {
            $stmt = $conn->prepare("UPDATE settings SET setting_value=? WHERE setting_key='theme'");
            $stmt->bind_param('s', $new_theme);
            $stmt->execute();
            $stmt->close();
            $conn->close();
        }
        $theme = $new_theme;
        header('Location: index.php?' . http_build_query(array_merge($_GET, [])));
        exit;
    }

    if (!isset($_SESSION['logged_in'])) {
        if (isset($_POST['do_login'])) {
            $uname = $_POST['username'] ?? '';
            $upass = $_POST['password'] ?? '';
            $stored_pass = get_setting('admin_password');
            if ($uname === 'admin' && password_verify($upass, $stored_pass)) {
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                header('Location: index.php');
                exit;
            } else {
                $login_error = 'Invalid username or password.';
            }
        }
    } else {
        if (time() - $_SESSION['login_time'] > 28800) {
            session_destroy();
            header('Location: index.php?msg=session_expired');
            exit;
        }
        if (time() - ($_SESSION['last_active'] ?? $_SESSION['login_time']) > 2400) {
            session_destroy();
            header('Location: index.php?msg=idle_logout');
            exit;
        }
        $_SESSION['last_active'] = time();

        if (isset($_GET['logout'])) {
            session_destroy();
            header('Location: index.php');
            exit;
        }
    }
}

$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? '';
$msg = '';
$err = '';

if ($db_exists && isset($_SESSION['logged_in'])) {
    $conn = db_connect();

    if ($page === 'purchase' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_purchase'])) {
        $order_date = $_POST['order_date'] ?? date('Y-m-d');
        $inventory_date = $_POST['inventory_date'] ?? date('Y-m-d');
        $supplier_id = (int) ($_POST['supplier_id'] ?? 0);
        $cheque_number = sanitize($_POST['cheque_number'] ?? '');
        $bank_name = sanitize($_POST['bank_name'] ?? '');
        $cheque_date = !empty($_POST['cheque_date']) ? $_POST['cheque_date'] : null;
        $cheque_amount = (float) ($_POST['cheque_amount'] ?? 0);
        $notes = sanitize($_POST['po_notes'] ?? '');
        $bikes_data = $_POST['bikes'] ?? [];
        $total_units = count($bikes_data);

        $po_stmt = $conn->prepare('INSERT INTO purchase_orders (order_date,supplier_id,cheque_number,bank_name,cheque_date,cheque_amount,total_units,notes) VALUES (?,?,?,?,?,?,?,?)');
        $po_stmt->bind_param('sisssdis', $order_date, $supplier_id, $cheque_number, $bank_name, $cheque_date, $cheque_amount, $total_units, $notes);
        $po_stmt->execute();
        $po_id = $conn->insert_id;
        $po_stmt->close();

        $tax_rate = (float) (get_setting('tax_rate') ?? 0.1);
        $tax_on = get_setting('tax_on') ?? 'purchase_price';
        $bike_stmt = $conn->prepare("INSERT INTO bikes (purchase_order_id,order_date,inventory_date,chassis_number,motor_number,model_id,color,purchase_price,tax_amount,status,safeguard_notes,accessories,notes) VALUES (?,?,?,?,?,?,?,?,?,'in_stock',?,?,?)");

        $saved_count = 0;
        $errors_list = [];
        foreach ($bikes_data as $b) {
            $chassis = sanitize($b['chassis'] ?? '');
            $motor = sanitize($b['motor'] ?? '');
            $model_id = (int) ($b['model_id'] ?? 0);
            $color = sanitize($b['color'] ?? '');
            $pp = (float) ($b['purchase_price'] ?? 0);
            $safe_notes = sanitize($b['safeguard_notes'] ?? '');
            $accessories = sanitize($b['accessories'] ?? '');
            $bnotes = sanitize($b['notes'] ?? '');
            if (empty($chassis))
                continue;
            $tax = ($pp * $tax_rate) / 100;
            $bike_stmt->bind_param('issssisddsss', $po_id, $order_date, $inventory_date, $chassis, $motor, $model_id, $color, $pp, $tax, $safe_notes, $accessories, $bnotes);
            if (!$bike_stmt->execute()) {
                $errors_list[] = "Chassis $chassis: " . $bike_stmt->error;
            } else {
                $saved_count++;
            }
        }
        $bike_stmt->close();

        if (!empty($cheque_number) && $cheque_amount > 0) {
            $sup_r = $conn->query("SELECT name FROM suppliers WHERE id=$supplier_id");
            $sup_row = $sup_r ? $sup_r->fetch_assoc() : null;
            $party = $sup_row ? $sup_row['name'] : 'Unknown Supplier';
            $chq_stmt = $conn->prepare("INSERT INTO cheque_register (cheque_number,bank_name,cheque_date,amount,type,status,reference_type,reference_id,party_name,notes) VALUES (?,?,?,?,'payment','pending','purchase_order',?,?,?)");
            $chq_stmt->bind_param('sssdiiss', $cheque_number, $bank_name, $cheque_date, $cheque_amount, $po_id, $party, $notes);
            $chq_stmt->execute();
            $chq_stmt->close();
        }

        if (!empty($errors_list)) {
            $err = "Saved $saved_count bikes. Errors: " . implode('; ', $errors_list);
        } else {
            $msg = "Purchase order saved. $saved_count bike(s) added to inventory.";
        }
    }

    if ($page === 'suppliers' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'add' || $action === 'edit') {
            $name = sanitize($_POST['name'] ?? '');
            $contact = sanitize($_POST['contact'] ?? '');
            $address = sanitize($_POST['address'] ?? '');
            if (empty($name)) {
                $err = 'Supplier name is required.';
            } else {
                if ($action === 'add') {
                    $st = $conn->prepare('INSERT INTO suppliers (name,contact,address) VALUES (?,?,?)');
                    $st->bind_param('sss', $name, $contact, $address);
                    $st->execute();
                    $st->close();
                    $msg = 'Supplier added successfully.';
                } else {
                    $sid = (int) ($_POST['id'] ?? 0);
                    $st = $conn->prepare('UPDATE suppliers SET name=?,contact=?,address=? WHERE id=?');
                    $st->bind_param('sssi', $name, $contact, $address, $sid);
                    $st->execute();
                    $st->close();
                    $msg = 'Supplier updated successfully.';
                }
            }
        }
        if ($action === 'delete') {
            $sid = (int) ($_POST['id'] ?? 0);
            $st = $conn->prepare('DELETE FROM suppliers WHERE id=?');
            $st->bind_param('i', $sid);
            $st->execute();
            $st->close();
            $msg = 'Supplier deleted.';
        }
    }

    if ($page === 'customers' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'add' || $action === 'edit') {
            $name = sanitize($_POST['name'] ?? '');
            $phone = sanitize($_POST['phone'] ?? '');
            $cnic = sanitize($_POST['cnic'] ?? '');
            $address = sanitize($_POST['address'] ?? '');
            if (empty($name)) {
                $err = 'Customer name is required.';
            } else {
                if ($action === 'add') {
                    $st = $conn->prepare('INSERT INTO customers (name,phone,cnic,address) VALUES (?,?,?,?)');
                    $st->bind_param('ssss', $name, $phone, $cnic, $address);
                    $st->execute();
                    $st->close();
                    $msg = 'Customer added.';
                } else {
                    $cid = (int) ($_POST['id'] ?? 0);
                    $st = $conn->prepare('UPDATE customers SET name=?,phone=?,cnic=?,address=? WHERE id=?');
                    $st->bind_param('ssssi', $name, $phone, $cnic, $address, $cid);
                    $st->execute();
                    $st->close();
                    $msg = 'Customer updated.';
                }
            }
        }
        if ($action === 'delete') {
            $cid = (int) ($_POST['id'] ?? 0);
            $st = $conn->prepare('DELETE FROM customers WHERE id=?');
            $st->bind_param('i', $cid);
            $st->execute();
            $st->close();
            $msg = 'Customer deleted.';
        }
    }

    if ($page === 'models' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'add' || $action === 'edit') {
            $mc = sanitize($_POST['model_code'] ?? '');
            $mn = sanitize($_POST['model_name'] ?? '');
            $cat = sanitize($_POST['category'] ?? '');
            $sc = sanitize($_POST['short_code'] ?? '');
            if (empty($mc) || empty($mn)) {
                $err = 'Model code and name are required.';
            } else {
                if ($action === 'add') {
                    $st = $conn->prepare('INSERT INTO models (model_code,model_name,category,short_code) VALUES (?,?,?,?)');
                    $st->bind_param('ssss', $mc, $mn, $cat, $sc);
                    $st->execute();
                    $st->close();
                    $msg = 'Model added.';
                } else {
                    $mid = (int) ($_POST['id'] ?? 0);
                    $st = $conn->prepare('UPDATE models SET model_code=?,model_name=?,category=?,short_code=? WHERE id=?');
                    $st->bind_param('ssssi', $mc, $mn, $cat, $sc, $mid);
                    $st->execute();
                    $st->close();
                    $msg = 'Model updated.';
                }
            }
        }
        if ($action === 'delete') {
            $mid = (int) ($_POST['id'] ?? 0);
            $st = $conn->prepare('DELETE FROM models WHERE id=?');
            $st->bind_param('i', $mid);
            $st->execute();
            $st->close();
            $msg = 'Model deleted.';
        }
    }

    if ($page === 'sale' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sale'])) {
        $bike_id = (int) ($_POST['bike_id'] ?? 0);
        $selling_price = (float) ($_POST['selling_price'] ?? 0);
        $selling_date = $_POST['selling_date'] ?? date('Y-m-d');
        $customer_id = (int) ($_POST['customer_id'] ?? 0);
        $payment_type = sanitize($_POST['payment_type'] ?? 'cash');
        $cheque_number = sanitize($_POST['cheque_number'] ?? '');
        $bank_name = sanitize($_POST['bank_name'] ?? '');
        $cheque_date = !empty($_POST['cheque_date']) ? $_POST['cheque_date'] : null;
        $cheque_amount = (float) ($_POST['cheque_amount'] ?? 0);
        $sale_notes = sanitize($_POST['sale_notes'] ?? '');
        $accessories = sanitize($_POST['accessories'] ?? '');

        if ($bike_id && $selling_price > 0 && $selling_date) {
            $br = $conn->query("SELECT * FROM bikes WHERE id=$bike_id AND status='in_stock'");
            $bike = $br ? $br->fetch_assoc() : null;
            if ($bike) {
                $tax_rate = (float) (get_setting('tax_rate') ?? 0.1);
                $tax_on = get_setting('tax_on') ?? 'purchase_price';
                $base = ($tax_on === 'selling_price') ? $selling_price : $bike['purchase_price'];
                $tax_amount = ($base * $tax_rate) / 100;
                $margin = $selling_price - $bike['purchase_price'] - $tax_amount;

                $st = $conn->prepare("UPDATE bikes SET selling_price=?,selling_date=?,customer_id=?,tax_amount=?,margin=?,status='sold',accessories=?,notes=? WHERE id=?");
                $st->bind_param('dsiddssi', $selling_price, $selling_date, $customer_id, $tax_amount, $margin, $accessories, $sale_notes, $bike_id);
                $st->execute();
                $st->close();

                $cust_r = $conn->query("SELECT name FROM customers WHERE id=$customer_id");
                $cust_row = $cust_r ? $cust_r->fetch_assoc() : null;
                $party_name = $cust_row ? $cust_row['name'] : 'Cash Customer';

                $pay_st = $conn->prepare("INSERT INTO payments (payment_date,payment_type,amount,reference_type,reference_id,party_name,notes) VALUES (?,?,?,'sale',?,?,?)");
                $pay_st->bind_param('ssdiss', $selling_date, $payment_type, $selling_price, $bike_id, $party_name, $sale_notes);
                $pay_st->execute();
                $pay_st->close();

                if ($payment_type === 'cheque' && !empty($cheque_number)) {
                    $chq_st = $conn->prepare("INSERT INTO cheque_register (cheque_number,bank_name,cheque_date,amount,type,status,reference_type,reference_id,party_name,notes) VALUES (?,?,?,?,'receipt','pending','sale',?,?,?)");
                    $chq_st->bind_param('sssdiss', $cheque_number, $bank_name, $cheque_date, $cheque_amount, $bike_id, $party_name, $sale_notes);
                    $chq_st->execute();
                    $chq_st->close();
                }

                $led_st = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'credit',?,'customer',?,?,'sale',?,?)");
                $desc = 'Sale of Chassis: ' . $bike['chassis_number'];
                $led_st->bind_param('sdisid', $selling_date, $selling_price, $customer_id, $desc, $bike_id, $selling_price);
                $led_st->execute();
                $led_st->close();

                $_SESSION['last_sale_bike_id'] = $bike_id;
                $msg = 'Sale recorded successfully. Margin: ' . fmt_money($margin);
            } else {
                $err = 'Bike not found or already sold.';
            }
        } else {
            $err = 'Please fill all required fields.';
        }
    }

    if ($page === 'returns' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_return'])) {
        $bike_id = (int) ($_POST['bike_id'] ?? 0);
        $return_date = !empty($_POST['return_date']) ? $_POST['return_date'] : date('Y-m-d');
        $return_amount = (float) ($_POST['return_amount'] ?? 0);
        $refund_method = sanitize($_POST['refund_method'] ?? 'cash');
        $cheque_number = sanitize($_POST['cheque_number'] ?? '');
        $bank_name = sanitize($_POST['bank_name'] ?? '');
        $cheque_date = !empty($_POST['cheque_date']) ? $_POST['cheque_date'] : null;
        $return_notes = sanitize($_POST['return_notes'] ?? '');

        if ($bike_id && $return_date) {
            $conn->query("UPDATE bikes SET status='returned', return_date='$return_date', return_amount=$return_amount, return_notes='" . mysqli_real_escape_string($conn, $return_notes) . "' WHERE id=$bike_id AND status='sold'");

            if ($refund_method === 'cheque' && !empty($cheque_number)) {
                $br = $conn->query("SELECT b.*, c.name as cust_name FROM bikes b LEFT JOIN customers c ON b.customer_id=c.id WHERE b.id=$bike_id");
                $bike = $br ? $br->fetch_assoc() : null;
                $party = $bike ? ($bike['cust_name'] ?? 'Unknown') : 'Unknown';
                $chq_st = $conn->prepare("INSERT INTO cheque_register (cheque_number,bank_name,cheque_date,amount,type,status,reference_type,reference_id,party_name,notes) VALUES (?,?,?,?,'refund','pending','return',?,?,?)");
                $chq_st->bind_param('sssdiss', $cheque_number, $bank_name, $cheque_date, $return_amount, $bike_id, $party, $return_notes);
                $chq_st->execute();
                $chq_st->close();
            }

            $led_st = $conn->prepare("INSERT INTO ledger (entry_date,entry_type,amount,party_type,party_id,description,reference_type,reference_id,balance) VALUES (?,'debit',?,'customer',0,?,'return',?,?)");
            $desc = "Return for Bike ID: $bike_id";
            $led_st->bind_param('sdsid', $return_date, $return_amount, $desc, $bike_id, $return_amount);
            $led_st->execute();
            $led_st->close();

            $msg = 'Return processed successfully.';
        } else {
            $err = 'Please fill all required fields.';
        }
    }

    if ($page === 'cheques' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'clear') {
            $cid = (int) ($_POST['id'] ?? 0);
            $conn->query("UPDATE cheque_register SET status='cleared' WHERE id=$cid");
            $msg = 'Cheque marked as cleared.';
        }
        if ($action === 'bounce') {
            $cid = (int) ($_POST['id'] ?? 0);
            $conn->query("UPDATE cheque_register SET status='bounced' WHERE id=$cid");
            $msg = 'Cheque marked as bounced.';
        }
        if ($action === 'delete') {
            $cid = (int) ($_POST['id'] ?? 0);
            $conn->query("DELETE FROM cheque_register WHERE id=$cid");
            $msg = 'Cheque deleted.';
        }
    }

    if ($page === 'inventory' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($action === 'delete') {
            $bid = (int) ($_POST['id'] ?? 0);
            $conn->query("DELETE FROM bikes WHERE id=$bid");
            $msg = 'Bike deleted from inventory.';
        }
        if ($action === 'edit') {
            $bid = (int) ($_POST['id'] ?? 0);
            $color = sanitize($_POST['color'] ?? '');
            $pp = (float) ($_POST['purchase_price'] ?? 0);
            $status = sanitize($_POST['status'] ?? 'in_stock');
            $notes = sanitize($_POST['notes'] ?? '');
            $safe = sanitize($_POST['safeguard_notes'] ?? '');
            $conn->query("UPDATE bikes SET color='" . mysqli_real_escape_string($conn, $color) . "', purchase_price=$pp, status='" . mysqli_real_escape_string($conn, $status) . "', notes='" . mysqli_real_escape_string($conn, $notes) . "', safeguard_notes='" . mysqli_real_escape_string($conn, $safe) . "' WHERE id=$bid");
            $msg = 'Bike updated.';
        }
        if ($action === 'bulk_delete') {
            $ids = $_POST['selected_bikes'] ?? [];
            $ids = array_map('intval', $ids);
            if (!empty($ids)) {
                $id_str = implode(',', $ids);
                $conn->query("DELETE FROM bikes WHERE id IN ($id_str)");
                $msg = count($ids) . ' bike(s) deleted.';
            }
        }
        if ($action === 'bulk_export') {
            $ids = $_POST['selected_bikes'] ?? [];
            $ids = array_map('intval', $ids);
            if (!empty($ids)) {
                $id_str = implode(',', $ids);
                $er = $conn->query("SELECT b.*, m.model_name, m.model_code FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.id IN ($id_str)");
                $csv_data = "Sr,Chassis,Motor,Model,Color,Purchase Price,Status,Selling Price,Selling Date,Margin\n";
                $sr = 1;
                while ($row = $er->fetch_assoc()) {
                    $csv_data .= "$sr,{$row['chassis_number']},{$row['motor_number']},{$row['model_name']},{$row['color']},{$row['purchase_price']},{$row['status']},{$row['selling_price']},{$row['selling_date']},{$row['margin']}\n";
                    $sr++;
                }
                header('Content-Type: text/csv');
                header('Content-Disposition: attachment; filename="inventory_export_' . date('Ymd') . '.csv"');
                echo $csv_data;
                exit;
            }
        }
    }

    if ($page === 'settings' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
        $fields = ['company_name', 'branch_name', 'tax_rate', 'currency', 'tax_on', 'show_purchase_on_invoice'];
        $st = $conn->prepare('UPDATE settings SET setting_value=? WHERE setting_key=?');
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $val = sanitize($_POST[$f]);
                $st->bind_param('ss', $val, $f);
                $st->execute();
            }
        }
        if (!empty($_POST['new_password']) && strlen($_POST['new_password']) >= 8) {
            $np = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $st->bind_param('ss', $np, $_POST['new_password']);
            $conn->query("UPDATE settings SET setting_value='" . mysqli_real_escape_string($conn, $np) . "' WHERE setting_key='admin_password'");
        }
        $st->close();
        $msg = 'Settings saved.';
    }

    if ($page === 'settings' && isset($_GET['action']) && $_GET['action'] === 'backup') {
        $tables_list = ['settings', 'suppliers', 'customers', 'models', 'purchase_orders', 'bikes', 'cheque_register', 'payments', 'ledger'];
        $sql_dump = "-- BNI Enterprises Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n-- Author: $author\n\n";
        $sql_dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        foreach ($tables_list as $tbl) {
            $sql_dump .= "TRUNCATE TABLE `$tbl`;\n";
            $r = $conn->query("SELECT * FROM `$tbl`");
            if ($r && $r->num_rows > 0) {
                while ($row = $r->fetch_assoc()) {
                    $vals = array_map(function ($v) use ($conn) {
                        return $v === null ? 'NULL' : "'" . mysqli_real_escape_string($conn, $v) . "'";
                    }, array_values($row));
                    $cols = '`' . implode('`,`', array_keys($row)) . '`';
                    $sql_dump .= "INSERT INTO `$tbl` ($cols) VALUES (" . implode(',', $vals) . ");\n";
                }
            }
            $sql_dump .= "\n";
        }
        $sql_dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="bni_backup_' . date('Ymd_His') . '.sql"');
        echo $sql_dump;
        exit;
    }
    if ($page === 'settings' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_db']) && isset($_FILES['backup_file'])) {
        $file = $_FILES['backup_file'];
        if ($file['error'] === UPLOAD_ERR_OK && pathinfo($file['name'], PATHINFO_EXTENSION) === 'sql') {
            $sql_content = file_get_contents($file['tmp_name']);
            if ($conn->multi_query($sql_content)) {
                while ($conn->more_results() && $conn->next_result()) {
                    ;
                }
                $msg = 'Database restored successfully.';
            } else {
                $err = 'Restore failed: ' . $conn->error;
            }
        } else {
            $err = 'Invalid file uploaded. Please upload a valid .sql file.';
        }
    }
    if ($page === 'inventory' && isset($_GET['export_csv']) && $_GET['export_csv'] == 1) {
        $status_f = sanitize($_GET['status_f'] ?? '');
        $where = '1=1';
        if ($status_f && in_array($status_f, ['in_stock', 'sold', 'returned', 'reserved']))
            $where .= " AND b.status='$status_f'";
        $er = $conn->query("SELECT b.*, m.model_name, m.model_code, c.name as cust_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id WHERE $where ORDER BY b.id DESC");
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="inventory_' . date('Ymd') . '.csv"');
        echo "Sr,Chassis,Motor,Model,Color,Purchase Price,Status,Selling Price,Selling Date,Customer,Margin\n";
        $sr = 1;
        while ($row = $er->fetch_assoc()) {
            echo "$sr,\"{$row['chassis_number']}\",\"{$row['motor_number']}\",\"{$row['model_name']}\",\"{$row['color']}\",{$row['purchase_price']},{$row['status']},{$row['selling_price']},\"{$row['selling_date']}\",\"{$row['cust_name']}\",{$row['margin']}\n";
            $sr++;
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
<style>
:root {
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
.sidebar{width:var(--sidebar-w);background:var(--bg2);border-right:2px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;height:100vh;z-index:100;overflow-y:auto;transition:width 0.2s, transform 0.2s}
.sidebar-header{padding:12px 10px;border-bottom:2px solid var(--border);text-align:center}
.sidebar-header .company{font-size:0.85rem;font-weight:700;color:var(--accent);line-height:1.3}
.sidebar-header .branch{font-size:0.72rem;color:var(--text2);margin-top:2px}
.sidebar nav ul{list-style:none;padding:0;margin:0}
.sidebar nav ul li a{display:flex;align-items:center;gap:8px;padding:10px 14px;color:var(--text);font-size:0.83rem;border-bottom:1px solid var(--border);transition:background 0.15s}
.sidebar nav ul li a:hover{background:var(--surface);text-decoration:none}
.sidebar nav ul li a.active{background:var(--accent);color:#fff}
.sidebar nav ul li a .icon{font-size:1rem;min-width:18px;text-align:center}
.sidebar-footer{margin-top:auto;padding:10px;border-top:2px solid var(--border)}
.sidebar-footer form{display:inline}
.sidebar-footer button{background:var(--danger);color:#fff;border:1px solid var(--danger-h);padding:6px 14px;font-size:0.8rem;border-radius:2px;width:100%}

.main-wrap{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh;transition:margin-left 0.2s;min-width:0}
.topbar{height:var(--topbar-h);background:var(--bg2);border-bottom:2px solid var(--border);display:flex;align-items:center;padding:0 16px;position:sticky;top:0;z-index:50;gap:10px}
.topbar .hamburger{display:flex;background:none;border:1px solid var(--border);color:var(--text);padding:5px 8px;border-radius:2px;font-size:1.1rem;cursor:pointer}
@media (min-width: 601px) {
body.sidebar-collapsed { --sidebar-w: 60px; }
body.sidebar-collapsed .sidebar { overflow-x: hidden; white-space: nowrap; }
body.sidebar-collapsed .sidebar-header .company, body.sidebar-collapsed .sidebar-header .branch { display: none; }
body.sidebar-collapsed .sidebar-header { padding: 15px 0; }
body.sidebar-collapsed .sidebar-header::after { content: '⚡'; font-size: 1.4rem; display: block; text-align: center; color: var(--accent); }
body.sidebar-collapsed nav ul li a { font-size: 0; justify-content: center; padding: 12px 0; }
body.sidebar-collapsed nav ul li a .icon { font-size: 1.2rem; margin: 0; }
body.sidebar-collapsed .sidebar-footer form button { font-size: 0; padding: 10px 0; }
body.sidebar-collapsed .sidebar-footer form button::after { content: '🚪'; font-size: 1.1rem; }
}
.topbar .page-title{font-size:0.95rem;font-weight:700;color:var(--text);flex:1}
.topbar .topbar-actions{display:flex;gap:8px;align-items:center}
.topbar .topbar-actions form button{background:var(--surface);border:1px solid var(--border);color:var(--text);padding:4px 10px;font-size:0.78rem;border-radius:2px}
.topbar .topbar-actions form button:hover{background:var(--bg3)}

.content{flex:1;padding:16px;overflow-x:hidden;max-width:100%}

.toast-wrap{position:fixed;top:60px;right:16px;z-index:9999;display:flex;flex-direction:column;gap:8px}
.toast{padding:10px 18px;border-radius:2px;font-size:0.85rem;border:1px solid;min-width:220px;max-width:340px;animation:fadeIn 0.3s;font-weight:600}
.toast.success{background:#1e4d1e;border-color:var(--success);color:#b8f0b8}
.toast.error{background:#4d1e1e;border-color:var(--danger);color:#f0b8b8}
[data-theme="light"] .toast.success{background:#d4f4d4;border-color:var(--success);color:#1a4d1a}
[data-theme="light"] .toast.error{background:#f4d4d4;border-color:var(--danger);color:#4d1a1a}
@keyframes fadeIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}

.fieldset{border:2px solid var(--border);padding:12px 14px;margin-bottom:14px;border-radius:2px;min-width:0;max-width:100%}
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
.card{background:var(--bg2);border:2px solid var(--border);padding:12px 14px;border-radius:2px;display:flex;align-items:center;gap:12px}
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
.data-table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch;margin-bottom:14px;width:100%;max-width:100%}
.data-table{width:100%;border:1px solid var(--border);font-size:0.82rem}
.data-table th,.data-table td{border:1px solid var(--border);padding:6px 9px;white-space:nowrap}
.data-table th{background:var(--bg2);color:var(--text);font-weight:700;text-align:left;font-size:0.78rem;text-transform:uppercase;letter-spacing:0.3px;cursor:pointer;user-select:none}
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

.filter-bar{display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;margin-bottom:12px;padding:10px;background:var(--bg2);border:1px solid var(--border)}
.filter-bar .form-group{min-width:120px;flex:0 0 auto}
.filter-bar .form-group label{font-size:0.72rem}
.filter-bar .form-group input,.filter-bar .form-group select{font-size:0.82rem;padding:5px 7px}

.modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:500;align-items:center;justify-content:center}
.modal-overlay.open{display:flex}
.modal{background:var(--bg2);border:2px solid var(--border);padding:18px;width:90%;max-width:500px;max-height:90vh;overflow-y:auto;border-radius:2px;position:relative}
.modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;border-bottom:1px solid var(--border);padding-bottom:8px}
.modal-header h3{font-size:0.9rem;font-weight:700;color:var(--accent);text-transform:uppercase}
.modal-close{background:var(--danger);border:none;color:#fff;padding:3px 8px;font-size:0.9rem;cursor:pointer;border-radius:1px}

.bike-row{background:var(--surface);border:1px solid var(--border);padding:10px;margin-bottom:8px;border-radius:2px;position:relative}
.bike-row-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px}
.bike-row-num{font-size:0.78rem;font-weight:700;color:var(--accent);text-transform:uppercase}
.bike-row-del{background:var(--danger);border:none;color:#fff;padding:2px 8px;font-size:0.78rem;cursor:pointer;border-radius:1px}

.login-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg)}
.login-box{background:var(--bg2);border:2px solid var(--border);padding:30px;width:340px;border-radius:2px}
.login-box h2{font-size:1.1rem;font-weight:700;color:var(--accent);text-align:center;margin-bottom:4px;text-transform:uppercase}
.login-box .login-sub{font-size:0.78rem;color:var(--text2);text-align:center;margin-bottom:20px}
.login-box .form-group{margin-bottom:12px}
.login-box .login-btn{width:100%;background:var(--accent);border:1px solid var(--accent-h);color:#fff;padding:9px;font-size:0.9rem;font-weight:700;border-radius:2px;cursor:pointer;margin-top:4px}
.login-box .login-btn:hover{background:var(--accent-h)}
.login-err{color:var(--danger);font-size:0.82rem;text-align:center;margin-bottom:10px;padding:6px;background:#3d1a1a;border:1px solid var(--danger);border-radius:1px}
[data-theme="light"] .login-err{background:#f4d4d4}

.install-wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg)}
.install-box{background:var(--bg2);border:2px solid var(--accent);padding:30px;width:400px;border-radius:2px;text-align:center}
.install-box h2{color:var(--accent);font-size:1.1rem;margin-bottom:8px}
.install-box p{color:var(--text2);font-size:0.83rem;margin-bottom:18px}

.sub-tabs{display:flex;gap:0;margin-bottom:14px;border-bottom:2px solid var(--border);flex-wrap:wrap}
.sub-tab{padding:7px 14px;background:var(--surface);border:1px solid var(--border);border-bottom:none;color:var(--text);font-size:0.8rem;font-weight:600;cursor:pointer;text-decoration:none;border-radius:2px 2px 0 0;margin-right:2px}
.sub-tab:hover{background:var(--bg3);text-decoration:none;color:var(--text)}
.sub-tab.active{background:var(--accent);color:#fff;border-color:var(--accent)}
.sub-panel{display:none}
.sub-panel.active{display:block}

.invoice-wrap{background:#fff;color:#111;padding:20px;font-family:Arial,sans-serif;font-size:12px;max-width:700px;margin:0 auto;border:1px solid #ccc}
.invoice-header{text-align:center;margin-bottom:16px;border-bottom:2px solid #333;padding-bottom:10px}
.invoice-header h1{font-size:1.3rem;color:#1a1a1a;font-weight:700}
.invoice-header h2{font-size:0.85rem;color:#555;font-weight:400}
.invoice-section{margin-bottom:12px}
.invoice-section h3{font-size:0.78rem;text-transform:uppercase;font-weight:700;border-bottom:1px solid #ccc;padding-bottom:3px;margin-bottom:6px}
.invoice-table{width:100%;border-collapse:collapse;font-size:0.83rem}
.invoice-table th,.invoice-table td{border:1px solid #ccc;padding:5px 8px}
.invoice-table th{background:#f0f0f0;font-weight:700}
.invoice-total{text-align:right;font-size:0.95rem;font-weight:700;margin-top:10px;padding:8px;background:#f0f0f0;border:1px solid #ccc}
.invoice-footer{text-align:center;margin-top:16px;border-top:1px solid #ccc;padding-top:8px;font-size:0.75rem;color:#777}

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
@media print{
.sidebar,.topbar,.filter-bar,.pagination,.btn,.actions-col,.print-btn-wrap,.no-print{display:none!important}
.main-wrap{margin-left:0!important}
.content{padding:0!important}
body{background:#fff!important;color:#111!important}
.data-table th,.data-table td{color:#111!important;background:#fff!important;border-color:#666!important}
.invoice-wrap{border:none!important;padding:0!important}
}
@media(max-width:900px){
.card-grid{grid-template-columns:repeat(2,1fr)}
.split-grid-3{grid-template-columns:1fr 1fr}
}
@media(max-width:600px){
.card-grid, .split-grid, .split-grid-3{grid-template-columns:1fr}
.sidebar{transform:translateX(-100%)}
.sidebar.open{transform:translateX(0)}
.sidebar-overlay.open{display:block}
.main-wrap{margin-left:0}
.form-row{flex-direction:column}
.form-group{min-width:0}
.filter-bar{flex-direction:column}
.data-table th,.data-table td{font-size:0.75rem;padding:4px 6px}
.btn{font-size:0.78rem;padding:6px 10px}
.modal{width:98%;padding:12px}
.stats-row{flex-direction:column}
}
</style>
</head>
<body>
<script>
if (localStorage.getItem('sidebarCollapsed') === '1' && window.innerWidth > 600) {
    document.body.classList.add('sidebar-collapsed');
}
</script>
<?php if (!$db_exists): ?>
<div class="install-wrap">
<div class="install-box">
<div style="font-size:2.5rem;margin-bottom:10px">⚡</div>
<h2>BNI Enterprises Setup</h2>
<p>Welcome! The database needs to be installed. Click the button below to create the database and all required tables automatically.</p>
<?php if (isset($_GET['db_error'])): ?>
<div class="login-err">Database connection failed. Please check your credentials in index.php.</div>
<?php endif; ?>
<form method="POST">
<button type="submit" name="do_install" class="btn btn-primary" style="width:100%;font-size:0.95rem;padding:10px">⚡ Install Database</button>
</form>
<p style="margin-top:14px;font-size:0.75rem;color:var(--text3)">Author: <?= $author ?> | v<?= $app_version ?></p>
</div>
</div>
<?php elseif (!isset($_SESSION['logged_in'])): ?>
<div class="login-wrap">
<div class="login-box">
<div style="font-size:2.5rem;text-align:center;margin-bottom:8px">⚡</div>
<h2>BNI Enterprises</h2>
<div class="login-sub"><?= sanitize(get_setting('branch_name') ?? 'Dera (Ahmed Metro)') ?></div>
<?php if (isset($_GET['msg'])): ?>
<div class="login-err"><?= $_GET['msg'] === 'idle_logout' ? 'Session expired due to inactivity.' : 'Your session has expired. Please login again.' ?></div>
<?php endif; ?>
<?php if (isset($login_error)): ?>
<div class="login-err"><?= $login_error ?></div>
<?php endif; ?>
<form method="POST">
<div class="form-group"><label>Username <span class="req">*</span></label><input type="text" name="username" required autocomplete="username" placeholder="admin"></div>
<div class="form-group" style="margin-top:10px"><label>Password <span class="req">*</span></label><input type="password" name="password" required autocomplete="current-password" placeholder="••••••••"></div>
<button type="submit" name="do_login" class="login-btn" style="margin-top:14px">🔐 Login</button>
</form>
<p style="margin-top:14px;font-size:0.75rem;color:var(--text3);text-align:center">Author: <?= $author ?> | v<?= $app_version ?></p>
</div>
</div>
<?php
else:
    $company_name = get_setting('company_name') ?? 'BNI Enterprises';
    $branch_name = get_setting('branch_name') ?? 'Dera (Ahmed Metro)';
    $currency = get_setting('currency') ?? 'Rs.';
    $pages_nav = [
        ['dashboard', '⌂', 'Dashboard'],
        ['purchase', '📦', 'Purchase Entry'],
        ['inventory', '📋', 'Inventory / Stock'],
        ['sale', '🛒', 'Sales Entry'],
        ['returns', '↩', 'Returns'],
        ['cheques', '💳', 'Cheque Register'],
        ['customer_ledger', '👤', 'Customer Ledger'],
        ['supplier_ledger', '🏭', 'Supplier Ledger'],
        ['reports', '📊', 'Reports'],
        ['models', '🚲', 'Models'],
        ['customers', '👥', 'Customers'],
        ['suppliers', '🏢', 'Suppliers'],
        ['settings', '⚙', 'Settings'],
    ];
?>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<div class="sidebar" id="sidebar">
<div class="sidebar-header">
<div class="company">⚡ <?= sanitize($company_name) ?></div>
<div class="branch"><?= sanitize($branch_name) ?></div>
</div>
<nav>
<ul>
<?php foreach ($pages_nav as $pn): ?>
<li><a href="index.php?page=<?= $pn[0] ?>" class="<?= $page === $pn[0] ? 'active' : '' ?>"><span class="icon"><?= $pn[1] ?></span><?= $pn[2] ?></a></li>
<?php endforeach; ?>
</ul>
</nav>
<div class="sidebar-footer">
<form method="GET" action="index.php"><input type="hidden" name="logout" value="1"><button type="submit">🚪 Logout</button></form>
</div>
</div>
<div class="main-wrap">
<div class="topbar">
<button class="hamburger" onclick="toggleSidebar()">☰</button>
<div class="page-title">
<?php foreach ($pages_nav as $pn) { if ($pn[0] === $page) echo $pn[1] . ' ' . $pn[2]; } ?>
</div>
<div class="topbar-actions">
<form method="POST" action="index.php?<?= http_build_query(array_merge($_GET, [])) ?>">
<button type="submit" name="toggle_theme" title="Toggle Theme"><?= ($theme ?? 'dark') === 'dark' ? '☀' : '🌙' ?> Theme</button>
</form>
<span style="font-size:0.75rem;color:var(--text3)"><?= date('d/m/Y H:i') ?></span>
</div>
</div>
<div class="content">
<?php if ($msg): ?><div class="toast-wrap" id="toastWrap"><div class="toast success"><?= sanitize($msg) ?></div></div><?php endif; ?>
<?php if ($err): ?><div class="toast-wrap" id="toastWrap"><div class="toast error"><?= sanitize($err) ?></div></div><?php endif; ?>

<?php
    $per_page = 20;
    $current_pg = max(1, (int) ($_GET['pg'] ?? 1));
    $offset = ($current_pg - 1) * $per_page;

    if ($page === 'dashboard'):
        $total_stock = $conn->query("SELECT COUNT(*) as c FROM bikes WHERE status='in_stock'")->fetch_assoc()['c'];
        $total_sold = $conn->query("SELECT COUNT(*) as c FROM bikes WHERE status='sold'")->fetch_assoc()['c'];
        $total_returned = $conn->query("SELECT COUNT(*) as c FROM bikes WHERE status='returned'")->fetch_assoc()['c'];
        $total_purchase_val = $conn->query('SELECT SUM(purchase_price) as s FROM bikes')->fetch_assoc()['s'] ?? 0;
        $total_sales_val = $conn->query("SELECT SUM(selling_price) as s FROM bikes WHERE status='sold'")->fetch_assoc()['s'] ?? 0;
        $total_tax = $conn->query("SELECT SUM(tax_amount) as s FROM bikes WHERE status='sold'")->fetch_assoc()['s'] ?? 0;
        $total_margin = $conn->query("SELECT SUM(margin) as s FROM bikes WHERE status='sold'")->fetch_assoc()['s'] ?? 0;
        $chq_issued = $conn->query("SELECT COUNT(*) as c, SUM(amount) as s FROM cheque_register WHERE type='payment'")->fetch_assoc();
        $chq_received = $conn->query("SELECT COUNT(*) as c, SUM(amount) as s FROM cheque_register WHERE type='receipt'")->fetch_assoc();
        $pending_cheques = $conn->query("SELECT COUNT(*) as c, SUM(amount) as s FROM cheque_register WHERE status='pending'")->fetch_assoc();
?>
<div class="card-grid">
<div class="card accent"><div class="card-icon">📦</div><div class="card-body"><div class="card-label">In Stock</div><div class="card-value"><?= number_format($total_stock) ?></div><div class="card-sub">bikes</div></div></div>
<div class="card success"><div class="card-icon">✅</div><div class="card-body"><div class="card-label">Total Sold</div><div class="card-value"><?= number_format($total_sold) ?></div><div class="card-sub">bikes</div></div></div>
<div class="card danger"><div class="card-icon">↩</div><div class="card-body"><div class="card-label">Returned</div><div class="card-value"><?= number_format($total_returned) ?></div><div class="card-sub">bikes</div></div></div>
<div class="card warning"><div class="card-icon">💰</div><div class="card-body"><div class="card-label">Purchase Value</div><div class="card-value" style="font-size:1rem"><?= $currency ?> <?= number_format($total_purchase_val) ?></div></div></div>
<div class="card success"><div class="card-icon">💵</div><div class="card-body"><div class="card-label">Sales Value</div><div class="card-value" style="font-size:1rem"><?= $currency ?> <?= number_format($total_sales_val) ?></div></div></div>
<div class="card"><div class="card-icon">🧾</div><div class="card-body"><div class="card-label">Total Tax Paid</div><div class="card-value" style="font-size:1rem"><?= $currency ?> <?= number_format($total_tax, 2) ?></div></div></div>
<div class="card success"><div class="card-icon">📈</div><div class="card-body"><div class="card-label">Total Profit</div><div class="card-value" style="font-size:1rem;color:var(--success)"><?= $currency ?> <?= number_format($total_margin) ?></div></div></div>
<div class="card"><div class="card-icon">💳</div><div class="card-body"><div class="card-label">Pending Cheques</div><div class="card-value" style="font-size:1rem;color:var(--warning)"><?= number_format($pending_cheques['c']) ?></div><div class="card-sub"><?= $currency ?> <?= number_format($pending_cheques['s'] ?? 0) ?></div></div></div>
</div>
<?php if ($pending_cheques['c'] > 0): ?>
<div style="background:#3d2a00;border:1px solid var(--warning);padding:8px 14px;margin-bottom:12px;border-radius:2px;font-size:0.82rem;color:#f0c858">
⚠ <strong><?= $pending_cheques['c'] ?> pending cheque(s)</strong> totaling <?= $currency ?> <?= number_format($pending_cheques['s'] ?? 0) ?> — <a href="index.php?page=cheques">View Cheques →</a>
</div>
<?php endif; ?>

<fieldset class="fieldset"><legend>📊 Model-wise Stock Summary</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Model</th><th>Category</th><th>Inventory</th><th>Sold</th><th>Returned</th><th>Available</th></tr></thead>
<tbody>
<?php
        $model_summary = $conn->query("SELECT m.model_name, m.category,
    SUM(CASE WHEN 1=1 THEN 1 ELSE 0 END) as total_inv,
    SUM(CASE WHEN b.status='sold' THEN 1 ELSE 0 END) as sold_cnt,
    SUM(CASE WHEN b.status='returned' THEN 1 ELSE 0 END) as ret_cnt,
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
<fieldset class="fieldset"><legend>🛒 Recent 10 Sales</legend>
<div class="data-table-wrap">
<table class="data-table">
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

<fieldset class="fieldset"><legend>📦 Recent 10 Purchases</legend>
<div class="data-table-wrap">
<table class="data-table">
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
<form method="POST" id="purchaseForm">
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
<button type="button" class="btn btn-default btn-sm" onclick="document.getElementById('addSupplierModal').classList.add('open')">+</button>
</div>
</div>
</div>
<div class="form-row">
<div class="form-group"><label>Cheque Number</label><input type="text" name="cheque_number" placeholder="CHQ-001"></div>
<div class="form-group"><label>Bank Name</label><input type="text" name="bank_name" placeholder="HBL, MCB, etc."></div>
<div class="form-group"><label>Cheque Date</label><input type="date" name="cheque_date"></div>
<div class="form-group"><label>Cheque Amount</label><input type="number" name="cheque_amount" step="0.01" min="0" placeholder="0.00"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Notes</label><textarea name="po_notes" rows="2" placeholder="Any additional notes..."></textarea></div>
</div>
</fieldset>

<fieldset class="fieldset"><legend>🚲 Bike Units</legend>
<div id="bikesList"></div>
<button type="button" class="btn btn-success" onclick="addBikeRow()" style="margin-top:6px">+ Add Bike</button>
</fieldset>

<div style="display:flex;gap:8px;flex-wrap:wrap">
<button type="submit" name="save_purchase" class="btn btn-primary">💾 Save Purchase Order</button>
<a href="index.php?page=inventory" class="btn btn-default">← Back to Inventory</a>
</div>
</form>

<div class="modal-overlay" id="addSupplierModal">
<div class="modal">
<div class="modal-header"><h3>Add New Supplier</h3><button class="modal-close" onclick="document.getElementById('addSupplierModal').classList.remove('open')">✕</button></div>
<form method="POST" action="index.php?page=suppliers&action=add">
<div class="form-group" style="margin-bottom:8px"><label>Name <span class="req">*</span></label><input type="text" name="name" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Contact</label><input type="text" name="contact"></div>
<div class="form-group" style="margin-bottom:12px"><label>Address</label><textarea name="address" rows="2"></textarea></div>
<button type="submit" class="btn btn-primary">Save Supplier</button>
</form>
</div>
</div>

<div class="modal-overlay" id="addModelModal">
<div class="modal">
<div class="modal-header"><h3>Add New Model</h3><button class="modal-close" onclick="document.getElementById('addModelModal').classList.remove('open')">✕</button></div>
<form method="POST" action="index.php?page=models&action=add">
<div class="form-group" style="margin-bottom:8px"><label>Model Code <span class="req">*</span></label><input type="text" name="model_code" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Model Name <span class="req">*</span></label><input type="text" name="model_name" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Category</label><input type="text" name="category" value="Electric Bike"></div>
<div class="form-group" style="margin-bottom:12px"><label>Short Code</label><input type="text" name="short_code"></div>
<button type="submit" class="btn btn-primary">Save Model</button>
</form>
</div>
</div>

<script>
var prefillModelId = <?= (int) ($_GET['model_id'] ?? 0) ?>;
var bikeCount = 0;
var modelsOptions = `<?php $models_list->data_seek(0);
        $mo = '';
        while ($m = $models_list->fetch_assoc())
            $mo .= '<option value="' . $m['id'] . '">' . $m['model_code'] . ' - ' . $m['model_name'] . '</option>';
        echo $mo; ?>`;
function addBikeRow() {
    bikeCount++;
    var d = document.createElement('div');
    d.className = 'bike-row';
    d.id = 'bikeRow_'+bikeCount;
    d.innerHTML = `<div class="bike-row-header"><span class="bike-row-num">🚲 Bike #${bikeCount}</span><button type="button" class="bike-row-del" onclick="removeBikeRow(${bikeCount})">✕ Remove</button></div>
    <div class="form-row">
    <div class="form-group"><label>Chassis Number <span class="req">*</span></label><input type="text" name="bikes[${bikeCount}][chassis]" required placeholder="e.g. KIU-2024-001" onblur="checkChassis(this)"></div>
    <div class="form-group"><label>Motor Number</label><input type="text" name="bikes[${bikeCount}][motor]" placeholder="e.g. MT-001"></div>
    <div class="form-group"><label>Model <span class="req">*</span></label>
    <div style="display:flex;gap:4px"><select name="bikes[${bikeCount}][model_id]" required style="flex:1"><option value="">-- Model --</option>${modelsOptions}</select>
    <button type="button" class="btn btn-default btn-sm" onclick="document.getElementById('addModelModal').classList.add('open')">+</button></div></div>
    </div>
    <div class="form-row">
    <div class="form-group"><label>Color</label><input type="text" name="bikes[${bikeCount}][color]" placeholder="Red, Black, White..."></div>
    <div class="form-group"><label>Purchase Price (Rs.) <span class="req">*</span></label><input type="number" name="bikes[${bikeCount}][purchase_price]" step="0.01" min="0" required placeholder="0.00"></div>
    <div class="form-group"><label>Safeguard Notes</label><input type="text" name="bikes[${bikeCount}][safeguard_notes]" placeholder="Helmet, Tyre, Warranty..."></div>
    </div>
    <div class="form-row">
    <div class="form-group"><label>Accessories</label><input type="text" name="bikes[${bikeCount}][accessories]" placeholder="Helmet, Charger, Basket..."></div>
    <div class="form-group"><label>Notes</label><input type="text" name="bikes[${bikeCount}][notes]" placeholder="Any notes..."></div>
    </div>`;
    document.getElementById('bikesList').appendChild(d);
    if (prefillModelId && bikeCount === 1) {
        document.querySelector(`select[name="bikes[${bikeCount}][model_id]"]`).value = prefillModelId;
    }
}
function removeBikeRow(n) {
    var el = document.getElementById('bikeRow_'+n);
    if (el) el.remove();
}
function checkChassis(inp) {
    var val = inp.value.trim();
    if (!val) return;
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'index.php?ajax=check_chassis&chassis='+encodeURIComponent(val));
    xhr.onload = function() {
        if (xhr.responseText === '1') {
            inp.style.borderColor = '#e74c3c';
            inp.title = 'WARNING: This chassis number already exists!';
            alert('WARNING: Chassis number "' + val + '" already exists in the system!');
        } else {
            inp.style.borderColor = '#4ec94e';
            inp.title = 'Chassis number is unique.';
        }
    };
    xhr.send();
}
addBikeRow();
</script>

<?php
    elseif ($page === 'inventory'):
        $status_f = sanitize($_GET['status_f'] ?? '');
        $model_f = (int) ($_GET['model_f'] ?? 0);
        $color_f = sanitize($_GET['color_f'] ?? '');
        $search_f = sanitize($_GET['search_f'] ?? '');
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';

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

        $total_rows_r = $conn->query("SELECT COUNT(*) as c FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE $where");
        $total_rows = $total_rows_r->fetch_assoc()['c'];
        $total_pages = ceil($total_rows / $per_page);

        $bikes_result = $conn->query("SELECT b.*, m.model_name, m.model_code, c.name as cust_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id WHERE $where ORDER BY b.created_at DESC LIMIT $per_page OFFSET $offset");
        $models_filter_list = $conn->query('SELECT id, model_code FROM models ORDER BY model_name');
        $sort_col = sanitize($_GET['sort'] ?? 'id');
        $sort_dir = sanitize($_GET['dir'] ?? 'desc');
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
<fieldset class="fieldset"><legend>🚲 Bike Details — <?= sanitize($view_bike['chassis_number']) ?></legend>
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
                ['Margin', $view_bike['margin'] ? fmt_money($view_bike['margin']) : '-'],
                ['Order Date', fmt_date($view_bike['order_date'])],
                ['Inventory Date', fmt_date($view_bike['inventory_date'])],
                ['Selling Date', fmt_date($view_bike['selling_date'])],
                ['Customer', $view_bike['cust_name'] ?? '-'],
                ['Customer Phone', $view_bike['cust_phone'] ?? '-'],
                ['Supplier', $view_bike['sup_name'] ?? '-'],
                ['Accessories', $view_bike['accessories'] ?? '-'],
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
<?php if ($view_bike['status'] === 'returned' || $view_bike['return_date']): ?>
<li><div class="timeline-dot" style="background:#e74c3c"></div><div class="timeline-content"><div class="timeline-date"><?= fmt_date($view_bike['return_date']) ?></div><div class="timeline-text">↩ <strong>Returned</strong> — Amount: <?= fmt_money($view_bike['return_amount']) ?> | Notes: <?= sanitize($view_bike['return_notes'] ?? '-') ?></div></div></li>
<?php endif; ?>
</ul>
</fieldset>
<?php else: ?>

<form method="POST" id="bulkForm" action="index.php?page=inventory&action=bulk_delete">
<div class="filter-bar no-print">
<div class="form-group"><label>Search</label><input type="text" name="search_f" value="<?= sanitize($search_f) ?>" placeholder="Chassis, Motor, Model, Color" onchange="this.form.submit()" form="filterForm"></div>
<div class="form-group"><label>Status</label>
<select name="status_f" onchange="this.form.submit()" form="filterForm">
<option value="">All</option>
<option value="in_stock" <?= $status_f === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
<option value="sold" <?= $status_f === 'sold' ? 'selected' : '' ?>>Sold</option>
<option value="returned" <?= $status_f === 'returned' ? 'selected' : '' ?>>Returned</option>
<option value="reserved" <?= $status_f === 'reserved' ? 'selected' : '' ?>>Reserved</option>
</select>
</div>
<div class="form-group"><label>Model</label>
<select name="model_f" onchange="this.form.submit()" form="filterForm">
<option value="0">All Models</option>
<?php $models_filter_list->data_seek(0);
            while ($mf = $models_filter_list->fetch_assoc()): ?>
<option value="<?= $mf['id'] ?>" <?= $model_f == $mf['id'] ? 'selected' : '' ?>><?= sanitize($mf['model_code']) ?></option>
<?php endwhile; ?>
</select>
</div>
<div class="form-group"><label>Color</label><input type="text" name="color_f" value="<?= sanitize($color_f) ?>" placeholder="Color" onchange="this.form.submit()" form="filterForm"></div>
<div class="form-group"><label>From</label><input type="date" name="date_from" value="<?= $date_from ?>" onchange="this.form.submit()" form="filterForm"></div>
<div class="form-group"><label>To</label><input type="date" name="date_to" value="<?= $date_to ?>" onchange="this.form.submit()" form="filterForm"></div>
<div class="form-group" style="justify-content:flex-end">
<a href="index.php?page=inventory&export_csv=1&status_f=<?= urlencode($status_f) ?>&model_f=<?= $model_f ?>&color_f=<?= urlencode($color_f) ?>&search_f=<?= urlencode($search_f) ?>" class="btn btn-default btn-sm">⬇ CSV</a>
</div>
</div>
<form method="GET" id="filterForm" action="index.php">
<input type="hidden" name="page" value="inventory">
</form>

<div style="display:flex;gap:8px;margin-bottom:8px;flex-wrap:wrap;align-items:center" class="no-print">
<span style="font-size:0.8rem;color:var(--text2)">Showing <?= $total_rows ?> record(s)</span>
<a href="index.php?page=purchase" class="btn btn-success btn-sm">+ New Purchase</a>
<button type="submit" name="bulk_action" value="bulk_delete" class="btn btn-danger btn-sm" onclick="return confirm('Delete selected bikes?')">🗑 Delete Selected</button>
<button type="submit" form="bulkExportForm" class="btn btn-default btn-sm">⬇ Export Selected</button>
<button onclick="window.print()" type="button" class="btn btn-default btn-sm">🖨 Print</button>
<button type="button" class="btn btn-default btn-sm" onclick="toggleSelectAll()">☑ Select All</button>
</div>

<div class="data-table-wrap">
<table class="data-table" id="invTable">
<thead>
<tr>
<th style="width:30px"><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
<th onclick="sortTable(1)">Sr#</th>
<th onclick="sortTable(2)">Chassis</th>
<th onclick="sortTable(3)">Motor#</th>
<th onclick="sortTable(4)">Model</th>
<th onclick="sortTable(5)">Color</th>
<th onclick="sortTable(6)">Purchase Price</th>
<th onclick="sortTable(7)">Status</th>
<th onclick="sortTable(8)">Selling Price</th>
<th onclick="sortTable(9)">Selling Date</th>
<th onclick="sortTable(10)">Margin</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php
            $sr = $offset + 1;
            $total_pp = 0;
            $total_sp = 0;
            $total_mg = 0;
            while ($bike = $bikes_result->fetch_assoc()):
                $st_badge = $bike['status'] === 'sold' ? 'badge-success' : ($bike['status'] === 'returned' ? 'badge-danger' : ($bike['status'] === 'reserved' ? 'badge-warning' : 'badge-info'));
                $total_pp += $bike['purchase_price'];
                $total_sp += $bike['selling_price'] ?? 0;
                $total_mg += $bike['margin'] ?? 0;
?>
<tr class="row-<?= $bike['status'] ?>">
<td><input type="checkbox" name="selected_bikes[]" value="<?= $bike['id'] ?>" class="bike-check"></td>
<td><?= $sr++ ?></td>
<td style="font-family:Consolas,monospace;font-size:0.8rem"><?= sanitize($bike['chassis_number']) ?></td>
<td style="font-family:Consolas,monospace;font-size:0.8rem"><?= sanitize($bike['motor_number'] ?? '-') ?></td>
<td><?= sanitize($bike['model_name'] ?? '-') ?></td>
<td><?= sanitize($bike['color'] ?? '-') ?></td>
<td><?= fmt_money($bike['purchase_price']) ?></td>
<td><span class="badge <?= $st_badge ?>"><?= strtoupper($bike['status']) ?></span></td>
<td><?= $bike['selling_price'] ? fmt_money($bike['selling_price']) : '-' ?></td>
<td><?= fmt_date($bike['selling_date']) ?></td>
<td style="color:<?= ($bike['margin'] ?? 0) >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= $bike['status'] === 'sold' ? fmt_money($bike['margin']) : '-' ?></td>
<td>
<div class="actions-col">
<a href="index.php?page=inventory&view_id=<?= $bike['id'] ?>" class="btn btn-default btn-sm" title="View">👁</a>
<?php if ($bike['status'] === 'in_stock'): ?>
<a href="index.php?page=sale&bike_id=<?= $bike['id'] ?>" class="btn btn-success btn-sm" title="Sell">🛒</a>
<?php endif; ?>
<?php if ($bike['status'] === 'sold'): ?>
<a href="index.php?page=returns&bike_id=<?= $bike['id'] ?>" class="btn btn-warning btn-sm" title="Return">↩</a>
<?php endif; ?>
<a href="index.php?page=inventory&edit_id=<?= $bike['id'] ?>" class="btn btn-primary btn-sm" title="Edit">✏</a>
<form method="POST" action="index.php?page=inventory&action=delete" style="display:inline" onsubmit="return confirm('Delete this bike? This cannot be undone.')">
<input type="hidden" name="id" value="<?= $bike['id'] ?>">
<button type="submit" class="btn btn-danger btn-sm" title="Delete">🗑</button>
</form>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot>
<tr>
<td colspan="6"><strong>PAGE TOTAL</strong></td>
<td><strong><?= fmt_money($total_pp) ?></strong></td>
<td></td>
<td><strong><?= fmt_money($total_sp) ?></strong></td>
<td></td>
<td style="color:<?= $total_mg >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><strong><?= fmt_money($total_mg) ?></strong></td>
<td></td>
</tr>
</tfoot>
</table>
</div>
</form>

<form method="POST" id="bulkExportForm" action="index.php?page=inventory&action=bulk_export">
<div id="hiddenBikeIds"></div>
</form>

<?php
            $qstr = http_build_query(['page' => 'inventory', 'status_f' => $status_f, 'model_f' => $model_f, 'color_f' => $color_f, 'search_f' => $search_f, 'date_from' => $date_from, 'date_to' => $date_to]);
            if ($total_pages > 1):
?>
<div class="pagination no-print">
<?php if ($current_pg > 1): ?><a href="index.php?<?= $qstr ?>&pg=<?= $current_pg - 1 ?>">‹ Prev</a><?php endif; ?>
<?php for ($i = max(1, $current_pg - 2); $i <= min($total_pages, $current_pg + 2); $i++): ?>
<a href="index.php?<?= $qstr ?>&pg=<?= $i ?>" class="<?= $i == $current_pg ? 'active-page' : '' ?>"><?= $i ?></a>
<?php endfor; ?>
<?php if ($current_pg < $total_pages): ?><a href="index.php?<?= $qstr ?>&pg=<?= $current_pg + 1 ?>">Next ›</a><?php endif; ?>
<span>Page <?= $current_pg ?> of <?= $total_pages ?> | Total: <?= $total_rows ?> bikes</span>
</div>
<?php endif; ?>

<?php if ($edit_bike): ?>
<div class="modal-overlay open" id="editBikeModal">
<div class="modal">
<div class="modal-header"><h3>✏ Edit Bike — <?= sanitize($edit_bike['chassis_number']) ?></h3><a href="index.php?page=inventory" class="modal-close">✕</a></div>
<form method="POST" action="index.php?page=inventory&action=edit">
<input type="hidden" name="id" value="<?= $edit_bike['id'] ?>">
<div class="form-group" style="margin-bottom:8px"><label>Color</label><input type="text" name="color" value="<?= sanitize($edit_bike['color']) ?>"></div>
<div class="form-group" style="margin-bottom:8px"><label>Purchase Price</label><input type="number" name="purchase_price" step="0.01" value="<?= $edit_bike['purchase_price'] ?>"></div>
<div class="form-group" style="margin-bottom:8px"><label>Status</label>
<select name="status">
<option value="in_stock" <?= $edit_bike['status'] === 'in_stock' ? 'selected' : '' ?>>In Stock</option>
<option value="sold" <?= $edit_bike['status'] === 'sold' ? 'selected' : '' ?>>Sold</option>
<option value="returned" <?= $edit_bike['status'] === 'returned' ? 'selected' : '' ?>>Returned</option>
<option value="reserved" <?= $edit_bike['status'] === 'reserved' ? 'selected' : '' ?>>Reserved</option>
</select>
</div>
<div class="form-group" style="margin-bottom:8px"><label>Safeguard Notes</label><input type="text" name="safeguard_notes" value="<?= sanitize($edit_bike['safeguard_notes'] ?? '') ?>"></div>
<div class="form-group" style="margin-bottom:12px"><label>Notes</label><textarea name="notes" rows="2"><?= sanitize($edit_bike['notes'] ?? '') ?></textarea></div>
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
function sortTable(col) {
    var table = document.getElementById('invTable');
    var tbody = table.tBodies[0];
    var rows = Array.from(tbody.rows);
    var asc = table.dataset.sortCol == col && table.dataset.sortDir == 'asc';
    rows.sort(function(a,b){
        var av = a.cells[col]?a.cells[col].innerText.replace(/[^0-9.-]/g,''):'';
        var bv = b.cells[col]?b.cells[col].innerText.replace(/[^0-9.-]/g,''):'';
        var an = parseFloat(av), bn = parseFloat(bv);
        if (!isNaN(an) && !isNaN(bn)) return asc ? an-bn : bn-an;
        return asc ? av.localeCompare(bv) : bv.localeCompare(av);
    });
    rows.forEach(function(r){ tbody.appendChild(r); });
    table.dataset.sortCol = col;
    table.dataset.sortDir = asc ? 'desc' : 'asc';
}
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
        $customers_list = $conn->query('SELECT id, name, phone FROM customers ORDER BY name');
        $last_sale_bike_id = $_SESSION['last_sale_bike_id'] ?? 0;
        unset($_SESSION['last_sale_bike_id']);
?>
<form method="POST" id="saleForm">
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
<div class="form-group"><label>Tax Amount (<?= get_setting('tax_rate') ?? 0.1 ?>% of <?= get_setting('tax_on') === 'selling_price' ? 'Selling' : 'Purchase' ?> Price)</label><input type="text" id="taxDisplay" readonly style="background:var(--bg3);color:var(--text2)" placeholder="Auto-calculated"></div>
<div class="form-group"><label>Margin / Profit</label><input type="text" id="marginDisplay" readonly style="background:var(--bg3);font-weight:700" placeholder="Auto-calculated"></div>
</div>
<div class="form-row">
<div class="form-group">
<label>Customer</label>
<div style="display:flex;gap:4px">
<select name="customer_id" id="customerSel" style="flex:1">
<option value="0">-- Walk-in / Cash Customer --</option>
<?php $customers_list->data_seek(0);
        while ($cl = $customers_list->fetch_assoc()): ?>
<option value="<?= $cl['id'] ?>"><?= sanitize($cl['name']) ?> — <?= sanitize($cl['phone']) ?></option>
<?php endwhile; ?>
</select>
<button type="button" class="btn btn-default btn-sm" onclick="document.getElementById('addCustModal').classList.add('open')">+</button>
</div>
</div>
<div class="form-group"><label>Payment Method <span class="req">*</span></label>
<select name="payment_type" id="payType" onchange="toggleChequeFields(this.value)">
<option value="cash">Cash</option>
<option value="cheque">Cheque</option>
<option value="bank_transfer">Bank Transfer</option>
<option value="online">Online</option>
</select>
</div>
</div>
<div id="chequeFields" style="display:none">
<div class="form-row">
<div class="form-group"><label>Cheque Number</label><input type="text" name="cheque_number" placeholder="CHQ-001"></div>
<div class="form-group"><label>Bank Name</label><input type="text" name="bank_name" placeholder="HBL, MCB..."></div>
<div class="form-group"><label>Cheque Date</label><input type="date" name="cheque_date"></div>
<div class="form-group"><label>Cheque Amount</label><input type="number" name="cheque_amount" step="0.01" min="0" placeholder="0.00"></div>
</div>
</div>
<div class="form-row">
<div class="form-group"><label>Accessories Given</label><input type="text" name="accessories" placeholder="Helmet, Charger, Lock..."></div>
<div class="form-group"><label>Notes</label><input type="text" name="sale_notes" placeholder="Any notes..."></div>
</div>
</fieldset>
<div style="display:flex;gap:8px;flex-wrap:wrap">
<button type="submit" name="save_sale" class="btn btn-success">💾 Record Sale</button>
<a href="index.php?page=inventory" class="btn btn-default">← Back to Inventory</a>
</div>
</form>

<?php if ($last_sale_bike_id): ?>
<div style="margin-top:16px">
<a href="index.php?page=sale&print_invoice=<?= $last_sale_bike_id ?>" class="btn btn-primary" target="_blank">🖨 Print Invoice</a>
</div>
<?php endif; ?>

<?php
        $print_inv_id = (int) ($_GET['print_invoice'] ?? 0);
        if ($print_inv_id):
            $inv_r = $conn->query("SELECT b.*, m.model_name, m.model_code, m.category, c.name as cust_name, c.phone as cust_phone, c.cnic as cust_cnic, c.address as cust_addr FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id WHERE b.id=$print_inv_id");
            $inv = $inv_r ? $inv_r->fetch_assoc() : null;
            $show_pp = get_setting('show_purchase_on_invoice') == '1';
            $inv_no = 'INV-' . date('Ymd') . '-' . str_pad($print_inv_id, 3, '0', STR_PAD_LEFT);
            if ($inv):
?>
<div class="invoice-wrap" id="invoiceArea">
<div class="invoice-header">
<h1>⚡ <?= sanitize(get_setting('company_name') ?? 'BNI Enterprises') ?></h1>
<h2><?= sanitize(get_setting('branch_name') ?? 'Dera (Ahmed Metro)') ?></h2>
<div style="font-size:0.8rem;margin-top:4px">Sale Invoice</div>
</div>
<div style="display:flex;justify-content:space-between;margin-bottom:12px;font-size:0.82rem">
<div><strong>Invoice #:</strong> <?= $inv_no ?><br><strong>Date:</strong> <?= fmt_date($inv['selling_date']) ?></div>
<div style="text-align:right"><strong>Customer:</strong> <?= sanitize($inv['cust_name'] ?? 'Walk-in Customer') ?><br>
<?php if ($inv['cust_phone']): ?><strong>Phone:</strong> <?= sanitize($inv['cust_phone']) ?><br><?php endif; ?>
<?php if ($inv['cust_cnic']): ?><strong>CNIC:</strong> <?= sanitize($inv['cust_cnic']) ?><?php endif; ?>
</div>
</div>
<div class="invoice-section">
<h3>Bike Details</h3>
<table class="invoice-table">
<thead><tr><th>Field</th><th>Details</th></tr></thead>
<tbody>
<tr><td>Model</td><td><?= sanitize($inv['model_name']) ?> (<?= sanitize($inv['model_code']) ?>)</td></tr>
<tr><td>Category</td><td><?= sanitize($inv['category']) ?></td></tr>
<tr><td>Chassis No.</td><td style="font-family:Consolas,monospace"><?= sanitize($inv['chassis_number']) ?></td></tr>
<tr><td>Motor No.</td><td style="font-family:Consolas,monospace"><?= sanitize($inv['motor_number'] ?? '-') ?></td></tr>
<tr><td>Color</td><td><?= sanitize($inv['color']) ?></td></tr>
<?php if ($inv['accessories']): ?><tr><td>Accessories</td><td><?= sanitize($inv['accessories']) ?></td></tr><?php endif; ?>
</tbody>
</table>
</div>
<div class="invoice-section">
<h3>Payment Details</h3>
<table class="invoice-table">
<thead><tr><th>Description</th><th style="text-align:right">Amount</th></tr></thead>
<tbody>
<?php if ($show_pp): ?>
<tr><td>Purchase Price</td><td style="text-align:right"><?= fmt_money($inv['purchase_price']) ?></td></tr>
<?php endif; ?>
<tr><td>Selling Price</td><td style="text-align:right"><?= fmt_money($inv['selling_price']) ?></td></tr>
<tr><td>Tax (<?= get_setting('tax_rate') ?? 0.1 ?>%)</td><td style="text-align:right"><?= fmt_money($inv['tax_amount']) ?></td></tr>
</tbody>
</table>
<div class="invoice-total">Total Amount: <?= fmt_money($inv['selling_price']) ?></div>
</div>
<div class="invoice-footer">Thank you for your purchase! — <?= sanitize(get_setting('company_name') ?? 'BNI Enterprises') ?>, <?= sanitize(get_setting('branch_name') ?? '') ?></div>
</div>
<div class="no-print" style="margin-top:10px"><button onclick="window.print()" class="btn btn-primary">🖨 Print Invoice</button></div>
<?php endif; ?>
<?php endif; ?>

<div class="modal-overlay" id="addCustModal">
<div class="modal">
<div class="modal-header"><h3>Add New Customer</h3><button class="modal-close" onclick="document.getElementById('addCustModal').classList.remove('open')">✕</button></div>
<form method="POST" action="index.php?page=customers&action=add">
<div class="form-group" style="margin-bottom:8px"><label>Name <span class="req">*</span></label><input type="text" name="name" required></div>
<div class="form-group" style="margin-bottom:8px"><label>Phone</label><input type="text" name="phone"></div>
<div class="form-group" style="margin-bottom:8px"><label>CNIC</label><input type="text" name="cnic" placeholder="XXXXX-XXXXXXX-X"></div>
<div class="form-group" style="margin-bottom:12px"><label>Address</label><textarea name="address" rows="2"></textarea></div>
<button type="submit" class="btn btn-primary">Save Customer</button>
</form>
</div>
</div>

<script>
var taxRate = <?= (float) (get_setting('tax_rate') ?? 0.1) ?>;
var taxOn = '<?= get_setting('tax_on') ?? 'purchase_price' ?>';
function fillBikeDetails(sel) {
    var opt = sel.options[sel.selectedIndex];
    var pp = opt.dataset.pp || 0;
    document.getElementById('purchasePriceDisplay').value = pp ? parseFloat(pp).toLocaleString('en-PK',{minimumFractionDigits:2}) : '';
    calcMargin();
}
function calcMargin() {
    var sp = parseFloat(document.getElementById('sellingPrice').value) || 0;
    var pp = parseFloat(document.getElementById('purchasePriceDisplay').value.replace(/,/g,'')) || 0;
    var base = taxOn === 'selling_price' ? sp : pp;
    var tax = (base * taxRate) / 100;
    var margin = sp - pp - tax;
    document.getElementById('taxDisplay').value = 'Rs. ' + tax.toFixed(2);
    var md = document.getElementById('marginDisplay');
    md.value = 'Rs. ' + margin.toFixed(2);
    md.style.color = margin >= 0 ? '#4ec94e' : '#e74c3c';
}
function toggleChequeFields(val) {
    document.getElementById('chequeFields').style.display = val === 'cheque' ? 'block' : 'none';
}
window.onload = function() {
    var sel = document.getElementById('bikeSelect');
    if (sel.value) fillBikeDetails(sel);
};
</script>

<?php
    elseif ($page === 'returns'):
        $sold_bikes = $conn->query("SELECT b.id, b.chassis_number, b.color, b.selling_price, b.purchase_price, m.model_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.status='sold' ORDER BY b.selling_date DESC");
        $prefill_ret_id = (int) ($_GET['bike_id'] ?? 0);
?>
<form method="POST">
<fieldset class="fieldset"><legend>↩ Return / Adjustment</legend>
<div class="form-row">
<div class="form-group">
<label>Select Sold Bike <span class="req">*</span></label>
<select name="bike_id" required>
<option value="">-- Select Bike --</option>
<?php while ($sb = $sold_bikes->fetch_assoc()): ?>
<option value="<?= $sb['id'] ?>" <?= $prefill_ret_id == $sb['id'] ? 'selected' : '' ?>><?= sanitize($sb['chassis_number']) ?> | <?= sanitize($sb['model_name']) ?> | <?= sanitize($sb['color']) ?> | Sold: <?= fmt_money($sb['selling_price']) ?></option>
<?php endwhile; ?>
</select>
</div>
<div class="form-group"><label>Return Date <span class="req">*</span></label><input type="date" name="return_date" value="<?= date('Y-m-d') ?>" required></div>
<div class="form-group"><label>Return Amount (<?= $currency ?>) <span class="req">*</span></label><input type="number" name="return_amount" step="0.01" min="0" required placeholder="0.00"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Refund Method <span class="req">*</span></label>
<select name="refund_method" id="refundMethod" onchange="toggleRetCheque(this.value)">
<option value="cash">Cash</option>
<option value="cheque">Cheque</option>
</select>
</div>
</div>
<div id="retChequeFields" style="display:none">
<div class="form-row">
<div class="form-group"><label>Cheque Number</label><input type="text" name="cheque_number" placeholder="CHQ-001"></div>
<div class="form-group"><label>Bank Name</label><input type="text" name="bank_name" placeholder="HBL, MCB..."></div>
<div class="form-group"><label>Cheque Date</label><input type="date" name="cheque_date"></div>
</div>
</div>
<div class="form-row">
<div class="form-group"><label>Return Notes</label><textarea name="return_notes" rows="3" placeholder="Reason for return, cheque details, account number, etc."></textarea></div>
</div>
</fieldset>
<button type="submit" name="save_return" class="btn btn-warning">↩ Process Return</button>
<a href="index.php?page=inventory" class="btn btn-default">← Cancel</a>
</form>
<script>
function toggleRetCheque(v) {
    document.getElementById('retChequeFields').style.display = v==='cheque'?'block':'none';
}
</script>

<?php
    elseif ($page === 'cheques'):
        $chq_status_f = sanitize($_GET['chq_status'] ?? '');
        $chq_type_f = sanitize($_GET['chq_type'] ?? '');
        $chq_bank_f = sanitize($_GET['chq_bank'] ?? '');
        $chq_from = $_GET['chq_from'] ?? '';
        $chq_to = $_GET['chq_to'] ?? '';
        $chq_where = ['1=1'];
        if ($chq_status_f && in_array($chq_status_f, ['pending', 'cleared', 'bounced', 'cancelled']))
            $chq_where[] = "status='$chq_status_f'";
        if ($chq_type_f && in_array($chq_type_f, ['payment', 'receipt', 'refund']))
            $chq_where[] = "type='$chq_type_f'";
        if ($chq_bank_f)
            $chq_where[] = "bank_name LIKE '%" . mysqli_real_escape_string($conn, $chq_bank_f) . "%'";
        if ($chq_from)
            $chq_where[] = "cheque_date >= '" . mysqli_real_escape_string($conn, $chq_from) . "'";
        if ($chq_to)
            $chq_where[] = "cheque_date <= '" . mysqli_real_escape_string($conn, $chq_to) . "'";
        $chq_wstr = implode(' AND ', $chq_where);
        $chq_total_rows = $conn->query("SELECT COUNT(*) as c FROM cheque_register WHERE $chq_wstr")->fetch_assoc()['c'];
        $chq_total_pages = ceil($chq_total_rows / $per_page);
        $cheques_result = $conn->query("SELECT * FROM cheque_register WHERE $chq_wstr ORDER BY cheque_date DESC LIMIT $per_page OFFSET $offset");
        $chq_summary = $conn->query('SELECT status, COUNT(*) as cnt, SUM(amount) as total FROM cheque_register GROUP BY status');
        $chq_sum_data = [];
        while ($cs = $chq_summary->fetch_assoc())
            $chq_sum_data[$cs['status']] = $cs;
?>
<div class="stats-row no-print">
<div class="stat-box"><div class="stat-val" style="color:var(--warning)"><?= number_format($chq_sum_data['pending']['cnt'] ?? 0) ?></div><div class="stat-lbl">Pending</div><div style="font-size:0.75rem;color:var(--text2)"><?= fmt_money($chq_sum_data['pending']['total'] ?? 0) ?></div></div>
<div class="stat-box"><div class="stat-val" style="color:var(--success)"><?= number_format($chq_sum_data['cleared']['cnt'] ?? 0) ?></div><div class="stat-lbl">Cleared</div><div style="font-size:0.75rem;color:var(--text2)"><?= fmt_money($chq_sum_data['cleared']['total'] ?? 0) ?></div></div>
<div class="stat-box"><div class="stat-val" style="color:var(--danger)"><?= number_format($chq_sum_data['bounced']['cnt'] ?? 0) ?></div><div class="stat-lbl">Bounced</div><div style="font-size:0.75rem;color:var(--text2)"><?= fmt_money($chq_sum_data['bounced']['total'] ?? 0) ?></div></div>
<div class="stat-box"><div class="stat-val"><?= number_format($chq_sum_data['cancelled']['cnt'] ?? 0) ?></div><div class="stat-lbl">Cancelled</div></div>
</div>
<div class="filter-bar no-print">
<form method="GET" action="index.php" style="display:contents">
<input type="hidden" name="page" value="cheques">
<div class="form-group"><label>Status</label>
<select name="chq_status" onchange="this.form.submit()">
<option value="">All Status</option>
<option value="pending" <?= $chq_status_f === 'pending' ? 'selected' : '' ?>>Pending</option>
<option value="cleared" <?= $chq_status_f === 'cleared' ? 'selected' : '' ?>>Cleared</option>
<option value="bounced" <?= $chq_status_f === 'bounced' ? 'selected' : '' ?>>Bounced</option>
<option value="cancelled" <?= $chq_status_f === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
</select>
</div>
<div class="form-group"><label>Type</label>
<select name="chq_type" onchange="this.form.submit()">
<option value="">All Types</option>
<option value="payment" <?= $chq_type_f === 'payment' ? 'selected' : '' ?>>Payment</option>
<option value="receipt" <?= $chq_type_f === 'receipt' ? 'selected' : '' ?>>Receipt</option>
<option value="refund" <?= $chq_type_f === 'refund' ? 'selected' : '' ?>>Refund</option>
</select>
</div>
<div class="form-group"><label>Bank</label><input type="text" name="chq_bank" value="<?= sanitize($chq_bank_f) ?>" placeholder="Bank name" onchange="this.form.submit()"></div>
<div class="form-group"><label>From</label><input type="date" name="chq_from" value="<?= $chq_from ?>" onchange="this.form.submit()"></div>
<div class="form-group"><label>To</label><input type="date" name="chq_to" value="<?= $chq_to ?>" onchange="this.form.submit()"></div>
</form>
</div>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Cheque #</th><th>Bank</th><th>Date</th><th>Amount</th><th>Type</th><th>Status</th><th>Party</th><th>Reference</th><th class="no-print">Actions</th></tr></thead>
<tbody>
<?php
        $sr = $offset + 1;
        $chq_total_amt = 0;
        while ($chq = $cheques_result->fetch_assoc()):
            $chq_total_amt += $chq['amount'];
            $st_badge = $chq['status'] === 'cleared' ? 'badge-success' : ($chq['status'] === 'bounced' ? 'badge-danger' : ($chq['status'] === 'cancelled' ? 'badge-default' : 'badge-warning'));
            $tp_badge = $chq['type'] === 'receipt' ? 'badge-success' : ($chq['type'] === 'refund' ? 'badge-warning' : 'badge-info');
?>
<tr>
<td><?= $sr++ ?></td>
<td style="font-family:Consolas,monospace"><?= sanitize($chq['cheque_number']) ?></td>
<td><?= sanitize($chq['bank_name']) ?></td>
<td><?= fmt_date($chq['cheque_date']) ?></td>
<td><?= fmt_money($chq['amount']) ?></td>
<td><span class="badge <?= $tp_badge ?>"><?= strtoupper($chq['type']) ?></span></td>
<td><span class="badge <?= $st_badge ?>"><?= strtoupper($chq['status']) ?></span></td>
<td><?= sanitize($chq['party_name']) ?></td>
<td style="font-size:0.75rem"><?= sanitize($chq['reference_type']) ?> #<?= $chq['reference_id'] ?></td>
<td class="no-print">
<div class="actions-col">
<?php if ($chq['status'] === 'pending'): ?>
<form method="POST" action="index.php?page=cheques&action=clear" style="display:inline">
<input type="hidden" name="id" value="<?= $chq['id'] ?>">
<button type="submit" class="btn btn-success btn-sm" title="Mark Cleared">✓ Clear</button>
</form>
<form method="POST" action="index.php?page=cheques&action=bounce" style="display:inline">
<input type="hidden" name="id" value="<?= $chq['id'] ?>">
<button type="submit" class="btn btn-danger btn-sm" title="Mark Bounced" onclick="return confirm('Mark this cheque as bounced?')">✗ Bounce</button>
</form>
<?php endif; ?>
<form method="POST" action="index.php?page=cheques&action=delete" style="display:inline">
<input type="hidden" name="id" value="<?= $chq['id'] ?>">
<button type="submit" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Delete this cheque entry?')">🗑</button>
</form>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="4"><strong>TOTAL</strong></td><td><strong><?= fmt_money($chq_total_amt) ?></strong></td><td colspan="5"></td></tr></tfoot>
</table>
</div>
<?php
        $qstr2 = http_build_query(['page' => 'cheques', 'chq_status' => $chq_status_f, 'chq_type' => $chq_type_f, 'chq_bank' => $chq_bank_f, 'chq_from' => $chq_from, 'chq_to' => $chq_to]);
        if ($chq_total_pages > 1):
?>
<div class="pagination no-print">
<?php if ($current_pg > 1): ?><a href="index.php?<?= $qstr2 ?>&pg=<?= $current_pg - 1 ?>">‹ Prev</a><?php endif; ?>
<?php for ($i = max(1, $current_pg - 2); $i <= min($chq_total_pages, $current_pg + 2); $i++): ?>
<a href="index.php?<?= $qstr2 ?>&pg=<?= $i ?>" class="<?= $i == $current_pg ? 'active-page' : '' ?>"><?= $i ?></a>
<?php endfor; ?>
<?php if ($current_pg < $chq_total_pages): ?><a href="index.php?<?= $qstr2 ?>&pg=<?= $current_pg + 1 ?>">Next ›</a><?php endif; ?>
</div>
<?php endif; ?>

<?php
    elseif ($page === 'customer_ledger'):
        $sel_cust = (int) ($_GET['cust_id'] ?? 0);
        $customers_for_led = $conn->query('SELECT id, name, phone FROM customers ORDER BY name');
?>
<div class="filter-bar no-print">
<form method="GET" action="index.php" style="display:contents">
<input type="hidden" name="page" value="customer_ledger">
<div class="form-group"><label>Select Customer <span class="req">*</span></label>
<select name="cust_id" onchange="this.form.submit()">
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
?>
<div class="print-btn-wrap no-print">
<button onclick="window.print()" class="btn btn-default btn-sm">🖨 Print Ledger</button>
</div>
<fieldset class="fieldset"><legend>👤 Customer Ledger — <?= sanitize($cust_info['name']) ?></legend>
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:12px;font-size:0.83rem">
<span><strong>Phone:</strong> <?= sanitize($cust_info['phone'] ?? '-') ?></span>
<span><strong>CNIC:</strong> <?= sanitize($cust_info['cnic'] ?? '-') ?></span>
<span><strong>Address:</strong> <?= sanitize($cust_info['address'] ?? '-') ?></span>
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
<?php endif; ?>

<?php
    elseif ($page === 'supplier_ledger'):
        $sel_sup = (int) ($_GET['sup_id'] ?? 0);
        $suppliers_for_led = $conn->query('SELECT id, name FROM suppliers ORDER BY name');
?>
<div class="filter-bar no-print">
<form method="GET" action="index.php" style="display:contents">
<input type="hidden" name="page" value="supplier_ledger">
<div class="form-group"><label>Select Supplier</label>
<select name="sup_id" onchange="this.form.submit()">
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
            $sup_orders = $conn->query("SELECT po.*, SUM(b.purchase_price) as bikes_total, COUNT(b.id) as bike_count FROM purchase_orders po LEFT JOIN bikes b ON po.id=b.purchase_order_id WHERE po.supplier_id=$sel_sup GROUP BY po.id ORDER BY po.order_date ASC");
            $sup_running = 0;
            $sup_sr = 1;
            $sup_total = 0;
?>
<fieldset class="fieldset"><legend>🏭 Supplier Ledger — <?= sanitize($sup_info['name']) ?></legend>
<div style="margin-bottom:10px;font-size:0.83rem">
<strong>Contact:</strong> <?= sanitize($sup_info['contact'] ?? '-') ?> | <strong>Address:</strong> <?= sanitize($sup_info['address'] ?? '-') ?>
</div>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Order Date</th><th>Cheque #</th><th>Bank</th><th>Cheque Date</th><th>Units</th><th>Cheque Amount</th><th>Bikes Total</th><th>Balance</th></tr></thead>
<tbody>
<?php
            while ($so = $sup_orders->fetch_assoc()):
                $sup_running += $so['cheque_amount'];
                $sup_total += $so['cheque_amount'];
?>
<tr>
<td><?= $sup_sr++ ?></td>
<td><?= fmt_date($so['order_date']) ?></td>
<td style="font-family:Consolas,monospace"><?= sanitize($so['cheque_number']) ?></td>
<td><?= sanitize($so['bank_name']) ?></td>
<td><?= fmt_date($so['cheque_date']) ?></td>
<td><?= $so['bike_count'] ?></td>
<td><?= fmt_money($so['cheque_amount']) ?></td>
<td><?= fmt_money($so['bikes_total'] ?? 0) ?></td>
<td style="font-weight:700"><?= fmt_money($sup_running) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="6"><strong>TOTAL</strong></td><td><strong><?= fmt_money($sup_total) ?></strong></td><td></td><td></td></tr></tfoot>
</table>
</div>
</fieldset>
<?php endif; ?>

<?php
    elseif ($page === 'reports'):
        $sub = sanitize($_GET['sub'] ?? 'stock');
        $rep_from = !empty($_GET['rep_from']) ? $_GET['rep_from'] : date('Y-01-01');
        $rep_to = !empty($_GET['rep_to']) ? $_GET['rep_to'] : date('Y-12-31');
        $rep_year = !empty($_GET['rep_year']) ? (int)$_GET['rep_year'] : (int)date('Y');
        $rep_month = !empty($_GET['rep_month']) ? (int)$_GET['rep_month'] : (int)date('n');
?>
<div class="sub-tabs no-print">
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
        ];
        foreach ($sub_items as $si):
?>
<a href="index.php?page=reports&sub=<?= $si[0] ?>&rep_from=<?= $rep_from ?>&rep_to=<?= $rep_to ?>" class="sub-tab <?= $sub === $si[0] ? 'active' : '' ?>"><?= $si[1] ?></a>
<?php endforeach; ?>
</div>

<div class="filter-bar no-print">
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
<button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-end">🔍 Filter</button>
</form>
<button onclick="window.print()" class="btn btn-default btn-sm" style="align-self:flex-end">🖨 Print</button>
</div>

<?php
        if ($sub === 'stock'):
            $stock_bikes = $conn->query("SELECT b.*, m.model_name, m.category, m.short_code FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.status='in_stock' ORDER BY m.model_name, b.inventory_date");
            $stk_total = 0;
?>
<fieldset class="fieldset"><legend>📦 Current Stock Report</legend>
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
            $sold_bikes_r = $conn->query("SELECT b.*, m.model_name, m.short_code, c.name as cust_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id WHERE b.status='sold' AND b.selling_date BETWEEN '" . mysqli_real_escape_string($conn, $rep_from) . "' AND '" . mysqli_real_escape_string($conn, $rep_to) . "' ORDER BY b.selling_date DESC");
            $sold_total_sp = 0;
            $sold_total_pp = 0;
            $sold_total_mg = 0;
            $sold_total_tax = 0;
?>
<fieldset class="fieldset"><legend>✅ Sold Bikes Report (<?= fmt_date($rep_from) ?> - <?= fmt_date($rep_to) ?>)</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Chassis</th><th>Model</th><th>Color</th><th>Customer</th><th>Selling Date</th><th>Purchase Price</th><th>Selling Price</th><th>Tax</th><th>Margin</th></tr></thead>
<tbody>
<?php
            $sr = 1;
            while ($sb = $sold_bikes_r->fetch_assoc()):
                $sold_total_sp += $sb['selling_price'];
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
<td><?= fmt_money($sb['selling_price']) ?></td>
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
    SUM(CASE WHEN b.status='returned' THEN 1 ELSE 0 END) as ret_cnt,
    SUM(b.purchase_price) as total_pp,
    SUM(CASE WHEN b.status='sold' THEN b.selling_price ELSE 0 END) as total_sp,
    SUM(CASE WHEN b.status='sold' THEN b.margin ELSE 0 END) as total_mg
    FROM models m LEFT JOIN bikes b ON m.id=b.model_id
    GROUP BY m.id ORDER BY m.model_name");
            $mw_t = [0, 0, 0, 0, 0, 0, 0];
?>
<fieldset class="fieldset"><legend>📊 Model-wise Sales Report</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Model</th><th>Short Code</th><th>Category</th><th>Inventory</th><th>Sold</th><th>Available</th><th>Returned</th><th>Total Purchase</th><th>Total Sales</th><th>Total Margin</th></tr></thead>
<tbody>
<?php
            while ($mw = $mw_result->fetch_assoc()):
                $mw_t[0] += $mw['total_inv'];
                $mw_t[1] += $mw['sold_cnt'];
                $mw_t[2] += $mw['avail_cnt'];
                $mw_t[3] += $mw['ret_cnt'];
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
<td><?= fmt_money($mw['total_pp']) ?></td>
<td><?= fmt_money($mw['total_sp']) ?></td>
<td style="color:<?= $mw['total_mg'] >= 0 ? 'var(--success)' : 'var(--danger)' ?>"><?= fmt_money($mw['total_mg']) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td><strong>TOTAL</strong></td><td colspan="3"><strong><?= $mw_t[0] ?></strong></td><td><strong><?= $mw_t[1] ?></strong></td><td><strong><?= $mw_t[2] ?></strong></td><td><strong><?= $mw_t[3] ?></strong></td><td><strong><?= fmt_money($mw_t[4]) ?></strong></td><td><strong><?= fmt_money($mw_t[5]) ?></strong></td><td style="color:var(--success)"><strong><?= fmt_money($mw_t[6]) ?></strong></td></tr></tfoot>
</table>
</div>
</fieldset>

<?php
        elseif ($sub === 'tax'):
            $tax_result = $conn->query("SELECT DATE_FORMAT(selling_date,'%Y-%m') as ym, COUNT(*) as cnt, SUM(tax_amount) as total_tax, SUM(purchase_price) as total_pp FROM bikes WHERE status='sold' AND selling_date BETWEEN '" . mysqli_real_escape_string($conn, $rep_from) . "' AND '" . mysqli_real_escape_string($conn, $rep_to) . "' GROUP BY ym ORDER BY ym DESC");
            $tax_total = 0;
?>
<fieldset class="fieldset"><legend>🧾 Tax Report by Month</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Month</th><th>Bikes Sold</th><th>Total Purchase Value</th><th>Tax Amount (<?= get_setting('tax_rate') ?? 0.1 ?>%)</th></tr></thead>
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
            $profit_monthly = $conn->query("SELECT DATE_FORMAT(selling_date,'%Y-%m') as ym, COUNT(*) as cnt, SUM(selling_price) as total_sp, SUM(purchase_price) as total_pp, SUM(margin) as total_margin, SUM(tax_amount) as total_tax FROM bikes WHERE status='sold' AND selling_date BETWEEN '" . mysqli_real_escape_string($conn, $rep_from) . "' AND '" . mysqli_real_escape_string($conn, $rep_to) . "' GROUP BY ym ORDER BY ym DESC");
            $profit_t = [0, 0, 0, 0, 0];
?>
<fieldset class="fieldset"><legend>📈 Profit / Margin Report</legend>
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
            $bank_result = $conn->query('SELECT bank_name, type, status, COUNT(*) as cnt, SUM(amount) as total FROM cheque_register GROUP BY bank_name, type, status ORDER BY bank_name, type');
            $bank_data = [];
            while ($br2 = $bank_result->fetch_assoc()) {
                $bank_data[$br2['bank_name']][$br2['type']][$br2['status']] = $br2;
            }
?>
<fieldset class="fieldset"><legend>💳 Bank / Cheque Report</legend>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Bank</th><th>Type</th><th>Pending</th><th>Cleared</th><th>Bounced</th><th>Cancelled</th><th>Total Count</th><th>Total Amount</th></tr></thead>
<tbody>
<?php
            $bank_totals = [0, 0];
            foreach ($bank_data as $bank => $types):
                foreach ($types as $type => $statuses):
                    $pending = $statuses['pending']['total'] ?? 0;
                    $cleared = $statuses['cleared']['total'] ?? 0;
                    $bounced = $statuses['bounced']['total'] ?? 0;
                    $cancelled = $statuses['cancelled']['total'] ?? 0;
                    $cnt = array_sum(array_column($statuses, 'cnt'));
                    $ttl = $pending + $cleared + $bounced + $cancelled;
                    $bank_totals[0] += $cnt;
                    $bank_totals[1] += $ttl;
?>
<tr>
<td><?= sanitize($bank) ?></td>
<td><span class="badge badge-<?= $type === 'receipt' ? 'success' : ($type === 'refund' ? 'warning' : 'info') ?>"><?= strtoupper($type) ?></span></td>
<td style="color:var(--warning)"><?= fmt_money($pending) ?></td>
<td style="color:var(--success)"><?= fmt_money($cleared) ?></td>
<td style="color:var(--danger)"><?= fmt_money($bounced) ?></td>
<td><?= fmt_money($cancelled) ?></td>
<td><?= $cnt ?></td>
<td><?= fmt_money($ttl) ?></td>
</tr>
<?php endforeach;
            endforeach; ?>
</tbody>
<tfoot><tr><td colspan="6"><strong>TOTAL</strong></td><td><strong><?= $bank_totals[0] ?></strong></td><td><strong><?= fmt_money($bank_totals[1]) ?></strong></td></tr></tfoot>
</table>
</div>
</fieldset>

<?php
        elseif ($sub === 'monthly'):
            $monthly_r = $conn->query("SELECT DATE_FORMAT(order_date,'%Y-%m') as ym, COUNT(*) as purchased, SUM(purchase_price) as pp_total FROM bikes WHERE YEAR(order_date)=$rep_year GROUP BY ym");
            $sold_monthly_r = $conn->query("SELECT DATE_FORMAT(selling_date,'%Y-%m') as ym, COUNT(*) as sold_cnt, SUM(selling_price) as sp_total, SUM(margin) as mg_total FROM bikes WHERE status='sold' AND YEAR(selling_date)=$rep_year GROUP BY ym");
            $monthly_purch = [];
            $monthly_sales = [];
            while ($mr = $monthly_r->fetch_assoc())
                $monthly_purch[$mr['ym']] = $mr;
            while ($mr2 = $sold_monthly_r->fetch_assoc())
                $monthly_sales[$mr2['ym']] = $mr2;
            $all_months = array_unique(array_merge(array_keys($monthly_purch), array_keys($monthly_sales)));
            sort($all_months);
?>
<fieldset class="fieldset"><legend>📅 Monthly Summary — <?= $rep_year ?></legend>
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
            $daily_date = $_GET['daily_date'] ?? date('Y-m-d');
            $daily_sales = $conn->query("SELECT b.*, m.model_name, c.name as cust_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id WHERE b.selling_date='" . mysqli_real_escape_string($conn, $daily_date) . "' AND b.status='sold'");
            $daily_purch = $conn->query("SELECT b.*, m.model_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.inventory_date='" . mysqli_real_escape_string($conn, $daily_date) . "'");
?>
<div class="filter-bar no-print" style="margin-bottom:8px">
<form method="GET" action="index.php" style="display:contents">
<input type="hidden" name="page" value="reports">
<input type="hidden" name="sub" value="daily">
<div class="form-group"><label>Select Date</label><input type="date" name="daily_date" value="<?= $daily_date ?>"></div>
<button type="submit" class="btn btn-primary btn-sm" style="align-self:flex-end">🔍 View</button>
</form>
</div>
<fieldset class="fieldset"><legend>📆 Daily Ledger — <?= fmt_date($daily_date) ?></legend>
<h4 style="font-size:0.82rem;color:var(--success);margin-bottom:6px">Sales</h4>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Chassis</th><th>Model</th><th>Customer</th><th>Selling Price</th><th>Tax</th><th>Margin</th></tr></thead>
<tbody>
<?php $d_sp = 0;
            $d_mg = 0;
            while ($ds = $daily_sales->fetch_assoc()):
                $d_sp += $ds['selling_price'];
                $d_mg += $ds['margin']; ?>
<tr class="row-sold"><td><?= sanitize($ds['chassis_number']) ?></td><td><?= sanitize($ds['model_name']) ?></td><td><?= sanitize($ds['cust_name'] ?? 'Walk-in') ?></td><td><?= fmt_money($ds['selling_price']) ?></td><td><?= fmt_money($ds['tax_amount']) ?></td><td style="color:var(--success)"><?= fmt_money($ds['margin']) ?></td></tr>
<?php endwhile; ?>
</tbody>
<tfoot><tr><td colspan="3"><strong>TOTAL</strong></td><td><strong><?= fmt_money($d_sp) ?></strong></td><td></td><td style="color:var(--success)"><strong><?= fmt_money($d_mg) ?></strong></td></tr></tfoot>
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
</fieldset>

<?php
        elseif ($sub === 'purchase_vs_sales'):
            $pvs = $conn->query("SELECT DATE_FORMAT(order_date,'%Y-%m') as ym, COUNT(*) as p_cnt, SUM(purchase_price) as p_val FROM bikes WHERE YEAR(order_date)=$rep_year GROUP BY ym");
            $svp = $conn->query("SELECT DATE_FORMAT(selling_date,'%Y-%m') as ym, COUNT(*) as s_cnt, SUM(selling_price) as s_val FROM bikes WHERE status='sold' AND YEAR(selling_date)=$rep_year GROUP BY ym");
            $pvs_data = [];
            $svp_data = [];
            while ($r = $pvs->fetch_assoc())
                $pvs_data[$r['ym']] = $r;
            while ($r = $svp->fetch_assoc())
                $svp_data[$r['ym']] = $r;
            $all_m = array_unique(array_merge(array_keys($pvs_data), array_keys($svp_data)));
            sort($all_m);
?>
<fieldset class="fieldset"><legend>🔄 Purchase vs Sales — <?= $rep_year ?></legend>
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
<?php endif; ?>

<?php
    elseif ($page === 'models'):
        $models_result = $conn->query("SELECT m.*, COUNT(b.id) as bike_count, SUM(CASE WHEN b.status='in_stock' THEN 1 ELSE 0 END) as in_stock, SUM(CASE WHEN b.status='sold' THEN 1 ELSE 0 END) as sold_cnt FROM models m LEFT JOIN bikes b ON m.id=b.model_id GROUP BY m.id ORDER BY m.model_name");
        $edit_model_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_model = null;
        if ($edit_model_id) {
            $em = $conn->query("SELECT * FROM models WHERE id=$edit_model_id");
            $edit_model = $em ? $em->fetch_assoc() : null;
        }
?>
<div style="display:flex;gap:8px;margin-bottom:10px" class="no-print">
<button class="btn btn-success" onclick="document.getElementById('addModelFormArea').style.display='block';document.getElementById('addModelFormArea').scrollIntoView()">+ Add Model</button>
</div>
<div id="addModelFormArea" style="display:<?= $edit_model ? 'block' : 'none' ?>;margin-bottom:14px">
<fieldset class="fieldset"><legend><?= $edit_model ? '✏ Edit Model' : '+ Add New Model' ?></legend>
<form method="POST" action="index.php?page=models&action=<?= $edit_model ? 'edit' : 'add' ?>">
<?php if ($edit_model): ?><input type="hidden" name="id" value="<?= $edit_model['id'] ?>"><?php endif; ?>
<div class="form-row">
<div class="form-group"><label>Model Code <span class="req">*</span></label><input type="text" name="model_code" value="<?= sanitize($edit_model['model_code'] ?? '') ?>" required></div>
<div class="form-group"><label>Model Name <span class="req">*</span></label><input type="text" name="model_name" value="<?= sanitize($edit_model['model_name'] ?? '') ?>" required></div>
<div class="form-group"><label>Category</label><input type="text" name="category" value="<?= sanitize($edit_model['category'] ?? 'Electric Bike') ?>"></div>
<div class="form-group"><label>Short Code</label><input type="text" name="short_code" value="<?= sanitize($edit_model['short_code'] ?? '') ?>"></div>
</div>
<button type="submit" class="btn btn-primary">💾 Save</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('addModelFormArea').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Model Code</th><th>Model Name</th><th>Category</th><th>Short Code</th><th>Total Inventory</th><th>In Stock</th><th>Sold</th><th class="no-print">Actions</th></tr></thead>
<tbody>
<?php $sr = 1;
        while ($mdl = $models_result->fetch_assoc()): ?>
<tr>
<td><?= $sr++ ?></td>
<td><strong><?= sanitize($mdl['model_code']) ?></strong></td>
<td><?= sanitize($mdl['model_name']) ?></td>
<td><?= sanitize($mdl['category']) ?></td>
<td><code><?= sanitize($mdl['short_code']) ?></code></td>
<td><?= $mdl['bike_count'] ?></td>
<td><span class="badge badge-info"><?= $mdl['in_stock'] ?></span></td>
<td><span class="badge badge-success"><?= $mdl['sold_cnt'] ?></span></td>
<td class="no-print">
<div class="actions-col">
<a href="index.php?page=purchase&model_id=<?= $mdl['id'] ?>" class="btn btn-success btn-sm" title="Purchase">📦</a>
<a href="index.php?page=sale&model_id=<?= $mdl['id'] ?>" class="btn btn-warning btn-sm" title="Sell">🛒</a>
<a href="index.php?page=models&edit_id=<?= $mdl['id'] ?>" class="btn btn-primary btn-sm">✏ Edit</a>
<form method="POST" action="index.php?page=models&action=delete" style="display:inline" onsubmit="return confirm('Delete this model? Only possible if no bikes are linked.')">
<input type="hidden" name="id" value="<?= $mdl['id'] ?>">
<button type="submit" class="btn btn-danger btn-sm">🗑</button>
</form>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<?php
    elseif ($page === 'customers'):
        $cust_result = $conn->query("SELECT c.*, COUNT(b.id) as bike_count, SUM(CASE WHEN b.status='sold' THEN b.selling_price ELSE 0 END) as total_purchases FROM customers c LEFT JOIN bikes b ON c.id=b.customer_id GROUP BY c.id ORDER BY c.name");
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
        $cust_result = $conn->query("SELECT c.*, COUNT(b.id) as bike_count, SUM(CASE WHEN b.status='sold' THEN b.selling_price ELSE 0 END) as total_purchases FROM customers c LEFT JOIN bikes b ON c.id=b.customer_id WHERE $where_cust GROUP BY c.id ORDER BY c.name");
?>
<div style="display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap;align-items:center" class="no-print">
<form method="GET" action="index.php" style="display:flex;gap:6px;align-items:center">
<input type="hidden" name="page" value="customers">
<input type="text" name="search_cust" value="<?= $search_cust ?>" placeholder="Search by name, phone, CNIC..." style="padding:6px 10px;border:1px solid var(--input-border);background:var(--input-bg);color:var(--input-text);border-radius:1px">
<button type="submit" class="btn btn-default btn-sm">🔍</button>
</form>
<button class="btn btn-success" onclick="document.getElementById('addCustFormArea').style.display='block'">+ Add Customer</button>
</div>
<div id="addCustFormArea" style="display:<?= $edit_cust ? 'block' : 'none' ?>;margin-bottom:14px">
<fieldset class="fieldset"><legend><?= $edit_cust ? '✏ Edit Customer' : '+ Add New Customer' ?></legend>
<form method="POST" action="index.php?page=customers&action=<?= $edit_cust ? 'edit' : 'add' ?>">
<?php if ($edit_cust): ?><input type="hidden" name="id" value="<?= $edit_cust['id'] ?>"><?php endif; ?>
<div class="form-row">
<div class="form-group"><label>Name <span class="req">*</span></label><input type="text" name="name" value="<?= sanitize($edit_cust['name'] ?? '') ?>" required></div>
<div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?= sanitize($edit_cust['phone'] ?? '') ?>"></div>
<div class="form-group"><label>CNIC</label><input type="text" name="cnic" value="<?= sanitize($edit_cust['cnic'] ?? '') ?>" placeholder="XXXXX-XXXXXXX-X"></div>
</div>
<div class="form-row">
<div class="form-group"><label>Address</label><textarea name="address" rows="2"><?= sanitize($edit_cust['address'] ?? '') ?></textarea></div>
</div>
<button type="submit" class="btn btn-primary">💾 Save</button>
<button type="button" class="btn btn-default" onclick="document.getElementById('addCustFormArea').style.display='none'">Cancel</button>
</form>
</fieldset>
</div>
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Name</th><th>Phone</th><th>CNIC</th><th>Address</th><th>Bikes Purchased</th><th>Total Amount</th><th class="no-print">Actions</th></tr></thead>
<tbody>
<?php $sr = 1;
        while ($cu = $cust_result->fetch_assoc()): ?>
<tr>
<td><?= $sr++ ?></td>
<td><strong><?= sanitize($cu['name']) ?></strong></td>
<td><?= sanitize($cu['phone'] ?? '-') ?></td>
<td style="font-family:Consolas,monospace"><?= sanitize($cu['cnic'] ?? '-') ?></td>
<td><?= sanitize($cu['address'] ?? '-') ?></td>
<td><?= $cu['bike_count'] ?></td>
<td><?= fmt_money($cu['total_purchases'] ?? 0) ?></td>
<td class="no-print">
<div class="actions-col">
<a href="index.php?page=customer_ledger&cust_id=<?= $cu['id'] ?>" class="btn btn-default btn-sm" title="Ledger">📒</a>
<a href="index.php?page=customers&edit_id=<?= $cu['id'] ?>" class="btn btn-primary btn-sm">✏</a>
<form method="POST" action="index.php?page=customers&action=delete" style="display:inline" onsubmit="return confirm('Delete customer?')">
<input type="hidden" name="id" value="<?= $cu['id'] ?>">
<button type="submit" class="btn btn-danger btn-sm">🗑</button>
</form>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<?php
    elseif ($page === 'suppliers'):
        $sup_result = $conn->query('SELECT s.*, COUNT(po.id) as order_count, SUM(po.cheque_amount) as total_paid FROM suppliers s LEFT JOIN purchase_orders po ON s.id=po.supplier_id GROUP BY s.id ORDER BY s.name');
        $edit_sup_id = (int) ($_GET['edit_id'] ?? 0);
        $edit_sup = null;
        if ($edit_sup_id) {
            $es = $conn->query("SELECT * FROM suppliers WHERE id=$edit_sup_id");
            $edit_sup = $es ? $es->fetch_assoc() : null;
        }
?>
<div style="display:flex;gap:8px;margin-bottom:10px" class="no-print">
<button class="btn btn-success" onclick="document.getElementById('addSupFormArea').style.display='block'">+ Add Supplier</button>
</div>
<div id="addSupFormArea" style="display:<?= $edit_sup ? 'block' : 'none' ?>;margin-bottom:14px">
<fieldset class="fieldset"><legend><?= $edit_sup ? '✏ Edit Supplier' : '+ Add New Supplier' ?></legend>
<form method="POST" action="index.php?page=suppliers&action=<?= $edit_sup ? 'edit' : 'add' ?>">
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
<div class="data-table-wrap">
<table class="data-table">
<thead><tr><th>Sr#</th><th>Name</th><th>Contact</th><th>Address</th><th>Orders</th><th>Total Paid</th><th class="no-print">Actions</th></tr></thead>
<tbody>
<?php $sr = 1;
        while ($sv = $sup_result->fetch_assoc()): ?>
<tr>
<td><?= $sr++ ?></td>
<td><strong><?= sanitize($sv['name']) ?></strong></td>
<td><?= sanitize($sv['contact'] ?? '-') ?></td>
<td><?= sanitize($sv['address'] ?? '-') ?></td>
<td><?= $sv['order_count'] ?></td>
<td><?= fmt_money($sv['total_paid'] ?? 0) ?></td>
<td class="no-print">
<div class="actions-col">
<a href="index.php?page=supplier_ledger&sup_id=<?= $sv['id'] ?>" class="btn btn-default btn-sm" title="Ledger">📒</a>
<a href="index.php?page=suppliers&edit_id=<?= $sv['id'] ?>" class="btn btn-primary btn-sm">✏</a>
<form method="POST" action="index.php?page=suppliers&action=delete" style="display:inline" onsubmit="return confirm('Delete supplier?')">
<input type="hidden" name="id" value="<?= $sv['id'] ?>">
<button type="submit" class="btn btn-danger btn-sm">🗑</button>
</form>
</div>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>

<?php
    elseif ($page === 'settings'):
        $s_company = get_setting('company_name') ?? 'BNI Enterprises';
        $s_branch = get_setting('branch_name') ?? 'Dera (Ahmed Metro)';
        $s_tax = get_setting('tax_rate') ?? '0.1';
        $s_curr = get_setting('currency') ?? 'Rs.';
        $s_taxon = get_setting('tax_on') ?? 'purchase_price';
        $s_show_pp = get_setting('show_purchase_on_invoice') ?? '0';
?>
<form method="POST" enctype="multipart/form-data">
<fieldset class="fieldset"><legend>⚙ Company Settings</legend>
<div class="form-row">
<div class="form-group"><label>Company Name</label><input type="text" name="company_name" value="<?= sanitize($s_company) ?>"></div>
<div class="form-group"><label>Branch Name</label><input type="text" name="branch_name" value="<?= sanitize($s_branch) ?>"></div>
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
</div>
</fieldset>
<fieldset class="fieldset"><legend>🔐 Change Admin Password</legend>
<div class="form-row">
<div class="form-group"><label>New Password (min 8 characters, leave blank to keep current)</label><input type="password" name="new_password" minlength="8" placeholder="Leave blank to keep existing password"></div>
</div>
<div style="font-size:0.78rem;color:var(--text2);margin-top:4px">⚠ Password must be at least 8 characters. Leave empty to keep the current password unchanged.</div>
</fieldset>
<fieldset class="fieldset"><legend>💾 Database Backup & Restore</legend>
<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:14px">
<a href="index.php?page=settings&action=backup" class="btn btn-primary">⬇ Download SQL Backup</a>
<span style="font-size:0.8rem;color:var(--text2)">Downloads a full SQL dump of the database.</span>
</div>
<div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;border-top:1px solid var(--border);padding-top:14px">
<input type="file" name="backup_file" accept=".sql" style="font-size:0.8rem;background:var(--input-bg);color:var(--input-text);border:1px solid var(--input-border);padding:5px;border-radius:2px">
<button type="submit" name="restore_db" class="btn btn-danger" onclick="return confirm('WARNING: Restoring will overwrite all current data! Are you absolutely sure?')">⬆ Restore Database</button>
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
<?php endif; ?>

<?php if ($db_exists && isset($_SESSION['logged_in'])): ?>
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
setInterval(function() {
    if (window.location.href.indexOf('page=dashboard') !== -1) {
        document.querySelectorAll('.card-value').forEach(function(el) {});
    }
}, 60000);
</script>
<?php endif; ?>

<?php
if (isset($conn) && $conn) {
    if (isset($_GET['ajax']) && $_GET['ajax'] === 'check_chassis') {
        $chassis = sanitize($_GET['chassis'] ?? '');
        $r = $conn->query("SELECT id FROM bikes WHERE chassis_number='" . mysqli_real_escape_string($conn, $chassis) . "'");
        echo ($r && $r->num_rows > 0) ? '1' : '0';
        $conn->close();
        exit;
    }
    $conn->close();
}
?>
</body>
</html>