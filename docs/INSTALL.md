# Instalação

Requisitos: PHP 8.1+ com PDO MySQL e mbstring; MySQL 8+; Apache com mod_rewrite, ou servidor PHP para desenvolvimento.

1. Copiar .env.example para .env e preencher credenciais.
2. Importar database/schema.sql e, uma única vez, database/demo.sql.
3. Apontar document root da hospedagem para a pasta public/ (obrigatório para manter .env e src fora do acesso web). Não publicar a raiz do repositório como document root.
4. Desenvolvimento: php -S localhost:8000 -t public public/router.php
5. Visitar / e testar GET /api/health e GET /api/products.

Endpoints: GET /api/products; GET /api/reservations/slots?date=AAAA-MM-DD; POST /api/reservations; POST /api/delivery/quote; POST /api/orders; POST /api/payments/checkout.

Pedido JSON: {"customer_name":"Demo","customer_phone":"21999999999","fulfillment":"pickup","items":[{"product_id":1,"quantity":2}]}.
Reserva JSON: {"customer_name":"Demo","customer_phone":"21999999999","date":"AAAA-MM-DD","time":"19:00","guests":2}.
Entrega JSON: {"neighborhood":"Centro","subtotal_cents":7000}.

**Importante:** gateway é somente simulado, sem cobrança. Não habilitar pagamentos reais sem adapter oficial, validação de webhook, idempotência, HTTPS, autenticação e rate limiting. O frontend contém cardápio e reserva; carrinho e painel administrativo são próximos módulos a implementar.
