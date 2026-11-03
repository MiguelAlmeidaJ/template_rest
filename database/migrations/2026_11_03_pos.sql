-- PDV: vendas presenciais registradas separadamente dos pedidos de entrega.
CREATE TABLE IF NOT EXISTS pos_sales (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  operator_id INT UNSIGNED NOT NULL,
  description VARCHAR(180) NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  unit_price_cents INT UNSIGNED NOT NULL,
  total_cents INT UNSIGNED NOT NULL,
  payment_method ENUM('cash','pix','card') NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_pos_sales_created (created_at),
  INDEX idx_pos_sales_operator (operator_id),
  CONSTRAINT fk_pos_sales_operator FOREIGN KEY (operator_id) REFERENCES admins(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Registrar o dia real em que o pagamento online foi confirmado.
ALTER TABLE lunch_orders ADD COLUMN paid_at DATETIME NULL AFTER payment_status;
UPDATE lunch_orders SET paid_at=created_at WHERE payment_status='paid' AND paid_at IS NULL;
CREATE INDEX idx_lunch_orders_paid_at ON lunch_orders(paid_at);
