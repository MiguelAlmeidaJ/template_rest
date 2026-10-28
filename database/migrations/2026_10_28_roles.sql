ALTER TABLE admins ADD COLUMN role ENUM('employee','manager','admin') NOT NULL DEFAULT 'admin' AFTER password_hash;
ALTER TABLE admins ADD COLUMN customer_id BIGINT UNSIGNED NULL AFTER role;
ALTER TABLE admins ADD UNIQUE KEY uq_admin_customer(customer_id);
ALTER TABLE admins ADD CONSTRAINT fk_admin_customer FOREIGN KEY(customer_id) REFERENCES customer_accounts(id) ON DELETE SET NULL;

-- Bring pre-existing admin accounts into the unified user directory without changing passwords.
INSERT INTO customer_accounts(email,password_hash)
SELECT a.email,a.password_hash FROM admins a
WHERE NOT EXISTS (SELECT 1 FROM customer_accounts c WHERE c.email=a.email);
UPDATE admins a JOIN customer_accounts c ON c.email=a.email
SET a.customer_id=c.id WHERE a.customer_id IS NULL;
