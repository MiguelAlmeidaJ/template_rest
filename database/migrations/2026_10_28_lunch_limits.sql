ALTER TABLE lunch_settings ADD COLUMN limit_mode ENUM('total','category') NOT NULL DEFAULT 'total' AFTER max_choices;
ALTER TABLE lunch_settings ADD COLUMN category_limits JSON NULL AFTER limit_mode;
UPDATE lunch_settings SET category_limits='{"Acompanhamentos":3,"Carnes":2,"Saladas":2,"Tortas e assados":1}' WHERE id=1 AND category_limits IS NULL;
ALTER TABLE lunch_order_boxes ADD COLUMN box_name VARCHAR(80) NOT NULL DEFAULT '' AFTER box_number;
