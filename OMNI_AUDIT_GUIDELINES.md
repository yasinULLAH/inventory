# OMNI Audit Lessons Learned & Technical Guidelines

## 1. The Two-Step Execution Protocol
To ensure maximum stability and allow for human review, the audit must always be performed in two distinct phases:
- **Phase 1: Discovery & Asset Capture**: Spider the application, capture high-DPI full-page screenshots, and generate comprehensive GitHub-style `.md` files for every module.
- **Phase 2: Corporate Reconstruction**: Generate the final `.docx` manual using only the local assets captured in Phase 1. This prevents browser/session timeouts from affecting document generation.

## 2. Technical Standards (Node.js 24+ Compatibility)
- **Image Handling**: `docx` v9.x in Node 24+ environment has strict requirements for binary data. 
    - **Mistake to Avoid**: Passing Node.js `Buffer` objects directly to `ImageRun` often triggers a `SharedArrayBuffer` TypeError.
    - **Fix**: Convert the image to a Base64 string OR create a clean `Uint8Array` copy using `Buffer.copy()` before passing it to the library.
- **Image-Size**: Always pass the `Buffer` to `sizeOf()`, not the file path, to avoid internal `TextDecoder` encoding errors on certain operating systems.

## 3. Aesthetic & Content Standards
- **Prose over Data**: Never produce a manual that lists raw input names (e.g., "input_user_name").
- **Human-Centric Translation**:
    - "Name" fields -> "Primary record identifier for classification."
    - "Select" boxes -> "Standardized categorization dropdown."
    - "Date" fields -> "Chronological tracking for historical reporting."
- **Visuals**: Use Hex `#2E74B5` (Corporate Blue) for all headers and structural borders. Ensure a 1920x1080 capture resolution.

## 4. Database Schema Coverage
The system manages 23+ normalized relational tables: settings, suppliers, customers, models, accessories, purchase_orders, bikes, sale_accessories, payments, installments, ledger, roles, role_permissions, users, income_expenses, quotations, money_destinations, sale_money_allocations, bank_deposits, deposit_allocations, cheque_register, leadership, gallery, bike_requests, quote_requests.


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
