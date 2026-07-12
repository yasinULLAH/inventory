-- BNI Enterprises production-safe database updates
-- Apply this file ONCE BEFORE uploading the updated PHP files.
-- It is idempotent and may be run again safely on MySQL 8.x.

SET NAMES utf8mb4;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS schema_migrations (
    migration_key VARCHAR(100) NOT NULL PRIMARY KEY,
    applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    details VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @ddl=IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='customer_id'),'DO 0','ALTER TABLE payments ADD COLUMN customer_id INT NULL AFTER reference_id'); PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
SET @ddl=IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='supplier_id'),'DO 0','ALTER TABLE payments ADD COLUMN supplier_id INT NULL AFTER customer_id'); PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
SET @ddl=IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='installments' AND COLUMN_NAME='penalty_paid'),'DO 0','ALTER TABLE installments ADD COLUMN penalty_paid DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER penalty_fee'); PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
SET @ddl=IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bikes' AND COLUMN_NAME='tax_rate_applied'),'DO 0','ALTER TABLE bikes ADD COLUMN tax_rate_applied DECIMAL(9,6) NULL AFTER tax_amount'); PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
SET @ddl=IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bikes' AND COLUMN_NAME='tax_basis'),'DO 0','ALTER TABLE bikes ADD COLUMN tax_basis ENUM(''purchase_price'',''selling_price'') NULL AFTER tax_rate_applied'); PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

SET @ddl=IF(EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND INDEX_NAME='idx_payments_customer'),'DO 0','ALTER TABLE payments ADD INDEX idx_payments_customer (customer_id)'); PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
SET @ddl=IF(EXISTS(SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND INDEX_NAME='idx_payments_supplier'),'DO 0','ALTER TABLE payments ADD INDEX idx_payments_supplier (supplier_id)'); PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

SET @ddl=IF(EXISTS(SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND CONSTRAINT_NAME='fk_payments_customer'),'DO 0','ALTER TABLE payments ADD CONSTRAINT fk_payments_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL'); PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;
SET @ddl=IF(EXISTS(SELECT 1 FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND CONSTRAINT_NAME='fk_payments_supplier'),'DO 0','ALTER TABLE payments ADD CONSTRAINT fk_payments_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL'); PREPARE s FROM @ddl; EXECUTE s; DEALLOCATE PREPARE s;

