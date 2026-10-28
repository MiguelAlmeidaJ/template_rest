-- Dados opcionais de perfil e campos separados do endereço.
ALTER TABLE customer_accounts
  ADD COLUMN full_name VARCHAR(150) NOT NULL DEFAULT '',
  ADD COLUMN phone VARCHAR(20) NOT NULL DEFAULT '',
  ADD COLUMN cpf VARCHAR(11) NULL;
ALTER TABLE customer_delivery_addresses
  ADD COLUMN number VARCHAR(20) NOT NULL DEFAULT '',
  ADD COLUMN complement VARCHAR(120) NOT NULL DEFAULT '';
