-- PDV: vendas presenciais registradas separadamente dos pedidos de entrega.
CREATE TABLE IF NOT EXISTS pos_sales (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  operator_id BIGINT UNSIGNED NOT NULL,
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
