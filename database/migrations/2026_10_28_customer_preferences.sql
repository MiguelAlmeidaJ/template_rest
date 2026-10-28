-- Customer delivery profiles and reusable lunch combinations.
CREATE TABLE IF NOT EXISTS customer_delivery_addresses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  label VARCHAR(70) NOT NULL DEFAULT 'Meu endereço',
  recipient VARCHAR(150) NOT NULL,
  phone VARCHAR(24) NOT NULL,
  address VARCHAR(350) NOT NULL,
  neighborhood VARCHAR(120) NOT NULL,
  reference_point VARCHAR(350) NOT NULL DEFAULT '',
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_customer_addresses (customer_id, is_default),
  CONSTRAINT fk_delivery_address_customer FOREIGN KEY (customer_id) REFERENCES customer_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO customer_delivery_addresses
  (customer_id, label, recipient, phone, address, neighborhood, reference_point, is_default)
SELECT a.customer_id, 'Principal', a.recipient, a.phone, a.address, a.neighborhood, a.reference_point, 1
FROM customer_addresses a
WHERE a.address <> ''
AND NOT EXISTS (SELECT 1 FROM customer_delivery_addresses d WHERE d.customer_id=a.customer_id);

CREATE TABLE IF NOT EXISTS customer_lunch_favorites (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  customer_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  boxes_json JSON NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_favorite_customer (customer_id),
  CONSTRAINT fk_favorite_customer FOREIGN KEY (customer_id) REFERENCES customer_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
