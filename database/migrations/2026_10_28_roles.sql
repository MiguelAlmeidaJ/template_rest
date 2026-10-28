ALTER TABLE admins ADD COLUMN role ENUM('employee','manager','admin') NOT NULL DEFAULT 'admin' AFTER password_hash;
ALTER TABLE admins ADD COLUMN customer_id BIGINT UNSIGNED NULL AFTER role;
ALTER TABLE admins ADD UNIQUE KEY uq_admin_customer(customer_id);
ALTER TABLE admins ADD CONSTRAINT fk_admin_customer FOREIGN KEY(customer_id) REFERENCES customer_accounts(id) ON DELETE SET NULL;
