import tkinter as tk
from tkinter import messagebox, filedialog, ttk
import sqlite3
import datetime
import hashlib
import random
import string
import os
from PIL import Image, ImageTk, ImageDraw, ImageFont
import io
import re
from datetime import datetime, timedelta

# --- Global Configuration ---
DB_NAME = 'bni_enterprises.db'
APP_VERSION = '1.0.0'
AUTHOR = 'Yasin Ullah'
CURRENCY_SYMBOL = 'Rs.'
TAX_RATE = 0.10  # 10%
TAX_ON_PRICE_TYPE = 'purchase_price'  # 'purchase_price' or 'selling_price'
SHOW_PURCHASE_ON_INVOICE = False
SESSION_TIMEOUT_SECONDS = 2400  # 40 minutes for idle logout
ABSOLUTE_SESSION_TIMEOUT_SECONDS = 28800  # 8 hours for absolute session limit
LOGIN_TIME = None
LAST_ACTIVE_TIME = None
CURRENT_USER_ID = None
CURRENT_USER_ROLE = None
LOGIN_ATTEMPTS = {}  # Store login attempts for anti-brute-force
MAX_LOGIN_ATTEMPTS = 5
LOCKOUT_TIME_MINUTES = 15

# --- TKINTER TREEVIEW PATCH ---
original_tree_init = ttk.Treeview.__init__
def patched_tree_init(self, master=None, **kw):
    original_tree_init(self, master, **kw)
    self._embedded_windows = {}
    self.bind("<ButtonRelease-1>", self._handle_embedded_click)

def patched_window_create(self, item, **kw):
    column = kw.get('column')
    window = kw.get('window')
    if not column or not window: return
    
    if not hasattr(self, '_embedded_windows'): self._embedded_windows = {}
    if item not in self._embedded_windows: self._embedded_windows[item] = {}
    
    if isinstance(window, ttk.Checkbutton):
        var_name = str(window.cget("variable"))
        try:
            val = window.tk.globalgetvar(var_name)
            is_checked = str(val) in ("1", "True", "true", 1, True)
        except:
            is_checked = False
        self.set(item, column, "☑" if is_checked else "☐")
        self._embedded_windows[item][column] = window
    elif isinstance(window, ttk.Frame):
        self.set(item, column, "⚙ Actions (Click)")
        self._embedded_windows[item][column] = window

def _handle_embedded_click(self, event):
    if self.identify("region", event.x, event.y) == "cell":
        col = self.identify_column(event.x)
        item = self.identify_row(event.y)
        col_name = self.column(col, "id")
        
        if hasattr(self, '_embedded_windows') and item in self._embedded_windows and col_name in self._embedded_windows[item]:
            widget = self._embedded_windows[item][col_name]
            if isinstance(widget, ttk.Checkbutton):
                widget.invoke()
                var_name = str(widget.cget("variable"))
                try:
                    val = widget.tk.globalgetvar(var_name)
                    self.set(item, col_name, "☑" if str(val) in ("1", "True", "true", 1, True) else "☐")
                except: pass
            elif isinstance(widget, ttk.Frame):
                menu = tk.Menu(self, tearoff=0)
                added = False
                for btn in widget.winfo_children():
                    if isinstance(btn, ttk.Button):
                        menu.add_command(label=btn.cget("text"), command=btn.cget("command"))
                        added = True
                if added: menu.tk_popup(event.x_root, event.y_root)

ttk.Treeview.__init__ = patched_tree_init
ttk.Treeview.window_create = patched_window_create
ttk.Treeview._handle_embedded_click = _handle_embedded_click
# ------------------------------

# --- Database Functions ---
def db_connect():
    try:
        conn = sqlite3.connect(DB_NAME)
        conn.execute("PRAGMA foreign_keys = ON;")
        return conn
    except sqlite3.Error as e:
        messagebox.showerror("Database Error", f"Error connecting to database: {e}")
        return None

def install_database():
    conn = db_connect()
    if not conn:
        return False

    cursor = conn.cursor()

    tables = [
        """CREATE TABLE IF NOT EXISTS settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            setting_key TEXT UNIQUE NOT NULL,
            setting_value TEXT
        )""",
        """CREATE TABLE IF NOT EXISTS suppliers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            contact TEXT,
            address TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )""",
        """CREATE TABLE IF NOT EXISTS customers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT,
            cnic TEXT,
            address TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )""",
        """CREATE TABLE IF NOT EXISTS models (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            model_code TEXT NOT NULL,
            model_name TEXT NOT NULL,
            category TEXT,
            short_code TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )""",
        """CREATE TABLE IF NOT EXISTS purchase_orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_date TEXT,
            supplier_id INTEGER,
            cheque_number TEXT,
            bank_name TEXT,
            cheque_date TEXT,
            cheque_amount REAL,
            total_units INTEGER,
            notes TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
        )""",
        """CREATE TABLE IF NOT EXISTS bikes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            purchase_order_id INTEGER,
            order_date TEXT,
            inventory_date TEXT,
            chassis_number TEXT UNIQUE NOT NULL,
            motor_number TEXT,
            model_id INTEGER,
            color TEXT,
            purchase_price REAL,
            selling_price REAL,
            selling_date TEXT,
            customer_id INTEGER,
            tax_amount REAL DEFAULT 0,
            margin REAL DEFAULT 0,
            status TEXT DEFAULT 'in_stock', -- 'in_stock','sold','returned','reserved'
            return_date TEXT,
            return_amount REAL,
            return_notes TEXT,
            accessories TEXT,
            safeguard_notes TEXT,
            notes TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (model_id) REFERENCES models(id) ON DELETE SET NULL,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
            FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE SET NULL
        )""",
        """CREATE TABLE IF NOT EXISTS cheque_register (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            cheque_number TEXT,
            bank_name TEXT,
            cheque_date TEXT,
            amount REAL,
            type TEXT, -- 'payment','receipt','refund'
            status TEXT DEFAULT 'pending', -- 'pending','cleared','bounced','cancelled'
            reference_type TEXT,
            reference_id INTEGER,
            party_name TEXT,
            notes TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )""",
        """CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            payment_date TEXT,
            payment_type TEXT, -- 'cash','cheque','bank_transfer','online'
            amount REAL,
            cheque_id INTEGER,
            reference_type TEXT,
            reference_id INTEGER,
            party_name TEXT,
            notes TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (cheque_id) REFERENCES cheque_register(id) ON DELETE SET NULL
        )""",
        """CREATE TABLE IF NOT EXISTS ledger (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            entry_date TEXT,
            entry_type TEXT, -- 'debit','credit'
            amount REAL,
            party_type TEXT, -- 'customer','supplier','other'
            party_id INTEGER,
            description TEXT,
            reference_type TEXT,
            reference_id INTEGER,
            balance REAL, -- This might be tricky to maintain in SQLite directly, better calculated dynamically
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )""",
        """CREATE TABLE IF NOT EXISTS roles (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT UNIQUE NOT NULL,
            description TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )""",
        """CREATE TABLE IF NOT EXISTS role_permissions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            role_id INTEGER NOT NULL,
            page TEXT NOT NULL,
            can_view INTEGER DEFAULT 0,
            can_add INTEGER DEFAULT 0,
            can_edit INTEGER DEFAULT 0,
            can_delete INTEGER DEFAULT 0,
            UNIQUE(role_id, page),
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
        )""",
        """CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            full_name TEXT,
            role_id INTEGER,
            is_active INTEGER DEFAULT 1,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
        )""",
        """CREATE TABLE IF NOT EXISTS income_expenses (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            entry_date TEXT NOT NULL,
            type TEXT NOT NULL, -- 'income','expense'
            category TEXT NOT NULL,
            amount REAL NOT NULL,
            payment_method TEXT DEFAULT 'cash', -- 'cash','cheque','bank_transfer','online','other'
            reference TEXT,
            notes TEXT,
            created_by INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        )""",
        """CREATE TABLE IF NOT EXISTS captcha (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            session_id TEXT UNIQUE NOT NULL,
            question TEXT NOT NULL,
            answer TEXT NOT NULL,
            timestamp TEXT DEFAULT CURRENT_TIMESTAMP
        )"""
    ]

    for sql in tables:
        try:
            cursor.execute(sql)
        except sqlite3.Error as e:
            messagebox.showerror("Database Error", f"Failed to create table: {e}\nSQL: {sql}")
            conn.close()
            return False
    
    # Insert default settings
    defaults = [
        ('company_name', 'BNI Enterprises'),
        ('branch_name', 'Dera (Ahmed Metro)'),
        ('tax_rate', str(TAX_RATE)),
        ('currency', CURRENCY_SYMBOL),
        ('tax_on', TAX_ON_PRICE_TYPE),
        ('theme', 'light'),
        ('admin_password', hashlib.sha256('admin123'.encode()).hexdigest()), # Will be replaced by actual hashed password
        ('show_purchase_on_invoice', '0'),
    ]

    for key, value in defaults:
        cursor.execute("INSERT OR IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)", (key, value))

    # Insert default roles
    cursor.execute("INSERT OR IGNORE INTO roles (id, name, description) VALUES (?, ?, ?)", (1, 'Administrator', 'Full access'))
    cursor.execute("INSERT OR IGNORE INTO roles (id, name, description) VALUES (?, ?, ?)", (2, 'Standard User', 'Limited access'))

    # Insert default admin user
    admin_password_hash = hash_password('admin123')
    cursor.execute("INSERT OR IGNORE INTO users (id, username, password_hash, full_name, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?)",
                   (1, 'admin', admin_password_hash, 'System Administrator', 1, 1))
    
    # Set admin_password in settings to match the hash of 'admin123'
    cursor.execute("UPDATE settings SET setting_value = ? WHERE setting_key = 'admin_password'", (admin_password_hash,))

    # Set default permissions for Administrator
    pages = ['dashboard', 'inventory', 'purchase', 'sale', 'customers', 'suppliers', 'models', 'reports', 'returns', 'cheques', 'settings', 'roles', 'users', 'income_expense', 'customer_ledger', 'supplier_ledger']
    for p in pages:
        cursor.execute("INSERT OR IGNORE INTO role_permissions (role_id, page, can_view, can_add, can_edit, can_delete) VALUES (?, ?, 1, 1, 1, 1)", (1, p))
    
    # Set default permissions for Standard User
    standard_user_pages = ['dashboard', 'inventory', 'sale', 'returns', 'cheques', 'customer_ledger', 'supplier_ledger', 'models', 'customers', 'suppliers', 'income_expense']
    for p in standard_user_pages:
        cursor.execute("INSERT OR IGNORE INTO role_permissions (role_id, page, can_view, can_add, can_edit, can_delete) VALUES (?, ?, 1, 1, 1, 0)", (2, p))

    # Seed data for models if empty
    cursor.execute("SELECT COUNT(*) FROM models")
    if cursor.fetchone()[0] == 0:
        models_seed = [
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
        ]
        cursor.executemany("INSERT INTO models (model_code, model_name, category, short_code) VALUES (?, ?, ?, ?)", models_seed)

    # Seed data for suppliers if empty
    cursor.execute("SELECT COUNT(*) FROM suppliers")
    if cursor.fetchone()[0] == 0:
        cursor.execute("INSERT INTO suppliers (name, contact, address) VALUES (?, ?, ?)", ('Default Supplier', '0300-0000000', 'Pakistan'))

    # Seed data for customers if empty
    cursor.execute("SELECT COUNT(*) FROM customers")
    if cursor.fetchone()[0] == 0:
        customers_seed = [
            ['Ahmed Ali', '0321-1234567', '35201-1234567-1', 'Dera Ghazi Khan, Punjab'],
            ['Muhammad Usman', '0333-7654321', '35201-7654321-3', 'Muzaffargarh, Punjab'],
            ['Bilal Hussain', '0345-9876543', '35201-9876543-5', 'Rajanpur, Punjab'],
            ['Zafar Iqbal', '0312-4567890', '35201-4567890-7', 'Layyah, Punjab'],
        ]
        cursor.executemany("INSERT INTO customers (name, phone, cnic, address) VALUES (?, ?, ?, ?)", customers_seed)

    conn.commit()
    conn.close()
    return True

def db_exists():
    if not os.path.exists(DB_NAME):
        return False
    try:
        conn = sqlite3.connect(DB_NAME)
        cursor = conn.cursor()
        cursor.execute("SELECT 1 FROM settings LIMIT 1")
        conn.close()
        return True
    except sqlite3.OperationalError:
        return False

def get_setting(key, default=None):
    if not db_exists():
        return default
    conn = db_connect()
    if not conn:
        return default
    cursor = conn.cursor()
    cursor.execute("SELECT setting_value FROM settings WHERE setting_key = ?", (key,))
    result = cursor.fetchone()
    conn.close()
    return result[0] if result else default

def update_setting(key, value):
    conn = db_connect()
    if not conn:
        return False
    cursor = conn.cursor()
    try:
        cursor.execute("UPDATE settings SET setting_value = ? WHERE setting_key = ?", (value, key))
        conn.commit()
        return True
    except sqlite3.Error as e:
        messagebox.showerror("Database Error", f"Failed to update setting: {e}")
        return False
    finally:
        conn.close()

# --- Utility Functions ---
def hash_password(password):
    return hashlib.sha256(password.encode()).hexdigest()

def verify_password(stored_hash, provided_password):
    return stored_hash == hashlib.sha256(provided_password.encode()).hexdigest()

def fmt_money(value):
    return f"{CURRENCY_SYMBOL} {float(value):,.2f}"

def fmt_date(date_str):
    if not date_str or date_str == '0000-00-00':
        return '-'
    try:
        dt = datetime.strptime(date_str, '%Y-%m-%d')
        return dt.strftime('%d/%m/%Y')
    except ValueError:
        return date_str

def sanitize(value):
    if value is None:
        return ''
    return str(value).strip().replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;').replace('"', '&quot;').replace("'", '&#39;')

def is_valid_password(password):
    if len(password) < 8:
        return False, "Password must be at least 8 characters long."
    if not re.search(r"[!@#$%^&*(),.?\":{}|<>]", password):
        return False, "Password must contain at least one special character."
    return True, ""

# --- Session Management ---
def check_session():
    global LAST_ACTIVE_TIME
    if CURRENT_USER_ID is None:
        return

    now = datetime.now()
    if (now - LOGIN_TIME).total_seconds() > ABSOLUTE_SESSION_TIMEOUT_SECONDS:
        logout_user("Your session has expired (absolute limit). Please log in again.")
        return
    
    if (now - LAST_ACTIVE_TIME).total_seconds() > SESSION_TIMEOUT_SECONDS:
        logout_user("Session expired due to inactivity. Please log in again.")
        return
    LAST_ACTIVE_TIME = now
    root.after(60000, check_session) # Check every minute

def logout_user(message="You have been logged out."):
    global CURRENT_USER_ID, CURRENT_USER_ROLE, LOGIN_TIME, LAST_ACTIVE_TIME
    CURRENT_USER_ID = None
    CURRENT_USER_ROLE = None
    LOGIN_TIME = None
    LAST_ACTIVE_TIME = None
    if messagebox.showinfo("Logout", message):
        app.show_login_screen()

# --- Role-based Access Control (RBAC) ---
def get_user_role_id(user_id):
    conn = db_connect()
    if not conn: return None
    cursor = conn.cursor()
    cursor.execute("SELECT role_id FROM users WHERE id = ?", (user_id,))
    role_id = cursor.fetchone()
    conn.close()
    return role_id[0] if role_id else None

def get_user_role_name(role_id):
    conn = db_connect()
    if not conn: return "Unknown"
    cursor = conn.cursor()
    cursor.execute("SELECT name FROM roles WHERE id = ?", (role_id,))
    role_name = cursor.fetchone()
    conn.close()
    return role_name[0] if role_name else "Unknown"

def has_permission(page, action='view'):
    global CURRENT_USER_ID, CURRENT_USER_ROLE
    if CURRENT_USER_ID is None or CURRENT_USER_ROLE is None:
        return False

    conn = db_connect()
    if not conn: return False
    cursor = conn.cursor()

    if CURRENT_USER_ROLE == 'Administrator':
        conn.close()
        return True

    col_map = {
        'view': 'can_view',
        'add': 'can_add',
        'edit': 'can_edit',
        'delete': 'can_delete'
    }
    col = col_map.get(action)
    if not col:
        conn.close()
        return False

    cursor.execute(f"SELECT {col} FROM role_permissions WHERE role_id = ? AND page = ?", (get_user_role_id(CURRENT_USER_ID), page))
    result = cursor.fetchone()
    conn.close()
    return result and result[0] == 1

def require_permission(page, action='view'):
    if not has_permission(page, action):
        messagebox.showerror("Access Denied", f"You do not have permission to {action} {page}.")
        return False
    return True

