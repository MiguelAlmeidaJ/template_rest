CREATE TABLE IF NOT EXISTS admin_notifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 type VARCHAR(30) NOT NULL,
 reference_id BIGINT UNSIGNED NULL,
 title VARCHAR(190) NOT NULL,
 message VARCHAR(255) NOT NULL,
 link VARCHAR(255) NOT NULL,
 is_read TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 read_at TIMESTAMP NULL DEFAULT NULL,
 INDEX idx_admin_notifications_read_created (is_read,created_at),
 INDEX idx_admin_notifications_type_ref(type,reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO admin_notifications(type,reference_id,title,message,link,created_at)
SELECT 'order',id,CONCAT('Novo pedido #',id),CONCAT('Pedido enviado por ',customer_name),'/admin/lunch-orders.php',created_at FROM lunch_orders WHERE status='pending' AND NOT EXISTS(SELECT 1 FROM admin_notifications n WHERE n.type='order' AND n.reference_id=lunch_orders.id);
INSERT INTO admin_notifications(type,reference_id,title,message,link,created_at)
SELECT 'reservation',id,CONCAT('Nova reserva #',id),CONCAT('Reserva solicitada por ',customer_name),'/admin/#reservas',created_at FROM reservations WHERE status='pending' AND NOT EXISTS(SELECT 1 FROM admin_notifications n WHERE n.type='reservation' AND n.reference_id=reservations.id);