CREATE TABLE IF NOT EXISTS installment_payment_allocations (
    id BIGINT NOT NULL AUTO_INCREMENT,
    payment_id INT NOT NULL,
    installment_id INT NOT NULL,
    principal_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    penalty_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_installment_payment (payment_id, installment_id),
    KEY idx_ipa_installment (installment_id),
    CONSTRAINT fk_ipa_payment FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    CONSTRAINT fk_ipa_installment FOREIGN KEY (installment_id) REFERENCES installments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS inventory_status_history (
    id BIGINT NOT NULL AUTO_INCREMENT,
    bike_id INT NULL,
    chassis_number VARCHAR(100) NOT NULL,
    old_status VARCHAR(40) NOT NULL,
    new_status VARCHAR(40) NOT NULL,
    changed_by INT NULL,
    change_reason VARCHAR(1000) NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_ish_bike (bike_id),
    KEY idx_ish_changed_at (changed_at),
    CONSTRAINT fk_ish_bike FOREIGN KEY (bike_id) REFERENCES bikes(id) ON DELETE SET NULL,
    CONSTRAINT fk_ish_user FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Backfill immutable payment ownership where it can be determined safely.
UPDATE payments p
JOIN bikes b ON p.transaction_type IN ('sale','customer_refund') AND p.reference_id=b.id
SET p.customer_id=b.customer_id
WHERE p.customer_id IS NULL AND b.customer_id IS NOT NULL;

UPDATE payments p
JOIN installments i ON p.transaction_type='installment' AND p.reference_id=i.id
SET p.customer_id=i.customer_id
WHERE p.customer_id IS NULL;

UPDATE payments p
JOIN purchase_orders po ON p.transaction_type='supplier_payment' AND p.reference_id=po.id
SET p.supplier_id=po.supplier_id
WHERE p.supplier_id IS NULL AND po.supplier_id IS NOT NULL;

UPDATE payments p
JOIN bikes b ON p.transaction_type='supplier_refund' AND p.reference_id=b.id
JOIN purchase_orders po ON po.id=b.purchase_order_id
SET p.supplier_id=po.supplier_id
WHERE p.supplier_id IS NULL AND po.supplier_id IS NOT NULL;

UPDATE payments p
JOIN (SELECT name, MIN(id) AS customer_id FROM customers GROUP BY name HAVING COUNT(*)=1) c ON c.name=p.party_name
SET p.customer_id=c.customer_id
WHERE p.customer_id IS NULL AND p.transaction_type IN ('sale','customer_advance') AND (p.reference_id IS NULL OR p.reference_id=0);

UPDATE payments p
JOIN (SELECT name, MIN(id) AS supplier_id FROM suppliers GROUP BY name HAVING COUNT(*)=1) s ON s.name=p.party_name
SET p.supplier_id=s.supplier_id
WHERE p.supplier_id IS NULL AND p.transaction_type IN ('supplier_payment','supplier_refund') AND (p.reference_id IS NULL OR p.reference_id=0);

-- Legacy non-cheque rows used the schema default "pending" even though they settled immediately.
UPDATE payments SET status='cleared' WHERE payment_type!='cheque' AND status='pending';

-- Existing penalty_fee values were recorded as collected by the old direct-payment workflow.
UPDATE installments SET penalty_paid=penalty_fee WHERE penalty_paid=0 AND penalty_fee>0;

-- Preserve the tax rate/basis already represented by each historical tax amount.
SET @configured_tax_basis = COALESCE((SELECT setting_value FROM settings WHERE setting_key='tax_on' LIMIT 1), 'purchase_price');
UPDATE bikes
SET tax_basis = IF(@configured_tax_basis='selling_price','selling_price','purchase_price')
WHERE tax_basis IS NULL;

UPDATE bikes
SET tax_rate_applied = CASE
    WHEN tax_basis='selling_price' AND selling_price>0 THEN tax_amount/selling_price
    WHEN purchase_price>0 THEN tax_amount/purchase_price
    ELSE 0
END
WHERE tax_rate_applied IS NULL;

-- Repair denormalized purchase-order totals from their authoritative bike rows.
UPDATE purchase_orders po
LEFT JOIN (
    SELECT purchase_order_id, COUNT(*) AS units, COALESCE(SUM(purchase_price),0) AS total
    FROM bikes WHERE purchase_order_id IS NOT NULL GROUP BY purchase_order_id
) b ON b.purchase_order_id=po.id
SET po.total_units=COALESCE(b.units,0), po.total_amount=COALESCE(b.total,0);

-- Repair sold-bike margins, including accessory revenue and accessory cost.
UPDATE bikes b
LEFT JOIN (
    SELECT sa.bike_id,
           COALESCE(SUM(sa.final_price),0) AS accessory_revenue,
           COALESCE(SUM(sa.quantity*a.purchase_price),0) AS accessory_cost
    FROM sale_accessories sa
    JOIN accessories a ON a.id=sa.accessory_id
    GROUP BY sa.bike_id
) x ON x.bike_id=b.id
SET b.margin=ROUND((b.selling_price+COALESCE(x.accessory_revenue,0))-(b.purchase_price+COALESCE(x.accessory_cost,0))-b.tax_amount,2)
WHERE b.status='sold';

-- Correct only harmless installment rounding drift (ten cents or less); larger differences need human review.
UPDATE installments i
JOIN (
    SELECT i2.bike_id, MAX(i2.id) AS last_id,
           ROUND((b.selling_price + COALESCE(sa.acc_total,0)) - COALESCE(sp.down_payment,0),2) AS expected_total,
           ROUND(SUM(i2.installment_amount),2) AS scheduled_total
    FROM installments i2
    JOIN bikes b ON b.id=i2.bike_id
    LEFT JOIN (SELECT bike_id, SUM(final_price) acc_total FROM sale_accessories GROUP BY bike_id) sa ON sa.bike_id=b.id
    LEFT JOIN (SELECT reference_id bike_id, SUM(amount) down_payment FROM ledger WHERE reference_type='down_payment' AND entry_type='credit' GROUP BY reference_id) sp ON sp.bike_id=b.id
    GROUP BY i2.bike_id, b.selling_price, sa.acc_total, sp.down_payment
) d ON d.last_id=i.id
SET i.installment_amount=ROUND(i.installment_amount+(d.expected_total-d.scheduled_total),2)
WHERE ABS(d.expected_total-d.scheduled_total)>0.001 AND ABS(d.expected_total-d.scheduled_total)<=0.10;

-- Ensure all permission-managed pages exist without granting new privileges unexpectedly.
INSERT IGNORE INTO role_permissions (role_id,page,can_view,can_add,can_edit,can_delete)
SELECT r.id,p.page_name,0,0,0,0
FROM roles r
CROSS JOIN (
    SELECT 'customer_ledger' page_name UNION ALL
    SELECT 'supplier_ledger' UNION ALL
    SELECT 'landing_page'
) p;

INSERT INTO schema_migrations (migration_key, details)
VALUES ('2026-07-production-integrity-hardening', 'Payment ownership, installment allocations, historical tax metadata and safe data repairs')
ON DUPLICATE KEY UPDATE details=VALUES(details);

SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;

-- Review-only diagnostics: these SELECTs do not modify data.
SELECT b.id AS bike_id, b.chassis_number, b.selling_price,
       ROUND(COALESCE(SUM(i.installment_amount),0),2) AS installment_schedule
FROM bikes b JOIN installments i ON i.bike_id=b.id
GROUP BY b.id,b.chassis_number,b.selling_price
HAVING installment_schedule > b.selling_price+0.10;