# --- Captcha Generation (Math Captcha) ---
def generate_captcha(session_id):
    num1 = random.randint(1, 10)
    num2 = random.randint(1, 10)
    operator = random.choice(['+', '-', '*'])
    
    question = f"{num1} {operator} {num2}"
    
    if operator == '+':
        answer = num1 + num2
    elif operator == '-':
        answer = num1 - num2
    else: # '*'
        answer = num1 * num2
        
    conn = db_connect()
    if conn:
        cursor = conn.cursor()
        cursor.execute("INSERT OR REPLACE INTO captcha (session_id, question, answer) VALUES (?, ?, ?)",
                       (session_id, question, str(answer)))
        conn.commit()
        conn.close()

    # Generate image
    img_width, img_height = 200, 60
    image = Image.new('RGB', (img_width, img_height), color = (255, 255, 255))
    draw = ImageDraw.Draw(image)
    
    try:
        font = ImageFont.truetype("arial.ttf", 30) # Use a common font
    except IOError:
        font = ImageFont.load_default()

    # Add noise (random pixels)
    for _ in range(150):
        x = random.randint(0, img_width - 1)
        y = random.randint(0, img_height - 1)
        draw.point((x, y), fill=(random.randint(0, 255), random.randint(0, 255), random.randint(0, 255)))

    # Add random lines
    for _ in range(3):
        x1, y1 = random.randint(0, img_width // 2), random.randint(0, img_height)
        x2, y2 = random.randint(img_width // 2, img_width), random.randint(0, img_height)
        draw.line((x1, y1, x2, y2), fill=(random.randint(0, 255), random.randint(0, 255), random.randint(0, 255)), width=1)

    # Add question text
    bbox = draw.textbbox((0, 0), question, font=font)
    text_width = bbox[2] - bbox[0]
    text_height = bbox[3] - bbox[1]
    text_x = (img_width - text_width) // 2
    text_y = (img_height - text_height) // 2
    draw.text((text_x, text_y), question, font=font, fill=(0, 0, 0))

    img_byte_arr = io.BytesIO()
    image.save(img_byte_arr, format='PNG')
    return ImageTk.PhotoImage(Image.open(io.BytesIO(img_byte_arr.getvalue()))), question

def verify_captcha(session_id, user_answer):
    conn = db_connect()
    if not conn: return False
    cursor = conn.cursor()
    cursor.execute("SELECT answer FROM captcha WHERE session_id = ? AND timestamp > datetime('now', '-10 minutes')", (session_id,))
    result = cursor.fetchone()
    
    # Clean up captcha after use (or after timeout)
    cursor.execute("DELETE FROM captcha WHERE session_id = ? OR timestamp < datetime('now', '-10 minutes')", (session_id,))
    conn.commit()
    conn.close()

    return result and str(user_answer) == result[0]

# --- Main Application Class ---
class BNIApp:
    def __init__(self, master):
        self.master = master
        master.title("BNI Enterprises - Bike Dealer Management System")
        master.geometry("1200x700")
        master.state('zoomed') # Start maximized
        
        self.style = ttk.Style()
        self.style.theme_use('clam') # Use 'clam' for better customization

        # Apply theme and dynamic styles
        self.apply_theme(get_setting('theme', 'light'))

        # Custom styles for components
        self.style.configure('TButton', background='#4a9eff', foreground='white', font=('Segoe UI', 10, 'bold'), borderwidth=0, relief='flat')
        self.style.map('TButton', background=[('active', '#2a7edf')])
        self.style.configure('TEntry', fieldbackground='#ffffff', foreground='#222222', borderwidth=1, relief='solid')
        self.style.configure('TCombobox', fieldbackground='#ffffff', foreground='#222222', borderwidth=1, relief='solid')
        
        self.style.configure('Treeview', rowheight=25)
        self.style.map('Treeview',
                       background=[('selected', '#4a9eff')])
        self.style.configure('Treeview.Heading',
                             font=('Segoe UI', 10, 'bold'),
                             background='#1e1e1e',
                             foreground='white',
                             bordercolor='#555555')
        self.style.map('Treeview.Heading',
                       background=[('active', '#3c3c3c')])

        self.install_check()

    def apply_theme(self, theme_name):
        if theme_name == 'dark':
            self.bg_color = '#2b2b2b'
            self.fg_color = '#d4d4d4'
            self.dark_mode_active = True
        else: # light
            self.bg_color = '#f0f0f0'
            self.fg_color = '#222222'
            self.dark_mode_active = False
            
        self.master.config(bg=self.bg_color)
        
        if hasattr(self, 'style'):
            self.style.configure('TFrame', background=self.bg_color)
            self.style.configure('TLabel', background=self.bg_color, foreground=self.fg_color)
            self.style.configure('TCheckbutton', background=self.bg_color, foreground=self.fg_color)
            self.style.configure('Treeview', background=self.bg_color, foreground=self.fg_color, fieldbackground=self.bg_color, bordercolor=self.bg_color)

    def install_check(self):
        if not db_exists():
            self.show_install_screen()
        else:
            global CURRENCY_SYMBOL, TAX_RATE, TAX_ON_PRICE_TYPE, SHOW_PURCHASE_ON_INVOICE
            CURRENCY_SYMBOL = get_setting('currency', 'Rs.')
            TAX_RATE = float(get_setting('tax_rate', '0.1'))
            TAX_ON_PRICE_TYPE = get_setting('tax_on', 'purchase_price')
            SHOW_PURCHASE_ON_INVOICE = get_setting('show_purchase_on_invoice', '0') == '1'
            self.show_login_screen()

    def show_install_screen(self):
        for widget in self.master.winfo_children():
            widget.destroy()

        install_frame = ttk.Frame(self.master, padding="30", style='TFrame')
        install_frame.place(relx=0.5, rely=0.5, anchor=tk.CENTER)

        ttk.Label(install_frame, text="⚡", font=("Segoe UI", 40), style='TLabel').pack(pady=10)
        ttk.Label(install_frame, text="BNI Enterprises Setup", font=("Segoe UI", 18, "bold"), style='TLabel').pack(pady=5)
        ttk.Label(install_frame, text="Welcome! The database needs to be installed. Click the button below to create the database and all required tables automatically.",
                  font=("Segoe UI", 10), wraplength=350, style='TLabel').pack(pady=10)

        install_button = ttk.Button(install_frame, text="⚡ Install Database", command=self.do_install_database, style='TButton')
        install_button.pack(pady=20, ipadx=10, ipady=5)

        ttk.Label(install_frame, text=f"Author: {AUTHOR} | v{APP_VERSION}", font=("Segoe UI", 8), style='TLabel', foreground='gray').pack(pady=10)

    def do_install_database(self):
        if install_database():
            messagebox.showinfo("Installation Complete", "Database installed successfully! You can now log in.")
            self.install_check()
        else:
            messagebox.showerror("Installation Failed", "Database installation failed. Please check permissions or database connection.")

    def show_login_screen(self):
        for widget in self.master.winfo_children():
            widget.destroy()

        login_frame = ttk.Frame(self.master, padding="30", style='TFrame')
        login_frame.place(relx=0.5, rely=0.5, anchor=tk.CENTER)

        ttk.Label(login_frame, text="⚡", font=("Segoe UI", 40), style='TLabel').pack(pady=10)
        ttk.Label(login_frame, text=get_setting('company_name', 'BNI Enterprises'), font=("Segoe UI", 18, "bold"), style='TLabel').pack(pady=5)
        ttk.Label(login_frame, text=get_setting('branch_name', 'Dera (Ahmed Metro)'), font=("Segoe UI", 10), style='TLabel').pack(pady=5)

        ttk.Label(login_frame, text="Username:", style='TLabel').pack(anchor='w', pady=(10, 0))
        self.username_entry = ttk.Entry(login_frame, width=30, style='TEntry')
        self.username_entry.pack(pady=5)
        self.username_entry.insert(0, 'admin') # Prefill for convenience

        ttk.Label(login_frame, text="Password:", style='TLabel').pack(anchor='w', pady=(10, 0))
        self.password_entry = ttk.Entry(login_frame, show="*", width=30, style='TEntry')
        self.password_entry.pack(pady=5)
        self.password_entry.insert(0, 'admin123') # Prefill for convenience

        self.captcha_session_id = str(random.randint(100000, 999999)) # Unique session ID for captcha
        self.captcha_image_tk, self.captcha_question = generate_captcha(self.captcha_session_id)

        self.captcha_label = ttk.Label(login_frame, image=self.captcha_image_tk, style='TLabel')
        self.captcha_label.pack(pady=5)

        ttk.Label(login_frame, text="Captcha Answer:", style='TLabel').pack(anchor='w', pady=(5, 0))
        self.captcha_entry = ttk.Entry(login_frame, width=30, style='TEntry')
        self.captcha_entry.pack(pady=5)

        login_button = ttk.Button(login_frame, text="🔐 Login", command=self.do_login, style='TButton')
        login_button.pack(pady=10, ipadx=10, ipady=5)
        
        ttk.Label(login_frame, text=f"Author: {AUTHOR} | v{APP_VERSION}", font=("Segoe UI", 8), style='TLabel', foreground='gray').pack(pady=10)

    def do_login(self):
        global CURRENT_USER_ID, CURRENT_USER_ROLE, LOGIN_TIME, LAST_ACTIVE_TIME, LOGIN_ATTEMPTS
        username = self.username_entry.get()
        password = self.password_entry.get()
        captcha_answer = self.captcha_entry.get()
        
        if not verify_captcha(self.captcha_session_id, captcha_answer):
            messagebox.showerror("Login Failed", "Incorrect captcha. Please try again.")
            self.refresh_captcha(self.captcha_label)
            return

        # Anti-brute-force check
        now = datetime.now()
        if username in LOGIN_ATTEMPTS:
            attempts, last_attempt_time = LOGIN_ATTEMPTS[username]
            if attempts >= MAX_LOGIN_ATTEMPTS and (now - last_attempt_time).total_seconds() < LOCKOUT_TIME_MINUTES * 60:
                remaining_time = int(LOCKOUT_TIME_MINUTES * 60 - (now - last_attempt_time).total_seconds())
                messagebox.showerror("Account Locked", f"Too many failed login attempts. Please try again after {remaining_time // 60} minutes and {remaining_time % 60} seconds.")
                self.refresh_captcha(self.captcha_label)
                return
            elif (now - last_attempt_time).total_seconds() >= LOCKOUT_TIME_MINUTES * 60:
                LOGIN_ATTEMPTS[username] = (0, now) # Reset attempts after lockout time
        else:
            LOGIN_ATTEMPTS[username] = (0, now) # Initialize for new user

        conn = db_connect()
        if not conn:
            return

        cursor = conn.cursor()
        cursor.execute("SELECT id, password_hash, is_active, role_id FROM users WHERE username = ?", (username,))
        user = cursor.fetchone()
        conn.close()

        if user and user[2] == 1 and verify_password(user[1], password):
            CURRENT_USER_ID = user[0]
            CURRENT_USER_ROLE = get_user_role_name(user[3])
            LOGIN_TIME = datetime.now()
            LAST_ACTIVE_TIME = datetime.now()
            LOGIN_ATTEMPTS.pop(username, None) # Clear attempts on successful login
            messagebox.showinfo("Login Success", f"Welcome, {username} ({CURRENT_USER_ROLE})!")
            self.show_main_app()
            self.master.after(60000, check_session) # Start session timeout check
        else:
            LOGIN_ATTEMPTS[username] = (LOGIN_ATTEMPTS[username][0] + 1, now) # Increment attempts
            messagebox.showerror("Login Failed", "Invalid username, password, or account is inactive.")
            self.refresh_captcha(self.captcha_label)

    def refresh_captcha(self, captcha_label_widget):
        self.captcha_session_id = str(random.randint(100000, 999999))
        self.captcha_image_tk, self.captcha_question = generate_captcha(self.captcha_session_id)
        captcha_label_widget.config(image=self.captcha_image_tk)
        captcha_label_widget.image = self.captcha_image_tk
        self.captcha_entry.delete(0, tk.END)

    def show_main_app(self):
        for widget in self.master.winfo_children():
            widget.destroy()

        self.master.grid_rowconfigure(0, weight=1)
        self.master.grid_columnconfigure(1, weight=1)

        # Sidebar
        self.sidebar_frame = ttk.Frame(self.master, width=220, style='TFrame', relief='solid', borderwidth=2)
        self.sidebar_frame.grid(row=0, column=0, sticky="ns", padx=(0,0), pady=(0,0))
        self.sidebar_frame.grid_propagate(False) # Prevent shrinking

        self.sidebar_collapsed = tk.BooleanVar(value=False)
        self.sidebar_width = 220
        self.collapsed_sidebar_width = 60

        self.create_sidebar(self.sidebar_frame)
        self.create_topbar()
        
        # Content Area
        self.content_frame = ttk.Frame(self.master, padding="10", style='TFrame')
        self.content_frame.grid(row=0, column=1, sticky="nsew", padx=(0,0), pady=(0,0))

        # Initialize with dashboard
        self.show_page('dashboard')
        
        # Make content frame scrollable if needed
        self.content_frame.grid_columnconfigure(0, weight=1)
        self.content_frame.grid_rowconfigure(0, weight=1)

    def toggle_sidebar(self):
        if self.sidebar_collapsed.get():
            self.sidebar_frame.config(width=self.sidebar_width)
            self.sidebar_collapsed.set(False)
            self.show_sidebar_full()
        else:
            self.sidebar_frame.config(width=self.collapsed_sidebar_width)
            self.sidebar_collapsed.set(True)
            self.show_sidebar_collapsed()

    def show_sidebar_full(self):
        for widget in self.sidebar_frame.winfo_children():
            widget.destroy()
        self.create_sidebar(self.sidebar_frame)

    def show_sidebar_collapsed(self):
        for widget in self.sidebar_frame.winfo_children():
            widget.destroy()
        
        ttk.Label(self.sidebar_frame, text="⚡", font=("Segoe UI", 20), style='TLabel').pack(pady=10)
        
        # Hamburger button for mobile/collapsed view
        self.collapse_button = ttk.Button(self.sidebar_frame, text="☰", command=self.toggle_sidebar, style='TButton')
        self.collapse_button.pack(pady=5, ipadx=5)

        nav_frame = ttk.Frame(self.sidebar_frame, style='TFrame')
        nav_frame.pack(fill='both', expand=True)

        for page_name, icon, _ in self.pages_nav:
            if has_permission(page_name, 'view'):
                btn = ttk.Button(nav_frame, text=icon, command=lambda p=page_name: self.show_page(p), style='TButton')
                btn.pack(fill='x', pady=2, padx=5)

        # Logout button
        logout_button = ttk.Button(self.sidebar_frame, text="🚪", command=lambda: logout_user(), style='TButton')
        logout_button.pack(side='bottom', fill='x', pady=10, padx=5)

    def create_sidebar(self, parent):
        # Header
        header_frame = ttk.Frame(parent, style='TFrame', relief='solid', borderwidth=1)
        header_frame.pack(fill='x', pady=(0, 5))
        ttk.Label(header_frame, text="⚡ " + get_setting('company_name', 'BNI Enterprises'), font=("Segoe UI", 12, "bold"), style='TLabel', foreground='#4a9eff').pack(pady=5)
        ttk.Label(header_frame, text=get_setting('branch_name', 'Dera (Ahmed Metro)'), font=("Segoe UI", 9), style='TLabel', foreground='gray').pack(pady=2)

        # Navigation
        self.all_nav = [
            ('dashboard', '⌂', 'Dashboard'),
            ('purchase', '📦', 'Purchase Entry'),
            ('inventory', '📋', 'Inventory / Stock'),
            ('sale', '🛒', 'Sales Entry'),
            ('returns', '↩', 'Returns'),
            ('cheques', '💳', 'Cheque Register'),
            ('income_expense', '💰', 'Income/Expense'),
            ('customer_ledger', '👤', 'Customer Ledger'),
            ('supplier_ledger', '🏭', 'Supplier Ledger'),
            ('reports', '📊', 'Reports'),
            ('models', '🚲', 'Models'),
            ('customers', '👥', 'Customers'),
            ('suppliers', '🏢', 'Suppliers'),
            ('users', '👨‍💼', 'Users'),
            ('roles', '🔑', 'Roles & Permissions'),
            ('settings', '⚙', 'Settings'),
        ]
        
        self.pages_nav = []
        for nav_item in self.all_nav:
            if has_permission(nav_item[0], 'view'):
                self.pages_nav.append(nav_item)

        nav_frame = ttk.Frame(parent, style='TFrame')
        nav_frame.pack(fill='both', expand=True)

        for page_name, icon, label_text in self.pages_nav:
            btn = ttk.Button(nav_frame, text=f"{icon} {label_text}", command=lambda p=page_name: self.show_page(p), style='TButton')
            btn.pack(fill='x', pady=2, padx=5)

        # Footer
        footer_frame = ttk.Frame(parent, style='TFrame', relief='solid', borderwidth=1)
        footer_frame.pack(side='bottom', fill='x', pady=(5,0))
        ttk.Label(footer_frame, text=f"Created by: {AUTHOR} – Bannu Software Solutions", font=("Segoe UI", 8), style='TLabel', foreground='gray').pack()
        ttk.Label(footer_frame, text="Website: https://www.yasinbss.com", font=("Segoe UI", 8), style='TLabel', foreground='gray').pack()
        ttk.Label(footer_frame, text="WhatsApp: 03361593533", font=("Segoe UI", 8), style='TLabel', foreground='gray').pack()
        logout_button = ttk.Button(footer_frame, text="🚪 Logout", command=lambda: logout_user(), style='TButton')
        logout_button.pack(pady=5, padx=5, fill='x')

    def create_topbar(self):
        self.topbar_frame = ttk.Frame(self.master, height=48, style='TFrame', relief='solid', borderwidth=2)
        self.topbar_frame.grid(row=0, column=1, sticky="new", padx=(0,0), pady=(0,0)) # Overlaps content frame top
        self.topbar_frame.grid_propagate(False)
        
        self.topbar_frame.grid_columnconfigure(0, weight=0) # For hamburger
        self.topbar_frame.grid_columnconfigure(1, weight=1) # For title
        self.topbar_frame.grid_columnconfigure(2, weight=0) # For user/theme/date

        # Hamburger button
        self.hamburger_button = ttk.Button(self.topbar_frame, text="☰", command=self.toggle_sidebar, style='TButton')
        self.hamburger_button.grid(row=0, column=0, padx=10, pady=5, sticky='w')

        self.page_title_label = ttk.Label(self.topbar_frame, text="Dashboard", font=("Segoe UI", 12, "bold"), style='TLabel')
        self.page_title_label.grid(row=0, column=1, sticky='w', padx=5)

        user_info_frame = ttk.Frame(self.topbar_frame, style='TFrame')
        user_info_frame.grid(row=0, column=2, sticky='e', padx=10)

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("SELECT full_name, username FROM users WHERE id = ?", (CURRENT_USER_ID,))
        user_data = cursor.fetchone()
        conn.close()
        
        user_name = user_data[0] if user_data[0] else user_data[1]

        ttk.Label(user_info_frame, text=f"👤 {sanitize(user_name)} ({CURRENT_USER_ROLE})", font=("Segoe UI", 9), style='TLabel', foreground='gray').pack(side='left', padx=5)

        theme_button = ttk.Button(user_info_frame, text="☀ Theme" if self.dark_mode_active else "🌙 Theme", command=self.toggle_theme, style='TButton')
        theme_button.pack(side='left', padx=5)

        self.date_time_label = ttk.Label(user_info_frame, text=datetime.now().strftime('%d/%m/%Y %H:%M'), font=("Segoe UI", 9), style='TLabel', foreground='gray')
        self.date_time_label.pack(side='left', padx=5)
        self.update_date_time()

    def update_date_time(self):
        self.date_time_label.config(text=datetime.now().strftime('%d/%m/%Y %H:%M'))
        self.master.after(1000, self.update_date_time) # Update every second

    def toggle_theme(self):
        new_theme = 'light' if self.dark_mode_active else 'dark'
        update_setting('theme', new_theme)
        self.apply_theme(new_theme)
        self.show_main_app() # Rebuild UI to apply theme (simplest way for now)

    def clear_content_frame(self):
        for widget in self.content_frame.winfo_children():
            widget.destroy()

    def show_page(self, page_name):
        self.clear_content_frame()
        self.page_title_label.config(text=f"{next((icon for p, icon, _ in self.all_nav if p == page_name), '')} {next((label for p, _, label in self.all_nav if p == page_name), page_name.replace('_', ' ').title())}")

        if not has_permission(page_name, 'view'):
            ttk.Label(self.content_frame, text="⛔ Access Denied", font=("Segoe UI", 24, "bold"), style='TLabel').pack(pady=50)
            ttk.Label(self.content_frame, text=f"You do not have permission to view the {page_name.replace('_', ' ').title()} page.", font=("Segoe UI", 12), style='TLabel').pack()
            return

        # Add page-specific content here
        if page_name == 'dashboard':
            self.create_dashboard_page()
        elif page_name == 'purchase':
            self.create_purchase_page()
        elif page_name == 'inventory':
            self.create_inventory_page()
        elif page_name == 'sale':
            self.create_sale_page()
        elif page_name == 'returns':
            self.create_returns_page()
        elif page_name == 'cheques':
            self.create_cheques_page()
        elif page_name == 'income_expense':
            self.create_income_expense_page()
        elif page_name == 'customer_ledger':
            self.create_customer_ledger_page()
        elif page_name == 'supplier_ledger':
            self.create_supplier_ledger_page()
        elif page_name == 'reports':
            self.create_reports_page()
        elif page_name == 'models':
            self.create_models_page()
        elif page_name == 'customers':
            self.create_customers_page()
        elif page_name == 'suppliers':
            self.create_suppliers_page()
        elif page_name == 'users':
            self.create_users_page()
        elif page_name == 'roles':
            self.create_roles_page()
        elif page_name == 'settings':
            self.create_settings_page()

    # --- Dashboard Page ---
    def create_dashboard_page(self):
        conn = db_connect()
        cursor = conn.cursor()

        total_stock = cursor.execute("SELECT COUNT(*) FROM bikes WHERE status='in_stock'").fetchone()[0]
        total_sold = cursor.execute("SELECT COUNT(*) FROM bikes WHERE status='sold'").fetchone()[0]
        total_returned = cursor.execute("SELECT COUNT(*) FROM bikes WHERE status='returned'").fetchone()[0]
        total_purchase_val = cursor.execute("SELECT SUM(purchase_price) FROM bikes").fetchone()[0] or 0
        total_sales_val = cursor.execute("SELECT SUM(selling_price) FROM bikes WHERE status='sold'").fetchone()[0] or 0
        total_tax = cursor.execute("SELECT SUM(tax_amount) FROM bikes WHERE status='sold'").fetchone()[0] or 0
        total_margin = cursor.execute("SELECT SUM(margin) FROM bikes WHERE status='sold'").fetchone()[0] or 0
        
        chq_issued = cursor.execute("SELECT COUNT(*), SUM(amount) FROM cheque_register WHERE type='payment'").fetchone()
        chq_received = cursor.execute("SELECT COUNT(*), SUM(amount) FROM cheque_register WHERE type='receipt'").fetchone()
        pending_cheques = cursor.execute("SELECT COUNT(*), SUM(amount) FROM cheque_register WHERE status='pending'").fetchone()

        conn.close()

        # Cards Grid
        cards_frame = ttk.Frame(self.content_frame, style='TFrame')
        cards_frame.pack(fill='x', pady=10)
        
        cards_data = [
            ("📦 In Stock", total_stock, "bikes", "accent"),
            ("✅ Total Sold", total_sold, "bikes", "success"),
            ("↩ Returned", total_returned, "bikes", "danger"),
            ("💰 Purchase Value", fmt_money(total_purchase_val), "", "warning"),
            ("💵 Sales Value", fmt_money(total_sales_val), "", "success"),
            ("🧾 Total Tax Paid", fmt_money(total_tax), "", "default"),
            ("📈 Total Profit", fmt_money(total_margin), "", "success"),
            ("💳 Pending Cheques", f"{pending_cheques[0] or 0}", f"{fmt_money(pending_cheques[1] or 0)}", "warning"),
        ]

        for i, (label, value, sub_text, style_class) in enumerate(cards_data):
            card = ttk.Frame(cards_frame, style='TFrame', relief='solid', borderwidth=2, padding=10)
            card.grid(row=i // 4, column=i % 4, padx=5, pady=5, sticky="nsew") # 4 columns
            
            ttk.Label(card, text=label, font=('Segoe UI', 9), style='TLabel', foreground='gray').pack(anchor='w')
            ttk.Label(card, text=value, font=('Segoe UI', 14, 'bold'), style='TLabel').pack(anchor='w')
            if sub_text:
                ttk.Label(card, text=sub_text, font=('Segoe UI', 9), style='TLabel', foreground='gray').pack(anchor='w')

        # Model-wise Stock Summary
        ttk.Label(self.content_frame, text="📊 Model-wise Stock Summary", font=("Segoe UI", 12, "bold"), style='TLabel').pack(pady=(20, 10), anchor='w')
        
        model_summary_frame = ttk.Frame(self.content_frame, style='TFrame')
        model_summary_frame.pack(fill='both', expand=True, padx=5)

        model_tree = ttk.Treeview(model_summary_frame, columns=("model", "category", "total_inv", "sold_cnt", "ret_cnt", "avail_cnt"), show="headings", style='Treeview')
        model_tree.pack(side='left', fill='both', expand=True)

        model_tree.heading("model", text="Model", anchor='w')
        model_tree.heading("category", text="Category", anchor='w')
        model_tree.heading("total_inv", text="Total Inventory", anchor='e')
        model_tree.heading("sold_cnt", text="Sold", anchor='e')
        model_tree.heading("ret_cnt", text="Returned", anchor='e')
        model_tree.heading("avail_cnt", text="Available", anchor='e')

        model_tree.column("model", width=150)
        model_tree.column("category", width=100)
        model_tree.column("total_inv", width=80, anchor='e')
        model_tree.column("sold_cnt", width=60, anchor='e')
        model_tree.column("ret_cnt", width=60, anchor='e')
        model_tree.column("avail_cnt", width=60, anchor='e')

        model_summary_data = self.get_model_summary_data()
        for row in model_summary_data:
            model_tree.insert("", "end", values=row)

        # Recent Sales and Purchases
        bottom_frame = ttk.Frame(self.content_frame, style='TFrame')
        bottom_frame.pack(fill='x', pady=10)
        bottom_frame.grid_columnconfigure(0, weight=1)
        bottom_frame.grid_columnconfigure(1, weight=1)

        # Recent Sales
        sales_frame = ttk.LabelFrame(bottom_frame, text="🛒 Recent 10 Sales", style='TFrame')
        sales_frame.grid(row=0, column=0, padx=5, pady=5, sticky="nsew")
        
        sales_tree = ttk.Treeview(sales_frame, columns=("date", "chassis", "model", "price", "margin"), show="headings", style='Treeview')
        sales_tree.pack(fill='both', expand=True)
        
        sales_tree.heading("date", text="Date", anchor='w')
        sales_tree.heading("chassis", text="Chassis", anchor='w')
        sales_tree.heading("model", text="Model", anchor='w')
        sales_tree.heading("price", text="Price", anchor='e')
        sales_tree.heading("margin", text="Margin", anchor='e')

        sales_tree.column("date", width=80)
        sales_tree.column("chassis", width=120)
        sales_tree.column("model", width=100)
        sales_tree.column("price", width=80, anchor='e')
        sales_tree.column("margin", width=80, anchor='e')

        recent_sales_data = self.get_recent_sales_data()
        for row in recent_sales_data:
            sales_tree.insert("", "end", values=row)

        # Recent Purchases
        purchases_frame = ttk.LabelFrame(bottom_frame, text="📦 Recent 10 Purchases", style='TFrame')
        purchases_frame.grid(row=0, column=1, padx=5, pady=5, sticky="nsew")

        purchases_tree = ttk.Treeview(purchases_frame, columns=("date", "chassis", "model", "price", "status"), show="headings", style='Treeview')
        purchases_tree.pack(fill='both', expand=True)
        
        purchases_tree.heading("date", text="Date", anchor='w')
        purchases_tree.heading("chassis", text="Chassis", anchor='w')
        purchases_tree.heading("model", text="Model", anchor='w')
        purchases_tree.heading("price", text="Price", anchor='e')
        purchases_tree.heading("status", text="Status", anchor='w')

        purchases_tree.column("date", width=80)
        purchases_tree.column("chassis", width=120)
        purchases_tree.column("model", width=100)
        purchases_tree.column("price", width=80, anchor='e')
        purchases_tree.column("status", width=80, anchor='w')

        recent_purchases_data = self.get_recent_purchases_data()
        for row in recent_purchases_data:
            purchases_tree.insert("", "end", values=row)

    def get_model_summary_data(self):
        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("""
            SELECT m.model_name, m.category,
            COUNT(b.id) as total_inv,
            SUM(CASE WHEN b.status='sold' THEN 1 ELSE 0 END) as sold_cnt,
            SUM(CASE WHEN b.status='returned' THEN 1 ELSE 0 END) as ret_cnt,
            SUM(CASE WHEN b.status='in_stock' THEN 1 ELSE 0 END) as avail_cnt
            FROM models m LEFT JOIN bikes b ON m.id=b.model_id
            GROUP BY m.id, m.model_name, m.category ORDER BY m.model_name
        """)
        data = []
        for row in cursor.fetchall():
            data.append((sanitize(row[0]), sanitize(row[1]), row[2], row[3], row[4], row[5]))
        conn.close()
        return data

    def get_recent_sales_data(self):
        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("""
            SELECT b.chassis_number, b.selling_date, b.selling_price, b.margin, m.model_name
            FROM bikes b LEFT JOIN models m ON b.model_id=m.id
            WHERE b.status='sold' ORDER BY b.selling_date DESC LIMIT 10
        """)
        data = []
        for row in cursor.fetchall():
            data.append((fmt_date(row[1]), sanitize(row[0]), sanitize(row[4]), fmt_money(row[2]), fmt_money(row[3])))
        conn.close()
        return data

    def get_recent_purchases_data(self):
        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("""
            SELECT b.chassis_number, b.inventory_date, b.purchase_price, b.status, m.model_name
            FROM bikes b LEFT JOIN models m ON b.model_id=m.id
            ORDER BY b.created_at DESC LIMIT 10
        """)
        data = []
        for row in cursor.fetchall():
            data.append((fmt_date(row[1]), sanitize(row[0]), sanitize(row[4]), fmt_money(row[2]), sanitize(row[3].upper())))
        conn.close()
        return data

    # --- Purchase Page ---
    def create_purchase_page(self):
        if not require_permission('purchase', 'add'): return

        self.purchase_form_frame = ttk.Frame(self.content_frame, style='TFrame', padding=10)
        self.purchase_form_frame.pack(fill='both', expand=True)

        # Purchase Order Details
        po_details_frame = ttk.LabelFrame(self.purchase_form_frame, text="📦 Purchase Order Details", style='TFrame', padding=10)
        po_details_frame.pack(fill='x', pady=10)

        # Row 1
        row1 = ttk.Frame(po_details_frame, style='TFrame')
        row1.pack(fill='x', pady=5)
        
        ttk.Label(row1, text="Order Date:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.po_order_date = ttk.Entry(row1)
        self.po_order_date.insert(0, datetime.now().strftime('%Y-%m-%d'))
        self.po_order_date.grid(row=0, column=1, sticky='ew', padx=5, pady=2)

        ttk.Label(row1, text="Inventory Date:").grid(row=0, column=2, sticky='w', padx=5, pady=2)
        self.po_inventory_date = ttk.Entry(row1)
        self.po_inventory_date.insert(0, datetime.now().strftime('%Y-%m-%d'))
        self.po_inventory_date.grid(row=0, column=3, sticky='ew', padx=5, pady=2)

        ttk.Label(row1, text="Supplier:").grid(row=0, column=4, sticky='w', padx=5, pady=2)
        self.po_supplier_id_var = tk.StringVar()
        self.po_supplier_combo = ttk.Combobox(row1, textvariable=self.po_supplier_id_var, state='readonly')
        self.po_supplier_combo.grid(row=0, column=5, sticky='ew', padx=5, pady=2)
        self.load_suppliers_into_combo(self.po_supplier_combo)
        ttk.Button(row1, text="+", command=lambda: self.open_add_dialog('suppliers'), style='TButton').grid(row=0, column=6, padx=2, pady=2)

        row1.grid_columnconfigure(1, weight=1)
        row1.grid_columnconfigure(3, weight=1)
        row1.grid_columnconfigure(5, weight=1)

        # Row 2 (Cheque Details)
        row2 = ttk.Frame(po_details_frame, style='TFrame')
        row2.pack(fill='x', pady=5)

        ttk.Label(row2, text="Cheque Number:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.po_cheque_number = ttk.Entry(row2)
        self.po_cheque_number.grid(row=0, column=1, sticky='ew', padx=5, pady=2)

        ttk.Label(row2, text="Bank Name:").grid(row=0, column=2, sticky='w', padx=5, pady=2)
        self.po_bank_name = ttk.Entry(row2)
        self.po_bank_name.grid(row=0, column=3, sticky='ew', padx=5, pady=2)

        ttk.Label(row2, text="Cheque Date:").grid(row=0, column=4, sticky='w', padx=5, pady=2)
        self.po_cheque_date = ttk.Entry(row2)
        self.po_cheque_date.grid(row=0, column=5, sticky='ew', padx=5, pady=2)

        ttk.Label(row2, text="Cheque Amount:").grid(row=0, column=6, sticky='w', padx=5, pady=2)
        self.po_cheque_amount = ttk.Entry(row2)
        self.po_cheque_amount.grid(row=0, column=7, sticky='ew', padx=5, pady=2)

        row2.grid_columnconfigure(1, weight=1)
        row2.grid_columnconfigure(3, weight=1)
        row2.grid_columnconfigure(5, weight=1)
        row2.grid_columnconfigure(7, weight=1)

        # Row 3 (Notes)
        row3 = ttk.Frame(po_details_frame, style='TFrame')
        row3.pack(fill='x', pady=5)
        ttk.Label(row3, text="Notes:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.po_notes = tk.Text(row3, height=3, width=50)
        self.po_notes.grid(row=0, column=1, sticky='ew', padx=5, pady=2)
        row3.grid_columnconfigure(1, weight=1)

        # Bike Units Section
        bike_units_frame = ttk.LabelFrame(self.purchase_form_frame, text="🚲 Bike Units", style='TFrame', padding=10)
        bike_units_frame.pack(fill='both', expand=True, pady=10)

        self.bikes_entry_frame = ttk.Frame(bike_units_frame, style='TFrame')
        self.bikes_entry_frame.pack(fill='both', expand=True)

        self.bike_entries = [] # List to hold (entry_widgets, model_combo_ref) for each bike row

        ttk.Button(bike_units_frame, text="+ Add Bike", command=self.add_bike_row, style='TButton').pack(pady=5)

        # Action Buttons
        button_frame = ttk.Frame(self.purchase_form_frame, style='TFrame')
        button_frame.pack(fill='x', pady=10)
        ttk.Button(button_frame, text="💾 Save Purchase Order", command=self.save_purchase_order, style='TButton').pack(side='left', padx=5)
        ttk.Button(button_frame, text="← Back to Inventory", command=lambda: self.show_page('inventory'), style='TButton').pack(side='left', padx=5)

        self.add_bike_row() # Add one row by default

    def add_bike_row(self):
        bike_row_index = len(self.bike_entries)
        
        row_frame = ttk.Frame(self.bikes_entry_frame, style='TFrame', relief='solid', borderwidth=1, padding=5)
        row_frame.pack(fill='x', pady=5)

        ttk.Label(row_frame, text=f"Bike #{bike_row_index + 1}", font=('Segoe UI', 10, 'bold'), style='TLabel').grid(row=0, column=0, sticky='w')
        ttk.Button(row_frame, text="✕ Remove", command=lambda idx=bike_row_index: self.remove_bike_row(idx), style='TButton').grid(row=0, column=6, sticky='e')

        # Bike details entries
        ttk.Label(row_frame, text="Chassis #:", style='TLabel').grid(row=1, column=0, sticky='w')
        chassis_entry = ttk.Entry(row_frame)
        chassis_entry.grid(row=1, column=1, sticky='ew', padx=2)

        ttk.Label(row_frame, text="Motor #:", style='TLabel').grid(row=1, column=2, sticky='w')
        motor_entry = ttk.Entry(row_frame)
        motor_entry.grid(row=1, column=3, sticky='ew', padx=2)

        ttk.Label(row_frame, text="Model:", style='TLabel').grid(row=1, column=4, sticky='w')
        model_id_var = tk.StringVar()
        model_combo = ttk.Combobox(row_frame, textvariable=model_id_var, state='readonly')
        self.load_models_into_combo(model_combo)
        model_combo.grid(row=1, column=5, sticky='ew', padx=2)
        ttk.Button(row_frame, text="+", command=lambda: self.open_add_dialog('models'), style='TButton').grid(row=1, column=6, padx=2)

        ttk.Label(row_frame, text="Color:", style='TLabel').grid(row=2, column=0, sticky='w')
        color_entry = ttk.Entry(row_frame)
        color_entry.grid(row=2, column=1, sticky='ew', padx=2)

        ttk.Label(row_frame, text="Purchase Price:", style='TLabel').grid(row=2, column=2, sticky='w')
        price_entry = ttk.Entry(row_frame)
        price_entry.grid(row=2, column=3, sticky='ew', padx=2)
        
        ttk.Label(row_frame, text="Safeguard Notes:", style='TLabel').grid(row=2, column=4, sticky='w')
        safeguard_entry = ttk.Entry(row_frame)
        safeguard_entry.grid(row=2, column=5, sticky='ew', padx=2)

        ttk.Label(row_frame, text="Accessories:", style='TLabel').grid(row=3, column=0, sticky='w')
        accessories_entry = ttk.Entry(row_frame)
        accessories_entry.grid(row=3, column=1, sticky='ew', padx=2)

        ttk.Label(row_frame, text="Notes:", style='TLabel').grid(row=3, column=2, sticky='w')
        notes_entry = ttk.Entry(row_frame)
        notes_entry.grid(row=3, column=3, sticky='ew', padx=2)

        # Configure columns to expand
        row_frame.grid_columnconfigure(1, weight=1)
        row_frame.grid_columnconfigure(3, weight=1)
        row_frame.grid_columnconfigure(5, weight=1)

        self.bike_entries.append({'frame': row_frame, 'chassis': chassis_entry, 'motor': motor_entry, 
                                  'model_var': model_id_var, 'model_combo': model_combo, 'color': color_entry, 
                                  'price': price_entry, 'safeguard': safeguard_entry, 'accessories': accessories_entry,
                                  'notes': notes_entry})

    def remove_bike_row(self, index):
        if len(self.bike_entries) > 1: # Always keep at least one row
            self.bike_entries[index]['frame'].destroy()
            self.bike_entries.pop(index)
            # Re-label remaining rows
            for i, entry_set in enumerate(self.bike_entries):
                entry_set['frame'].winfo_children()[0].config(text=f"Bike #{i + 1}")
        else:
            messagebox.showwarning("Cannot Remove", "At least one bike entry is required.")

    def load_suppliers_into_combo(self, combo_widget, include_all=False):
        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("SELECT id, name FROM suppliers ORDER BY name")
        suppliers = cursor.fetchall()
        conn.close()
        
        options = [f"{s[0]} - {s[1]}" for s in suppliers]
        if include_all:
            combo_widget['values'] = ["0 - All Suppliers"] + options
            combo_widget.set("0 - All Suppliers")
        else:
            combo_widget['values'] = options
            combo_widget.set("") # Clear selection

    def load_models_into_combo(self, combo_widget):
        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("SELECT id, model_code, model_name FROM models ORDER BY model_name")
        models = cursor.fetchall()
        conn.close()

        combo_widget['values'] = [f"{m[0]} - {m[1]} - {m[2]}" for m in models]
        combo_widget.set("") # Clear selection
        
    def open_add_dialog(self, entity_type):
        dialog = tk.Toplevel(self.master)
        dialog.title(f"Add New {entity_type.title()}")
        dialog.transient(self.master)
        dialog.grab_set()
        dialog.focus_set()

        frame = ttk.Frame(dialog, padding=10, style='TFrame')
        frame.pack(fill='both', expand=True)

        if entity_type == 'suppliers':
            ttk.Label(frame, text="Name:").pack(anchor='w')
            name_entry = ttk.Entry(frame, width=40)
            name_entry.pack(fill='x', pady=2)
            ttk.Label(frame, text="Contact:").pack(anchor='w')
            contact_entry = ttk.Entry(frame, width=40)
            contact_entry.pack(fill='x', pady=2)
            ttk.Label(frame, text="Address:").pack(anchor='w')
            address_text = tk.Text(frame, height=3, width=40)
            address_text.pack(fill='x', pady=2)
            
            def save_supplier():
                if not require_permission('suppliers', 'add'): return
                name = sanitize(name_entry.get())
                contact = sanitize(contact_entry.get())
                address = sanitize(address_text.get("1.0", tk.END))
                if not name:
                    messagebox.showerror("Validation Error", "Supplier name is required.")
                    return
                conn = db_connect()
                cursor = conn.cursor()
                cursor.execute("INSERT INTO suppliers (name, contact, address) VALUES (?, ?, ?)", (name, contact, address))
                conn.commit()
                conn.close()
                messagebox.showinfo("Success", "Supplier added.")
                self.load_suppliers_into_combo(self.po_supplier_combo) # Refresh combo
                dialog.destroy()
            ttk.Button(frame, text="Save", command=save_supplier, style='TButton').pack(pady=10)

        elif entity_type == 'models':
            ttk.Label(frame, text="Model Code:").pack(anchor='w')
            code_entry = ttk.Entry(frame, width=40)
            code_entry.pack(fill='x', pady=2)
            ttk.Label(frame, text="Model Name:").pack(anchor='w')
            name_entry = ttk.Entry(frame, width=40)
            name_entry.pack(fill='x', pady=2)
            ttk.Label(frame, text="Category:").pack(anchor='w')
            category_entry = ttk.Entry(frame, width=40)
            category_entry.insert(0, "Electric Bike")
            category_entry.pack(fill='x', pady=2)
            ttk.Label(frame, text="Short Code:").pack(anchor='w')
            short_code_entry = ttk.Entry(frame, width=40)
            short_code_entry.pack(fill='x', pady=2)

            def save_model():
                if not require_permission('models', 'add'): return
                model_code = sanitize(code_entry.get())
                model_name = sanitize(name_entry.get())
                category = sanitize(category_entry.get())
                short_code = sanitize(short_code_entry.get())
                if not model_code or not model_name:
                    messagebox.showerror("Validation Error", "Model code and name are required.")
                    return
                conn = db_connect()
                cursor = conn.cursor()
                cursor.execute("INSERT INTO models (model_code, model_name, category, short_code) VALUES (?, ?, ?, ?)", (model_code, model_name, category, short_code))
                conn.commit()
                conn.close()
                messagebox.showinfo("Success", "Model added.")
                # Refresh all model combos in active bike rows
                for entry_set in self.bike_entries:
                    self.load_models_into_combo(entry_set['model_combo'])
                dialog.destroy()
            ttk.Button(frame, text="Save", command=save_model, style='TButton').pack(pady=10)

    def save_purchase_order(self):
        if not require_permission('purchase', 'add'): return

        # Validate PO details
        order_date = self.po_order_date.get()
        inventory_date = self.po_inventory_date.get()
        supplier_id_str = self.po_supplier_id_var.get().split(' - ')[0]
        supplier_id = int(supplier_id_str) if supplier_id_str.isdigit() else 0

        cheque_number = sanitize(self.po_cheque_number.get())
        bank_name = sanitize(self.po_bank_name.get())
        cheque_date = sanitize(self.po_cheque_date.get()) or None
        cheque_amount = float(self.po_cheque_amount.get() or 0)
        po_notes = sanitize(self.po_notes.get("1.0", tk.END))

        if not order_date or not inventory_date or not supplier_id:
            messagebox.showerror("Validation Error", "Order Date, Inventory Date, and Supplier are required for the Purchase Order.")
            return

        bike_data_list = []
        for bike_entry in self.bike_entries:
            chassis = sanitize(bike_entry['chassis'].get())
            motor = sanitize(bike_entry['motor'].get())
            model_id_str = bike_entry['model_var'].get().split(' - ')[0]
            model_id = int(model_id_str) if model_id_str.isdigit() else 0
            color = sanitize(bike_entry['color'].get())
            price = float(bike_entry['price'].get() or 0)
            safeguard = sanitize(bike_entry['safeguard'].get())
            accessories = sanitize(bike_entry['accessories'].get())
            notes = sanitize(bike_entry['notes'].get())

            if not chassis or not model_id or not price:
                messagebox.showerror("Validation Error", f"Bike details (Chassis, Model, Purchase Price) for bike #{len(bike_data_list)+1} are required.")
                return
            
            # Check for duplicate chassis
            conn_check = db_connect()
            cursor_check = conn_check.cursor()
            cursor_check.execute("SELECT id FROM bikes WHERE chassis_number = ?", (chassis,))
            if cursor_check.fetchone():
                messagebox.showerror("Validation Error", f"Chassis number '{chassis}' already exists in inventory.")
                conn_check.close()
                return
            conn_check.close()

            bike_data_list.append({
                'chassis': chassis, 'motor': motor, 'model_id': model_id, 'color': color,
                'price': price, 'safeguard': safeguard, 'accessories': accessories, 'notes': notes
            })

        if not bike_data_list:
            messagebox.showerror("Validation Error", "At least one bike unit must be added.")
            return

        # Start transaction
        conn = db_connect()
        cursor = conn.cursor()
        try:
            # Insert Purchase Order
            total_units = len(bike_data_list)
            cursor.execute("""
                INSERT INTO purchase_orders (order_date, supplier_id, cheque_number, bank_name, cheque_date, cheque_amount, total_units, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            """, (order_date, supplier_id, cheque_number, bank_name, cheque_date, cheque_amount, total_units, po_notes))
            po_id = cursor.lastrowid

            # Insert Bikes
            tax_rate = float(get_setting('tax_rate', '0.1'))
            # tax_on = get_setting('tax_on', 'purchase_price') # Not directly used for tax_amount calc here in Python version, assuming based on purchase price

            for bike_data in bike_data_list:
                purchase_price = bike_data['price']
                tax_amount = (purchase_price * tax_rate) # Assuming tax on purchase price for inventory entry, sale calculation is dynamic
                
                cursor.execute("""
                    INSERT INTO bikes (purchase_order_id, order_date, inventory_date, chassis_number, motor_number, model_id, color, purchase_price, tax_amount, status, safeguard_notes, accessories, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'in_stock', ?, ?, ?)
                """, (po_id, order_date, inventory_date, bike_data['chassis'], bike_data['motor'], bike_data['model_id'], bike_data['color'], purchase_price, tax_amount, bike_data['safeguard'], bike_data['accessories'], bike_data['notes']))
            
            # Insert Cheque Register entry if applicable
            if cheque_number and cheque_amount > 0:
                cursor.execute("SELECT name FROM suppliers WHERE id = ?", (supplier_id,))
                supplier_name = cursor.fetchone()[0] or 'Unknown Supplier'
                
                cursor.execute("""
                    INSERT INTO cheque_register (cheque_number, bank_name, cheque_date, amount, type, status, reference_type, reference_id, party_name, notes)
                    VALUES (?, ?, ?, ?, 'payment', 'pending', 'purchase_order', ?, ?, ?)
                """, (cheque_number, bank_name, cheque_date, cheque_amount, po_id, supplier_name, po_notes))

            conn.commit()
            messagebox.showinfo("Success", f"Purchase order saved. {len(bike_data_list)} bike(s) added to inventory.")
            self.show_page('inventory')
        except sqlite3.Error as e:
            conn.rollback()
            messagebox.showerror("Database Error", f"Failed to save purchase order: {e}")
        finally:
            conn.close()

    # --- Inventory Page ---
    def create_inventory_page(self):
        if not require_permission('inventory', 'view'): return

        filter_frame = ttk.Frame(self.content_frame, style='TFrame', padding=10)
        filter_frame.pack(fill='x', pady=5)
        
        # Filter fields
        ttk.Label(filter_frame, text="Search:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.inv_search_var = tk.StringVar()
        ttk.Entry(filter_frame, textvariable=self.inv_search_var, width=25).grid(row=0, column=1, sticky='ew', padx=5, pady=2)

        ttk.Label(filter_frame, text="Status:").grid(row=0, column=2, sticky='w', padx=5, pady=2)
        self.inv_status_var = tk.StringVar(value="")
        ttk.Combobox(filter_frame, textvariable=self.inv_status_var, values=["", "in_stock", "sold", "returned", "reserved"], state='readonly', width=12).grid(row=0, column=3, sticky='ew', padx=5, pady=2)

        ttk.Label(filter_frame, text="Model:").grid(row=0, column=4, sticky='w', padx=5, pady=2)
        self.inv_model_var = tk.StringVar(value="0")
        self.inv_model_combo = ttk.Combobox(filter_frame, textvariable=self.inv_model_var, state='readonly', width=15)
        self.inv_model_combo.grid(row=0, column=5, sticky='ew', padx=5, pady=2)
        self.load_models_into_combo(self.inv_model_combo, include_all=True)

        ttk.Label(filter_frame, text="Color:").grid(row=0, column=6, sticky='w', padx=5, pady=2)
        self.inv_color_var = tk.StringVar()
        ttk.Entry(filter_frame, textvariable=self.inv_color_var, width=15).grid(row=0, column=7, sticky='ew', padx=5, pady=2)

        ttk.Label(filter_frame, text="From:").grid(row=0, column=8, sticky='w', padx=5, pady=2)
        self.inv_date_from = ttk.Entry(filter_frame, width=12)
        self.inv_date_from.grid(row=0, column=9, sticky='ew', padx=5, pady=2)

        ttk.Label(filter_frame, text="To:").grid(row=0, column=10, sticky='w', padx=5, pady=2)
        self.inv_date_to = ttk.Entry(filter_frame, width=12)
        self.inv_date_to.grid(row=0, column=11, sticky='ew', padx=5, pady=2)

        filter_button = ttk.Button(filter_frame, text="🔍 Filter", command=self.apply_inventory_filters, style='TButton')
        filter_button.grid(row=0, column=12, sticky='e', padx=5, pady=2)
        
        export_csv_button = ttk.Button(filter_frame, text="⬇ CSV", command=self.export_inventory_csv, style='TButton')
        export_csv_button.grid(row=0, column=13, sticky='e', padx=5, pady=2)
        
        print_button = ttk.Button(filter_frame, text="🖨 Print", command=self.print_inventory, style='TButton')
        print_button.grid(row=0, column=14, sticky='e', padx=5, pady=2)

        filter_frame.grid_columnconfigure(1, weight=1)
        filter_frame.grid_columnconfigure(3, weight=1)
        filter_frame.grid_columnconfigure(5, weight=1)
        filter_frame.grid_columnconfigure(7, weight=1)
        filter_frame.grid_columnconfigure(9, weight=1)
        filter_frame.grid_columnconfigure(11, weight=1)

        # Bulk Actions & Summary
        bulk_actions_frame = ttk.Frame(self.content_frame, style='TFrame', padding=5)
        bulk_actions_frame.pack(fill='x', pady=5)
        
        self.inv_total_records_label = ttk.Label(bulk_actions_frame, text="Showing 0 record(s)", style='TLabel')
        self.inv_total_records_label.pack(side='left', padx=5)

        ttk.Button(bulk_actions_frame, text="+ New Purchase", command=lambda: self.show_page('purchase'), style='TButton').pack(side='left', padx=5)
        ttk.Button(bulk_actions_frame, text="🗑 Delete Selected", command=self.bulk_delete_bikes, style='TButton').pack(side='left', padx=5)
        ttk.Button(bulk_actions_frame, text="⬇ Export Selected", command=self.bulk_export_bikes, style='TButton').pack(side='left', padx=5)
        self.select_all_checkbox = ttk.Checkbutton(bulk_actions_frame, text="☑ Select All", command=self.toggle_select_all_bikes, style='TCheckbutton')
        self.select_all_checkbox.pack(side='left', padx=5)

        # Inventory Table
        self.inventory_tree_frame = ttk.Frame(self.content_frame, style='TFrame')
        self.inventory_tree_frame.pack(fill='both', expand=True, padx=5, pady=5)

        columns = ("select", "sr", "chassis", "motor", "model", "color", "purchase_price", "status", "selling_price", "selling_date", "margin", "actions")
        self.inventory_tree = ttk.Treeview(self.inventory_tree_frame, columns=columns, show="headings", style='Treeview')
        self.inventory_tree.pack(side='left', fill='both', expand=True)

        # Scrollbar for Treeview
        vsb = ttk.Scrollbar(self.inventory_tree_frame, orient="vertical", command=self.inventory_tree.yview)
        vsb.pack(side='right', fill='y')
        self.inventory_tree.configure(yscrollcommand=vsb.set)

        self.inventory_tree.heading("select", text="", command=self.toggle_select_all_bikes)
        self.inventory_tree.heading("sr", text="Sr#", command=lambda: self.sort_treeview(self.inventory_tree, "sr"))
        self.inventory_tree.heading("chassis", text="Chassis", command=lambda: self.sort_treeview(self.inventory_tree, "chassis"))
        self.inventory_tree.heading("motor", text="Motor#", command=lambda: self.sort_treeview(self.inventory_tree, "motor"))
        self.inventory_tree.heading("model", text="Model", command=lambda: self.sort_treeview(self.inventory_tree, "model"))
        self.inventory_tree.heading("color", text="Color", command=lambda: self.sort_treeview(self.inventory_tree, "color"))
        self.inventory_tree.heading("purchase_price", text="Purchase Price", anchor='e', command=lambda: self.sort_treeview(self.inventory_tree, "purchase_price"))
        self.inventory_tree.heading("status", text="Status", command=lambda: self.sort_treeview(self.inventory_tree, "status"))
        self.inventory_tree.heading("selling_price", text="Selling Price", anchor='e', command=lambda: self.sort_treeview(self.inventory_tree, "selling_price"))
        self.inventory_tree.heading("selling_date", text="Selling Date", command=lambda: self.sort_treeview(self.inventory_tree, "selling_date"))
        self.inventory_tree.heading("margin", text="Margin", anchor='e', command=lambda: self.sort_treeview(self.inventory_tree, "margin"))
        self.inventory_tree.heading("actions", text="Actions")

        self.inventory_tree.column("select", width=30, anchor='center')
        self.inventory_tree.column("sr", width=40, anchor='e')
        self.inventory_tree.column("chassis", width=120)
        self.inventory_tree.column("motor", width=100)
        self.inventory_tree.column("model", width=120)
        self.inventory_tree.column("color", width=80)
        self.inventory_tree.column("purchase_price", width=100, anchor='e')
        self.inventory_tree.column("status", width=80)
        self.inventory_tree.column("selling_price", width=100, anchor='e')
        self.inventory_tree.column("selling_date", width=80)
        self.inventory_tree.column("margin", width=90, anchor='e')
        self.inventory_tree.column("actions", width=150, anchor='center')

        self.inventory_data_rows = [] # Store raw data for sorting and filtering
        self.display_inventory()
        
        # Edit/View modals
        self.edit_bike_dialog = None
        self.view_bike_dialog = None
        
    def load_models_into_combo(self, combo_widget, include_all=False):
        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("SELECT id, model_code, model_name FROM models ORDER BY model_name")
        models = cursor.fetchall()
        conn.close()

        options = [f"{m[0]} - {m[1]} - {m[2]}" for m in models]
        if include_all:
            combo_widget['values'] = ["0 - All Models"] + options
            combo_widget.set("0 - All Models")
        else:
            combo_widget['values'] = options
            combo_widget.set("")

    def apply_inventory_filters(self):
        self.display_inventory()

    def display_inventory(self):
        for i in self.inventory_tree.get_children():
            self.inventory_tree.delete(i)

        conn = db_connect()
        cursor = conn.cursor()

        search_f = self.inv_search_var.get()
        status_f = self.inv_status_var.get()
        model_f_str = self.inv_model_var.get().split(' - ')[0]
        model_f = int(model_f_str) if model_f_str.isdigit() else 0
        color_f = self.inv_color_var.get()
        date_from = self.inv_date_from.get()
        date_to = self.inv_date_to.get()

        where_clauses = ["1=1"]
        params = []

        if status_f and status_f != "":
            where_clauses.append("b.status = ?")
            params.append(status_f)
        if model_f > 0:
            where_clauses.append("b.model_id = ?")
            params.append(model_f)
        if color_f:
            where_clauses.append("b.color LIKE ?")
            params.append(f"%{color_f}%")
        if search_f:
            where_clauses.append("(b.chassis_number LIKE ? OR b.motor_number LIKE ? OR m.model_name LIKE ? OR b.color LIKE ?)")
            params.extend([f"%{search_f}%", f"%{search_f}%", f"%{search_f}%", f"%{search_f}%"])
        if date_from:
            where_clauses.append("b.inventory_date >= ?")
            params.append(date_from)
        if date_to:
            where_clauses.append("b.inventory_date <= ?")
            params.append(date_to)

        where_str = " AND ".join(where_clauses)

        cursor.execute(f"""
            SELECT b.*, m.model_name, m.model_code, c.name as cust_name 
            FROM bikes b 
            LEFT JOIN models m ON b.model_id=m.id 
            LEFT JOIN customers c ON b.customer_id=c.id 
            WHERE {where_str} 
            ORDER BY b.created_at DESC
        """, tuple(params))
        
        self.inventory_data_rows = []
        for row_idx, bike in enumerate(cursor.fetchall()):
            bike_id = bike[0]
            status_badge = bike[14].upper()
            purchase_price = bike[8]
            selling_price = bike[9] if bike[9] else 0
            margin = bike[15] if bike[15] else 0
            
            self.inventory_data_rows.append({
                'id': bike_id,
                'sr': row_idx + 1,
                'chassis': sanitize(bike[4]),
                'motor': sanitize(bike[5] or '-'),
                'model': sanitize(bike[17] or '-'), # model_name
                'color': sanitize(bike[7] or '-'),
                'purchase_price': fmt_money(purchase_price),
                'status': status_badge,
                'selling_price': fmt_money(selling_price) if selling_price else '-',
                'selling_date': fmt_date(bike[10]),
                'margin': fmt_money(margin) if bike[14] == 'sold' else '-',
                'raw': bike # Store raw data for editing/viewing
            })
            
            # Insert into Treeview with a tag for styling
            tag = bike[14] # 'in_stock', 'sold', 'returned', 'reserved'
            self.inventory_tree.insert("", "end", iid=bike_id, values=(
                "", # Checkbox column
                row_idx + 1, 
                sanitize(bike[4]), 
                sanitize(bike[5] or '-'), 
                sanitize(bike[17] or '-'), 
                sanitize(bike[7] or '-'), 
                fmt_money(purchase_price), 
                status_badge, 
                fmt_money(selling_price) if selling_price else '-', 
                fmt_date(bike[10]), 
                fmt_money(margin) if bike[14] == 'sold' else '-', 
                "" # Actions column
            ), tags=(tag,))
            
            # Add action buttons directly to the cell
            self.add_inventory_action_buttons(bike_id, bike[14], row_idx)

        self.inv_total_records_label.config(text=f"Showing {len(self.inventory_data_rows)} record(s)")
        conn.close()

    def add_inventory_action_buttons(self, bike_id, status, row_idx):
        # Frame for buttons
        button_frame = ttk.Frame(self.inventory_tree, style='TFrame')
        
        ttk.Button(button_frame, text="👁", command=lambda bid=bike_id: self.view_bike_details(bid), style='TButton', width=3).pack(side='left', padx=1)
        
        if status == 'in_stock':
            ttk.Button(button_frame, text="🛒", command=lambda bid=bike_id: self.show_page('sale', bike_id=bid), style='TButton', width=3).pack(side='left', padx=1)
        elif status == 'sold':
            ttk.Button(button_frame, text="↩", command=lambda bid=bike_id: self.show_page('returns', bike_id=bid), style='TButton', width=3).pack(side='left', padx=1)
        
        ttk.Button(button_frame, text="✏", command=lambda bid=bike_id: self.edit_bike_details(bid), style='TButton', width=3).pack(side='left', padx=1)
        ttk.Button(button_frame, text="🗑", command=lambda bid=bike_id: self.delete_bike(bid), style='TButton', width=3).pack(side='left', padx=1)

        self.inventory_tree.set(bike_id, "actions", button_frame)
        self.inventory_tree.window_create(self.inventory_tree.get_children()[row_idx], column="actions", anchor="center", window=button_frame)
        
        # Checkbox for bulk actions
        checkbox_var = tk.BooleanVar()
        checkbox = ttk.Checkbutton(self.inventory_tree, variable=checkbox_var, command=lambda: self.update_select_all_checkbox())
        checkbox.var = checkbox_var # Attach var to checkbox widget
        self.inventory_tree.set(bike_id, "select", checkbox)
        self.inventory_tree.window_create(self.inventory_tree.get_children()[row_idx], column="select", anchor="center", window=checkbox)

    def toggle_select_all_bikes(self):
        select_all_state = self.select_all_checkbox.instate(['selected'])
        for item_id in self.inventory_tree.get_children():
            widget = self.inventory_tree.item(item_id)['values'][0] # Get the checkbox widget
            if isinstance(widget, tk.Checkbutton):
                widget.var.set(not select_all_state) # Toggle its state

    def update_select_all_checkbox(self):
        all_selected = True
        for item_id in self.inventory_tree.get_children():
            widget = self.inventory_tree.item(item_id)['values'][0]
            if isinstance(widget, tk.Checkbutton) and not widget.var.get():
                all_selected = False
                break
        if all_selected:
            self.select_all_checkbox.state(['selected'])
        else:
            self.select_all_checkbox.state(['!selected'])

    def get_selected_bike_ids(self):
        selected_ids = []
        for item_id in self.inventory_tree.get_children():
            widget = self.inventory_tree.item(item_id)['values'][0]
            if isinstance(widget, tk.Checkbutton) and widget.var.get():
                selected_ids.append(item_id) # item_id is the bike_id in this case
        return selected_ids

    def bulk_delete_bikes(self):
        if not require_permission('inventory', 'delete'): return
        selected_ids = self.get_selected_bike_ids()
        if not selected_ids:
            messagebox.showwarning("No Selection", "Please select bikes to delete.")
            return

        if messagebox.askyesno("Confirm Delete", f"Are you sure you want to delete {len(selected_ids)} selected bike(s)? This action cannot be undone."):
            conn = db_connect()
            cursor = conn.cursor()
            try:
                for bike_id in selected_ids:
                    cursor.execute("DELETE FROM bikes WHERE id = ?", (bike_id,))
                conn.commit()
                messagebox.showinfo("Success", f"{len(selected_ids)} bike(s) deleted successfully.")
                self.display_inventory()
            except sqlite3.Error as e:
                conn.rollback()
                messagebox.showerror("Database Error", f"Failed to delete bikes: {e}")
            finally:
                conn.close()

    def bulk_export_bikes(self):
        selected_ids = self.get_selected_bike_ids()
        if not selected_ids:
            messagebox.showwarning("No Selection", "Please select bikes to export.")
            return

        file_path = filedialog.asksaveasfilename(defaultextension=".csv", filetypes=[("CSV files", "*.csv")])
        if not file_path:
            return

        conn = db_connect()
        cursor = conn.cursor()
        
        id_placeholders = ','.join('?' for _ in selected_ids)
        cursor.execute(f"""
            SELECT b.chassis_number, b.motor_number, m.model_name, b.color, b.purchase_price, 
                   b.status, b.selling_price, b.selling_date, b.margin, c.name as cust_name
            FROM bikes b 
            LEFT JOIN models m ON b.model_id=m.id 
            LEFT JOIN customers c ON b.customer_id=c.id 
            WHERE b.id IN ({id_placeholders}) 
            ORDER BY b.id DESC
        """, tuple(selected_ids))
        
        data_to_export = cursor.fetchall()
        conn.close()

        try:
            with open(file_path, 'w', newline='', encoding='utf-8') as f:
                header = ["Sr", "Chassis", "Motor", "Model", "Color", "Purchase Price", "Status", "Selling Price", "Selling Date", "Margin", "Customer"]
                f.write(",".join(header) + "\n")
                
                for i, row in enumerate(data_to_export):
                    formatted_row = [
                        str(i + 1),
                        f'"{sanitize(row[0])}"',
                        f'"{sanitize(row[1] or "-")}"',
                        f'"{sanitize(row[2] or "-")}"',
                        f'"{sanitize(row[3] or "-")}"',
                        f'"{row[4]:.2f}"',
                        f'"{sanitize(row[5])}"',
                        f'"{row[6]:.2f}"' if row[6] else '-',
                        f'"{fmt_date(row[7])}"',
                        f'"{row[8]:.2f}"' if row[8] else '-',
                        f'"{sanitize(row[9] or "-")}"',
                    ]
                    f.write(",".join(formatted_row) + "\n")
            messagebox.showinfo("Export Success", f"Selected bikes exported to {file_path}")
        except Exception as e:
            messagebox.showerror("Export Error", f"Failed to export data: {e}")

    def export_inventory_csv(self):
        file_path = filedialog.asksaveasfilename(defaultextension=".csv", filetypes=[("CSV files", "*.csv")])
        if not file_path:
            return

        conn = db_connect()
        cursor = conn.cursor()
        
        search_f = self.inv_search_var.get()
        status_f = self.inv_status_var.get()
        model_f_str = self.inv_model_var.get().split(' - ')[0]
        model_f = int(model_f_str) if model_f_str.isdigit() else 0
        color_f = self.inv_color_var.get()
        date_from = self.inv_date_from.get()
        date_to = self.inv_date_to.get()

        where_clauses = ["1=1"]
        params = []

        if status_f and status_f != "":
            where_clauses.append("b.status = ?")
            params.append(status_f)
        if model_f > 0:
            where_clauses.append("b.model_id = ?")
            params.append(model_f)
        if color_f:
            where_clauses.append("b.color LIKE ?")
            params.append(f"%{color_f}%")
        if search_f:
            where_clauses.append("(b.chassis_number LIKE ? OR b.motor_number LIKE ? OR m.model_name LIKE ? OR b.color LIKE ?)")
            params.extend([f"%{search_f}%", f"%{search_f}%", f"%{search_f}%", f"%{search_f}%"])
        if date_from:
            where_clauses.append("b.inventory_date >= ?")
            params.append(date_from)
        if date_to:
            where_clauses.append("b.inventory_date <= ?")
            params.append(date_to)

        where_str = " AND ".join(where_clauses)

        cursor.execute(f"""
            SELECT b.chassis_number, b.motor_number, m.model_name, b.color, b.purchase_price, 
                   b.status, b.selling_price, b.selling_date, b.margin, c.name as cust_name
            FROM bikes b 
            LEFT JOIN models m ON b.model_id=m.id 
            LEFT JOIN customers c ON b.customer_id=c.id 
            WHERE {where_str} 
            ORDER BY b.created_at DESC
        """, tuple(params))
        
        data_to_export = cursor.fetchall()
        conn.close()

        try:
            with open(file_path, 'w', newline='', encoding='utf-8') as f:
                header = ["Sr", "Chassis", "Motor", "Model", "Color", "Purchase Price", "Status", "Selling Price", "Selling Date", "Margin", "Customer"]
                f.write(",".join(header) + "\n")
                
                for i, row in enumerate(data_to_export):
                    formatted_row = [
                        str(i + 1),
                        f'"{sanitize(row[0])}"',
                        f'"{sanitize(row[1] or "-")}"',
                        f'"{sanitize(row[2] or "-")}"',
                        f'"{sanitize(row[3] or "-")}"',
                        f'"{row[4]:.2f}"',
                        f'"{sanitize(row[5])}"',
                        f'"{row[6]:.2f}"' if row[6] else '-',
                        f'"{fmt_date(row[7])}"',
                        f'"{row[8]:.2f}"' if row[8] else '-',
                        f'"{sanitize(row[9] or "-")}"',
                    ]
                    f.write(",".join(formatted_row) + "\n")
            messagebox.showinfo("Export Success", f"All filtered inventory exported to {file_path}")
        except Exception as e:
            messagebox.showerror("Export Error", f"Failed to export data: {e}")

    def print_inventory(self):
        messagebox.showinfo("Print", "Printing functionality for inventory report is simulated. In a real application, this would generate a PDF or send to a printer.")
        # Actual printing would involve generating a report (e.g., PDF) and sending it to a printer.
        # This is a placeholder.

    def edit_bike_details(self, bike_id):
        if not require_permission('inventory', 'edit'): return

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM bikes WHERE id = ?", (bike_id,))
        bike = cursor.fetchone()
        conn.close()

        if not bike:
            messagebox.showerror("Error", "Bike not found.")
            return

        self.edit_bike_dialog = tk.Toplevel(self.master)
        self.edit_bike_dialog.title(f"Edit Bike - {bike[4]}")
        self.edit_bike_dialog.transient(self.master)
        self.edit_bike_dialog.grab_set()
        self.edit_bike_dialog.focus_set()

        frame = ttk.Frame(self.edit_bike_dialog, padding=10, style='TFrame')
        frame.pack(fill='both', expand=True)

        ttk.Label(frame, text="Chassis Number:").pack(anchor='w')
        chassis_label = ttk.Label(frame, text=sanitize(bike[4]), font=('Segoe UI', 10, 'bold'), style='TLabel')
        chassis_label.pack(fill='x', pady=2)

        ttk.Label(frame, text="Color:").pack(anchor='w')
        color_entry = ttk.Entry(frame, width=40)
        color_entry.insert(0, sanitize(bike[7]))
        color_entry.pack(fill='x', pady=2)

        ttk.Label(frame, text="Purchase Price:").pack(anchor='w')
        purchase_price_entry = ttk.Entry(frame, width=40)
        purchase_price_entry.insert(0, str(bike[8]))
        purchase_price_entry.pack(fill='x', pady=2)

        ttk.Label(frame, text="Status:").pack(anchor='w')
        status_var = tk.StringVar(value=bike[14])
        status_combo = ttk.Combobox(frame, textvariable=status_var, values=["in_stock", "sold", "returned", "reserved"], state='readonly', width=38)
        status_combo.pack(fill='x', pady=2)

        ttk.Label(frame, text="Safeguard Notes:").pack(anchor='w')
        safeguard_notes_entry = ttk.Entry(frame, width=40)
        safeguard_notes_entry.insert(0, sanitize(bike[19] or ''))
        safeguard_notes_entry.pack(fill='x', pady=2)

        ttk.Label(frame, text="Notes:").pack(anchor='w')
        notes_text = tk.Text(frame, height=3, width=40)
        notes_text.insert("1.0", sanitize(bike[20] or ''))
        notes_text.pack(fill='x', pady=2)

        def save_edit():
            if not require_permission('inventory', 'edit'): return
            try:
                new_color = sanitize(color_entry.get())
                new_pp = float(purchase_price_entry.get())
                new_status = status_var.get()
                new_safeguard = sanitize(safeguard_notes_entry.get())
                new_notes = sanitize(notes_text.get("1.0", tk.END))

                conn_update = db_connect()
                cursor_update = conn_update.cursor()
                cursor_update.execute("""
                    UPDATE bikes SET color=?, purchase_price=?, status=?, safeguard_notes=?, notes=?
                    WHERE id=?
                """, (new_color, new_pp, new_status, new_safeguard, new_notes, bike_id))
                conn_update.commit()
                conn_update.close()
                messagebox.showinfo("Success", "Bike updated successfully.")
                self.edit_bike_dialog.destroy()
                self.display_inventory()
            except ValueError:
                messagebox.showerror("Input Error", "Purchase price must be a valid number.")
            except sqlite3.Error as e:
                messagebox.showerror("Database Error", f"Failed to update bike: {e}")

        ttk.Button(frame, text="💾 Save Changes", command=save_edit, style='TButton').pack(pady=10)
        ttk.Button(frame, text="Cancel", command=self.edit_bike_dialog.destroy, style='TButton').pack(pady=2)

    def delete_bike(self, bike_id):
        if not require_permission('inventory', 'delete'): return
        if messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this bike? This cannot be undone."):
            conn = db_connect()
            cursor = conn.cursor()
            try:
                cursor.execute("DELETE FROM bikes WHERE id = ?", (bike_id,))
                conn.commit()
                messagebox.showinfo("Success", "Bike deleted successfully.")
                self.display_inventory()
            except sqlite3.Error as e:
                messagebox.showerror("Database Error", f"Failed to delete bike: {e}")
            finally:
                conn.close()

    def view_bike_details(self, bike_id):
        if not require_permission('inventory', 'view'): return
        
        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("""
            SELECT b.*, m.model_name, m.model_code, m.category, 
                   c.name as cust_name, c.phone as cust_phone, c.cnic as cust_cnic,
                   s.name as sup_name
            FROM bikes b 
            LEFT JOIN models m ON b.model_id=m.id 
            LEFT JOIN customers c ON b.customer_id=c.id 
            LEFT JOIN purchase_orders po ON b.purchase_order_id=po.id
            LEFT JOIN suppliers s ON po.supplier_id=s.id
            WHERE b.id=?
        """, (bike_id,))
        bike = cursor.fetchone()
        conn.close()

        if not bike:
            messagebox.showerror("Error", "Bike not found.")
            return

        self.view_bike_dialog = tk.Toplevel(self.master)
        self.view_bike_dialog.title(f"Bike Details - {bike[4]}")
        self.view_bike_dialog.transient(self.master)
        self.view_bike_dialog.grab_set()
        self.view_bike_dialog.focus_set()

        frame = ttk.Frame(self.view_bike_dialog, padding=10, style='TFrame')
        frame.pack(fill='both', expand=True)

        details_frame = ttk.LabelFrame(frame, text="Details", style='TFrame', padding=10)
        details_frame.pack(fill='x', pady=5)
        
        # Helper for detail rows
        def add_detail_row(parent, label, value, row_idx, col_idx=0):
            ttk.Label(parent, text=f"{label}:", font=('Segoe UI', 9, 'bold'), style='TLabel').grid(row=row_idx, column=col_idx, sticky='nw', padx=5, pady=2)
            ttk.Label(parent, text=f"{value}", font=('Segoe UI', 9), style='TLabel').grid(row=row_idx, column=col_idx+1, sticky='nw', padx=5, pady=2)

        # Bike details in a grid
        detail_fields = [
            ('Chassis Number', bike[4]), ('Motor Number', bike[5]), 
            ('Model', f"{bike[17]} ({bike[18]})"), ('Category', bike[19]),
            ('Color', bike[7]), ('Status', bike[14].upper()),
            ('Purchase Price', fmt_money(bike[8])), 
            ('Selling Price', fmt_money(bike[9]) if bike[9] else '-'),
            ('Tax Amount', fmt_money(bike[13])), 
            ('Margin', fmt_money(bike[15]) if bike[15] else '-'),
            ('Order Date', fmt_date(bike[2])), 
            ('Inventory Date', fmt_date(bike[3])),
            ('Selling Date', fmt_date(bike[10])), 
            ('Customer', bike[20] or '-'),
            ('Customer Phone', bike[21] or '-'), 
            ('Supplier', bike[22] or '-'),
            ('Accessories', bike[17] or '-'), 
            ('Safeguard Notes', bike[19] or '-'),
        ]
        
        current_row = 0
        current_col = 0
        for i, (label, value) in enumerate(detail_fields):
            add_detail_row(details_frame, label, sanitize(value), current_row, current_col)
            current_row += 1
            if current_row >= 8 and current_col == 0: # Arrange in 2 columns
                current_row = 0
                current_col = 2
        
        details_frame.grid_columnconfigure(1, weight=1)
        details_frame.grid_columnconfigure(3, weight=1)

        # Notes
        if bike[20]: # Check if general notes exist
            ttk.Label(details_frame, text=f"NOTES: {sanitize(bike[20])}", font=('Segoe UI', 9), style='TLabel').grid(row=current_row + 1, column=0, columnspan=4, sticky='w', padx=5, pady=5)


        # History Timeline
        history_frame = ttk.LabelFrame(frame, text="📅 Bike History Timeline", style='TFrame', padding=10)
        history_frame.pack(fill='x', pady=10)

        history_items = [
            (bike[2], "📦 Purchased", f"{sanitize(bike[22] or 'Unknown Supplier')} | {fmt_money(bike[8])}", '#4a9eff'),
            (bike[3], "📋 Added to Inventory", "Status: IN STOCK", '#4ec94e'),
        ]
        if bike[14] == 'sold' or bike[10]: # selling_date
            history_items.append((bike[10], "🛒 Sold", f"to {sanitize(bike[20] or 'Cash Customer')} — {fmt_money(bike[9])} | Margin: {fmt_money(bike[15])}", '#4ec94e'))
        if bike[14] == 'returned' or bike[16]: # return_date
            history_items.append((bike[16], "↩ Returned", f"Amount: {fmt_money(bike[11])} | Notes: {sanitize(bike[12] or '-')}", '#e74c3c'))

        for i, (date_str, event_type, description, color) in enumerate(history_items):
            event_frame = ttk.Frame(history_frame, style='TFrame')
            event_frame.pack(fill='x', pady=2)
            
            dot = tk.Canvas(event_frame, width=12, height=12, bg=color, highlightthickness=0)
            dot.create_oval(2, 2, 10, 10, fill=color, outline=color)
            dot.grid(row=0, column=0, padx=5)

            ttk.Label(event_frame, text=fmt_date(date_str), font=('Segoe UI', 8), style='TLabel', foreground='gray').grid(row=0, column=1, sticky='w')
            ttk.Label(event_frame, text=f"{event_type} — {description}", font=('Segoe UI', 9), style='TLabel').grid(row=1, column=1, sticky='w')
        
        ttk.Button(frame, text="🖨 Print", command=lambda: self.print_bike_details(bike_id), style='TButton').pack(pady=5)
        ttk.Button(frame, text="Close", command=self.view_bike_dialog.destroy, style='TButton').pack(pady=2)

    def print_bike_details(self, bike_id):
        # This function would typically generate a PDF report or send the formatted data to a printer.
        messagebox.showinfo("Print Details", f"Printing functionality for Bike ID {bike_id} details is simulated. In a real application, this would generate a PDF or send to a printer.")

    # --- Sale Page ---
    def create_sale_page(self, bike_id=None):
        if not require_permission('sale', 'add'): return

        self.sale_form_frame = ttk.Frame(self.content_frame, style='TFrame', padding=10)
        self.sale_form_frame.pack(fill='both', expand=True)

        sale_details_frame = ttk.LabelFrame(self.sale_form_frame, text="🛒 Sale Details", style='TFrame', padding=10)
        sale_details_frame.pack(fill='x', pady=10)

        # Row 1
        row1 = ttk.Frame(sale_details_frame, style='TFrame')
        row1.pack(fill='x', pady=5)
        
        ttk.Label(row1, text="Select Bike:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.sale_bike_id_var = tk.StringVar()
        self.sale_bike_combo = ttk.Combobox(row1, textvariable=self.sale_bike_id_var, state='readonly')
        self.sale_bike_combo.grid(row=0, column=1, sticky='ew', padx=5, pady=2)
        self.sale_bike_combo.bind("<<ComboboxSelected>>", self.fill_bike_details_for_sale)
        self.load_bikes_for_sale(self.sale_bike_combo, prefill_id=bike_id)
        
        ttk.Label(row1, text="Selling Date:").grid(row=0, column=2, sticky='w', padx=5, pady=2)
        self.sale_selling_date = ttk.Entry(row1)
        self.sale_selling_date.insert(0, datetime.now().strftime('%Y-%m-%d'))
        self.sale_selling_date.grid(row=0, column=3, sticky='ew', padx=5, pady=2)
        
        row1.grid_columnconfigure(1, weight=1)
        row1.grid_columnconfigure(3, weight=1)

        # Row 2 (Price Details)
        row2 = ttk.Frame(sale_details_frame, style='TFrame')
        row2.pack(fill='x', pady=5)

        ttk.Label(row2, text=f"Selling Price ({CURRENCY_SYMBOL}):").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.sale_selling_price = ttk.Entry(row2)
        self.sale_selling_price.grid(row=0, column=1, sticky='ew', padx=5, pady=2)
        self.sale_selling_price.bind("<KeyRelease>", self.calc_sale_margin)

        ttk.Label(row2, text="Purchase Price:").grid(row=0, column=2, sticky='w', padx=5, pady=2)
        self.sale_purchase_price_display = tk.Entry(row2, state='readonly', relief='flat', bg='lightgray')
        self.sale_purchase_price_display.grid(row=0, column=3, sticky='ew', padx=5, pady=2)

        ttk.Label(row2, text="Tax Amount:").grid(row=0, column=4, sticky='w', padx=5, pady=2)
        self.sale_tax_display = tk.Entry(row2, state='readonly', relief='flat', bg='lightgray')
        self.sale_tax_display.grid(row=0, column=5, sticky='ew', padx=5, pady=2)

        ttk.Label(row2, text="Margin / Profit:").grid(row=0, column=6, sticky='w', padx=5, pady=2)
        self.sale_margin_display = tk.Entry(row2, state='readonly', relief='flat', bg='lightgray', fg='green', font=('Segoe UI', 10, 'bold'))
        self.sale_margin_display.grid(row=0, column=7, sticky='ew', padx=5, pady=2)

        row2.grid_columnconfigure(1, weight=1)
        row2.grid_columnconfigure(3, weight=1)
        row2.grid_columnconfigure(5, weight=1)
        row2.grid_columnconfigure(7, weight=1)

        # Row 3 (Customer & Payment)
        row3 = ttk.Frame(sale_details_frame, style='TFrame')
        row3.pack(fill='x', pady=5)

        ttk.Label(row3, text="Customer:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.sale_customer_id_var = tk.StringVar()
        self.sale_customer_combo = ttk.Combobox(row3, textvariable=self.sale_customer_id_var, state='readonly')
        self.sale_customer_combo.grid(row=0, column=1, sticky='ew', padx=5, pady=2)
        self.load_customers_into_combo(self.sale_customer_combo, include_walkin=True)
        ttk.Button(row3, text="+", command=lambda: self.open_add_dialog('customers'), style='TButton').grid(row=0, column=2, padx=2)

        ttk.Label(row3, text="Payment Method:").grid(row=0, column=3, sticky='w', padx=5, pady=2)
        self.sale_payment_type_var = tk.StringVar(value="cash")
        self.sale_payment_type_combo = ttk.Combobox(row3, textvariable=self.sale_payment_type_var, values=["cash", "cheque", "bank_transfer", "online"], state='readonly')
        self.sale_payment_type_combo.grid(row=0, column=4, sticky='ew', padx=5, pady=2)
        self.sale_payment_type_combo.bind("<<ComboboxSelected>>", self.toggle_cheque_fields)

        row3.grid_columnconfigure(1, weight=1)
        row3.grid_columnconfigure(4, weight=1)

        # Cheque Fields (initially hidden)
        self.sale_cheque_fields_frame = ttk.Frame(sale_details_frame, style='TFrame')
        # self.sale_cheque_fields_frame.pack(fill='x', pady=5) # Don't pack initially

        ttk.Label(self.sale_cheque_fields_frame, text="Cheque Number:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.sale_cheque_number = ttk.Entry(self.sale_cheque_fields_frame)
        self.sale_cheque_number.grid(row=0, column=1, sticky='ew', padx=5, pady=2)

        ttk.Label(self.sale_cheque_fields_frame, text="Bank Name:").grid(row=0, column=2, sticky='w', padx=5, pady=2)
        self.sale_bank_name = ttk.Entry(self.sale_cheque_fields_frame)
        self.sale_bank_name.grid(row=0, column=3, sticky='ew', padx=5, pady=2)

        ttk.Label(self.sale_cheque_fields_frame, text="Cheque Date:").grid(row=0, column=4, sticky='w', padx=5, pady=2)
        self.sale_cheque_date = ttk.Entry(self.sale_cheque_fields_frame)
        self.sale_cheque_date.grid(row=0, column=5, sticky='ew', padx=5, pady=2)

        ttk.Label(self.sale_cheque_fields_frame, text="Cheque Amount:").grid(row=0, column=6, sticky='w', padx=5, pady=2)
        self.sale_cheque_amount = ttk.Entry(self.sale_cheque_fields_frame)
        self.sale_cheque_amount.grid(row=0, column=7, sticky='ew', padx=5, pady=2)

        self.sale_cheque_fields_frame.grid_columnconfigure(1, weight=1)
        self.sale_cheque_fields_frame.grid_columnconfigure(3, weight=1)
        self.sale_cheque_fields_frame.grid_columnconfigure(5, weight=1)
        self.sale_cheque_fields_frame.grid_columnconfigure(7, weight=1)

        # Row 4 (Accessories & Notes)
        row4 = ttk.Frame(sale_details_frame, style='TFrame')
        row4.pack(fill='x', pady=5)

        ttk.Label(row4, text="Accessories Given:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.sale_accessories = ttk.Entry(row4)
        self.sale_accessories.grid(row=0, column=1, sticky='ew', padx=5, pady=2)

        ttk.Label(row4, text="Notes:").grid(row=0, column=2, sticky='w', padx=5, pady=2)
        self.sale_notes = ttk.Entry(row4)
        self.sale_notes.grid(row=0, column=3, sticky='ew', padx=5, pady=2)

        row4.grid_columnconfigure(1, weight=1)
        row4.grid_columnconfigure(3, weight=1)

        # Action Buttons
        button_frame = ttk.Frame(self.sale_form_frame, style='TFrame')
        button_frame.pack(fill='x', pady=10)
        ttk.Button(button_frame, text="💾 Record Sale", command=self.record_sale, style='TButton').pack(side='left', padx=5)
        ttk.Button(button_frame, text="← Back to Inventory", command=lambda: self.show_page('inventory'), style='TButton').pack(side='left', padx=5)
        
        # Print Invoice button (initially hidden or shown based on last sale)
        self.print_invoice_button = ttk.Button(button_frame, text="🖨 Print Invoice", command=self.print_last_invoice, style='TButton')
        # self.print_invoice_button.pack(side='left', padx=5) # Only pack after a sale
        self.last_sale_bike_id = None # Track the last sold bike for invoice printing

        if bike_id: # If prefill bike_id is provided, automatically select it and fill details
            self.sale_bike_id_var.set(f"{bike_id} - {self.get_bike_chassis_model_color(bike_id)}")
            self.fill_bike_details_for_sale()

    def load_bikes_for_sale(self, combo_widget, prefill_id=None):
        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("""
            SELECT b.id, b.chassis_number, b.color, b.purchase_price, m.model_name 
            FROM bikes b LEFT JOIN models m ON b.model_id=m.id 
            WHERE b.status='in_stock' ORDER BY b.created_at DESC
        """)
        bikes = cursor.fetchall()
        conn.close()

        options = []
        for b in bikes:
            options.append(f"{b[0]} - {sanitize(b[1])} | {sanitize(b[4])} | {sanitize(b[2])} | Pp: {fmt_money(b[3])}")
        
        combo_widget['values'] = options
        if prefill_id:
            combo_widget.set(f"{prefill_id} - {self.get_bike_chassis_model_color(prefill_id)}")
            self.fill_bike_details_for_sale() # Trigger fill details for prefilled bike
        else:
            combo_widget.set("") # Clear selection

    def get_bike_chassis_model_color(self, bike_id):
        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("SELECT b.chassis_number, b.color, m.model_name FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.id=?", (bike_id,))
        bike_info = cursor.fetchone()
        conn.close()
        if bike_info:
            return f"{sanitize(bike_info[0])} | {sanitize(bike_info[2])} | {sanitize(bike_info[1])}"
        return ""

    def load_customers_into_combo(self, combo_widget, include_walkin=False):
        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("SELECT id, name, phone FROM customers ORDER BY name")
        customers = cursor.fetchall()
        conn.close()

        options = []
        if include_walkin:
            options.append("0 - Walk-in / Cash Customer")

        for c in customers:
            options.append(f"{c[0]} - {sanitize(c[1])} — {sanitize(c[2])}")
        
        combo_widget['values'] = options
        combo_widget.set(options[0] if options else "")

    def fill_bike_details_for_sale(self, event=None):
        selected_bike_str = self.sale_bike_id_var.get()
        if not selected_bike_str:
            self.sale_purchase_price_display.config(state='normal')
            self.sale_purchase_price_display.delete(0, tk.END)
            self.sale_purchase_price_display.config(state='readonly')
            self.sale_selling_price.delete(0, tk.END)
            self.sale_tax_display.config(state='normal')
            self.sale_tax_display.delete(0, tk.END)
            self.sale_tax_display.config(state='readonly')
            self.sale_margin_display.config(state='normal')
            self.sale_margin_display.delete(0, tk.END)
            self.sale_margin_display.config(state='readonly')
            return

        bike_id_str = selected_bike_str.split(' - ')[0]
        bike_id = int(bike_id_str)

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("SELECT purchase_price, accessories FROM bikes WHERE id = ?", (bike_id,))
        bike_data = cursor.fetchone()
        conn.close()

        if bike_data:
            self.sale_purchase_price_display.config(state='normal')
            self.sale_purchase_price_display.delete(0, tk.END)
            self.sale_purchase_price_display.insert(0, fmt_money(bike_data[0]))
            self.sale_purchase_price_display.config(state='readonly')

            self.sale_accessories.delete(0, tk.END)
            self.sale_accessories.insert(0, sanitize(bike_data[1] or ''))

            self.calc_sale_margin()

    def calc_sale_margin(self, event=None):
        try:
            selling_price = float(self.sale_selling_price.get() or 0)
        except ValueError:
            self.sale_selling_price.delete(0, tk.END)
            self.sale_selling_price.insert(0, "0.00")
            selling_price = 0.0

        selected_bike_str = self.sale_bike_id_var.get()
        if not selected_bike_str:
            return

        bike_id_str = selected_bike_str.split(' - ')[0]
        bike_id = int(bike_id_str)

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("SELECT purchase_price FROM bikes WHERE id = ?", (bike_id,))
        purchase_price = cursor.fetchone()[0]
        conn.close()

        global TAX_RATE, TAX_ON_PRICE_TYPE
        tax_rate = float(get_setting('tax_rate', '0.1'))
        tax_on = get_setting('tax_on', 'purchase_price')

        base_for_tax = selling_price if tax_on == 'selling_price' else purchase_price
        tax_amount = (base_for_tax * tax_rate)

        margin = selling_price - purchase_price - tax_amount

        self.sale_tax_display.config(state='normal')
        self.sale_tax_display.delete(0, tk.END)
        self.sale_tax_display.insert(0, fmt_money(tax_amount))
        self.sale_tax_display.config(state='readonly')

        self.sale_margin_display.config(state='normal')
        self.sale_margin_display.delete(0, tk.END)
        self.sale_margin_display.insert(0, fmt_money(margin))
        self.sale_margin_display.config(state='readonly')
        self.sale_margin_display.config(foreground='green' if margin >= 0 else 'red') # Set color based on profit/loss

    def toggle_cheque_fields(self, event=None):
        if self.sale_payment_type_var.get() == 'cheque':
            self.sale_cheque_fields_frame.pack(fill='x', pady=5)
        else:
            self.sale_cheque_fields_frame.pack_forget()

    def record_sale(self):
        if not require_permission('sale', 'add'): return

        selected_bike_str = self.sale_bike_id_var.get()
        if not selected_bike_str:
            messagebox.showerror("Validation Error", "Please select a bike to sell.")
            return
        bike_id = int(selected_bike_str.split(' - ')[0])

        selling_price_str = self.sale_selling_price.get()
        try:
            selling_price = float(selling_price_str)
            if selling_price <= 0:
                messagebox.showerror("Validation Error", "Selling price must be greater than zero.")
                return
        except ValueError:
            messagebox.showerror("Validation Error", "Invalid selling price.")
            return

        selling_date = self.sale_selling_date.get()
        if not selling_date:
            messagebox.showerror("Validation Error", "Selling date is required.")
            return

        customer_id_str = self.sale_customer_id_var.get().split(' - ')[0]
        customer_id = int(customer_id_str) if customer_id_str.isdigit() else 0

        payment_type = self.sale_payment_type_var.get()
        cheque_number = sanitize(self.sale_cheque_number.get()) if payment_type == 'cheque' else ''
        bank_name = sanitize(self.sale_bank_name.get()) if payment_type == 'cheque' else ''
        cheque_date = sanitize(self.sale_cheque_date.get()) if payment_type == 'cheque' else ''
        cheque_amount = float(self.sale_cheque_amount.get() or 0) if payment_type == 'cheque' else 0
        
        sale_accessories = sanitize(self.sale_accessories.get())
        sale_notes = sanitize(self.sale_notes.get())

        conn = db_connect()
        cursor = conn.cursor()

        try:
            # Get current bike details for calculation and checks
            cursor.execute("SELECT purchase_price, status FROM bikes WHERE id = ?", (bike_id,))
            bike_info = cursor.fetchone()
            if not bike_info or bike_info[1] != 'in_stock':
                messagebox.showerror("Error", "Bike not found or already sold/returned.")
                conn.close()
                return

            purchase_price = bike_info[0]
            tax_rate = float(get_setting('tax_rate', '0.1'))
            tax_on = get_setting('tax_on', 'purchase_price')

            base_for_tax = selling_price if tax_on == 'selling_price' else purchase_price
            tax_amount = (base_for_tax * tax_rate)
            margin = selling_price - purchase_price - tax_amount

            # Update bike status
            cursor.execute("""
                UPDATE bikes SET selling_price=?, selling_date=?, customer_id=?, tax_amount=?, margin=?, status='sold', accessories=?, notes=?
                WHERE id=?
            """, (selling_price, selling_date, customer_id, tax_amount, margin, sale_accessories, sale_notes, bike_id))

            # Record payment
            cursor.execute("SELECT name FROM customers WHERE id = ?", (customer_id,))
            customer_name = cursor.fetchone()
            party_name = customer_name[0] if customer_name else 'Cash Customer'

            cursor.execute("""
                INSERT INTO payments (payment_date, payment_type, amount, reference_type, reference_id, party_name, notes)
                VALUES (?, ?, ?, 'sale', ?, ?, ?)
            """, (selling_date, payment_type, selling_price, bike_id, party_name, sale_notes))

            # Record cheque if applicable
            cheque_id = None
            if payment_type == 'cheque' and cheque_number and cheque_amount > 0:
                cursor.execute("""
                    INSERT INTO cheque_register (cheque_number, bank_name, cheque_date, amount, type, status, reference_type, reference_id, party_name, notes)
                    VALUES (?, ?, ?, ?, 'receipt', 'pending', 'sale', ?, ?, ?)
                """, (cheque_number, bank_name, cheque_date, cheque_amount, bike_id, party_name, sale_notes))
                cheque_id = cursor.lastrowid
            
            # If a cheque was registered, update the payment entry with cheque_id
            if cheque_id:
                cursor.execute("UPDATE payments SET cheque_id = ? WHERE reference_type = 'sale' AND reference_id = ? AND payment_type = 'cheque'", (cheque_id, bike_id))

            # Record ledger entry
            description = f"Sale of Chassis: {bike_info[0]} ({get_bike_chassis_model_color(bike_id)})"
            cursor.execute("""
                INSERT INTO ledger (entry_date, entry_type, amount, party_type, party_id, description, reference_type, reference_id, balance)
                VALUES (?, 'credit', ?, 'customer', ?, ?, 'sale', ?, ?)
            """, (selling_date, selling_price, customer_id, description, bike_id, selling_price)) # Balance is typically dynamic, here just copying amount

            conn.commit()
            messagebox.showinfo("Success", f"Sale recorded successfully. Margin: {fmt_money(margin)}")
            self.last_sale_bike_id = bike_id # Store for invoice printing
            self.print_invoice_button.pack(side='left', padx=5) # Show print button
            self.clear_sale_form()
            self.show_page('inventory') # Redirect to inventory after sale
        except sqlite3.Error as e:
            conn.rollback()
            messagebox.showerror("Database Error", f"Failed to record sale: {e}")
        finally:
            conn.close()

    def clear_sale_form(self):
        self.sale_bike_id_var.set("")
        self.sale_selling_price.delete(0, tk.END)
        self.sale_selling_date.delete(0, tk.END)
        self.sale_selling_date.insert(0, datetime.now().strftime('%Y-%m-%d'))
        self.sale_customer_id_var.set("0 - Walk-in / Cash Customer")
        self.sale_payment_type_var.set("cash")
        self.sale_cheque_number.delete(0, tk.END)
        self.sale_bank_name.delete(0, tk.END)
        self.sale_cheque_date.delete(0, tk.END)
        self.sale_cheque_amount.delete(0, tk.END)
        self.sale_accessories.delete(0, tk.END)
        self.sale_notes.delete(0, tk.END)
        self.sale_purchase_price_display.config(state='normal')
        self.sale_purchase_price_display.delete(0, tk.END)
        self.sale_purchase_price_display.config(state='readonly')
        self.sale_tax_display.config(state='normal')
        self.sale_tax_display.delete(0, tk.END)
        self.sale_tax_display.config(state='readonly')
        self.sale_margin_display.config(state='normal')
        self.sale_margin_display.delete(0, tk.END)
        self.sale_margin_display.config(state='readonly')
        self.sale_cheque_fields_frame.pack_forget()

    def print_last_invoice(self):
        if not self.last_sale_bike_id:
            messagebox.showwarning("No Last Sale", "No recent sale to generate an invoice for.")
            return

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("""
            SELECT b.*, m.model_name, m.model_code, m.category, 
                   c.name as cust_name, c.phone as cust_phone, c.cnic as cust_cnic, c.address as cust_addr
            FROM bikes b 
            LEFT JOIN models m ON b.model_id=m.id 
            LEFT JOIN customers c ON b.customer_id=c.id 
            WHERE b.id=?
        """, (self.last_sale_bike_id,))
        inv_data = cursor.fetchone()
        conn.close()

        if not inv_data:
            messagebox.showerror("Error", "Invoice data not found for the last sale.")
            return

        # Generate invoice details
        invoice_dialog = tk.Toplevel(self.master)
        invoice_dialog.title(f"Invoice for Bike ID {self.last_sale_bike_id}")
        invoice_dialog.transient(self.master)
        invoice_dialog.grab_set()
        invoice_dialog.focus_set()

        invoice_frame = ttk.Frame(invoice_dialog, padding=20, style='TFrame')
        invoice_frame.pack(fill='both', expand=True)

        # Header
        ttk.Label(invoice_frame, text="⚡ " + get_setting('company_name', 'BNI Enterprises'), font=("Segoe UI", 16, "bold"), style='TLabel').pack(pady=5)
        ttk.Label(invoice_frame, text=get_setting('branch_name', 'Dera (Ahmed Metro)'), font=("Segoe UI", 10), style='TLabel').pack()
        ttk.Label(invoice_frame, text="Sale Invoice", font=("Segoe UI", 10, "italic"), style='TLabel').pack(pady=(0, 10))

        # Invoice Info
        info_frame = ttk.Frame(invoice_frame, style='TFrame')
        info_frame.pack(fill='x', pady=5)
        ttk.Label(info_frame, text=f"Invoice #: INV-{datetime.now().strftime('%Y%m%d')}-{str(self.last_sale_bike_id).zfill(3)}", style='TLabel').pack(side='left')
        ttk.Label(info_frame, text=f"Date: {fmt_date(inv_data[10])}", style='TLabel').pack(side='right')

        # Customer Details
        customer_frame = ttk.Frame(invoice_frame, style='TFrame')
        customer_frame.pack(fill='x', pady=5)
        ttk.Label(customer_frame, text=f"Customer: {sanitize(inv_data[20] or 'Walk-in Customer')}", style='TLabel').pack(anchor='w')
        if inv_data[21]: ttk.Label(customer_frame, text=f"Phone: {sanitize(inv_data[21])}", style='TLabel').pack(anchor='w')
        if inv_data[22]: ttk.Label(customer_frame, text=f"CNIC: {sanitize(inv_data[22])}", style='TLabel').pack(anchor='w')
        if inv_data[23]: ttk.Label(customer_frame, text=f"Address: {sanitize(inv_data[23])}", style='TLabel').pack(anchor='w')


        # Bike Details
        ttk.Label(invoice_frame, text="Bike Details", font=("Segoe UI", 12, "bold"), style='TLabel').pack(anchor='w', pady=(10, 5))
        bike_detail_tree = ttk.Treeview(invoice_frame, columns=("field", "value"), show="headings", height=6)
        bike_detail_tree.pack(fill='x', pady=5)
        bike_detail_tree.heading("field", text="Field")
        bike_detail_tree.heading("value", text="Details")
        
        bike_detail_tree.insert("", "end", values=("Model", f"{sanitize(inv_data[17])} ({sanitize(inv_data[18])})"))
        bike_detail_tree.insert("", "end", values=("Category", sanitize(inv_data[19])))
        bike_detail_tree.insert("", "end", values=("Chassis No.", sanitize(inv_data[4])))
        bike_detail_tree.insert("", "end", values=("Motor No.", sanitize(inv_data[5] or '-')))
        bike_detail_tree.insert("", "end", values=("Color", sanitize(inv_data[7])))
        if inv_data[16]: bike_detail_tree.insert("", "end", values=("Accessories", sanitize(inv_data[16]))) # accessories
        
        # Payment Details
        ttk.Label(invoice_frame, text="Payment Details", font=("Segoe UI", 12, "bold"), style='TLabel').pack(anchor='w', pady=(10, 5))
        payment_detail_tree = ttk.Treeview(invoice_frame, columns=("description", "amount"), show="headings", height=3)
        payment_detail_tree.pack(fill='x', pady=5)
        payment_detail_tree.heading("description", text="Description")
        payment_detail_tree.heading("amount", text="Amount", anchor='e')

        if SHOW_PURCHASE_ON_INVOICE:
            payment_detail_tree.insert("", "end", values=("Purchase Price", fmt_money(inv_data[8])))
        payment_detail_tree.insert("", "end", values=("Selling Price", fmt_money(inv_data[9])))
        payment_detail_tree.insert("", "end", values=(f"Tax ({TAX_RATE*100:.2f}%)", fmt_money(inv_data[13])))

        # Total
        total_frame = ttk.Frame(invoice_frame, style='TFrame')
        total_frame.pack(fill='x', pady=5)
        ttk.Label(total_frame, text="Total Amount:", font=("Segoe UI", 12, "bold"), style='TLabel').pack(side='left')
        ttk.Label(total_frame, text=fmt_money(inv_data[9]), font=("Segoe UI", 12, "bold"), style='TLabel').pack(side='right')

        # Footer
        ttk.Label(invoice_frame, text="Thank you for your purchase!", font=("Segoe UI", 9, "italic"), style='TLabel').pack(pady=10)
        ttk.Label(invoice_frame, text=f"{get_setting('company_name', 'BNI Enterprises')}, {get_setting('branch_name', '')}", font=("Segoe UI", 8), style='TLabel').pack()
        
        # Print button within the invoice dialog
        ttk.Button(invoice_frame, text="🖨 Print Invoice", command=lambda: self.print_current_dialog(invoice_dialog), style='TButton').pack(pady=10)

    def print_current_dialog(self, dialog):
        # This would typically save the content of the dialog to a file (e.g., PDF)
        # and then initiate a print job for that file.
        messagebox.showinfo("Print", "Printing functionality for this invoice is simulated. In a real application, this would generate a PDF or send to a printer.")
        # Example: You could save the content to a temporary HTML file and then use a browser's print function
        # Or use a PDF generation library like ReportLab or FPDF.
        
    # --- Returns Page ---
    def create_returns_page(self, bike_id=None):
        if not require_permission('returns', 'add'): return # Using 'add' permission for returns as it's an action

        self.returns_form_frame = ttk.Frame(self.content_frame, style='TFrame', padding=10)
        self.returns_form_frame.pack(fill='both', expand=True)

        return_details_frame = ttk.LabelFrame(self.returns_form_frame, text="↩ Return / Adjustment", style='TFrame', padding=10)
        return_details_frame.pack(fill='x', pady=10)

        # Row 1
        row1 = ttk.Frame(return_details_frame, style='TFrame')
        row1.pack(fill='x', pady=5)
        
        ttk.Label(row1, text="Select Sold Bike:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.return_bike_id_var = tk.StringVar()
        self.return_bike_combo = ttk.Combobox(row1, textvariable=self.return_bike_id_var, state='readonly')
        self.return_bike_combo.grid(row=0, column=1, sticky='ew', padx=5, pady=2)
        self.load_sold_bikes(self.return_bike_combo, prefill_id=bike_id)
        
        ttk.Label(row1, text="Return Date:").grid(row=0, column=2, sticky='w', padx=5, pady=2)
        self.return_date = ttk.Entry(row1)
        self.return_date.insert(0, datetime.now().strftime('%Y-%m-%d'))
        self.return_date.grid(row=0, column=3, sticky='ew', padx=5, pady=2)

        ttk.Label(row1, text=f"Return Amount ({CURRENCY_SYMBOL}):").grid(row=0, column=4, sticky='w', padx=5, pady=2)
        self.return_amount = ttk.Entry(row1)
        self.return_amount.grid(row=0, column=5, sticky='ew', padx=5, pady=2)
        
        row1.grid_columnconfigure(1, weight=1)
        row1.grid_columnconfigure(3, weight=1)
        row1.grid_columnconfigure(5, weight=1)

        # Row 2 (Refund Method)
        row2 = ttk.Frame(return_details_frame, style='TFrame')
        row2.pack(fill='x', pady=5)

        ttk.Label(row2, text="Refund Method:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.return_refund_method_var = tk.StringVar(value="cash")
        self.return_refund_method_combo = ttk.Combobox(row2, textvariable=self.return_refund_method_var, values=["cash", "cheque"], state='readonly')
        self.return_refund_method_combo.grid(row=0, column=1, sticky='ew', padx=5, pady=2)
        self.return_refund_method_combo.bind("<<ComboboxSelected>>", self.toggle_return_cheque_fields)

        row2.grid_columnconfigure(1, weight=1)

        # Cheque Fields for Return (initially hidden)
        self.return_cheque_fields_frame = ttk.Frame(return_details_frame, style='TFrame')

        ttk.Label(self.return_cheque_fields_frame, text="Cheque Number:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.return_cheque_number = ttk.Entry(self.return_cheque_fields_frame)
        self.return_cheque_number.grid(row=0, column=1, sticky='ew', padx=5, pady=2)

        ttk.Label(self.return_cheque_fields_frame, text="Bank Name:").grid(row=0, column=2, sticky='w', padx=5, pady=2)
        self.return_bank_name = ttk.Entry(self.return_cheque_fields_frame)
        self.return_bank_name.grid(row=0, column=3, sticky='ew', padx=5, pady=2)

        ttk.Label(self.return_cheque_fields_frame, text="Cheque Date:").grid(row=0, column=4, sticky='w', padx=5, pady=2)
        self.return_cheque_date = ttk.Entry(self.return_cheque_fields_frame)
        self.return_cheque_date.grid(row=0, column=5, sticky='ew', padx=5, pady=2)

        self.return_cheque_fields_frame.grid_columnconfigure(1, weight=1)
        self.return_cheque_fields_frame.grid_columnconfigure(3, weight=1)
        self.return_cheque_fields_frame.grid_columnconfigure(5, weight=1)

        # Row 3 (Notes)
        row3 = ttk.Frame(return_details_frame, style='TFrame')
        row3.pack(fill='x', pady=5)
        ttk.Label(row3, text="Return Notes:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.return_notes = tk.Text(row3, height=3, width=50)
        self.return_notes.grid(row=0, column=1, sticky='ew', padx=5, pady=2)
        row3.grid_columnconfigure(1, weight=1)

        # Action Buttons
        button_frame = ttk.Frame(self.returns_form_frame, style='TFrame')
        button_frame.pack(fill='x', pady=10)
        ttk.Button(button_frame, text="↩ Process Return", command=self.process_return, style='TButton').pack(side='left', padx=5)
        ttk.Button(button_frame, text="← Cancel", command=lambda: self.show_page('inventory'), style='TButton').pack(side='left', padx=5)

    def load_sold_bikes(self, combo_widget, prefill_id=None):
        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("""
            SELECT b.id, b.chassis_number, b.color, b.selling_price, m.model_name 
            FROM bikes b LEFT JOIN models m ON b.model_id=m.id 
            WHERE b.status='sold' ORDER BY b.selling_date DESC
        """)
        bikes = cursor.fetchall()
        conn.close()

        options = []
        for b in bikes:
            options.append(f"{b[0]} - {sanitize(b[1])} | {sanitize(b[4])} | {sanitize(b[2])} | Sold: {fmt_money(b[3])}")
        
        combo_widget['values'] = options
        if prefill_id:
            combo_widget.set(f"{prefill_id} - {self.get_bike_chassis_model_color(prefill_id)}")
        else:
            combo_widget.set("") # Clear selection

    def toggle_return_cheque_fields(self, event=None):
        if self.return_refund_method_var.get() == 'cheque':
            self.return_cheque_fields_frame.pack(fill='x', pady=5)
        else:
            self.return_cheque_fields_frame.pack_forget()

    def process_return(self):
        if not require_permission('returns', 'add'): return
        
        selected_bike_str = self.return_bike_id_var.get()
        if not selected_bike_str:
            messagebox.showerror("Validation Error", "Please select a bike to return.")
            return
        bike_id = int(selected_bike_str.split(' - ')[0])

        return_date = self.return_date.get()
        if not return_date:
            messagebox.showerror("Validation Error", "Return date is required.")
            return

        return_amount_str = self.return_amount.get()
        try:
            return_amount = float(return_amount_str)
            if return_amount < 0:
                messagebox.showerror("Validation Error", "Return amount cannot be negative.")
                return
        except ValueError:
            messagebox.showerror("Validation Error", "Invalid return amount.")
            return

        refund_method = self.return_refund_method_var.get()
        cheque_number = sanitize(self.return_cheque_number.get()) if refund_method == 'cheque' else ''
        bank_name = sanitize(self.return_bank_name.get()) if refund_method == 'cheque' else ''
        cheque_date = sanitize(self.return_cheque_date.get()) if refund_method == 'cheque' else ''
        return_notes = sanitize(self.return_notes.get("1.0", tk.END))

        conn = db_connect()
        cursor = conn.cursor()

        try:
            # Check if bike exists and is sold
            cursor.execute("SELECT status, customer_id FROM bikes WHERE id = ?", (bike_id,))
            bike_info = cursor.fetchone()
            if not bike_info or bike_info[0] != 'sold':
                messagebox.showerror("Error", "Bike not found or not in 'sold' status.")
                conn.close()
                return

            customer_id = bike_info[1]

            # Update bike status to 'returned'
            cursor.execute("""
                UPDATE bikes SET status='returned', return_date=?, return_amount=?, return_notes=?
                WHERE id=?
            """, (return_date, return_amount, return_notes, bike_id))

            # Record cheque if applicable
            if refund_method == 'cheque' and cheque_number and return_amount > 0:
                cursor.execute("SELECT name FROM customers WHERE id = ?", (customer_id,))
                customer_name = cursor.fetchone()
                party_name = customer_name[0] if customer_name else 'Unknown Customer'
                
                cursor.execute("""
                    INSERT INTO cheque_register (cheque_number, bank_name, cheque_date, amount, type, status, reference_type, reference_id, party_name, notes)
                    VALUES (?, ?, ?, ?, 'refund', 'pending', 'return', ?, ?, ?)
                """, (cheque_number, bank_name, cheque_date, return_amount, bike_id, party_name, return_notes))

            # Record ledger entry for debit (refund)
            description = f"Return for Bike ID: {bike_id}"
            cursor.execute("""
                INSERT INTO ledger (entry_date, entry_type, amount, party_type, party_id, description, reference_type, reference_id, balance)
                VALUES (?, 'debit', ?, 'customer', ?, ?, 'return', ?, ?)
            """, (return_date, return_amount, customer_id, description, bike_id, -return_amount)) # Negative for debit

            conn.commit()
            messagebox.showinfo("Success", "Return processed successfully.")
            self.show_page('inventory')
        except sqlite3.Error as e:
            conn.rollback()
            messagebox.showerror("Database Error", f"Failed to process return: {e}")
        finally:
            conn.close()

    # --- Cheques Page ---
    def create_cheques_page(self):
        if not require_permission('cheques', 'view'): return

        filter_frame = ttk.Frame(self.content_frame, style='TFrame', padding=10)
        filter_frame.pack(fill='x', pady=5)

        ttk.Label(filter_frame, text="Status:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.chq_status_var = tk.StringVar(value="")
        ttk.Combobox(filter_frame, textvariable=self.chq_status_var, values=["", "pending", "cleared", "bounced", "cancelled"], state='readonly').grid(row=0, column=1, sticky='ew', padx=5, pady=2)

        ttk.Label(filter_frame, text="Type:").grid(row=0, column=2, sticky='w', padx=5, pady=2)
        self.chq_type_var = tk.StringVar(value="")
        ttk.Combobox(filter_frame, textvariable=self.chq_type_var, values=["", "payment", "receipt", "refund"], state='readonly').grid(row=0, column=3, sticky='ew', padx=5, pady=2)

        ttk.Label(filter_frame, text="Bank:").grid(row=0, column=4, sticky='w', padx=5, pady=2)
        self.chq_bank_var = tk.StringVar()
        ttk.Entry(filter_frame, textvariable=self.chq_bank_var).grid(row=0, column=5, sticky='ew', padx=5, pady=2)

        ttk.Label(filter_frame, text="From:").grid(row=0, column=6, sticky='w', padx=5, pady=2)
        self.chq_date_from = ttk.Entry(filter_frame)
        self.chq_date_from.grid(row=0, column=7, sticky='ew', padx=5, pady=2)

        ttk.Label(filter_frame, text="To:").grid(row=0, column=8, sticky='w', padx=5, pady=2)
        self.chq_date_to = ttk.Entry(filter_frame)
        self.chq_date_to.grid(row=0, column=9, sticky='ew', padx=5, pady=2)

        ttk.Button(filter_frame, text="🔍 Filter", command=self.apply_cheque_filters, style='TButton').grid(row=0, column=10, sticky='e', padx=5, pady=2)

        filter_frame.grid_columnconfigure(1, weight=1)
        filter_frame.grid_columnconfigure(3, weight=1)
        filter_frame.grid_columnconfigure(5, weight=1)
        filter_frame.grid_columnconfigure(7, weight=1)
        filter_frame.grid_columnconfigure(9, weight=1)

        # Summary Boxes
        summary_frame = ttk.Frame(self.content_frame, style='TFrame')
        summary_frame.pack(fill='x', pady=5)
        self.chq_summary_labels = {}
        for i, (status, color) in enumerate([("Pending", "orange"), ("Cleared", "green"), ("Bounced", "red"), ("Cancelled", "gray")]):
            box = ttk.Frame(summary_frame, style='TFrame', relief='solid', borderwidth=1, padding=5)
            box.grid(row=0, column=i, padx=5, pady=5, sticky='ew')
            ttk.Label(box, text=status, font=('Segoe UI', 10, 'bold'), style='TLabel', foreground=color).pack()
            self.chq_summary_labels[status] = ttk.Label(box, text="0 / 0.00", style='TLabel')
            self.chq_summary_labels[status].pack()
            summary_frame.grid_columnconfigure(i, weight=1)

        # Cheques Table
        self.cheques_tree_frame = ttk.Frame(self.content_frame, style='TFrame')
        self.cheques_tree_frame.pack(fill='both', expand=True, padx=5, pady=5)

        columns = ("sr", "cheque_num", "bank", "date", "amount", "type", "status", "party", "reference", "actions")
        self.cheques_tree = ttk.Treeview(self.cheques_tree_frame, columns=columns, show="headings", style='Treeview')
        self.cheques_tree.pack(side='left', fill='both', expand=True)

        vsb = ttk.Scrollbar(self.cheques_tree_frame, orient="vertical", command=self.cheques_tree.yview)
        vsb.pack(side='right', fill='y')
        self.cheques_tree.configure(yscrollcommand=vsb.set)

        self.cheques_tree.heading("sr", text="Sr#")
        self.cheques_tree.heading("cheque_num", text="Cheque #")
        self.cheques_tree.heading("bank", text="Bank")
        self.cheques_tree.heading("date", text="Date")
        self.cheques_tree.heading("amount", text="Amount", anchor='e')
        self.cheques_tree.heading("type", text="Type")
        self.cheques_tree.heading("status", text="Status")
        self.cheques_tree.heading("party", text="Party")
        self.cheques_tree.heading("reference", text="Reference")
        self.cheques_tree.heading("actions", text="Actions")

        self.cheques_tree.column("sr", width=40, anchor='e')
        self.cheques_tree.column("cheque_num", width=100)
        self.cheques_tree.column("bank", width=100)
        self.cheques_tree.column("date", width=80)
        self.cheques_tree.column("amount", width=100, anchor='e')
        self.cheques_tree.column("type", width=80)
        self.cheques_tree.column("status", width=80)
        self.cheques_tree.column("party", width=120)
        self.cheques_tree.column("reference", width=120)
        self.cheques_tree.column("actions", width=150, anchor='center')

        self.display_cheques()

    def apply_cheque_filters(self):
        self.display_cheques()

    def display_cheques(self):
        for i in self.cheques_tree.get_children():
            self.cheques_tree.delete(i)

        conn = db_connect()
        cursor = conn.cursor()

        status_f = self.chq_status_var.get()
        type_f = self.chq_type_var.get()
        bank_f = self.chq_bank_var.get()
        date_from = self.chq_date_from.get()
        date_to = self.chq_date_to.get()

        where_clauses = ["1=1"]
        params = []

        if status_f:
            where_clauses.append("status = ?")
            params.append(status_f)
        if type_f:
            where_clauses.append("type = ?")
            params.append(type_f)
        if bank_f:
            where_clauses.append("bank_name LIKE ?")
            params.append(f"%{bank_f}%")
        if date_from:
            where_clauses.append("cheque_date >= ?")
            params.append(date_from)
        if date_to:
            where_clauses.append("cheque_date <= ?")
            params.append(date_to)

        where_str = " AND ".join(where_clauses)

        cursor.execute(f"SELECT * FROM cheque_register WHERE {where_str} ORDER BY cheque_date DESC", tuple(params))
        
        cheques = cursor.fetchall()
        
        total_chq_amount = 0
        for sr, chq in enumerate(cheques):
            total_chq_amount += chq[4]
            chq_id = chq[0]
            status_text = chq[6].upper()
            type_text = chq[5].upper()
            
            tag = chq[6] # For styling
            self.cheques_tree.insert("", "end", iid=chq_id, values=(
                sr + 1, 
                sanitize(chq[1]), 
                sanitize(chq[2]), 
                fmt_date(chq[3]), 
                fmt_money(chq[4]), 
                type_text, 
                status_text, 
                sanitize(chq[9]), 
                f"{sanitize(chq[7])} #{chq[8]}",
                ""
            ), tags=(tag,))
            self.add_cheque_action_buttons(chq_id, chq[6], sr)

        # Update summary
        cursor.execute(f"SELECT status, COUNT(*), SUM(amount) FROM cheque_register WHERE {where_str} GROUP BY status")
        summary_data = {row[0]: (row[1], row[2]) for row in cursor.fetchall()}
        
        for status_key, label_widget in self.chq_summary_labels.items():
            count, total = summary_data.get(status_key.lower(), (0, 0))
            label_widget.config(text=f"{count} / {fmt_money(total)}")

        conn.close()

    def add_cheque_action_buttons(self, cheque_id, status, row_idx):
        button_frame = ttk.Frame(self.cheques_tree, style='TFrame')
        
        if status == 'pending':
            ttk.Button(button_frame, text="✓ Clear", command=lambda cid=cheque_id: self.update_cheque_status(cid, 'cleared'), style='TButton', width=6).pack(side='left', padx=1)
            ttk.Button(button_frame, text="✗ Bounce", command=lambda cid=cheque_id: self.update_cheque_status(cid, 'bounced'), style='TButton', width=6).pack(side='left', padx=1)
        
        ttk.Button(button_frame, text="🗑", command=lambda cid=cheque_id: self.delete_cheque(cid), style='TButton', width=3).pack(side='left', padx=1)

        # Get the item ID using the actual cheque_id as the iid
        item_id = str(cheque_id)
        self.cheques_tree.set(item_id, "actions", button_frame)
        self.cheques_tree.window_create(item_id, column="actions", anchor="center", window=button_frame)

    def update_cheque_status(self, cheque_id, new_status):
        if not require_permission('cheques', 'edit'): return
        
        action_text = f"Mark as {new_status.title()}"
        if new_status == 'bounced':
            if not messagebox.askyesno("Confirm Bounce", f"Are you sure you want to mark this cheque as Bounced?"):
                return

        conn = db_connect()
        cursor = conn.cursor()
        try:
            cursor.execute("UPDATE cheque_register SET status = ? WHERE id = ?", (new_status, cheque_id))
            conn.commit()
            messagebox.showinfo("Success", f"Cheque status updated to {new_status.title()}.")
            self.display_cheques()
        except sqlite3.Error as e:
            messagebox.showerror("Database Error", f"Failed to update cheque status: {e}")
        finally:
            conn.close()

    def delete_cheque(self, cheque_id):
        if not require_permission('cheques', 'delete'): return
        if messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this cheque entry? This cannot be undone."):
            conn = db_connect()
            cursor = conn.cursor()
            try:
                cursor.execute("DELETE FROM cheque_register WHERE id = ?", (cheque_id,))
                conn.commit()
                messagebox.showinfo("Success", "Cheque entry deleted successfully.")
                self.display_cheques()
            except sqlite3.Error as e:
                messagebox.showerror("Database Error", f"Failed to delete cheque entry: {e}")
            finally:
                conn.close()

    # --- Income/Expense Page ---
    def create_income_expense_page(self):
        if not require_permission('income_expense', 'view'): return
        
        filter_frame = ttk.Frame(self.content_frame, style='TFrame', padding=10)
        filter_frame.pack(fill='x', pady=5)

        ttk.Label(filter_frame, text="From:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.ie_date_from = ttk.Entry(filter_frame, width=12)
        self.ie_date_from.insert(0, (datetime.now().replace(day=1)).strftime('%Y-%m-%d'))
        self.ie_date_from.grid(row=0, column=1, sticky='ew', padx=5, pady=2)

        ttk.Label(filter_frame, text="To:").grid(row=0, column=2, sticky='w', padx=5, pady=2)
        self.ie_date_to = ttk.Entry(filter_frame, width=12)
        self.ie_date_to.insert(0, datetime.now().strftime('%Y-%m-%d'))
        self.ie_date_to.grid(row=0, column=3, sticky='ew', padx=5, pady=2)

        ttk.Label(filter_frame, text="Type:").grid(row=0, column=4, sticky='w', padx=5, pady=2)
        self.ie_type_var = tk.StringVar(value="")
        ttk.Combobox(filter_frame, textvariable=self.ie_type_var, values=["", "income", "expense"], state='readonly', width=10).grid(row=0, column=5, sticky='ew', padx=5, pady=2)

        ttk.Label(filter_frame, text="Category:").grid(row=0, column=6, sticky='w', padx=5, pady=2)
        self.ie_category_var = tk.StringVar(value="")
        self.ie_category_combo = ttk.Combobox(filter_frame, textvariable=self.ie_category_var, state='readonly', width=15)
        self.ie_category_combo.grid(row=0, column=7, sticky='ew', padx=5, pady=2)
        self.load_ie_categories()

        ttk.Button(filter_frame, text="🔍 Filter", command=self.apply_ie_filters, style='TButton').grid(row=0, column=8, sticky='e', padx=5, pady=2)
        ttk.Button(filter_frame, text="Reset", command=self.reset_ie_filters, style='TButton').grid(row=0, column=9, sticky='e', padx=5, pady=2)
        
        if has_permission('income_expense', 'add'):
            ttk.Button(filter_frame, text="+ Add Entry", command=self.open_ie_entry_form, style='TButton').grid(row=0, column=10, sticky='e', padx=5, pady=2)

        filter_frame.grid_columnconfigure(1, weight=1)
        filter_frame.grid_columnconfigure(3, weight=1)
        filter_frame.grid_columnconfigure(5, weight=1)
        filter_frame.grid_columnconfigure(7, weight=1)

        # Summary Boxes
        summary_frame = ttk.Frame(self.content_frame, style='TFrame')
        summary_frame.pack(fill='x', pady=5)
        
        self.ie_summary_income = ttk.LabelFrame(summary_frame, text="Total Income", style='TFrame', padding=5)
        self.ie_summary_income.grid(row=0, column=0, padx=5, pady=5, sticky='ew')
        self.ie_income_value = ttk.Label(self.ie_summary_income, text="0.00", font=('Segoe UI', 12, 'bold'), style='TLabel', foreground='green')
        self.ie_income_value.pack()

        self.ie_summary_expense = ttk.LabelFrame(summary_frame, text="Total Expense", style='TFrame', padding=5)
        self.ie_summary_expense.grid(row=0, column=1, padx=5, pady=5, sticky='ew')
        self.ie_expense_value = ttk.Label(self.ie_summary_expense, text="0.00", font=('Segoe UI', 12, 'bold'), style='TLabel', foreground='red')
        self.ie_expense_value.pack()

        self.ie_summary_net = ttk.LabelFrame(summary_frame, text="Net Balance", style='TFrame', padding=5)
        self.ie_summary_net.grid(row=0, column=2, padx=5, pady=5, sticky='ew')
        self.ie_net_value = ttk.Label(self.ie_summary_net, text="0.00", font=('Segoe UI', 12, 'bold'), style='TLabel')
        self.ie_net_value.pack()

        summary_frame.grid_columnconfigure(0, weight=1)
        summary_frame.grid_columnconfigure(1, weight=1)
        summary_frame.grid_columnconfigure(2, weight=1)

        # Income/Expense Table
        self.ie_tree_frame = ttk.Frame(self.content_frame, style='TFrame')
        self.ie_tree_frame.pack(fill='both', expand=True, padx=5, pady=5)

        columns = ("sr", "date", "type", "category", "amount", "method", "reference", "by", "actions")
        self.ie_tree = ttk.Treeview(self.ie_tree_frame, columns=columns, show="headings", style='Treeview')
        self.ie_tree.pack(side='left', fill='both', expand=True)

        vsb = ttk.Scrollbar(self.ie_tree_frame, orient="vertical", command=self.ie_tree.yview)
        vsb.pack(side='right', fill='y')
        self.ie_tree.configure(yscrollcommand=vsb.set)

        self.ie_tree.heading("sr", text="Sr#")
        self.ie_tree.heading("date", text="Date")
        self.ie_tree.heading("type", text="Type")
        self.ie_tree.heading("category", text="Category")
        self.ie_tree.heading("amount", text="Amount", anchor='e')
        self.ie_tree.heading("method", text="Method")
        self.ie_tree.heading("reference", text="Reference")
        self.ie_tree.heading("by", text="By")
        self.ie_tree.heading("actions", text="Actions")

        self.ie_tree.column("sr", width=40, anchor='e')
        self.ie_tree.column("date", width=80)
        self.ie_tree.column("type", width=80)
        self.ie_tree.column("category", width=120)
        self.ie_tree.column("amount", width=100, anchor='e')
        self.ie_tree.column("method", width=100)
        self.ie_tree.column("reference", width=120)
        self.ie_tree.column("by", width=100)
        self.ie_tree.column("actions", width=100, anchor='center')

        self.display_ie_entries()

    def load_ie_categories(self):
        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("SELECT DISTINCT category FROM income_expenses ORDER BY category")
        categories = [row[0] for row in cursor.fetchall()]
        conn.close()
        self.ie_category_combo['values'] = [""] + categories

    def apply_ie_filters(self):
        self.display_ie_entries()

    def reset_ie_filters(self):
        self.ie_date_from.delete(0, tk.END)
        self.ie_date_from.insert(0, (datetime.now().replace(day=1)).strftime('%Y-%m-%d'))
        self.ie_date_to.delete(0, tk.END)
        self.ie_date_to.insert(0, datetime.now().strftime('%Y-%m-%d'))
        self.ie_type_var.set("")
        self.ie_category_var.set("")
        self.display_ie_entries()

    def display_ie_entries(self):
        for i in self.ie_tree.get_children():
            self.ie_tree.delete(i)

        conn = db_connect()
        cursor = conn.cursor()

        date_from = self.ie_date_from.get()
        date_to = self.ie_date_to.get()
        entry_type = self.ie_type_var.get()
        category = self.ie_category_var.get()

        where_clauses = ["entry_date BETWEEN ? AND ?"]
        params = [date_from, date_to]

        if entry_type:
            where_clauses.append("type = ?")
            params.append(entry_type)
        if category:
            where_clauses.append("category = ?")
            params.append(category)

        where_str = " AND ".join(where_clauses)

        cursor.execute(f"""
            SELECT ie.*, u.full_name FROM income_expenses ie 
            LEFT JOIN users u ON ie.created_by=u.id 
            WHERE {where_str} 
            ORDER BY entry_date DESC, id DESC
        """, tuple(params))
        
        entries = cursor.fetchall()

        sum_income = 0
        sum_expense = 0
        for sr, entry in enumerate(entries):
            ie_id = entry[0]
            if entry[2] == 'income':
                sum_income += entry[4]
                tag = 'income'
            else:
                sum_expense += entry[4]
                tag = 'expense'
            
            self.ie_tree.insert("", "end", iid=ie_id, values=(
                sr + 1, 
                fmt_date(entry[1]), 
                entry[2].title(), 
                sanitize(entry[3]), 
                fmt_money(entry[4]), 
                entry[5].replace('_', ' ').title(), 
                sanitize(entry[6] or '-'), 
                sanitize(entry[8] or '-'),
                ""
            ), tags=(tag,))
            self.add_ie_action_buttons(ie_id, sr)

        self.ie_income_value.config(text=fmt_money(sum_income))
        self.ie_expense_value.config(text=fmt_money(sum_expense))
        net_balance = sum_income - sum_expense
        self.ie_net_value.config(text=fmt_money(net_balance), foreground='green' if net_balance >= 0 else 'red')

        conn.close()

    def add_ie_action_buttons(self, ie_id, row_idx):
        button_frame = ttk.Frame(self.ie_tree, style='TFrame')
        
        if has_permission('income_expense', 'edit'):
            ttk.Button(button_frame, text="✏", command=lambda eid=ie_id: self.open_ie_entry_form(eid), style='TButton', width=3).pack(side='left', padx=1)
        if has_permission('income_expense', 'delete'):
            ttk.Button(button_frame, text="🗑", command=lambda eid=ie_id: self.delete_ie_entry(eid), style='TButton', width=3).pack(side='left', padx=1)

        item_id = str(ie_id)
        self.ie_tree.set(item_id, "actions", button_frame)
        self.ie_tree.window_create(item_id, column="actions", anchor="center", window=button_frame)

    def open_ie_entry_form(self, ie_id=None):
        if ie_id and not require_permission('income_expense', 'edit'): return
        if not ie_id and not require_permission('income_expense', 'add'): return

        dialog = tk.Toplevel(self.master)
        dialog.title("Add/Edit Income/Expense Entry")
        dialog.transient(self.master)
        dialog.grab_set()
        dialog.focus_set()

        frame = ttk.Frame(dialog, padding=10, style='TFrame')
        frame.pack(fill='both', expand=True)

        entry_data = None
        if ie_id:
            conn = db_connect()
            cursor = conn.cursor()
            cursor.execute("SELECT * FROM income_expenses WHERE id = ?", (ie_id,))
            entry_data = cursor.fetchone()
            conn.close()
            if not entry_data:
                messagebox.showerror("Error", "Entry not found.")
                dialog.destroy()
                return

        ttk.Label(frame, text="Date:").pack(anchor='w')
        date_entry = ttk.Entry(frame, width=40)
        date_entry.insert(0, entry_data[1] if entry_data else datetime.now().strftime('%Y-%m-%d'))
        date_entry.pack(fill='x', pady=2)

        ttk.Label(frame, text="Type:").pack(anchor='w')
        type_var = tk.StringVar(value=entry_data[2] if entry_data else 'expense')
        type_combo = ttk.Combobox(frame, textvariable=type_var, values=["income", "expense"], state='readonly', width=38)
        type_combo.pack(fill='x', pady=2)

        ttk.Label(frame, text="Category:").pack(anchor='w')
        category_entry = ttk.Entry(frame, width=40)
        category_entry.insert(0, entry_data[3] if entry_data else '')
        category_entry.pack(fill='x', pady=2)
        
        # Datalist for categories (simulated with a Combobox or just leave as Entry for now)
        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("SELECT DISTINCT category FROM income_expenses ORDER BY category")
        categories = [row[0] for row in cursor.fetchall()]
        conn.close()
        category_entry['values'] = categories # For autocompletion, if an actual autocompleting combobox is used

        ttk.Label(frame, text="Amount:").pack(anchor='w')
        amount_entry = ttk.Entry(frame, width=40)
        amount_entry.insert(0, str(entry_data[4]) if entry_data else '')
        amount_entry.pack(fill='x', pady=2)

        ttk.Label(frame, text="Payment Method:").pack(anchor='w')
        method_var = tk.StringVar(value=entry_data[5] if entry_data else 'cash')
        method_combo = ttk.Combobox(frame, textvariable=method_var, values=["cash", "bank_transfer", "cheque", "online", "other"], state='readonly', width=38)
        method_combo.pack(fill='x', pady=2)

        ttk.Label(frame, text="Reference:").pack(anchor='w')
        reference_entry = ttk.Entry(frame, width=40)
        reference_entry.insert(0, sanitize(entry_data[6] or '') if entry_data else '')
        reference_entry.pack(fill='x', pady=2)

        ttk.Label(frame, text="Notes:").pack(anchor='w')
        notes_text = tk.Text(frame, height=3, width=40)
        notes_text.insert("1.0", sanitize(entry_data[7] or '') if entry_data else '')
        notes_text.pack(fill='x', pady=2)

        def save_ie_entry():
            new_date = date_entry.get()
            new_type = type_var.get()
            new_category = sanitize(category_entry.get())
            try:
                new_amount = float(amount_entry.get())
                if new_amount <= 0:
                    messagebox.showerror("Validation Error", "Amount must be greater than zero.")
                    return
            except ValueError:
                messagebox.showerror("Validation Error", "Invalid amount.")
                return
            new_method = method_var.get()
            new_reference = sanitize(reference_entry.get())
            new_notes = sanitize(notes_text.get("1.0", tk.END))

            if not new_date or not new_type or not new_category or not new_amount:
                messagebox.showerror("Validation Error", "Date, Type, Category, and Amount are required.")
                return

            conn_save = db_connect()
            cursor_save = conn_save.cursor()
            try:
                if ie_id:
                    cursor_save.execute("""
                        UPDATE income_expenses SET entry_date=?, type=?, category=?, amount=?, payment_method=?, reference=?, notes=?
                        WHERE id=?
                    """, (new_date, new_type, new_category, new_amount, new_method, new_reference, new_notes, ie_id))
                else:
                    cursor_save.execute("""
                        INSERT INTO income_expenses (entry_date, type, category, amount, payment_method, reference, notes, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    """, (new_date, new_type, new_category, new_amount, new_method, new_reference, new_notes, CURRENT_USER_ID))
                conn_save.commit()
                messagebox.showinfo("Success", "Entry saved successfully.")
                dialog.destroy()
                self.load_ie_categories() # Refresh categories for auto-suggestion
                self.display_ie_entries()
            except sqlite3.Error as e:
                messagebox.showerror("Database Error", f"Failed to save entry: {e}")
            finally:
                conn_save.close()

        ttk.Button(frame, text="💾 Save", command=save_ie_entry, style='TButton').pack(pady=10)
        ttk.Button(frame, text="Cancel", command=dialog.destroy, style='TButton').pack(pady=2)

    def delete_ie_entry(self, ie_id):
        if not require_permission('income_expense', 'delete'): return
        if messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this entry? This cannot be undone."):
            conn = db_connect()
            cursor = conn.cursor()
            try:
                cursor.execute("DELETE FROM income_expenses WHERE id = ?", (ie_id,))
                conn.commit()
                messagebox.showinfo("Success", "Entry deleted successfully.")
                self.display_ie_entries()
            except sqlite3.Error as e:
                messagebox.showerror("Database Error", f"Failed to delete entry: {e}")
            finally:
                conn.close()

    # --- Customer Ledger Page ---
    def create_customer_ledger_page(self):
        if not require_permission('customer_ledger', 'view'): return

        filter_frame = ttk.Frame(self.content_frame, style='TFrame', padding=10)
        filter_frame.pack(fill='x', pady=5)

        ttk.Label(filter_frame, text="Select Customer:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.cl_customer_var = tk.StringVar()
        self.cl_customer_combo = ttk.Combobox(filter_frame, textvariable=self.cl_customer_var, state='readonly', width=30)
        self.cl_customer_combo.grid(row=0, column=1, sticky='ew', padx=5, pady=2)
        self.load_customers_into_combo(self.cl_customer_combo)
        self.cl_customer_combo.bind("<<ComboboxSelected>>", lambda e: self.display_customer_ledger())

        filter_frame.grid_columnconfigure(1, weight=1)
        
        ttk.Button(filter_frame, text="🖨 Print Ledger", command=self.print_customer_ledger, style='TButton').grid(row=0, column=2, padx=5)

        self.customer_ledger_display_frame = ttk.Frame(self.content_frame, style='TFrame')
        self.customer_ledger_display_frame.pack(fill='both', expand=True, padx=5, pady=5)

        # Initialize with no customer selected or default
        self.display_customer_ledger()

    def display_customer_ledger(self):
        for widget in self.customer_ledger_display_frame.winfo_children():
            widget.destroy()

        selected_customer_str = self.cl_customer_var.get()
        if not selected_customer_str:
            ttk.Label(self.customer_ledger_display_frame, text="Please select a customer to view their ledger.", style='TLabel').pack(pady=20)
            return

        cust_id_str = selected_customer_str.split(' - ')[0]
        customer_id = int(cust_id_str)

        conn = db_connect()
        cursor = conn.cursor()

        cursor.execute("SELECT name, phone, cnic, address FROM customers WHERE id = ?", (customer_id,))
        cust_info = cursor.fetchone()

        if not cust_info:
            ttk.Label(self.customer_ledger_display_frame, text="Customer not found.", style='TLabel').pack(pady=20)
            conn.close()
            return

        # Customer Info
        info_frame = ttk.LabelFrame(self.customer_ledger_display_frame, text=f"👤 {sanitize(cust_info[0])}", style='TFrame', padding=10)
        info_frame.pack(fill='x', pady=5)
        ttk.Label(info_frame, text=f"Phone: {sanitize(cust_info[1] or '-')}", style='TLabel').pack(anchor='w')
        ttk.Label(info_frame, text=f"CNIC: {sanitize(cust_info[2] or '-')}", style='TLabel').pack(anchor='w')
        ttk.Label(info_frame, text=f"Address: {sanitize(cust_info[3] or '-')}", style='TLabel').pack(anchor='w')

        # Ledger Entries
        ttk.Label(self.customer_ledger_display_frame, text="Ledger Entries", font=("Segoe UI", 11, "bold"), style='TLabel').pack(anchor='w', pady=(10, 5))
        ledger_tree_frame = ttk.Frame(self.customer_ledger_display_frame, style='TFrame')
        ledger_tree_frame.pack(fill='both', expand=True, pady=5)
        
        columns = ("sr", "date", "description", "debit", "credit", "balance")
        ledger_tree = ttk.Treeview(ledger_tree_frame, columns=columns, show="headings", style='Treeview')
        ledger_tree.pack(side='left', fill='both', expand=True)
        
        vsb = ttk.Scrollbar(ledger_tree_frame, orient="vertical", command=ledger_tree.yview)
        vsb.pack(side='right', fill='y')
        ledger_tree.configure(yscrollcommand=vsb.set)

        ledger_tree.heading("sr", text="Sr#")
        ledger_tree.heading("date", text="Date")
        ledger_tree.heading("description", text="Description")
        ledger_tree.heading("debit", text="Debit", anchor='e')
        ledger_tree.heading("credit", text="Credit", anchor='e')
        ledger_tree.heading("balance", text="Balance", anchor='e')

        ledger_tree.column("sr", width=40, anchor='e')
        ledger_tree.column("date", width=80)
        ledger_tree.column("description", width=200)
        ledger_tree.column("debit", width=100, anchor='e')
        ledger_tree.column("credit", width=100, anchor='e')
        ledger_tree.column("balance", width=120, anchor='e')

        cursor.execute("SELECT entry_date, entry_type, amount, description FROM ledger WHERE party_type='customer' AND party_id=? ORDER BY entry_date ASC, id ASC", (customer_id,))
        ledger_entries = cursor.fetchall()

        running_balance = 0.0
        total_debit = 0.0
        total_credit = 0.0

        for sr, entry in enumerate(ledger_entries):
            date, entry_type, amount, description = entry
            debit = amount if entry_type == 'debit' else 0.0
            credit = amount if entry_type == 'credit' else 0.0
            
            if entry_type == 'debit':
                running_balance -= amount
            else:
                running_balance += amount
            
            total_debit += debit
            total_credit += credit

            ledger_tree.insert("", "end", values=(
                sr + 1,
                fmt_date(date),
                sanitize(description),
                fmt_money(debit) if debit > 0 else '-',
                fmt_money(credit) if credit > 0 else '-',
                f"{fmt_money(abs(running_balance))} {'Cr' if running_balance >= 0 else 'Dr'}"
            ), tags=('credit' if running_balance >= 0 else 'debit',))

        # Footer row for totals
        ledger_tree.insert("", "end", values=(
            "", "TOTAL", "", fmt_money(total_debit), fmt_money(total_credit), 
            f"{fmt_money(abs(running_balance))} {'Cr' if running_balance >= 0 else 'Dr'}"
        ), tags=('total_row',))
        ledger_tree.tag_configure('total_row', font=('Segoe UI', 9, 'bold'))

        # Purchase History
        ttk.Label(self.customer_ledger_display_frame, text="Bike Purchase History", font=("Segoe UI", 11, "bold"), style='TLabel').pack(anchor='w', pady=(10, 5))
        purchase_history_tree_frame = ttk.Frame(self.customer_ledger_display_frame, style='TFrame')
        purchase_history_tree_frame.pack(fill='both', expand=True, pady=5)
        
        columns = ("date", "chassis", "model", "color", "selling_price", "status")
        ph_tree = ttk.Treeview(purchase_history_tree_frame, columns=columns, show="headings", style='Treeview')
        ph_tree.pack(side='left', fill='both', expand=True)

        vsb = ttk.Scrollbar(purchase_history_tree_frame, orient="vertical", command=ph_tree.yview)
        vsb.pack(side='right', fill='y')
        ph_tree.configure(yscrollcommand=vsb.set)

        ph_tree.heading("date", text="Date")
        ph_tree.heading("chassis", text="Chassis")
        ph_tree.heading("model", text="Model")
        ph_tree.heading("color", text="Color")
        ph_tree.heading("selling_price", text="Selling Price", anchor='e')
        ph_tree.heading("status", text="Status")

        ph_tree.column("date", width=80)
        ph_tree.column("chassis", width=120)
        ph_tree.column("model", width=100)
        ph_tree.column("color", width=80)
        ph_tree.column("selling_price", width=100, anchor='e')
        ph_tree.column("status", width=80)

        cursor.execute("""
            SELECT b.selling_date, b.chassis_number, m.model_name, b.color, b.selling_price, b.status
            FROM bikes b LEFT JOIN models m ON b.model_id=m.id
            WHERE b.customer_id=? ORDER BY b.selling_date DESC
        """, (customer_id,))
        purchase_history_entries = cursor.fetchall()

        for entry in purchase_history_entries:
            date, chassis, model, color, price, status = entry
            ph_tree.insert("", "end", values=(
                fmt_date(date),
                sanitize(chassis),
                sanitize(model),
                sanitize(color),
                fmt_money(price),
                status.upper()
            ), tags=(status,))

        conn.close()

    def print_customer_ledger(self):
        messagebox.showinfo("Print", "Printing functionality for customer ledger is simulated. This would generate a printable report (e.g., PDF).")

    # --- Supplier Ledger Page ---
    def create_supplier_ledger_page(self):
        if not require_permission('supplier_ledger', 'view'): return

        filter_frame = ttk.Frame(self.content_frame, style='TFrame', padding=10)
        filter_frame.pack(fill='x', pady=5)

        ttk.Label(filter_frame, text="Select Supplier:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.sl_supplier_var = tk.StringVar()
        self.sl_supplier_combo = ttk.Combobox(filter_frame, textvariable=self.sl_supplier_var, state='readonly', width=30)
        self.sl_supplier_combo.grid(row=0, column=1, sticky='ew', padx=5, pady=2)
        self.load_suppliers_into_combo(self.sl_supplier_combo, include_all=False) # Only actual suppliers, not "All"
        self.sl_supplier_combo.bind("<<ComboboxSelected>>", lambda e: self.display_supplier_ledger())

        filter_frame.grid_columnconfigure(1, weight=1)
        
        ttk.Button(filter_frame, text="🖨 Print Ledger", command=self.print_supplier_ledger, style='TButton').grid(row=0, column=2, padx=5)

        self.supplier_ledger_display_frame = ttk.Frame(self.content_frame, style='TFrame')
        self.supplier_ledger_display_frame.pack(fill='both', expand=True, padx=5, pady=5)

        self.display_supplier_ledger()

    def display_supplier_ledger(self):
        for widget in self.supplier_ledger_display_frame.winfo_children():
            widget.destroy()

        selected_supplier_str = self.sl_supplier_var.get()
        if not selected_supplier_str:
            ttk.Label(self.supplier_ledger_display_frame, text="Please select a supplier to view their ledger.", style='TLabel').pack(pady=20)
            return

        sup_id_str = selected_supplier_str.split(' - ')[0]
        supplier_id = int(sup_id_str)

        conn = db_connect()
        cursor = conn.cursor()

        cursor.execute("SELECT name, contact, address FROM suppliers WHERE id = ?", (supplier_id,))
        sup_info = cursor.fetchone()

        if not sup_info:
            ttk.Label(self.supplier_ledger_display_frame, text="Supplier not found.", style='TLabel').pack(pady=20)
            conn.close()
            return

        # Supplier Info
        info_frame = ttk.LabelFrame(self.supplier_ledger_display_frame, text=f"🏭 {sanitize(sup_info[0])}", style='TFrame', padding=10)
        info_frame.pack(fill='x', pady=5)
        ttk.Label(info_frame, text=f"Contact: {sanitize(sup_info[1] or '-')}", style='TLabel').pack(anchor='w')
        ttk.Label(info_frame, text=f"Address: {sanitize(sup_info[2] or '-')}", style='TLabel').pack(anchor='w')

        # Purchase Orders
        ttk.Label(self.supplier_ledger_display_frame, text="Purchase Orders", font=("Segoe UI", 11, "bold"), style='TLabel').pack(anchor='w', pady=(10, 5))
        po_tree_frame = ttk.Frame(self.supplier_ledger_display_frame, style='TFrame')
        po_tree_frame.pack(fill='both', expand=True, pady=5)
        
        columns = ("sr", "order_date", "cheque_num", "bank", "cheque_date", "units", "cheque_amount", "bikes_total", "balance")
        po_tree = ttk.Treeview(po_tree_frame, columns=columns, show="headings", style='Treeview')
        po_tree.pack(side='left', fill='both', expand=True)
        
        vsb = ttk.Scrollbar(po_tree_frame, orient="vertical", command=po_tree.yview)
        vsb.pack(side='right', fill='y')
        po_tree.configure(yscrollcommand=vsb.set)

        po_tree.heading("sr", text="Sr#")
        po_tree.heading("order_date", text="Order Date")
        po_tree.heading("cheque_num", text="Cheque #")
        po_tree.heading("bank", text="Bank")
        po_tree.heading("cheque_date", text="Cheque Date")
        po_tree.heading("units", text="Units", anchor='e')
        po_tree.heading("cheque_amount", text="Cheque Amount", anchor='e')
        po_tree.heading("bikes_total", text="Bikes Total", anchor='e')
        po_tree.heading("balance", text="Balance", anchor='e')

        po_tree.column("sr", width=40, anchor='e')
        po_tree.column("order_date", width=80)
        po_tree.column("cheque_num", width=100)
        po_tree.column("bank", width=100)
        po_tree.column("cheque_date", width=80)
        po_tree.column("units", width=60, anchor='e')
        po_tree.column("cheque_amount", width=100, anchor='e')
        po_tree.column("bikes_total", width=100, anchor='e')
        po_tree.column("balance", width=120, anchor='e')

        cursor.execute("""
            SELECT po.order_date, po.cheque_number, po.bank_name, po.cheque_date, po.cheque_amount, po.total_units,
                   SUM(b.purchase_price) as bikes_total_price
            FROM purchase_orders po
            LEFT JOIN bikes b ON po.id = b.purchase_order_id
            WHERE po.supplier_id = ?
            GROUP BY po.id
            ORDER BY po.order_date ASC
        """, (supplier_id,))
        po_entries = cursor.fetchall()

        running_balance = 0.0
        total_cheque_amount = 0.0

        for sr, entry in enumerate(po_entries):
            order_date, cheque_number, bank_name, cheque_date, cheque_amount, total_units, bikes_total_price = entry
            
            running_balance += (cheque_amount or 0)
            total_cheque_amount += (cheque_amount or 0)

            po_tree.insert("", "end", values=(
                sr + 1,
                fmt_date(order_date),
                sanitize(cheque_number or '-'),
                sanitize(bank_name or '-'),
                fmt_date(cheque_date),
                total_units or 0,
                fmt_money(cheque_amount or 0),
                fmt_money(bikes_total_price or 0),
                fmt_money(running_balance)
            ))
        
        po_tree.insert("", "end", values=(
            "", "TOTAL", "", "", "", "", fmt_money(total_cheque_amount), "", fmt_money(running_balance)
        ), tags=('total_row',))
        po_tree.tag_configure('total_row', font=('Segoe UI', 9, 'bold'))

        conn.close()

    def print_supplier_ledger(self):
        messagebox.showinfo("Print", "Printing functionality for supplier ledger is simulated. This would generate a printable report (e.g., PDF).")

    # --- Reports Page ---
    def create_reports_page(self):
        if not require_permission('reports', 'view'): return

        self.reports_frame = ttk.Frame(self.content_frame, style='TFrame', padding=10)
        self.reports_frame.pack(fill='both', expand=True)

        # Tabs for different reports
        self.report_notebook = ttk.Notebook(self.reports_frame)
        self.report_notebook.pack(fill='both', expand=True)

        self.report_pages = {
            'stock': self.create_stock_report,
            'sold': self.create_sold_bikes_report,
            'model_wise': self.create_model_wise_report,
            'tax': self.create_tax_report,
            'profit': self.create_profit_report,
            'bank': self.create_bank_report,
            'monthly': self.create_monthly_summary_report,
            'daily': self.create_daily_ledger_report,
            'purchase_vs_sales': self.create_purchase_vs_sales_report,
        }

        for tab_name, create_func in self.report_pages.items():
            tab_frame = ttk.Frame(self.report_notebook, style='TFrame')
            self.report_notebook.add(tab_frame, text=create_func.__name__.replace('create_', '').replace('_report', '').replace('_', ' ').title())
            create_func(tab_frame) # Call the function to populate each tab

    def create_report_filters_and_buttons(self, parent_frame, report_type):
        filter_bar = ttk.Frame(parent_frame, style='TFrame', padding=5)
        filter_bar.pack(fill='x', pady=5)

        date_group = ttk.LabelFrame(filter_bar, text="Date Range", style='TFrame', padding=5)
        date_group.pack(side='left', padx=5, pady=2)
        ttk.Label(date_group, text="From:").grid(row=0, column=0, sticky='w')
        from_entry = ttk.Entry(date_group, width=12)
        from_entry.insert(0, (datetime.now().replace(day=1)).strftime('%Y-%m-%d'))
        from_entry.grid(row=0, column=1, padx=2)
        ttk.Label(date_group, text="To:").grid(row=0, column=2, sticky='w')
        to_entry = ttk.Entry(date_group, width=12)
        to_entry.insert(0, datetime.now().strftime('%Y-%m-%d'))
        to_entry.grid(row=0, column=3, padx=2)

        year_group = ttk.LabelFrame(filter_bar, text="Year/Month", style='TFrame', padding=5)
        year_group.pack(side='left', padx=5, pady=2)
        ttk.Label(year_group, text="Year:").grid(row=0, column=0, sticky='w')
        year_var = tk.StringVar(value=str(datetime.now().year))
        year_combo = ttk.Combobox(year_group, textvariable=year_var, values=[str(y) for y in range(datetime.now().year - 5, datetime.now().year + 6)], state='readonly', width=6)
        year_combo.grid(row=0, column=1, padx=2)
        ttk.Label(year_group, text="Month:").grid(row=0, column=2, sticky='w')
        month_var = tk.StringVar(value=str(datetime.now().month))
        month_combo = ttk.Combobox(year_group, textvariable=month_var, values=[str(m) for m in range(1, 13)], state='readonly', width=6)
        month_combo.grid(row=0, column=3, padx=2)

        apply_button = ttk.Button(filter_bar, text="🔍 Filter", command=lambda: self.refresh_report_tab(report_type), style='TButton')
        apply_button.pack(side='left', padx=5, pady=2)
        print_button = ttk.Button(filter_bar, text="🖨 Print", command=lambda: messagebox.showinfo("Print", f"Printing {report_type.replace('_', ' ').title()} Report"), style='TButton')
        print_button.pack(side='left', padx=5, pady=2)

        # Store widgets for later retrieval
        self.report_filter_widgets = {
            report_type: {
                'from_entry': from_entry, 'to_entry': to_entry,
                'year_var': year_var, 'month_var': month_var,
                'apply_button': apply_button, 'print_button': print_button
            }
        }

    def refresh_report_tab(self, report_type):
        current_tab_frame = self.report_notebook.winfo_children()[self.report_notebook.index(self.report_notebook.select())]
        for widget in current_tab_frame.winfo_children():
            # Only destroy the report-specific content, not the filter bar
            if widget not in self.report_filter_widgets[report_type].values() and widget != self.report_filter_widgets[report_type]['apply_button'].master and widget != self.report_filter_widgets[report_type]['print_button'].master:
                widget.destroy()
        
        self.report_pages[report_type](current_tab_frame)


    def create_stock_report(self, parent_frame):
        self.create_report_filters_and_buttons(parent_frame, 'stock')
        
        # Report-specific content
        report_content_frame = ttk.Frame(parent_frame, style='TFrame')
        report_content_frame.pack(fill='both', expand=True, pady=5)
        
        columns = ("sr", "chassis", "motor", "model", "category", "color", "purchase_price", "inventory_date", "days_in_stock")
        tree = ttk.Treeview(report_content_frame, columns=columns, show="headings", style='Treeview')
        tree.pack(side='left', fill='both', expand=True)

        vsb = ttk.Scrollbar(report_content_frame, orient="vertical", command=tree.yview)
        vsb.pack(side='right', fill='y')
        tree.configure(yscrollcommand=vsb.set)

        tree.heading("sr", text="Sr#")
        tree.heading("chassis", text="Chassis")
        tree.heading("motor", text="Motor#")
        tree.heading("model", text="Model")
        tree.heading("category", text="Category")
        tree.heading("color", text="Color")
        tree.heading("purchase_price", text="Purchase Price", anchor='e')
        tree.heading("inventory_date", text="Inventory Date")
        tree.heading("days_in_stock", text="Days in Stock", anchor='e')

        tree.column("sr", width=40, anchor='e')
        tree.column("chassis", width=120)
        tree.column("motor", width=100)
        tree.column("model", width=120)
        tree.column("category", width=100)
        tree.column("color", width=80)
        tree.column("purchase_price", width=100, anchor='e')
        tree.column("inventory_date", width=90)
        tree.column("days_in_stock", width=90, anchor='e')

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("""
            SELECT b.chassis_number, b.motor_number, m.model_name, m.category, b.color, b.purchase_price, b.inventory_date
            FROM bikes b LEFT JOIN models m ON b.model_id=m.id WHERE b.status='in_stock'
            ORDER BY m.model_name, b.inventory_date
        """)
        stock_bikes = cursor.fetchall()
        conn.close()

        stk_total = 0
        for sr, bike in enumerate(stock_bikes):
            chassis, motor, model, category, color, purchase_price, inventory_date_str = bike
            days = (datetime.now() - datetime.strptime(inventory_date_str, '%Y-%m-%d')).days
            stk_total += purchase_price
            
            tree.insert("", "end", values=(
                sr + 1, sanitize(chassis), sanitize(motor or '-'), sanitize(model), 
                sanitize(category), sanitize(color), fmt_money(purchase_price), 
                fmt_date(inventory_date_str), f"{days} days"
            ))
        
        tree.insert("", "end", values=(
            "", "TOTAL", "", "", "", "", fmt_money(stk_total), "", ""
        ), tags=('total_row',))
        tree.tag_configure('total_row', font=('Segoe UI', 9, 'bold'))

    def create_sold_bikes_report(self, parent_frame):
        self.create_report_filters_and_buttons(parent_frame, 'sold')
        
        report_content_frame = ttk.Frame(parent_frame, style='TFrame')
        report_content_frame.pack(fill='both', expand=True, pady=5)
        
        columns = ("sr", "chassis", "model", "color", "customer", "selling_date", "purchase_price", "selling_price", "tax", "margin")
        tree = ttk.Treeview(report_content_frame, columns=columns, show="headings", style='Treeview')
        tree.pack(side='left', fill='both', expand=True)

        vsb = ttk.Scrollbar(report_content_frame, orient="vertical", command=tree.yview)
        vsb.pack(side='right', fill='y')
        tree.configure(yscrollcommand=vsb.set)

        tree.heading("sr", text="Sr#")
        tree.heading("chassis", text="Chassis")
        tree.heading("model", text="Model")
        tree.heading("color", text="Color")
        tree.heading("customer", text="Customer")
        tree.heading("selling_date", text="Selling Date")
        tree.heading("purchase_price", text="Purchase Price", anchor='e')
        tree.heading("selling_price", text="Selling Price", anchor='e')
        tree.heading("tax", text="Tax", anchor='e')
        tree.heading("margin", text="Margin", anchor='e')

        tree.column("sr", width=40, anchor='e')
        tree.column("chassis", width=120)
        tree.column("model", width=100)
        tree.column("color", width=80)
        tree.column("customer", width=120)
        tree.column("selling_date", width=90)
        tree.column("purchase_price", width=100, anchor='e')
        tree.column("selling_price", width=100, anchor='e')
        tree.column("tax", width=80, anchor='e')
        tree.column("margin", width=90, anchor='e')

        filter_widgets = self.report_filter_widgets['sold']
        rep_from = filter_widgets['from_entry'].get()
        rep_to = filter_widgets['to_entry'].get()

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute(f"""
            SELECT b.chassis_number, m.model_name, b.color, c.name as cust_name, b.selling_date,
                   b.purchase_price, b.selling_price, b.tax_amount, b.margin
            FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id
            WHERE b.status='sold' AND b.selling_date BETWEEN ? AND ?
            ORDER BY b.selling_date DESC
        """, (rep_from, rep_to))
        sold_bikes = cursor.fetchall()
        conn.close()

        sold_total_sp = 0
        sold_total_pp = 0
        sold_total_mg = 0
        sold_total_tax = 0

        for sr, bike in enumerate(sold_bikes):
            chassis, model, color, customer, selling_date_str, pp, sp, tax_amt, margin = bike
            sold_total_sp += sp
            sold_total_pp += pp
            sold_total_mg += margin
            sold_total_tax += tax_amt
            
            tree.insert("", "end", values=(
                sr + 1, sanitize(chassis), sanitize(model), sanitize(color), 
                sanitize(customer or 'Walk-in'), fmt_date(selling_date_str), 
                fmt_money(pp), fmt_money(sp), fmt_money(tax_amt), fmt_money(margin)
            ), tags=('sold',))
        
        tree.insert("", "end", values=(
            "", "TOTAL", "", "", "", "", fmt_money(sold_total_pp), fmt_money(sold_total_sp), 
            fmt_money(sold_total_tax), fmt_money(sold_total_mg)
        ), tags=('total_row',))
        tree.tag_configure('total_row', font=('Segoe UI', 9, 'bold'))

    def create_model_wise_report(self, parent_frame):
        self.create_report_filters_and_buttons(parent_frame, 'model_wise')
        
        report_content_frame = ttk.Frame(parent_frame, style='TFrame')
        report_content_frame.pack(fill='both', expand=True, pady=5)
        
        columns = ("model", "short_code", "category", "total_inv", "sold_cnt", "avail_cnt", "ret_cnt", "total_pp", "total_sp", "total_mg")
        tree = ttk.Treeview(report_content_frame, columns=columns, show="headings", style='Treeview')
        tree.pack(side='left', fill='both', expand=True)

        vsb = ttk.Scrollbar(report_content_frame, orient="vertical", command=tree.yview)
        vsb.pack(side='right', fill='y')
        tree.configure(yscrollcommand=vsb.set)

        tree.heading("model", text="Model")
        tree.heading("short_code", text="Short Code")
        tree.heading("category", text="Category")
        tree.heading("total_inv", text="Inventory", anchor='e')
        tree.heading("sold_cnt", text="Sold", anchor='e')
        tree.heading("avail_cnt", text="Available", anchor='e')
        tree.heading("ret_cnt", text="Returned", anchor='e')
        tree.heading("total_pp", text="Total Purchase", anchor='e')
        tree.heading("total_sp", text="Total Sales", anchor='e')
        tree.heading("total_mg", text="Total Margin", anchor='e')

        tree.column("model", width=150)
        tree.column("short_code", width=80)
        tree.column("category", width=100)
        tree.column("total_inv", width=80, anchor='e')
        tree.column("sold_cnt", width=60, anchor='e')
        tree.column("avail_cnt", width=70, anchor='e')
        tree.column("ret_cnt", width=70, anchor='e')
        tree.column("total_pp", width=100, anchor='e')
        tree.column("total_sp", width=100, anchor='e')
        tree.column("total_mg", width=100, anchor='e')

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("""
            SELECT m.model_name, m.short_code, m.category,
            COUNT(b.id) as total_inv,
            SUM(CASE WHEN b.status='sold' THEN 1 ELSE 0 END) as sold_cnt,
            SUM(CASE WHEN b.status='in_stock' THEN 1 ELSE 0 END) as avail_cnt,
            SUM(CASE WHEN b.status='returned' THEN 1 ELSE 0 END) as ret_cnt,
            SUM(b.purchase_price) as total_pp,
            SUM(CASE WHEN b.status='sold' THEN b.selling_price ELSE 0 END) as total_sp,
            SUM(CASE WHEN b.status='sold' THEN b.margin ELSE 0 END) as total_mg
            FROM models m LEFT JOIN bikes b ON m.id=b.model_id
            GROUP BY m.id ORDER BY m.model_name
        """)
        model_wise_data = cursor.fetchall()
        conn.close()

        mw_t = [0, 0, 0, 0, 0, 0, 0] # total_inv, sold_cnt, avail_cnt, ret_cnt, total_pp, total_sp, total_mg
        for row in model_wise_data:
            model_name, short_code, category, total_inv, sold_cnt, avail_cnt, ret_cnt, total_pp, total_sp, total_mg = row
            mw_t[0] += total_inv
            mw_t[1] += sold_cnt
            mw_t[2] += avail_cnt
            mw_t[3] += ret_cnt
            mw_t[4] += total_pp
            mw_t[5] += total_sp
            mw_t[6] += total_mg

            tree.insert("", "end", values=(
                sanitize(model_name), sanitize(short_code), sanitize(category), total_inv, 
                sold_cnt, avail_cnt, ret_cnt, fmt_money(total_pp), fmt_money(total_sp), 
                fmt_money(total_mg)
            ))
        
        tree.insert("", "end", values=(
            "TOTAL", "", "", mw_t[0], mw_t[1], mw_t[2], mw_t[3], fmt_money(mw_t[4]), 
            fmt_money(mw_t[5]), fmt_money(mw_t[6])
        ), tags=('total_row',))
        tree.tag_configure('total_row', font=('Segoe UI', 9, 'bold'))

    def create_tax_report(self, parent_frame):
        self.create_report_filters_and_buttons(parent_frame, 'tax')
        
        report_content_frame = ttk.Frame(parent_frame, style='TFrame')
        report_content_frame.pack(fill='both', expand=True, pady=5)
        
        columns = ("month", "bikes_sold", "total_pp", "tax_amount")
        tree = ttk.Treeview(report_content_frame, columns=columns, show="headings", style='Treeview')
        tree.pack(side='left', fill='both', expand=True)

        vsb = ttk.Scrollbar(report_content_frame, orient="vertical", command=tree.yview)
        vsb.pack(side='right', fill='y')
        tree.configure(yscrollcommand=vsb.set)

        tree.heading("month", text="Month")
        tree.heading("bikes_sold", text="Bikes Sold", anchor='e')
        tree.heading("total_pp", text="Total Purchase Value", anchor='e')
        tree.heading("tax_amount", text=f"Tax Amount ({TAX_RATE*100:.2f}%)", anchor='e')

        tree.column("month", width=120)
        tree.column("bikes_sold", width=90, anchor='e')
        tree.column("total_pp", width=150, anchor='e')
        tree.column("tax_amount", width=150, anchor='e')

        filter_widgets = self.report_filter_widgets['tax']
        rep_from = filter_widgets['from_entry'].get()
        rep_to = filter_widgets['to_entry'].get()

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute(f"""
            SELECT STRFTIME('%Y-%m', selling_date) as ym, COUNT(*) as cnt, SUM(tax_amount) as total_tax, SUM(purchase_price) as total_pp
            FROM bikes WHERE status='sold' AND selling_date BETWEEN ? AND ?
            GROUP BY ym ORDER BY ym DESC
        """, (rep_from, rep_to))
        tax_report_data = cursor.fetchall()
        conn.close()

        total_tax_overall = 0
        for ym, cnt, total_tax, total_pp in tax_report_data:
            total_tax_overall += total_tax
            tree.insert("", "end", values=(
                datetime.strptime(ym + '-01', '%Y-%m-%d').strftime('%B %Y'),
                cnt, fmt_money(total_pp), fmt_money(total_tax)
            ))
        
        tree.insert("", "end", values=(
            "", "TOTAL TAX", "", fmt_money(total_tax_overall)
        ), tags=('total_row',))
        tree.tag_configure('total_row', font=('Segoe UI', 9, 'bold'))

    def create_profit_report(self, parent_frame):
        self.create_report_filters_and_buttons(parent_frame, 'profit')
        
        report_content_frame = ttk.Frame(parent_frame, style='TFrame')
        report_content_frame.pack(fill='both', expand=True, pady=5)
        
        columns = ("month", "bikes_sold", "total_pp", "total_sp", "total_tax", "net_profit", "avg_margin")
        tree = ttk.Treeview(report_content_frame, columns=columns, show="headings", style='Treeview')
        tree.pack(side='left', fill='both', expand=True)

        vsb = ttk.Scrollbar(report_content_frame, orient="vertical", command=tree.yview)
        vsb.pack(side='right', fill='y')
        tree.configure(yscrollcommand=vsb.set)

        tree.heading("month", text="Month")
        tree.heading("bikes_sold", text="Bikes Sold", anchor='e')
        tree.heading("total_pp", text="Total Purchase", anchor='e')
        tree.heading("total_sp", text="Total Sales", anchor='e')
        tree.heading("total_tax", text="Total Tax", anchor='e')
        tree.heading("net_profit", text="Net Profit", anchor='e')
        tree.heading("avg_margin", text="Avg Margin", anchor='e')

        tree.column("month", width=120)
        tree.column("bikes_sold", width=90, anchor='e')
        tree.column("total_pp", width=120, anchor='e')
        tree.column("total_sp", width=120, anchor='e')
        tree.column("total_tax", width=100, anchor='e')
        tree.column("net_profit", width=100, anchor='e')
        tree.column("avg_margin", width=100, anchor='e')

        filter_widgets = self.report_filter_widgets['profit']
        rep_from = filter_widgets['from_entry'].get()
        rep_to = filter_widgets['to_entry'].get()

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute(f"""
            SELECT STRFTIME('%Y-%m', selling_date) as ym, COUNT(*) as cnt, 
                   SUM(selling_price) as total_sp, SUM(purchase_price) as total_pp, 
                   SUM(margin) as total_margin, SUM(tax_amount) as total_tax 
            FROM bikes WHERE status='sold' AND selling_date BETWEEN ? AND ?
            GROUP BY ym ORDER BY ym DESC
        """, (rep_from, rep_to))
        profit_report_data = cursor.fetchall()
        conn.close()

        profit_t = [0, 0, 0, 0, 0] # cnt, total_pp, total_sp, total_tax, total_margin
        for ym, cnt, total_sp, total_pp, total_margin, total_tax in profit_report_data:
            profit_t[0] += cnt
            profit_t[1] += total_pp
            profit_t[2] += total_sp
            profit_t[3] += total_tax
            profit_t[4] += total_margin
            avg_margin = total_margin / cnt if cnt > 0 else 0
            
            tree.insert("", "end", values=(
                datetime.strptime(ym + '-01', '%Y-%m-%d').strftime('%B %Y'),
                cnt, fmt_money(total_pp), fmt_money(total_sp), fmt_money(total_tax), 
                fmt_money(total_margin), fmt_money(avg_margin)
            ))
        
        tree.insert("", "end", values=(
            "TOTAL", profit_t[0], fmt_money(profit_t[1]), fmt_money(profit_t[2]), 
            fmt_money(profit_t[3]), fmt_money(profit_t[4]), fmt_money(profit_t[4] / profit_t[0] if profit_t[0] > 0 else 0)
        ), tags=('total_row',))
        tree.tag_configure('total_row', font=('Segoe UI', 9, 'bold'))

    def create_bank_report(self, parent_frame):
        self.create_report_filters_and_buttons(parent_frame, 'bank')
        
        report_content_frame = ttk.Frame(parent_frame, style='TFrame')
        report_content_frame.pack(fill='both', expand=True, pady=5)
        
        columns = ("bank", "type", "pending", "cleared", "bounced", "cancelled", "total_count", "total_amount")
        tree = ttk.Treeview(report_content_frame, columns=columns, show="headings", style='Treeview')
        tree.pack(side='left', fill='both', expand=True)

        vsb = ttk.Scrollbar(report_content_frame, orient="vertical", command=tree.yview)
        vsb.pack(side='right', fill='y')
        tree.configure(yscrollcommand=vsb.set)

        tree.heading("bank", text="Bank")
        tree.heading("type", text="Type")
        tree.heading("pending", text="Pending", anchor='e')
        tree.heading("cleared", text="Cleared", anchor='e')
        tree.heading("bounced", text="Bounced", anchor='e')
        tree.heading("cancelled", text="Cancelled", anchor='e')
        tree.heading("total_count", text="Total Count", anchor='e')
        tree.heading("total_amount", text="Total Amount", anchor='e')

        tree.column("bank", width=120)
        tree.column("type", width=80)
        tree.column("pending", width=100, anchor='e')
        tree.column("cleared", width=100, anchor='e')
        tree.column("bounced", width=100, anchor='e')
        tree.column("cancelled", width=100, anchor='e')
        tree.column("total_count", width=80, anchor='e')
        tree.column("total_amount", width=120, anchor='e')

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("""
            SELECT bank_name, type, status, COUNT(*) as cnt, SUM(amount) as total
            FROM cheque_register GROUP BY bank_name, type, status ORDER BY bank_name, type
        """)
        bank_report_data = cursor.fetchall()
        conn.close()

        bank_data_grouped = {}
        for bank, type_, status, cnt, total in bank_report_data:
            if bank not in bank_data_grouped:
                bank_data_grouped[bank] = {}
            if type_ not in bank_data_grouped[bank]:
                bank_data_grouped[bank][type_] = {'pending':0,'cleared':0,'bounced':0,'cancelled':0, 'cnt_sum':0, 'amt_sum':0}
            
            bank_data_grouped[bank][type_][status] = total or 0
            bank_data_grouped[bank][type_]['cnt_sum'] += cnt
            bank_data_grouped[bank][type_]['amt_sum'] += (total or 0)
        
        bank_totals_all = [0, 0] # [total_count, total_amount]
        for bank_name, types in bank_data_grouped.items():
            for type_name, statuses in types.items():
                pending = statuses['pending']
                cleared = statuses['cleared']
                bounced = statuses['bounced']
                cancelled = statuses['cancelled']
                cnt_sum = statuses['cnt_sum']
                amt_sum = statuses['amt_sum']

                bank_totals_all[0] += cnt_sum
                bank_totals_all[1] += amt_sum

                tree.insert("", "end", values=(
                    sanitize(bank_name), type_name.title(), 
                    fmt_money(pending), fmt_money(cleared), fmt_money(bounced), fmt_money(cancelled),
                    cnt_sum, fmt_money(amt_sum)
                ))
        
        tree.insert("", "end", values=(
            "TOTAL", "", "", "", "", "", bank_totals_all[0], fmt_money(bank_totals_all[1])
        ), tags=('total_row',))
        tree.tag_configure('total_row', font=('Segoe UI', 9, 'bold'))


    def create_monthly_summary_report(self, parent_frame):
        self.create_report_filters_and_buttons(parent_frame, 'monthly')
        
        report_content_frame = ttk.Frame(parent_frame, style='TFrame')
        report_content_frame.pack(fill='both', expand=True, pady=5)
        
        columns = ("month", "purchased_units", "purchase_value", "sold_units", "sales_value", "profit")
        tree = ttk.Treeview(report_content_frame, columns=columns, show="headings", style='Treeview')
        tree.pack(side='left', fill='both', expand=True)

        vsb = ttk.Scrollbar(report_content_frame, orient="vertical", command=tree.yview)
        vsb.pack(side='right', fill='y')
        tree.configure(yscrollcommand=vsb.set)

        tree.heading("month", text="Month")
        tree.heading("purchased_units", text="Purchased Units", anchor='e')
        tree.heading("purchase_value", text="Purchase Value", anchor='e')
        tree.heading("sold_units", text="Sold Units", anchor='e')
        tree.heading("sales_value", text="Sales Value", anchor='e')
        tree.heading("profit", text="Profit", anchor='e')

        tree.column("month", width=120)
        tree.column("purchased_units", width=100, anchor='e')
        tree.column("purchase_value", width=120, anchor='e')
        tree.column("sold_units", width=80, anchor='e')
        tree.column("sales_value", width=120, anchor='e')
        tree.column("profit", width=100, anchor='e')

        filter_widgets = self.report_filter_widgets['monthly']
        rep_year = int(filter_widgets['year_var'].get())

        conn = db_connect()
        cursor = conn.cursor()
        
        cursor.execute(f"""
            SELECT STRFTIME('%Y-%m', order_date) as ym, COUNT(*) as purchased, SUM(purchase_price) as pp_total 
            FROM bikes WHERE STRFTIME('%Y', order_date)=? GROUP BY ym
        """, (str(rep_year),))
        monthly_purch = {row[0]: {'purchased': row[1], 'pp_total': row[2]} for row in cursor.fetchall()}

        cursor.execute(f"""
            SELECT STRFTIME('%Y-%m', selling_date) as ym, COUNT(*) as sold_cnt, SUM(selling_price) as sp_total, SUM(margin) as mg_total 
            FROM bikes WHERE status='sold' AND STRFTIME('%Y', selling_date)=? GROUP BY ym
        """, (str(rep_year),))
        monthly_sales = {row[0]: {'sold_cnt': row[1], 'sp_total': row[2], 'mg_total': row[3]} for row in cursor.fetchall()}
        conn.close()

        all_months_keys = sorted(list(set(monthly_purch.keys()).union(set(monthly_sales.keys()))))

        mt = [0, 0, 0, 0, 0] # purchased_units, purchase_value, sold_units, sales_value, profit
        for ym in all_months_keys:
            p = monthly_purch.get(ym, {'purchased': 0, 'pp_total': 0})
            s = monthly_sales.get(ym, {'sold_cnt': 0, 'sp_total': 0, 'mg_total': 0})
            
            mt[0] += p['purchased']
            mt[1] += p['pp_total']
            mt[2] += s['sold_cnt']
            mt[3] += s['sp_total']
            mt[4] += s['mg_total']
            
            tree.insert("", "end", values=(
                datetime.strptime(ym + '-01', '%Y-%m-%d').strftime('%B %Y'),
                p['purchased'], fmt_money(p['pp_total']), s['sold_cnt'], fmt_money(s['sp_total']), 
                fmt_money(s['mg_total'])
            ))
        
        tree.insert("", "end", values=(
            "TOTAL", mt[0], fmt_money(mt[1]), mt[2], fmt_money(mt[3]), fmt_money(mt[4])
        ), tags=('total_row',))
        tree.tag_configure('total_row', font=('Segoe UI', 9, 'bold'))

    def create_daily_ledger_report(self, parent_frame):
        # Specific filter for daily date
        filter_bar = ttk.Frame(parent_frame, style='TFrame', padding=5)
        filter_bar.pack(fill='x', pady=5)
        
        ttk.Label(filter_bar, text="Select Date:").grid(row=0, column=0, sticky='w')
        self.daily_date_entry = ttk.Entry(filter_bar, width=12)
        self.daily_date_entry.insert(0, datetime.now().strftime('%Y-%m-%d'))
        self.daily_date_entry.grid(row=0, column=1, padx=2)
        
        apply_button = ttk.Button(filter_bar, text="🔍 View", command=lambda: self.refresh_report_tab('daily'), style='TButton')
        apply_button.grid(row=0, column=2, padx=5)
        print_button = ttk.Button(filter_bar, text="🖨 Print", command=lambda: messagebox.showinfo("Print", "Printing Daily Ledger Report"), style='TButton')
        print_button.grid(row=0, column=3, padx=5)
        
        self.report_filter_widgets['daily'] = {'daily_date_entry': self.daily_date_entry} # Store this specific widget
        
        # Report-specific content
        report_content_frame = ttk.Frame(parent_frame, style='TFrame')
        report_content_frame.pack(fill='both', expand=True, pady=5)
        
        daily_date = self.daily_date_entry.get()

        conn = db_connect()
        cursor = conn.cursor()

        # Sales
        ttk.Label(report_content_frame, text="Sales", font=("Segoe UI", 11, "bold"), style='TLabel').pack(anchor='w', pady=(5, 2))
        sales_tree_frame = ttk.Frame(report_content_frame, style='TFrame')
        sales_tree_frame.pack(fill='both', expand=True, pady=2)
        
        sales_columns = ("chassis", "model", "customer", "selling_price", "tax", "margin")
        sales_tree = ttk.Treeview(sales_tree_frame, columns=sales_columns, show="headings", style='Treeview')
        sales_tree.pack(side='left', fill='both', expand=True)
        sales_tree.heading("chassis", text="Chassis")
        sales_tree.heading("model", text="Model")
        sales_tree.heading("customer", text="Customer")
        sales_tree.heading("selling_price", text="Selling Price", anchor='e')
        sales_tree.heading("tax", text="Tax", anchor='e')
        sales_tree.heading("margin", text="Margin", anchor='e')
        sales_tree.column("chassis", width=120)
        sales_tree.column("model", width=100)
        sales_tree.column("customer", width=120)
        sales_tree.column("selling_price", width=100, anchor='e')
        sales_tree.column("tax", width=80, anchor='e')
        sales_tree.column("margin", width=90, anchor='e')

        cursor.execute("""
            SELECT b.chassis_number, m.model_name, c.name as cust_name, b.selling_price, b.tax_amount, b.margin
            FROM bikes b LEFT JOIN models m ON b.model_id=m.id LEFT JOIN customers c ON b.customer_id=c.id
            WHERE b.selling_date=? AND b.status='sold'
        """, (daily_date,))
        daily_sales_data = cursor.fetchall()

        d_sp = 0
        d_mg = 0
        for chassis, model, customer, sp, tax, mg in daily_sales_data:
            d_sp += sp
            d_mg += mg
            sales_tree.insert("", "end", values=(
                sanitize(chassis), sanitize(model), sanitize(customer or 'Walk-in'),
                fmt_money(sp), fmt_money(tax), fmt_money(mg)
            ))
        sales_tree.insert("", "end", values=(
            "", "TOTAL", "", fmt_money(d_sp), "", fmt_money(d_mg)
        ), tags=('total_row',))
        sales_tree.tag_configure('total_row', font=('Segoe UI', 9, 'bold'))

        # Inventory Added
        ttk.Label(report_content_frame, text="Inventory Added", font=("Segoe UI", 11, "bold"), style='TLabel').pack(anchor='w', pady=(10, 2))
        purch_tree_frame = ttk.Frame(report_content_frame, style='TFrame')
        purch_tree_frame.pack(fill='both', expand=True, pady=2)

        purch_columns = ("chassis", "motor", "model", "color", "purchase_price", "status")
        purch_tree = ttk.Treeview(purch_tree_frame, columns=purch_columns, show="headings", style='Treeview')
        purch_tree.pack(side='left', fill='both', expand=True)
        purch_tree.heading("chassis", text="Chassis")
        purch_tree.heading("motor", text="Motor#")
        purch_tree.heading("model", text="Model")
        purch_tree.heading("color", text="Color")
        purch_tree.heading("purchase_price", text="Purchase Price", anchor='e')
        purch_tree.heading("status", text="Status")
        purch_tree.column("chassis", width=120)
        purch_tree.column("motor", width=100)
        purch_tree.column("model", width=100)
        purch_tree.column("color", width=80)
        purch_tree.column("purchase_price", width=100, anchor='e')
        purch_tree.column("status", width=80)
        
        cursor.execute("""
            SELECT b.chassis_number, b.motor_number, m.model_name, b.color, b.purchase_price, b.status
            FROM bikes b LEFT JOIN models m ON b.model_id=m.id
            WHERE b.inventory_date=?
        """, (daily_date,))
        daily_purch_data = cursor.fetchall()

        d_pp = 0
        for chassis, motor, model, color, pp, status in daily_purch_data:
            d_pp += pp
            purch_tree.insert("", "end", values=(
                sanitize(chassis), sanitize(motor or '-'), sanitize(model),
                sanitize(color), fmt_money(pp), status.upper()
            ))
        purch_tree.insert("", "end", values=(
            "", "TOTAL", "", "", fmt_money(d_pp), ""
        ), tags=('total_row',))
        purch_tree.tag_configure('total_row', font=('Segoe UI', 9, 'bold'))

        conn.close()

    def create_purchase_vs_sales_report(self, parent_frame):
        self.create_report_filters_and_buttons(parent_frame, 'purchase_vs_sales')
        
        report_content_frame = ttk.Frame(parent_frame, style='TFrame')
        report_content_frame.pack(fill='both', expand=True, pady=5)
        
        columns = ("month", "purchased", "purchase_value", "sold", "sales_value", "difference")
        tree = ttk.Treeview(report_content_frame, columns=columns, show="headings", style='Treeview')
        tree.pack(side='left', fill='both', expand=True)

        vsb = ttk.Scrollbar(report_content_frame, orient="vertical", command=tree.yview)
        vsb.pack(side='right', fill='y')
        tree.configure(yscrollcommand=vsb.set)

        tree.heading("month", text="Month")
        tree.heading("purchased", text="Purchased", anchor='e')
        tree.heading("purchase_value", text="Purchase Value", anchor='e')
        tree.heading("sold", text="Sold", anchor='e')
        tree.heading("sales_value", text="Sales Value", anchor='e')
        tree.heading("difference", text="Difference", anchor='e')

        tree.column("month", width=120)
        tree.column("purchased", width=90, anchor='e')
        tree.column("purchase_value", width=120, anchor='e')
        tree.column("sold", width=80, anchor='e')
        tree.column("sales_value", width=120, anchor='e')
        tree.column("difference", width=100, anchor='e')

        filter_widgets = self.report_filter_widgets['purchase_vs_sales']
        rep_year = int(filter_widgets['year_var'].get())

        conn = db_connect()
        cursor = conn.cursor()

        cursor.execute(f"""
            SELECT STRFTIME('%Y-%m', order_date) as ym, COUNT(*) as p_cnt, SUM(purchase_price) as p_val 
            FROM bikes WHERE STRFTIME('%Y', order_date)=? GROUP BY ym
        """, (str(rep_year),))
        pvs_data = {row[0]: {'p_cnt': row[1], 'p_val': row[2]} for row in cursor.fetchall()}

        cursor.execute(f"""
            SELECT STRFTIME('%Y-%m', selling_date) as ym, COUNT(*) as s_cnt, SUM(selling_price) as s_val 
            FROM bikes WHERE status='sold' AND STRFTIME('%Y', selling_date)=? GROUP BY ym
        """, (str(rep_year),))
        svp_data = {row[0]: {'s_cnt': row[1], 's_val': row[2]} for row in cursor.fetchall()}
        conn.close()

        all_months_keys = sorted(list(set(pvs_data.keys()).union(set(svp_data.keys()))))

        pt = [0, 0, 0, 0] # p_cnt, p_val, s_cnt, s_val
        for ym in all_months_keys:
            p = pvs_data.get(ym, {'p_cnt': 0, 'p_val': 0})
            s = svp_data.get(ym, {'s_cnt': 0, 's_val': 0})
            
            diff = (s['s_val'] or 0) - (p['p_val'] or 0)
            pt[0] += p['p_cnt']
            pt[1] += p['p_val']
            pt[2] += s['s_cnt']
            pt[3] += s['s_val']
            
            tree.insert("", "end", values=(
                datetime.strptime(ym + '-01', '%Y-%m-%d').strftime('%B %Y'),
                p['p_cnt'], fmt_money(p['p_val']), s['s_cnt'], fmt_money(s['s_val']), 
                fmt_money(diff)
            ))
        
        tree.insert("", "end", values=(
            "TOTAL", pt[0], fmt_money(pt[1]), pt[2], fmt_money(pt[3]), fmt_money(pt[3] - pt[1])
        ), tags=('total_row',))
        tree.tag_configure('total_row', font=('Segoe UI', 9, 'bold'))

    # --- Models Page ---
    def create_models_page(self):
        if not require_permission('models', 'view'): return

        self.models_form_frame = ttk.Frame(self.content_frame, style='TFrame', padding=10)
        self.models_form_frame.pack(fill='x', pady=5)

        ttk.Button(self.models_form_frame, text="+ Add Model", command=self.open_add_model_form, style='TButton').pack(anchor='w', pady=5)

        self.add_model_form = ttk.LabelFrame(self.models_form_frame, text="+ Add New Model", style='TFrame', padding=10)
        # self.add_model_form.pack(fill='x', pady=5) # Initially hidden

        ttk.Label(self.add_model_form, text="Model Code:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.model_code_entry = ttk.Entry(self.add_model_form)
        self.model_code_entry.grid(row=0, column=1, sticky='ew', padx=5, pady=2)

        ttk.Label(self.add_model_form, text="Model Name:").grid(row=0, column=2, sticky='w', padx=5, pady=2)
        self.model_name_entry = ttk.Entry(self.add_model_form)
        self.model_name_entry.grid(row=0, column=3, sticky='ew', padx=5, pady=2)

        ttk.Label(self.add_model_form, text="Category:").grid(row=1, column=0, sticky='w', padx=5, pady=2)
        self.model_category_entry = ttk.Entry(self.add_model_form)
        self.model_category_entry.insert(0, "Electric Bike")
        self.model_category_entry.grid(row=1, column=1, sticky='ew', padx=5, pady=2)

        ttk.Label(self.add_model_form, text="Short Code:").grid(row=1, column=2, sticky='w', padx=5, pady=2)
        self.model_short_code_entry = ttk.Entry(self.add_model_form)
        self.model_short_code_entry.grid(row=1, column=3, sticky='ew', padx=5, pady=2)

        self.add_model_form.grid_columnconfigure(1, weight=1)
        self.add_model_form.grid_columnconfigure(3, weight=1)

        button_frame = ttk.Frame(self.add_model_form, style='TFrame')
        button_frame.grid(row=2, column=0, columnspan=4, pady=10)
        self.save_model_button = ttk.Button(button_frame, text="💾 Save Model", command=self.save_model, style='TButton')
        self.save_model_button.pack(side='left', padx=5)
        ttk.Button(button_frame, text="Cancel", command=self.hide_add_model_form, style='TButton').pack(side='left', padx=5)

        # Models Table
        self.models_tree_frame = ttk.Frame(self.content_frame, style='TFrame')
        self.models_tree_frame.pack(fill='both', expand=True, padx=5, pady=5)

        columns = ("sr", "model_code", "model_name", "category", "short_code", "total_inv", "in_stock", "sold", "actions")
        self.models_tree = ttk.Treeview(self.models_tree_frame, columns=columns, show="headings", style='Treeview')
        self.models_tree.pack(side='left', fill='both', expand=True)

        vsb = ttk.Scrollbar(self.models_tree_frame, orient="vertical", command=self.models_tree.yview)
        vsb.pack(side='right', fill='y')
        self.models_tree.configure(yscrollcommand=vsb.set)

        self.models_tree.heading("sr", text="Sr#")
        self.models_tree.heading("model_code", text="Model Code")
        self.models_tree.heading("model_name", text="Model Name")
        self.models_tree.heading("category", text="Category")
        self.models_tree.heading("short_code", text="Short Code")
        self.models_tree.heading("total_inv", text="Total Inventory", anchor='e')
        self.models_tree.heading("in_stock", text="In Stock", anchor='e')
        self.models_tree.heading("sold", text="Sold", anchor='e')
        self.models_tree.heading("actions", text="Actions")

        self.models_tree.column("sr", width=40, anchor='e')
        self.models_tree.column("model_code", width=100)
        self.models_tree.column("model_name", width=150)
        self.models_tree.column("category", width=100)
        self.models_tree.column("short_code", width=80)
        self.models_tree.column("total_inv", width=90, anchor='e')
        self.models_tree.column("in_stock", width=80, anchor='e')
        self.models_tree.column("sold", width=80, anchor='e')
        self.models_tree.column("actions", width=150, anchor='center')

        self.display_models()
        self.current_edit_model_id = None

    def open_add_model_form(self):
        self.add_model_form.pack(fill='x', pady=5)
        self.add_model_form.config(text="+ Add New Model")
        self.model_code_entry.delete(0, tk.END)
        self.model_name_entry.delete(0, tk.END)
        self.model_category_entry.delete(0, tk.END)
        self.model_category_entry.insert(0, "Electric Bike")
        self.model_short_code_entry.delete(0, tk.END)
        self.current_edit_model_id = None
        self.save_model_button.config(text="💾 Save Model")

    def hide_add_model_form(self):
        self.add_model_form.pack_forget()

    def display_models(self):
        for i in self.models_tree.get_children():
            self.models_tree.delete(i)

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("""
            SELECT m.*, COUNT(b.id) as bike_count, 
            SUM(CASE WHEN b.status='in_stock' THEN 1 ELSE 0 END) as in_stock_cnt,
            SUM(CASE WHEN b.status='sold' THEN 1 ELSE 0 END) as sold_cnt
            FROM models m LEFT JOIN bikes b ON m.id=b.model_id GROUP BY m.id ORDER BY m.model_name
        """)
        
        models_data = cursor.fetchall()
        
        for sr, model in enumerate(models_data):
            model_id = model[0]
            self.models_tree.insert("", "end", iid=model_id, values=(
                sr + 1, 
                sanitize(model[1]), 
                sanitize(model[2]), 
                sanitize(model[3]), 
                sanitize(model[4]), 
                model[5] if len(model) > 5 else 0, # bike_count
                model[6] if len(model) > 6 else 0, # in_stock
                model[7] if len(model) > 7 else 0, # sold
                ""
            ))
            self.add_model_action_buttons(model_id, sr)
        conn.close()

    def add_model_action_buttons(self, model_id, row_idx):
        button_frame = ttk.Frame(self.models_tree, style='TFrame')
        
        if has_permission('purchase', 'add'):
            ttk.Button(button_frame, text="📦", command=lambda mid=model_id: self.show_page('purchase', model_id=mid), style='TButton', width=3).pack(side='left', padx=1)
        if has_permission('sale', 'add'):
            ttk.Button(button_frame, text="🛒", command=lambda mid=model_id: self.show_page('sale', model_id=mid), style='TButton', width=3).pack(side='left', padx=1)
        if has_permission('models', 'edit'):
            ttk.Button(button_frame, text="✏", command=lambda mid=model_id: self.edit_model(mid), style='TButton', width=3).pack(side='left', padx=1)
        if has_permission('models', 'delete'):
            ttk.Button(button_frame, text="🗑", command=lambda mid=model_id: self.delete_model(mid), style='TButton', width=3).pack(side='left', padx=1)

        item_id = str(model_id)
        self.models_tree.set(item_id, "actions", button_frame)
        self.models_tree.window_create(item_id, column="actions", anchor="center", window=button_frame)

    def save_model(self):
        model_code = sanitize(self.model_code_entry.get())
        model_name = sanitize(self.model_name_entry.get())
        category = sanitize(self.model_category_entry.get())
        short_code = sanitize(self.model_short_code_entry.get())

        if not model_code or not model_name:
            messagebox.showerror("Validation Error", "Model code and name are required.")
            return

        conn = db_connect()
        cursor = conn.cursor()
        try:
            if self.current_edit_model_id:
                if not require_permission('models', 'edit'): return
                cursor.execute("""
                    UPDATE models SET model_code=?, model_name=?, category=?, short_code=?
                    WHERE id=?
                """, (model_code, model_name, category, short_code, self.current_edit_model_id))
            else:
                if not require_permission('models', 'add'): return
                cursor.execute("""
                    INSERT INTO models (model_code, model_name, category, short_code)
                    VALUES (?, ?, ?, ?)
                """, (model_code, model_name, category, short_code))
            conn.commit()
            messagebox.showinfo("Success", "Model saved successfully.")
            self.hide_add_model_form()
            self.display_models()
        except sqlite3.Error as e:
            messagebox.showerror("Database Error", f"Failed to save model: {e}")
        finally:
            conn.close()

    def edit_model(self, model_id):
        if not require_permission('models', 'edit'): return

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM models WHERE id = ?", (model_id,))
        model_data = cursor.fetchone()
        conn.close()

        if model_data:
            self.open_add_model_form()
            self.add_model_form.config(text=f"✏ Edit Model (ID: {model_id})")
            self.model_code_entry.delete(0, tk.END)
            self.model_code_entry.insert(0, sanitize(model_data[1]))
            self.model_name_entry.delete(0, tk.END)
            self.model_name_entry.insert(0, sanitize(model_data[2]))
            self.model_category_entry.delete(0, tk.END)
            self.model_category_entry.insert(0, sanitize(model_data[3]))
            self.model_short_code_entry.delete(0, tk.END)
            self.model_short_code_entry.insert(0, sanitize(model_data[4]))
            self.current_edit_model_id = model_id
            self.save_model_button.config(text="💾 Update Model")

    def delete_model(self, model_id):
        if not require_permission('models', 'delete'): return
        
        if messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this model? This is only possible if no bikes are linked to it."):
            conn = db_connect()
            cursor = conn.cursor()
            try:
                cursor.execute("DELETE FROM models WHERE id = ?", (model_id,))
                conn.commit()
                messagebox.showinfo("Success", "Model deleted successfully.")
                self.display_models()
            except sqlite3.IntegrityError:
                messagebox.showerror("Error", "Cannot delete model. There are bikes currently linked to this model.")
                conn.rollback()
            except sqlite3.Error as e:
                messagebox.showerror("Database Error", f"Failed to delete model: {e}")
            finally:
                conn.close()

    # --- Customers Page ---
    def create_customers_page(self):
        if not require_permission('customers', 'view'): return

        self.customers_filter_frame = ttk.Frame(self.content_frame, style='TFrame', padding=10)
        self.customers_filter_frame.pack(fill='x', pady=5)

        ttk.Label(self.customers_filter_frame, text="Search:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.cust_search_var = tk.StringVar()
        ttk.Entry(self.customers_filter_frame, textvariable=self.cust_search_var, width=30).grid(row=0, column=1, sticky='ew', padx=5, pady=2)
        ttk.Button(self.customers_filter_frame, text="🔍", command=self.display_customers, style='TButton').grid(row=0, column=2, padx=2)
        
        if has_permission('customers', 'add'):
            ttk.Button(self.customers_filter_frame, text="+ Add Customer", command=self.open_add_customer_form, style='TButton').grid(row=0, column=3, padx=5)

        self.customers_filter_frame.grid_columnconfigure(1, weight=1)

        self.add_customer_form = ttk.LabelFrame(self.content_frame, text="+ Add New Customer", style='TFrame', padding=10)
        # self.add_customer_form.pack(fill='x', pady=5) # Initially hidden

        ttk.Label(self.add_customer_form, text="Name:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.customer_name_entry = ttk.Entry(self.add_customer_form)
        self.customer_name_entry.grid(row=0, column=1, sticky='ew', padx=5, pady=2)

        ttk.Label(self.add_customer_form, text="Phone:").grid(row=0, column=2, sticky='w', padx=5, pady=2)
        self.customer_phone_entry = ttk.Entry(self.add_customer_form)
        self.customer_phone_entry.grid(row=0, column=3, sticky='ew', padx=5, pady=2)

        ttk.Label(self.add_customer_form, text="CNIC:").grid(row=1, column=0, sticky='w', padx=5, pady=2)
        self.customer_cnic_entry = ttk.Entry(self.add_customer_form)
        self.customer_cnic_entry.grid(row=1, column=1, sticky='ew', padx=5, pady=2)

        ttk.Label(self.add_customer_form, text="Address:").grid(row=1, column=2, sticky='w', padx=5, pady=2)
        self.customer_address_entry = ttk.Entry(self.add_customer_form)
        self.customer_address_entry.grid(row=1, column=3, sticky='ew', padx=5, pady=2)

        self.add_customer_form.grid_columnconfigure(1, weight=1)
        self.add_customer_form.grid_columnconfigure(3, weight=1)

        button_frame = ttk.Frame(self.add_customer_form, style='TFrame')
        button_frame.grid(row=2, column=0, columnspan=4, pady=10)
        self.save_customer_button = ttk.Button(button_frame, text="💾 Save Customer", command=self.save_customer, style='TButton')
        self.save_customer_button.pack(side='left', padx=5)
        ttk.Button(button_frame, text="Cancel", command=self.hide_add_customer_form, style='TButton').pack(side='left', padx=5)

        # Customers Table
        self.customers_tree_frame = ttk.Frame(self.content_frame, style='TFrame')
        self.customers_tree_frame.pack(fill='both', expand=True, padx=5, pady=5)

        columns = ("sr", "name", "phone", "cnic", "address", "bikes_purchased", "total_amount", "actions")
        self.customers_tree = ttk.Treeview(self.customers_tree_frame, columns=columns, show="headings", style='Treeview')
        self.customers_tree.pack(side='left', fill='both', expand=True)

        vsb = ttk.Scrollbar(self.customers_tree_frame, orient="vertical", command=self.customers_tree.yview)
        vsb.pack(side='right', fill='y')
        self.customers_tree.configure(yscrollcommand=vsb.set)

        self.customers_tree.heading("sr", text="Sr#")
        self.customers_tree.heading("name", text="Name")
        self.customers_tree.heading("phone", text="Phone")
        self.customers_tree.heading("cnic", text="CNIC")
        self.customers_tree.heading("address", text="Address")
        self.customers_tree.heading("bikes_purchased", text="Bikes Purchased", anchor='e')
        self.customers_tree.heading("total_amount", text="Total Amount", anchor='e')
        self.customers_tree.heading("actions", text="Actions")

        self.customers_tree.column("sr", width=40, anchor='e')
        self.customers_tree.column("name", width=120)
        self.customers_tree.column("phone", width=100)
        self.customers_tree.column("cnic", width=120)
        self.customers_tree.column("address", width=150)
        self.customers_tree.column("bikes_purchased", width=100, anchor='e')
        self.customers_tree.column("total_amount", width=100, anchor='e')
        self.customers_tree.column("actions", width=150, anchor='center')

        self.display_customers()
        self.current_edit_customer_id = None

    def open_add_customer_form(self):
        self.add_customer_form.pack(fill='x', pady=5)
        self.add_customer_form.config(text="+ Add New Customer")
        self.customer_name_entry.delete(0, tk.END)
        self.customer_phone_entry.delete(0, tk.END)
        self.customer_cnic_entry.delete(0, tk.END)
        self.customer_address_entry.delete(0, tk.END)
        self.current_edit_customer_id = None
        self.save_customer_button.config(text="💾 Save Customer")

    def hide_add_customer_form(self):
        self.add_customer_form.pack_forget()

    def display_customers(self):
        for i in self.customers_tree.get_children():
            self.customers_tree.delete(i)

        conn = db_connect()
        cursor = conn.cursor()

        search_f = self.cust_search_var.get()
        where_clauses = ["1=1"]
        params = []

        if search_f:
            where_clauses.append("(c.name LIKE ? OR c.phone LIKE ? OR c.cnic LIKE ?)")
            params.extend([f"%{search_f}%", f"%{search_f}%", f"%{search_f}%"])

        where_str = " AND ".join(where_clauses)

        cursor.execute(f"""
            SELECT c.*, COUNT(b.id) as bike_count, SUM(CASE WHEN b.status='sold' THEN b.selling_price ELSE 0 END) as total_purchases 
            FROM customers c LEFT JOIN bikes b ON c.id=b.customer_id 
            WHERE {where_str} GROUP BY c.id ORDER BY c.name
        """, tuple(params))
        
        customers_data = cursor.fetchall()
        
        for sr, customer in enumerate(customers_data):
            customer_id = customer[0]
            self.customers_tree.insert("", "end", iid=customer_id, values=(
                sr + 1, 
                sanitize(customer[1]), 
                sanitize(customer[2] or '-'), 
                sanitize(customer[3] or '-'), 
                sanitize(customer[4] or '-'), 
                customer[5] if len(customer) > 5 else 0, # bike_count
                fmt_money(customer[6]) if len(customer) > 6 else fmt_money(0), # total_purchases
                ""
            ))
            self.add_customer_action_buttons(customer_id, sr)
        conn.close()

    def add_customer_action_buttons(self, customer_id, row_idx):
        button_frame = ttk.Frame(self.customers_tree, style='TFrame')
        
        if has_permission('customer_ledger', 'view'):
            ttk.Button(button_frame, text="📒", command=lambda cid=customer_id: self.show_page('customer_ledger', cust_id=cid), style='TButton', width=3).pack(side='left', padx=1)
        if has_permission('customers', 'edit'):
            ttk.Button(button_frame, text="✏", command=lambda cid=customer_id: self.edit_customer(cid), style='TButton', width=3).pack(side='left', padx=1)
        if has_permission('customers', 'delete'):
            ttk.Button(button_frame, text="🗑", command=lambda cid=customer_id: self.delete_customer(cid), style='TButton', width=3).pack(side='left', padx=1)

        item_id = str(customer_id)
        self.customers_tree.set(item_id, "actions", button_frame)
        self.customers_tree.window_create(item_id, column="actions", anchor="center", window=button_frame)

    def save_customer(self):
        name = sanitize(self.customer_name_entry.get())
        phone = sanitize(self.customer_phone_entry.get())
        cnic = sanitize(self.customer_cnic_entry.get())
        address = sanitize(self.customer_address_entry.get())

        if not name:
            messagebox.showerror("Validation Error", "Customer name is required.")
            return

        conn = db_connect()
        cursor = conn.cursor()
        try:
            if self.current_edit_customer_id:
                if not require_permission('customers', 'edit'): return
                cursor.execute("""
                    UPDATE customers SET name=?, phone=?, cnic=?, address=?
                    WHERE id=?
                """, (name, phone, cnic, address, self.current_edit_customer_id))
            else:
                if not require_permission('customers', 'add'): return
                cursor.execute("""
                    INSERT INTO customers (name, phone, cnic, address)
                    VALUES (?, ?, ?, ?)
                """, (name, phone, cnic, address))
            conn.commit()
            messagebox.showinfo("Success", "Customer saved successfully.")
            self.hide_add_customer_form()
            self.display_customers()
        except sqlite3.Error as e:
            messagebox.showerror("Database Error", f"Failed to save customer: {e}")
        finally:
            conn.close()

    def edit_customer(self, customer_id):
        if not require_permission('customers', 'edit'): return

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM customers WHERE id = ?", (customer_id,))
        customer_data = cursor.fetchone()
        conn.close()

        if customer_data:
            self.open_add_customer_form()
            self.add_customer_form.config(text=f"✏ Edit Customer (ID: {customer_id})")
            self.customer_name_entry.delete(0, tk.END)
            self.customer_name_entry.insert(0, sanitize(customer_data[1]))
            self.customer_phone_entry.delete(0, tk.END)
            self.customer_phone_entry.insert(0, sanitize(customer_data[2]))
            self.customer_cnic_entry.delete(0, tk.END)
            self.customer_cnic_entry.insert(0, sanitize(customer_data[3]))
            self.customer_address_entry.delete(0, tk.END)
            self.customer_address_entry.insert(0, sanitize(customer_data[4]))
            self.current_edit_customer_id = customer_id
            self.save_customer_button.config(text="💾 Update Customer")

    def delete_customer(self, customer_id):
        if not require_permission('customers', 'delete'): return
        
        if messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this customer? This may affect linked sales records."):
            conn = db_connect()
            cursor = conn.cursor()
            try:
                cursor.execute("DELETE FROM customers WHERE id = ?", (customer_id,))
                conn.commit()
                messagebox.showinfo("Success", "Customer deleted successfully.")
                self.display_customers()
            except sqlite3.IntegrityError:
                messagebox.showerror("Error", "Cannot delete customer. There are sales records linked to this customer.")
                conn.rollback()
            except sqlite3.Error as e:
                messagebox.showerror("Database Error", f"Failed to delete customer: {e}")
            finally:
                conn.close()

    # --- Suppliers Page ---
    def create_suppliers_page(self):
        if not require_permission('suppliers', 'view'): return

        self.suppliers_form_frame = ttk.Frame(self.content_frame, style='TFrame', padding=10)
        self.suppliers_form_frame.pack(fill='x', pady=5)

        if has_permission('suppliers', 'add'):
            ttk.Button(self.suppliers_form_frame, text="+ Add Supplier", command=self.open_add_supplier_form, style='TButton').pack(anchor='w', pady=5)

        self.add_supplier_form = ttk.LabelFrame(self.suppliers_form_frame, text="+ Add New Supplier", style='TFrame', padding=10)
        # self.add_supplier_form.pack(fill='x', pady=5) # Initially hidden

        ttk.Label(self.add_supplier_form, text="Name:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.supplier_name_entry = ttk.Entry(self.add_supplier_form)
        self.supplier_name_entry.grid(row=0, column=1, sticky='ew', padx=5, pady=2)

        ttk.Label(self.add_supplier_form, text="Contact:").grid(row=0, column=2, sticky='w', padx=5, pady=2)
        self.supplier_contact_entry = ttk.Entry(self.add_supplier_form)
        self.supplier_contact_entry.grid(row=0, column=3, sticky='ew', padx=5, pady=2)

        ttk.Label(self.add_supplier_form, text="Address:").grid(row=1, column=0, sticky='w', padx=5, pady=2)
        self.supplier_address_entry = ttk.Entry(self.add_supplier_form)
        self.supplier_address_entry.grid(row=1, column=1, columnspan=3, sticky='ew', padx=5, pady=2)

        self.add_supplier_form.grid_columnconfigure(1, weight=1)
        self.add_supplier_form.grid_columnconfigure(3, weight=1)

        button_frame = ttk.Frame(self.add_supplier_form, style='TFrame')
        button_frame.grid(row=2, column=0, columnspan=4, pady=10)
        self.save_supplier_button = ttk.Button(button_frame, text="💾 Save Supplier", command=self.save_supplier, style='TButton')
        self.save_supplier_button.pack(side='left', padx=5)
        ttk.Button(button_frame, text="Cancel", command=self.hide_add_supplier_form, style='TButton').pack(side='left', padx=5)

        # Suppliers Table
        self.suppliers_tree_frame = ttk.Frame(self.content_frame, style='TFrame')
        self.suppliers_tree_frame.pack(fill='both', expand=True, padx=5, pady=5)

        columns = ("sr", "name", "contact", "address", "orders", "total_paid", "actions")
        self.suppliers_tree = ttk.Treeview(self.suppliers_tree_frame, columns=columns, show="headings", style='Treeview')
        self.suppliers_tree.pack(side='left', fill='both', expand=True)

        vsb = ttk.Scrollbar(self.suppliers_tree_frame, orient="vertical", command=self.suppliers_tree.yview)
        vsb.pack(side='right', fill='y')
        self.suppliers_tree.configure(yscrollcommand=vsb.set)

        self.suppliers_tree.heading("sr", text="Sr#")
        self.suppliers_tree.heading("name", text="Name")
        self.suppliers_tree.heading("contact", text="Contact")
        self.suppliers_tree.heading("address", text="Address")
        self.suppliers_tree.heading("orders", text="Orders", anchor='e')
        self.suppliers_tree.heading("total_paid", text="Total Paid", anchor='e')
        self.suppliers_tree.heading("actions", text="Actions")

        self.suppliers_tree.column("sr", width=40, anchor='e')
        self.suppliers_tree.column("name", width=120)
        self.suppliers_tree.column("contact", width=100)
        self.suppliers_tree.column("address", width=150)
        self.suppliers_tree.column("orders", width=80, anchor='e')
        self.suppliers_tree.column("total_paid", width=100, anchor='e')
        self.suppliers_tree.column("actions", width=150, anchor='center')

        self.display_suppliers()
        self.current_edit_supplier_id = None

    def open_add_supplier_form(self):
        self.add_supplier_form.pack(fill='x', pady=5)
        self.add_supplier_form.config(text="+ Add New Supplier")
        self.supplier_name_entry.delete(0, tk.END)
        self.supplier_contact_entry.delete(0, tk.END)
        self.supplier_address_entry.delete(0, tk.END)
        self.current_edit_supplier_id = None
        self.save_supplier_button.config(text="💾 Save Supplier")

    def hide_add_supplier_form(self):
        self.add_supplier_form.pack_forget()

    def display_suppliers(self):
        for i in self.suppliers_tree.get_children():
            self.suppliers_tree.delete(i)

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("""
            SELECT s.*, COUNT(po.id) as order_count, SUM(po.cheque_amount) as total_paid 
            FROM suppliers s LEFT JOIN purchase_orders po ON s.id=po.supplier_id 
            GROUP BY s.id ORDER BY s.name
        """)
        
        suppliers_data = cursor.fetchall()
        
        for sr, supplier in enumerate(suppliers_data):
            supplier_id = supplier[0]
            self.suppliers_tree.insert("", "end", iid=supplier_id, values=(
                sr + 1, 
                sanitize(supplier[1]), 
                sanitize(supplier[2] or '-'), 
                sanitize(supplier[3] or '-'), 
                supplier[4] if len(supplier) > 4 else 0, # order_count
                fmt_money(supplier[5]) if len(supplier) > 5 else fmt_money(0), # total_paid
                ""
            ))
            self.add_supplier_action_buttons(supplier_id, sr)
        conn.close()

    def add_supplier_action_buttons(self, supplier_id, row_idx):
        button_frame = ttk.Frame(self.suppliers_tree, style='TFrame')
        
        if has_permission('supplier_ledger', 'view'):
            ttk.Button(button_frame, text="📒", command=lambda sid=supplier_id: self.show_page('supplier_ledger', sup_id=sid), style='TButton', width=3).pack(side='left', padx=1)
        if has_permission('suppliers', 'edit'):
            ttk.Button(button_frame, text="✏", command=lambda sid=supplier_id: self.edit_supplier(sid), style='TButton', width=3).pack(side='left', padx=1)
        if has_permission('suppliers', 'delete'):
            ttk.Button(button_frame, text="🗑", command=lambda sid=supplier_id: self.delete_supplier(sid), style='TButton', width=3).pack(side='left', padx=1)

        item_id = str(supplier_id)
        self.suppliers_tree.set(item_id, "actions", button_frame)
        self.suppliers_tree.window_create(item_id, column="actions", anchor="center", window=button_frame)

    def save_supplier(self):
        name = sanitize(self.supplier_name_entry.get())
        contact = sanitize(self.supplier_contact_entry.get())
        address = sanitize(self.supplier_address_entry.get())

        if not name:
            messagebox.showerror("Validation Error", "Supplier name is required.")
            return

        conn = db_connect()
        cursor = conn.cursor()
        try:
            if self.current_edit_supplier_id:
                if not require_permission('suppliers', 'edit'): return
                cursor.execute("""
                    UPDATE suppliers SET name=?, contact=?, address=?
                    WHERE id=?
                """, (name, contact, address, self.current_edit_supplier_id))
            else:
                if not require_permission('suppliers', 'add'): return
                cursor.execute("""
                    INSERT INTO suppliers (name, contact, address)
                    VALUES (?, ?, ?)
                """, (name, contact, address))
            conn.commit()
            messagebox.showinfo("Success", "Supplier saved successfully.")
            self.hide_add_supplier_form()
            self.display_suppliers()
        except sqlite3.Error as e:
            messagebox.showerror("Database Error", f"Failed to save supplier: {e}")
        finally:
            conn.close()

    def edit_supplier(self, supplier_id):
        if not require_permission('suppliers', 'edit'): return

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("SELECT * FROM suppliers WHERE id = ?", (supplier_id,))
        supplier_data = cursor.fetchone()
        conn.close()

        if supplier_data:
            self.open_add_supplier_form()
            self.add_supplier_form.config(text=f"✏ Edit Supplier (ID: {supplier_id})")
            self.supplier_name_entry.delete(0, tk.END)
            self.supplier_name_entry.insert(0, sanitize(supplier_data[1]))
            self.supplier_contact_entry.delete(0, tk.END)
            self.supplier_contact_entry.insert(0, sanitize(supplier_data[2]))
            self.supplier_address_entry.delete(0, tk.END)
            self.supplier_address_entry.insert(0, sanitize(supplier_data[3]))
            self.current_edit_supplier_id = supplier_id
            self.save_supplier_button.config(text="💾 Update Supplier")

    def delete_supplier(self, supplier_id):
        if not require_permission('suppliers', 'delete'): return
        
        if messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this supplier? This may affect linked purchase orders."):
            conn = db_connect()
            cursor = conn.cursor()
            try:
                cursor.execute("DELETE FROM suppliers WHERE id = ?", (supplier_id,))
                conn.commit()
                messagebox.showinfo("Success", "Supplier deleted successfully.")
                self.display_suppliers()
            except sqlite3.IntegrityError:
                messagebox.showerror("Error", "Cannot delete supplier. There are purchase orders linked to this supplier.")
                conn.rollback()
            except sqlite3.Error as e:
                messagebox.showerror("Database Error", f"Failed to delete supplier: {e}")
            finally:
                conn.close()

    # --- Users Page ---
    def create_users_page(self):
        if not require_permission('users', 'view'): return

        self.users_form_frame = ttk.Frame(self.content_frame, style='TFrame', padding=10)
        self.users_form_frame.pack(fill='x', pady=5)

        if has_permission('users', 'add'):
            ttk.Button(self.users_form_frame, text="+ Add User", command=self.open_add_user_form, style='TButton').pack(anchor='w', pady=5)

        self.add_user_form = ttk.LabelFrame(self.users_form_frame, text="+ Add New User", style='TFrame', padding=10)
        # self.add_user_form.pack(fill='x', pady=5) # Initially hidden

        ttk.Label(self.add_user_form, text="Username:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.user_username_entry = ttk.Entry(self.add_user_form)
        self.user_username_entry.grid(row=0, column=1, sticky='ew', padx=5, pady=2)

        ttk.Label(self.add_user_form, text="Full Name:").grid(row=0, column=2, sticky='w', padx=5, pady=2)
        self.user_full_name_entry = ttk.Entry(self.add_user_form)
        self.user_full_name_entry.grid(row=0, column=3, sticky='ew', padx=5, pady=2)

        ttk.Label(self.add_user_form, text="Role:").grid(row=1, column=0, sticky='w', padx=5, pady=2)
        self.user_role_var = tk.StringVar()
        self.user_role_combo = ttk.Combobox(self.add_user_form, textvariable=self.user_role_var, state='readonly')
        self.user_role_combo.grid(row=1, column=1, sticky='ew', padx=5, pady=2)
        self.load_roles_into_combo(self.user_role_combo)

        ttk.Label(self.add_user_form, text="Password:").grid(row=1, column=2, sticky='w', padx=5, pady=2)
        self.user_password_entry = ttk.Entry(self.add_user_form, show="*")
        self.user_password_entry.grid(row=1, column=3, sticky='ew', padx=5, pady=2)

        self.user_is_active_var = tk.BooleanVar(value=True)
        ttk.Checkbutton(self.add_user_form, text="Active", variable=self.user_is_active_var).grid(row=2, column=0, columnspan=2, sticky='w', padx=5, pady=2)
        
        ttk.Label(self.add_user_form, text="Min 8 chars, 1 special char req.", font=('Segoe UI', 8), foreground='gray').grid(row=2, column=2, columnspan=2, sticky='w', padx=5, pady=2)

        self.add_user_form.grid_columnconfigure(1, weight=1)
        self.add_user_form.grid_columnconfigure(3, weight=1)

        button_frame = ttk.Frame(self.add_user_form, style='TFrame')
        button_frame.grid(row=3, column=0, columnspan=4, pady=10)
        self.save_user_button = ttk.Button(button_frame, text="💾 Save User", command=self.save_user, style='TButton')
        self.save_user_button.pack(side='left', padx=5)
        ttk.Button(button_frame, text="Cancel", command=self.hide_add_user_form, style='TButton').pack(side='left', padx=5)

        # Users Table
        self.users_tree_frame = ttk.Frame(self.content_frame, style='TFrame')
        self.users_tree_frame.pack(fill='both', expand=True, padx=5, pady=5)

        columns = ("sr", "username", "full_name", "role", "status", "created", "actions")
        self.users_tree = ttk.Treeview(self.users_tree_frame, columns=columns, show="headings", style='Treeview')
        self.users_tree.pack(side='left', fill='both', expand=True)

        vsb = ttk.Scrollbar(self.users_tree_frame, orient="vertical", command=self.users_tree.yview)
        vsb.pack(side='right', fill='y')
        self.users_tree.configure(yscrollcommand=vsb.set)

        self.users_tree.heading("sr", text="Sr#")
        self.users_tree.heading("username", text="Username")
        self.users_tree.heading("full_name", text="Full Name")
        self.users_tree.heading("role", text="Role")
        self.users_tree.heading("status", text="Status")
        self.users_tree.heading("created", text="Created")
        self.users_tree.heading("actions", text="Actions")

        self.users_tree.column("sr", width=40, anchor='e')
        self.users_tree.column("username", width=120)
        self.users_tree.column("full_name", width=150)
        self.users_tree.column("role", width=100)
        self.users_tree.column("status", width=80)
        self.users_tree.column("created", width=90)
        self.users_tree.column("actions", width=100, anchor='center')

        self.display_users()
        self.current_edit_user_id = None

    def load_roles_into_combo(self, combo_widget):
        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("SELECT id, name FROM roles ORDER BY name")
        roles = cursor.fetchall()
        conn.close()
        combo_widget['values'] = [f"{r[0]} - {r[1]}" for r in roles]
        combo_widget.set("") # Clear selection

    def open_add_user_form(self):
        self.add_user_form.pack(fill='x', pady=5)
        self.add_user_form.config(text="+ Add New User")
        self.user_username_entry.delete(0, tk.END)
        self.user_full_name_entry.delete(0, tk.END)
        self.user_role_var.set("")
        self.user_password_entry.delete(0, tk.END)
        self.user_password_entry.config(state='normal')
        self.user_is_active_var.set(True)
        self.current_edit_user_id = None
        self.save_user_button.config(text="💾 Save User")
        self.user_username_entry.config(state='normal') # Can change username for new user

    def hide_add_user_form(self):
        self.add_user_form.pack_forget()

    def display_users(self):
        for i in self.users_tree.get_children():
            self.users_tree.delete(i)

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("""
            SELECT u.id, u.username, u.full_name, r.name as role_name, u.is_active, u.created_at
            FROM users u LEFT JOIN roles r ON u.role_id=r.id ORDER BY u.id
        """)
        
        users_data = cursor.fetchall()
        
        for sr, user in enumerate(users_data):
            user_id = user[0]
            status_text = "Active" if user[4] else "Disabled"
            self.users_tree.insert("", "end", iid=user_id, values=(
                sr + 1, 
                sanitize(user[1]), 
                sanitize(user[2] or '-'), 
                sanitize(user[3] or '-'), 
                status_text, 
                fmt_date(user[5]),
                ""
            ))
            self.add_user_action_buttons(user_id, sr)
        conn.close()

    def add_user_action_buttons(self, user_id, row_idx):
        button_frame = ttk.Frame(self.users_tree, style='TFrame')
        
        if has_permission('users', 'edit'):
            ttk.Button(button_frame, text="✏", command=lambda uid=user_id: self.edit_user(uid), style='TButton', width=3).pack(side='left', padx=1)
        
        # Admin cannot be deleted. Current user cannot delete themselves.
        if has_permission('users', 'delete') and user_id != 1 and user_id != CURRENT_USER_ID:
            ttk.Button(button_frame, text="🗑", command=lambda uid=user_id: self.delete_user(uid), style='TButton', width=3).pack(side='left', padx=1)

        item_id = str(user_id)
        self.users_tree.set(item_id, "actions", button_frame)
        self.users_tree.window_create(item_id, column="actions", anchor="center", window=button_frame)

    def save_user(self):
        username = sanitize(self.user_username_entry.get())
        full_name = sanitize(self.user_full_name_entry.get())
        role_id_str = self.user_role_var.get().split(' - ')[0]
        role_id = int(role_id_str) if role_id_str.isdigit() else 2 # Default to Standard User
        password = self.user_password_entry.get()
        is_active = 1 if self.user_is_active_var.get() else 0

        if not username:
            messagebox.showerror("Validation Error", "Username is required.")
            return
        if self.current_edit_user_id is None and not password:
            messagebox.showerror("Validation Error", "Password is required for new users.")
            return
        if password:
            is_valid, msg = is_valid_password(password)
            if not is_valid:
                messagebox.showerror("Validation Error", msg)
                return

        conn = db_connect()
        cursor = conn.cursor()
        try:
            if self.current_edit_user_id:
                if not require_permission('users', 'edit'): return
                # Username can't be changed for existing users (for simplicity and uniqueness)
                if password:
                    password_hash = hash_password(password)
                    cursor.execute("""
                        UPDATE users SET full_name=?, role_id=?, is_active=?, password_hash=?
                        WHERE id=?
                    """, (full_name, role_id, is_active, password_hash, self.current_edit_user_id))
                else:
                    cursor.execute("""
                        UPDATE users SET full_name=?, role_id=?, is_active=?
                        WHERE id=?
                    """, (full_name, role_id, is_active, self.current_edit_user_id))
            else:
                if not require_permission('users', 'add'): return
                password_hash = hash_password(password)
                cursor.execute("""
                    INSERT INTO users (username, password_hash, full_name, role_id, is_active)
                    VALUES (?, ?, ?, ?, ?)
                """, (username, password_hash, full_name, role_id, is_active))
            conn.commit()
            messagebox.showinfo("Success", "User saved successfully.")
            self.hide_add_user_form()
            self.display_users()
        except sqlite3.IntegrityError:
            messagebox.showerror("Error", "Username already exists. Please choose a different username.")
            conn.rollback()
        except sqlite3.Error as e:
            messagebox.showerror("Database Error", f"Failed to save user: {e}")
        finally:
            conn.close()

    def edit_user(self, user_id):
        if not require_permission('users', 'edit'): return

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("SELECT id, username, full_name, role_id, is_active FROM users WHERE id = ?", (user_id,))
        user_data = cursor.fetchone()
        conn.close()

        if user_data:
            self.open_add_user_form()
            self.add_user_form.config(text=f"✏ Edit User (ID: {user_id})")
            self.user_username_entry.delete(0, tk.END)
            self.user_username_entry.insert(0, sanitize(user_data[1]))
            self.user_username_entry.config(state='readonly') # Username cannot be changed for existing users
            self.user_full_name_entry.delete(0, tk.END)
            self.user_full_name_entry.insert(0, sanitize(user_data[2]))
            
            conn_roles = db_connect()
            cursor_roles = conn_roles.cursor()
            cursor_roles.execute("SELECT name FROM roles WHERE id = ?", (user_data[3],))
            role_name = cursor_roles.fetchone()
            conn_roles.close()
            
            if role_name:
                self.user_role_var.set(f"{user_data[3]} - {role_name[0]}")
            else:
                self.user_role_var.set("") # Fallback
            
            self.user_password_entry.delete(0, tk.END)
            self.user_password_entry.config(state='normal', placeholder="Leave blank to keep current") # Add placeholder
            self.user_is_active_var.set(True if user_data[4] else False)
            self.current_edit_user_id = user_id
            self.save_user_button.config(text="💾 Update User")

    def delete_user(self, user_id):
        if not require_permission('users', 'delete'): return
        
        if user_id == 1: # Admin user
            messagebox.showerror("Error", "The main 'admin' user cannot be deleted.")
            return
        if user_id == CURRENT_USER_ID:
            messagebox.showerror("Error", "You cannot delete your own user account.")
            return
            
        if messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this user? This cannot be undone."):
            conn = db_connect()
            cursor = conn.cursor()
            try:
                cursor.execute("DELETE FROM users WHERE id = ?", (user_id,))
                conn.commit()
                messagebox.showinfo("Success", "User deleted successfully.")
                self.display_users()
            except sqlite3.Error as e:
                messagebox.showerror("Database Error", f"Failed to delete user: {e}")
            finally:
                conn.close()

    # --- Roles & Permissions Page ---
    def create_roles_page(self):
        if not require_permission('roles', 'view'): return

        self.roles_form_frame = ttk.Frame(self.content_frame, style='TFrame', padding=10)
        self.roles_form_frame.pack(fill='x', pady=5)

        if has_permission('roles', 'add'):
            ttk.Button(self.roles_form_frame, text="+ Add Role", command=self.open_add_role_form, style='TButton').pack(anchor='w', pady=5)

        self.add_role_form = ttk.LabelFrame(self.roles_form_frame, text="+ Add New Role", style='TFrame', padding=10)
        # self.add_role_form.pack(fill='x', pady=5) # Initially hidden

        ttk.Label(self.add_role_form, text="Role Name:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.role_name_entry = ttk.Entry(self.add_role_form)
        self.role_name_entry.grid(row=0, column=1, sticky='ew', padx=5, pady=2)

        ttk.Label(self.add_role_form, text="Description:").grid(row=0, column=2, sticky='w', padx=5, pady=2)
        self.role_description_entry = ttk.Entry(self.add_role_form)
        self.role_description_entry.grid(row=0, column=3, sticky='ew', padx=5, pady=2)

        self.add_role_form.grid_columnconfigure(1, weight=1)
        self.add_role_form.grid_columnconfigure(3, weight=1)

        ttk.Label(self.add_role_form, text="Permissions (check to allow):", font=('Segoe UI', 10, 'bold')).grid(row=1, column=0, columnspan=4, sticky='w', pady=(10, 5))

        self.permissions_frame = ttk.Frame(self.add_role_form, style='TFrame', relief='solid', borderwidth=1)
        self.permissions_frame.grid(row=2, column=0, columnspan=4, sticky='nsew', padx=5, pady=5)
        
        # Permissions Table (Treeview for better display)
        self.perm_tree = ttk.Treeview(self.permissions_frame, columns=("page", "view", "add", "edit", "delete"), show="headings", style='Treeview', height=10)
        self.perm_tree.pack(fill='both', expand=True)

        self.perm_tree.heading("page", text="Page")
        self.perm_tree.heading("view", text="View", anchor='center')
        self.perm_tree.heading("add", text="Add", anchor='center')
        self.perm_tree.heading("edit", text="Edit", anchor='center')
        self.perm_tree.heading("delete", text="Delete", anchor='center')

        self.perm_tree.column("page", width=150)
        self.perm_tree.column("view", width=50, anchor='center')
        self.perm_tree.column("add", width=50, anchor='center')
        self.perm_tree.column("edit", width=50, anchor='center')
        self.perm_tree.column("delete", width=60, anchor='center')

        self.permission_vars = {} # {page_name: {action: tk.BooleanVar}}
        self.load_permissions_grid() # Populate with all pages and empty checkboxes

        button_frame = ttk.Frame(self.add_role_form, style='TFrame')
        button_frame.grid(row=3, column=0, columnspan=4, pady=10)
        self.save_role_button = ttk.Button(button_frame, text="💾 Save Role", command=self.save_role, style='TButton')
        self.save_role_button.pack(side='left', padx=5)
        ttk.Button(button_frame, text="Cancel", command=self.hide_add_role_form, style='TButton').pack(side='left', padx=5)

        # Roles Table
        self.roles_tree_frame = ttk.Frame(self.content_frame, style='TFrame')
        self.roles_tree_frame.pack(fill='both', expand=True, padx=5, pady=5)

        columns = ("sr", "name", "description", "users", "actions")
        self.roles_tree = ttk.Treeview(self.roles_tree_frame, columns=columns, show="headings", style='Treeview')
        self.roles_tree.pack(side='left', fill='both', expand=True)

        vsb = ttk.Scrollbar(self.roles_tree_frame, orient="vertical", command=self.roles_tree.yview)
        vsb.pack(side='right', fill='y')
        self.roles_tree.configure(yscrollcommand=vsb.set)

        self.roles_tree.heading("sr", text="Sr#")
        self.roles_tree.heading("name", text="Role")
        self.roles_tree.heading("description", text="Description")
        self.roles_tree.heading("users", text="Users", anchor='e')
        self.roles_tree.heading("actions", text="Actions")

        self.roles_tree.column("sr", width=40, anchor='e')
        self.roles_tree.column("name", width=120)
        self.roles_tree.column("description", width=200)
        self.roles_tree.column("users", width=80, anchor='e')
        self.roles_tree.column("actions", width=100, anchor='center')

        self.display_roles()
        self.current_edit_role_id = None

    def load_permissions_grid(self, current_perms=None):
        for i in self.perm_tree.get_children():
            self.perm_tree.delete(i)
        
        all_pages = {
            'dashboard': 'Dashboard', 'inventory': 'Inventory / Stock', 'purchase': 'Purchase Orders', 
            'sale': 'Sales', 'returns': 'Returns', 'cheques': 'Cheque Register', 
            'income_expense': 'Income/Expense', 'customer_ledger': 'Customer Ledger', 
            'supplier_ledger': 'Supplier Ledger', 'reports': 'Reports', 'models': 'Models', 
            'customers': 'Customers', 'suppliers': 'Suppliers', 'users': 'Users', 
            'roles': 'Roles & Permissions', 'settings': 'Settings'
        }
        actions = ['view', 'add', 'edit', 'delete']

        for page_name, page_label in all_pages.items():
            self.permission_vars[page_name] = {}
            row_values = [page_label]
            for action in actions:
                var = tk.BooleanVar()
                if current_perms and page_name in current_perms and current_perms[page_name][f'can_{action}']:
                    var.set(True)
                self.permission_vars[page_name][action] = var
                # Placeholder for checkbox, actual widget will be created in the treeview
                row_values.append(var) 
            
            # Insert into treeview with a unique ID for each page
            self.perm_tree.insert("", "end", iid=page_name, values=row_values)
            
            # Create and place actual checkbuttons in the cells
            for col_idx, action in enumerate(actions):
                chk = ttk.Checkbutton(self.perm_tree, variable=self.permission_vars[page_name][action])
                self.perm_tree.window_create(page_name, column=col_idx + 1, window=chk)

    def open_add_role_form(self):
        self.add_role_form.pack(fill='x', pady=5)
        self.add_role_form.config(text="+ Add New Role")
        self.role_name_entry.delete(0, tk.END)
        self.role_description_entry.delete(0, tk.END)
        self.load_permissions_grid(None) # Clear permissions
        self.current_edit_role_id = None
        self.save_role_button.config(text="💾 Save Role")
        self.role_name_entry.config(state='normal') # Allow editing name for new role

    def hide_add_role_form(self):
        self.add_role_form.pack_forget()

    def display_roles(self):
        for i in self.roles_tree.get_children():
            self.roles_tree.delete(i)

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("SELECT r.id, r.name, r.description, COUNT(u.id) FROM roles r LEFT JOIN users u ON r.id=u.role_id GROUP BY r.id ORDER BY r.id")
        
        roles_data = cursor.fetchall()
        
        for sr, role in enumerate(roles_data):
            role_id = role[0]
            self.roles_tree.insert("", "end", iid=role_id, values=(
                sr + 1, 
                sanitize(role[1]), 
                sanitize(role[2] or '-'), 
                role[3], # user count
                ""
            ))
            self.add_role_action_buttons(role_id, sr)
        conn.close()

    def add_role_action_buttons(self, role_id, row_idx):
        button_frame = ttk.Frame(self.roles_tree, style='TFrame')
        
        if has_permission('roles', 'edit'):
            ttk.Button(button_frame, text="✏", command=lambda rid=role_id: self.edit_role(rid), style='TButton', width=3).pack(side='left', padx=1)
        
        # Admin role (ID 1) cannot be deleted
        if has_permission('roles', 'delete') and role_id != 1:
            ttk.Button(button_frame, text="🗑", command=lambda rid=role_id: self.delete_role(rid), style='TButton', width=3).pack(side='left', padx=1)

        item_id = str(role_id)
        self.roles_tree.set(item_id, "actions", button_frame)
        self.roles_tree.window_create(item_id, column="actions", anchor="center", window=button_frame)

    def save_role(self):
        name = sanitize(self.role_name_entry.get())
        description = sanitize(self.role_description_entry.get())

        if not name:
            messagebox.showerror("Validation Error", "Role name is required.")
            return

        conn = db_connect()
        cursor = conn.cursor()
        try:
            if self.current_edit_role_id:
                if not require_permission('roles', 'edit'): return
                cursor.execute("""
                    UPDATE roles SET name=?, description=?
                    WHERE id=?
                """, (name, description, self.current_edit_role_id))
                role_id = self.current_edit_role_id
            else:
                if not require_permission('roles', 'add'): return
                cursor.execute("""
                    INSERT INTO roles (name, description)
                    VALUES (?, ?)
                """, (name, description))
                role_id = cursor.lastrowid
            
            # Update permissions
            cursor.execute("DELETE FROM role_permissions WHERE role_id=?", (role_id,))
            for page_name, actions_vars in self.permission_vars.items():
                can_view = 1 if actions_vars['view'].get() else 0
                can_add = 1 if actions_vars['add'].get() else 0
                can_edit = 1 if actions_vars['edit'].get() else 0
                can_delete = 1 if actions_vars['delete'].get() else 0
                cursor.execute("""
                    INSERT INTO role_permissions (role_id, page, can_view, can_add, can_edit, can_delete)
                    VALUES (?, ?, ?, ?, ?, ?)
                """, (role_id, page_name, can_view, can_add, can_edit, can_delete))

            conn.commit()
            messagebox.showinfo("Success", "Role and permissions saved successfully.")
            self.hide_add_role_form()
            self.display_roles()
        except sqlite3.IntegrityError:
            messagebox.showerror("Error", "Role name already exists. Please choose a different name.")
            conn.rollback()
        except sqlite3.Error as e:
            messagebox.showerror("Database Error", f"Failed to save role: {e}")
        finally:
            conn.close()

    def edit_role(self, role_id):
        if not require_permission('roles', 'edit'): return

        conn = db_connect()
        cursor = conn.cursor()
        cursor.execute("SELECT id, name, description FROM roles WHERE id = ?", (role_id,))
        role_data = cursor.fetchone()
        
        current_perms = {}
        cursor.execute("SELECT page, can_view, can_add, can_edit, can_delete FROM role_permissions WHERE role_id = ?", (role_id,))
        for p_name, v, a, e, d in cursor.fetchall():
            current_perms[p_name] = {'can_view':v, 'can_add':a, 'can_edit':e, 'can_delete':d}
        conn.close()

        if role_data:
            self.open_add_role_form()
            self.add_role_form.config(text=f"✏ Edit Role (ID: {role_id})")
            self.role_name_entry.delete(0, tk.END)
            self.role_name_entry.insert(0, sanitize(role_data[1]))
            # Admin role name cannot be edited
            if role_id == 1:
                self.role_name_entry.config(state='readonly')
            else:
                self.role_name_entry.config(state='normal')

            self.role_description_entry.delete(0, tk.END)
            self.role_description_entry.insert(0, sanitize(role_data[2]))
            self.load_permissions_grid(current_perms) # Load existing permissions
            self.current_edit_role_id = role_id
            self.save_role_button.config(text="💾 Update Role")

    def delete_role(self, role_id):
        if not require_permission('roles', 'delete'): return
        
        if role_id == 1: # Admin role
            messagebox.showerror("Error", "The 'Administrator' role cannot be deleted.")
            return

        if messagebox.askyesno("Confirm Delete", "Are you sure you want to delete this role? All users assigned to this role will have their role unset (or set to default). This cannot be undone."):
            conn = db_connect()
            cursor = conn.cursor()
            try:
                # Optionally reassign users of this role to a default role or null
                cursor.execute("UPDATE users SET role_id = NULL WHERE role_id = ?", (role_id,))
                cursor.execute("DELETE FROM roles WHERE id = ?", (role_id,))
                conn.commit()
                messagebox.showinfo("Success", "Role deleted successfully. Users previously assigned to this role have been updated.")
                self.display_roles()
            except sqlite3.Error as e:
                messagebox.showerror("Database Error", f"Failed to delete role: {e}")
            finally:
                conn.close()

    # --- Settings Page ---
    def create_settings_page(self):
        if not require_permission('settings', 'view'): return

        self.settings_form_frame = ttk.Frame(self.content_frame, style='TFrame', padding=10)
        self.settings_form_frame.pack(fill='x', pady=5)

        # Company Settings
        company_settings_frame = ttk.LabelFrame(self.settings_form_frame, text="⚙ Company Settings", style='TFrame', padding=10)
        company_settings_frame.pack(fill='x', pady=10)

        ttk.Label(company_settings_frame, text="Company Name:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
        self.setting_company_name_entry = ttk.Entry(company_settings_frame)
        self.setting_company_name_entry.grid(row=0, column=1, sticky='ew', padx=5, pady=2)
        self.setting_company_name_entry.insert(0, get_setting('company_name', 'BNI Enterprises'))

        ttk.Label(company_settings_frame, text="Branch Name:").grid(row=0, column=2, sticky='w', padx=5, pady=2)
        self.setting_branch_name_entry = ttk.Entry(company_settings_frame)
        self.setting_branch_name_entry.grid(row=0, column=3, sticky='ew', padx=5, pady=2)
        self.setting_branch_name_entry.insert(0, get_setting('branch_name', 'Dera (Ahmed Metro)'))

        ttk.Label(company_settings_frame, text="Currency Symbol:").grid(row=1, column=0, sticky='w', padx=5, pady=2)
        self.setting_currency_entry = ttk.Entry(company_settings_frame, width=10)
        self.setting_currency_entry.grid(row=1, column=1, sticky='w', padx=5, pady=2)
        self.setting_currency_entry.insert(0, get_setting('currency', 'Rs.'))

        ttk.Label(company_settings_frame, text="Tax Rate (%):").grid(row=1, column=2, sticky='w', padx=5, pady=2)
        self.setting_tax_rate_entry = ttk.Entry(company_settings_frame, width=10)
        self.setting_tax_rate_entry.grid(row=1, column=3, sticky='w', padx=5, pady=2)
        self.setting_tax_rate_entry.insert(0, str(float(get_setting('tax_rate', '0.1')) * 100)) # Display as percentage

        ttk.Label(company_settings_frame, text="Tax Calculated On:").grid(row=2, column=0, sticky='w', padx=5, pady=2)
        self.setting_tax_on_var = tk.StringVar(value=get_setting('tax_on', 'purchase_price'))
        ttk.Combobox(company_settings_frame, textvariable=self.setting_tax_on_var, values=["purchase_price", "selling_price"], state='readonly', width=15).grid(row=2, column=1, sticky='w', padx=5, pady=2)

        ttk.Label(company_settings_frame, text="Show Purchase Price on Invoice:").grid(row=2, column=2, sticky='w', padx=5, pady=2)
        self.setting_show_pp_var = tk.StringVar(value=get_setting('show_purchase_on_invoice', '0'))
        ttk.Combobox(company_settings_frame, textvariable=self.setting_show_pp_var, values=["0", "1"], state='readonly', width=15).grid(row=2, column=3, sticky='w', padx=5, pady=2)
        self.setting_show_pp_var.trace_add("write", lambda *args: self.update_show_pp_text())
        self.show_pp_label = ttk.Label(company_settings_frame, text="", style='TLabel')
        self.show_pp_label.grid(row=3, column=3, sticky='w', padx=5, pady=2)
        self.update_show_pp_text()


        company_settings_frame.grid_columnconfigure(1, weight=1)
        company_settings_frame.grid_columnconfigure(3, weight=1)

        # Change Admin Password (only visible/editable by Admin)
        if CURRENT_USER_ROLE == 'Administrator':
            password_settings_frame = ttk.LabelFrame(self.settings_form_frame, text="🔐 Change Admin Password", style='TFrame', padding=10)
            password_settings_frame.pack(fill='x', pady=10)

            ttk.Label(password_settings_frame, text="New Password:").grid(row=0, column=0, sticky='w', padx=5, pady=2)
            self.setting_new_password_entry = ttk.Entry(password_settings_frame, show="*")
            self.setting_new_password_entry.grid(row=0, column=1, sticky='ew', padx=5, pady=2)
            ttk.Label(password_settings_frame, text="Min 8 chars, 1 special char. Leave blank to keep current.", font=('Segoe UI', 8), foreground='gray').grid(row=1, column=0, columnspan=2, sticky='w', padx=5, pady=2)
            
            password_settings_frame.grid_columnconfigure(1, weight=1)

        # Database Backup & Restore
        db_settings_frame = ttk.LabelFrame(self.settings_form_frame, text="💾 Database Backup & Restore", style='TFrame', padding=10)
        db_settings_frame.pack(fill='x', pady=10)

        ttk.Button(db_settings_frame, text="⬇ Download SQL Backup", command=self.download_sql_backup, style='TButton').grid(row=0, column=0, padx=5, pady=5)
        ttk.Label(db_settings_frame, text="Downloads a full SQL dump of the database.", style='TLabel').grid(row=0, column=1, sticky='w', padx=5, pady=5)

        self.restore_file_path = tk.StringVar()
        ttk.Button(db_settings_frame, text="Select SQL File...", command=self.select_backup_file, style='TButton').grid(row=1, column=0, padx=5, pady=5)
        ttk.Entry(db_settings_frame, textvariable=self.restore_file_path, state='readonly').grid(row=1, column=1, sticky='ew', padx=5, pady=5)
        ttk.Button(db_settings_frame, text="⬆ Restore Database", command=self.restore_sql_backup, style='TButton', state='disabled').grid(row=2, column=0, padx=5, pady=5) # Initially disabled
        self.restore_file_path.trace_add("write", lambda *args: self.toggle_restore_button())
        ttk.Label(db_settings_frame, text="WARNING: Restoring will overwrite all current data!").grid(row=2, column=1, sticky='w', padx=5, pady=5)

        db_settings_frame.grid_columnconfigure(1, weight=1)

        # System Info
        system_info_frame = ttk.LabelFrame(self.settings_form_frame, text="ℹ System Info", style='TFrame', padding=10)
        system_info_frame.pack(fill='x', pady=10)

        info_rows = [
            ("App Version:", APP_VERSION),
            ("Author:", AUTHOR),
            ("Python Version:", f"{tk.TkVersion}"),
            ("SQLite Version:", sqlite3.sqlite_version),
            ("Database File:", DB_NAME),
            ("Server Time:", datetime.now().strftime('%d/%m/%Y %H:%M:%S'))
        ]
        for i, (label, value) in enumerate(info_rows):
            ttk.Label(system_info_frame, text=label, font=('Segoe UI', 9, 'bold'), style='TLabel').grid(row=i, column=0, sticky='w', padx=5, pady=2)
            ttk.Label(system_info_frame, text=value, style='TLabel').grid(row=i, column=1, sticky='w', padx=5, pady=2)
        system_info_frame.grid_columnconfigure(1, weight=1)

        # Save Settings Button
        if has_permission('settings', 'edit'):
            ttk.Button(self.settings_form_frame, text="💾 Save Settings", command=self.save_settings, style='TButton').pack(pady=10)

    def update_show_pp_text(self):
        val = self.setting_show_pp_var.get()
        if val == '1':
            self.show_pp_label.config(text="Yes (Visible)")
        else:
            self.show_pp_label.config(text="No (Hidden)")

    def save_settings(self):
        if not require_permission('settings', 'edit'): return
        
        try:
            company_name = sanitize(self.setting_company_name_entry.get())
            branch_name = sanitize(self.setting_branch_name_entry.get())
            currency = sanitize(self.setting_currency_entry.get())
            tax_rate = float(self.setting_tax_rate_entry.get()) / 100 # Convert percentage back to decimal
            tax_on = self.setting_tax_on_var.get()
            show_pp_on_invoice = self.setting_show_pp_var.get()

            if not company_name: messagebox.showerror("Validation", "Company Name is required."); return
            if tax_rate < 0 or tax_rate > 100: messagebox.showerror("Validation", "Tax Rate must be between 0 and 100."); return

            update_setting('company_name', company_name)
            update_setting('branch_name', branch_name)
            update_setting('currency', currency)
            update_setting('tax_rate', str(tax_rate))
            update_setting('tax_on', tax_on)
            update_setting('show_purchase_on_invoice', show_pp_on_invoice)

            # Update global variables for immediate effect
            global CURRENCY_SYMBOL, TAX_RATE, TAX_ON_PRICE_TYPE, SHOW_PURCHASE_ON_INVOICE
            CURRENCY_SYMBOL = currency
            TAX_RATE = tax_rate
            TAX_ON_PRICE_TYPE = tax_on
            SHOW_PURCHASE_ON_INVOICE = show_pp_on_invoice == '1'

            # Password change (only if current user is admin)
            if CURRENT_USER_ROLE == 'Administrator':
                new_password = self.setting_new_password_entry.get()
                if new_password:
                    is_valid, msg = is_valid_password(new_password)
                    if not is_valid:
                        messagebox.showerror("Validation Error", msg)
                        return
                    new_password_hash = hash_password(new_password)
                    conn = db_connect()
                    cursor = conn.cursor()
                    cursor.execute("UPDATE users SET password_hash=? WHERE id=1", (new_password_hash,))
                    conn.commit()
                    conn.close()
                    messagebox.showinfo("Password Change", "Admin password updated successfully!")
            
            messagebox.showinfo("Settings Saved", "Application settings updated successfully.")
            self.show_main_app() # Rebuild UI to reflect changes
        except ValueError:
            messagebox.showerror("Input Error", "Invalid input for numeric fields. Please check.")
        except Exception as e:
            messagebox.showerror("Error", f"An unexpected error occurred: {e}")

    def download_sql_backup(self):
        if not require_permission('settings', 'view'): return
        
        file_path = filedialog.asksaveasfilename(defaultextension=".sql", filetypes=[("SQL files", "*.sql")])
        if not file_path:
            return

        conn = db_connect()
        if not conn: return
        cursor = conn.cursor()

        sql_dump = f"-- BNI Enterprises Database Backup\n" \
                   f"-- Generated: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n" \
                   f"-- Author: {AUTHOR}\n\n"

        sql_dump += "PRAGMA foreign_keys = OFF;\n\n"

        cursor.execute("SELECT name FROM sqlite_master WHERE type='table';")
        tables = cursor.fetchall()
        
        for table_name_tuple in tables:
            table_name = table_name_tuple[0]
            if table_name in ['sqlite_sequence']: # Skip internal SQLite tables
                continue

            sql_dump += f"DROP TABLE IF EXISTS `{table_name}`;\n"
            cursor.execute(f"SELECT sql FROM sqlite_master WHERE type='table' AND name='{table_name}';")
            create_table_sql = cursor.fetchone()[0]
            sql_dump += f"{create_table_sql};\n"

            cursor.execute(f"SELECT * FROM `{table_name}`")
            columns = [col[0] for col in cursor.description]
            for row in cursor.fetchall():
                values = []
                for val in row:
                    if val is None:
                        values.append('NULL')
                    elif isinstance(val, (int, float)):
                        values.append(str(val))
                    else:
                        values.append(f"'{str(val).replace("'", "''")}'")
                sql_dump += f"INSERT INTO `{table_name}` ({', '.join([f'`{c}`' for c in columns])}) VALUES ({', '.join(values)});\n"
            sql_dump += "\n"

        sql_dump += "PRAGMA foreign_keys = ON;\n"
        
        conn.close()

        try:
            with open(file_path, 'w', encoding='utf-8') as f:
                f.write(sql_dump)
            messagebox.showinfo("Backup Success", f"Database backup saved to {file_path}")
        except Exception as e:
            messagebox.showerror("Backup Error", f"Failed to save backup: {e}")

    def select_backup_file(self):
        file_path = filedialog.askopenfilename(filetypes=[("SQL files", "*.sql")])
        if file_path:
            self.restore_file_path.set(file_path)

    def toggle_restore_button(self):
        # Find the restore button widget by text, or by a specific name/id if available
        # This is a bit hacky, normally you'd save a reference to the button
        restore_button = None
        for widget in self.settings_form_frame.winfo_children():
            if isinstance(widget, ttk.LabelFrame): # db_settings_frame
                for child in widget.winfo_children():
                    if isinstance(child, ttk.Button) and child.cget("text") == "⬆ Restore Database":
                        restore_button = child
                        break
            if restore_button:
                break
        
        if restore_button:
            if self.restore_file_path.get():
                restore_button.config(state='normal')
            else:
                restore_button.config(state='disabled')

    def restore_sql_backup(self):
        if not require_permission('settings', 'edit'): return
        
        file_path = self.restore_file_path.get()
        if not file_path:
            messagebox.showerror("Error", "No backup file selected.")
            return

        if not messagebox.askyesno("Confirm Restore", "WARNING: Restoring will delete ALL current data and replace it with the backup. Are you absolutely sure you want to proceed?"):
            return

        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                sql_content = f.read()
            
            conn = sqlite3.connect(DB_NAME)
            cursor = conn.cursor()
            
            # Disable foreign key checks for restore
            cursor.execute("PRAGMA foreign_keys = OFF;")
            
            # Split SQL content by semicolon (but be careful with semicolons inside strings/comments)
            # A more robust solution might use a dedicated SQL parser. For simple dumps, this might work.
            statements = [s.strip() for s in sql_content.split(';') if s.strip()]
            
            for statement in statements:
                try:
                    cursor.execute(statement)
                except sqlite3.Error as e:
                    messagebox.showerror("Restore Error", f"Error executing SQL statement: {e}\nStatement: {statement[:100]}...")
                    conn.rollback()
                    conn.close()
                    return

            cursor.execute("PRAGMA foreign_keys = ON;") # Re-enable foreign key checks
            conn.commit()
            conn.close()
            
            messagebox.showinfo("Restore Success", "Database restored successfully. Application will restart to apply changes.")
            self.master.destroy() # Close current window
            # Relaunch the application (this assumes you have a main entry point)
            root = tk.Tk()
            app = BNIApp(root)
            root.mainloop()

        except FileNotFoundError:
            messagebox.showerror("Error", "Selected backup file not found.")
        except Exception as e:
            messagebox.showerror("Restore Error", f"An error occurred during database restore: {e}")
        finally:
            self.restore_file_path.set("") # Clear file path after attempt

    # --- Treeview Sorting (General purpose) ---
    def sort_treeview(self, tree, col_name, reverse=False):
        # Get data from the treeview
        data = [(tree.set(child, col_name), child) for child in tree.get_children('')]

        # Try to convert to float for numeric sorting, otherwise string sort
        try:
            # Clean currency symbols and commas for numeric conversion
            numeric_data = []
            for item, child_id in data:
                cleaned_item = item.replace(CURRENCY_SYMBOL, '').replace(',', '').strip()
                try:
                    numeric_data.append((float(cleaned_item), child_id))
                except ValueError:
                    numeric_data = None # Not purely numeric, fall back to string sort
                    break
            
            if numeric_data is not None:
                data = sorted(numeric_data, reverse=reverse)
            else:
                data = sorted(data, key=lambda x: x[0], reverse=reverse)
        except Exception:
            data = sorted(data, key=lambda x: x[0], reverse=reverse)

        for index, (val, child_id) in enumerate(data):
            tree.move(child_id, '', index)

        # Reverse the sorting next time
        tree.heading(col_name, command=lambda: self.sort_treeview(tree, col_name, not reverse))


# --- Main Application Execution ---
if __name__ == "__main__":
    root = tk.Tk()
    app = BNIApp(root)
    root.mainloop()