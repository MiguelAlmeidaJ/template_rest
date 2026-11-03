-- Detalhamento de itens de cada venda presencial.
-- Preserva pos_sales e os relatórios anteriores.
CREATE TABLE IF NOT EXISTS pos_sale_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sale_id BIGINT UNSIGNED NOT NULL,
  description VARCHAR(180) NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  unit_price_cents INT UNSIGNED NOT NULL,
  total_cents BIGINT UNSIGNED NOT NULL,
  INDEX idx_pos_sale_items_sale (sale_id),
  CONSTRAINT fk_pos_sale_items_sale FOREIGN KEY (sale_id) REFERENCES pos_sales(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
