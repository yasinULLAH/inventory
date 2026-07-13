# Return Feature — Full Edge Case Audit Report

## CRITICAL BUGS (Already Fixed Above)

### 1. `assert_valid_bike_status_transition` blocks restock
**File:** `index.php:1185`  
**Issue:** The function only allows `['returned']` transitions. After adding `in_stock` to the edit form dropdown for returned bikes, submitting a restock would throw "Invalid inventory status transition from returned to in_stock."  
**Fix applied:** Changed to `['returned', 'in_stock', 'damaged_lost']` and edit form now also offers `damaged_lost` option.

---

## REMAINING ISSUES TO FIX

### Issue A: No server-side validation for cheque fields on return
**Severity:** Medium  
**Files:** `index.php:2437-2439` (sales), `index.php:2379-2381` (purchase)  
**Problem:** When refund method is `cheque`, the JS makes fields required, but there's no server-side check. A user can bypass JS and submit with method=cheque but empty cheque_number / bank_name.  
**Fix:** Add server-side validation before processing:

```php
if ($refund_method === 'cheque' && (empty($cheque_number) || empty($bank_name))) {
    $err = 'Cheque number and bank name are required for cheque refunds.';
    goto end_returns_post;
}
```

### Issue B: Purchase return — no validation against actual supplier payment
**Severity:** Medium  
**Files:** `index.php:2387-2416`  
**Problem:** The purchase return doesn't check how much was actually paid to the supplier. You could return a bike that was never paid for and still record a supplier_refund. This could create negative supplier balances (you "receive" money you never paid).  
**Fix:** Add a check similar to the sales return:

```php
$paid_to_supplier = $conn->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE transaction_type='supplier_payment' AND reference_id IN (SELECT id FROM purchase_orders WHERE supplier_id={$bike_info['supplier_id']}) AND status!='bounced'")->fetch_row()[0];
$actual_cost = min($full_reversal_amount, (float) $paid_to_supplier[0]);
if ($return_amount - $actual_cost > 0.0001) {
    throw new Exception('Supplier refund cannot exceed the amount actually paid for this purchase.');
}
```

### Issue C: Return of walk-in customer bikes links customer_id=0 in payments/ledger
**Severity:** Low  
**Files:** `index.php:2470-2471` (payment), `index.php:2476` (ledger)  
**Problem:** When a walk-in customer (customer_id=NULL) bike is returned, `$bike_info['customer_id']` is NULL. mysqli binds NULL as 0 (int), so the refund payment and ledger entries get `customer_id=0`. This creates orphan records — the original sale payment has `customer_id=NULL` but the refund has `customer_id=0`.  
**Fix:** Skip customer_id binding entirely when null, or explicitly set it to NULL:

```php
$cust_id_for_payment = $bike_info['customer_id'];
// Change bind to allow NULL for customer_id
// Or create a separate INSERT path for walk-in returns
```

### Issue D: No status history entries for return events
**Severity:** Low  
**Files:** `index.php:2460` (sales return), `index.php:2398` (purchase return)  
**Problem:** The `inventory_status_history` table logs status changes only from the edit form (line 3060-3064). Returns don't log history entries. There's no audit trail showing when/why a bike was returned.  
**Fix:** Add history INSERT in both return handlers:

```php
// In sales return handler (after line 2462):
$history_stmt = $conn->prepare('INSERT INTO inventory_status_history (bike_id, chassis_number, old_status, new_status, changed_by, change_reason) VALUES (?,?,\'sold\',\'returned\',?,?)');
$history_stmt->bind_param('isis', $bike_id, $bike_info['chassis_number'], $_SESSION['user_id'], $return_notes ?: 'Sales return processed');
$history_stmt->execute();

// In purchase return handler (after line 2400):
$history_stmt = $conn->prepare('INSERT INTO inventory_status_history (bike_id, chassis_number, old_status, new_status, changed_by, change_reason) VALUES (?,?,?,\'returned_to_supplier\',?,?)');
$history_stmt->bind_param('issis', $bike_id, $bike_info['chassis_number'], $bike_info['status'], $_SESSION['user_id'], $return_notes ?: 'Purchase return processed');
$history_stmt->execute();
```

### Issue E: 0-amount return still creates a payment record
**Severity:** Low  
**Files:** `index.php:2470`  
**Problem:** If `return_amount` is 0, a `customer_refund` payment record is still created with amount=0. This is unnecessary data clutter.  
**Fix:** Skip the payment INSERT when `$return_amount <= 0`:

```php
if ($return_amount > 0) {
    $pay_st = $conn->prepare("INSERT INTO payments ...");
    $pay_st->execute();
    $pay_st->close();
}
```

### Issue F: Sale dropdown doesn't indicate bike was previously returned
**Severity:** Low  
**Files:** `index.php:5418-5434`  
**Problem:** When a returned bike appears in the sale dropdown, it shows chassis/model/color/purchase_price but doesn't indicate it was previously sold and returned. The user might not know the bike's history.  
**Fix:** Show a "(RETURNED)" badge next to returned bikes in the dropdown:

```php
// Line 5431 area - when generating option for bike:
$badge = $bs['status'] === 'returned' ? ' ⚠️RETURNED' : '';
<option ...><?= sanitize($bs['chassis_number']) ?> | ... <?= $badge ?></option>
```

### Issue G: Purchase return amount can be 0
**Severity:** Low  
**Files:** `index.php:2377, 2395`  
**Problem:** Similar to Issue E — if return_amount is 0, a `supplier_refund` payment with amount 0 is still created.  
**Fix:** Same as Issue E — skip the payment INSERT when `$return_amount <= 0`.

---

## OBSERVATIONS (No Fix Needed — Correct Behavior)

### 1. Dashboard aggregate totals correctly use `status='sold'`
Aggregate queries (total sold count, sales value, margin, tax, trend) correctly exclude returned bikes. Including them would inflate sales figures since the sale was reversed.

### 2. Model/Customer sold counts correctly use `status='sold'`
The model stock page and customer total_purchases correctly count only `sold` status. Returned bikes are reversed sales.

### 3. Profit/Tax reports correctly exclude returned bikes
Returned bikes have margin=0 and tax_amount=0. Including them would not change totals but would inflate "Bikes Sold" counts.

### 4. Deposit allocations and money allocations deleted on return
Lines 2493-2494 correctly clear tracking data. Deposit records remain but lose their allocation links — this is acceptable since the allocation was reversed.

### 5. Paid installments not cancelled on return
Only `pending`/`overdue` installments are cancelled. `paid` installments stay — this is correct since the money is already collected.

### 6. Accessory stock correctly restored on sales return
Line 2487-2492 restores stock. Custom accessories created during sale also get stock restored — correct.

### 7. Quotation conversion uses `lock_in_stock_bike` which now accepts `returned`
No additional fix needed — already handled.

---

## PLAN SUMMARY

| # | Severity | Area | Fix Required | Effort |
|---|----------|------|-------------|--------|
| A | Medium | Return forms | Add server-side cheque validation | 5 min |
| B | Medium | Purchase return | Add paid-to-supplier check | 10 min |
| C | Low | Sales return | Handle NULL customer_id correctly | 5 min |
| D | Low | Both returns | Add status_history INSERT | 10 min |
| E | Low | Sales return | Skip 0-amount payment record | 2 min |
| F | Low | Sale dropdown | Show returned badge in selector | 5 min |
| G | Low | Purchase return | Skip 0-amount payment record | 2 min |

Total estimated effort: ~40 minutes
