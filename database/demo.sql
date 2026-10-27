INSERT INTO categories(name) VALUES ('Entradas'),('Pratos principais'),('Bebidas');
INSERT INTO products(category_id,name,description,price_cents) VALUES (1,'Bruschetta da casa','Tomate e pão artesanal',2490),(2,'Costela Raiz & Brasa','Costela assada lentamente',6990),(2,'Risoto de cogumelos','Arroz arbóreo com cogumelos',5790),(3,'Limonada da casa','Limão e hortelã',1290);
INSERT INTO delivery_zones(name,fee_cents,min_order_cents) VALUES ('Centro',600,3000),('Jardim',900,4000);
INSERT INTO reservation_slots(date,time,capacity) VALUES(DATE_ADD(CURDATE(),INTERVAL 1 DAY),'19:00',20),(DATE_ADD(CURDATE(),INTERVAL 1 DAY),'20:30',20);
