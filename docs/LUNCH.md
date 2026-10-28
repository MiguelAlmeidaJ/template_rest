# Quentinhas — primeira versão
Migração: database/migrations/2026_10_28_marmitas.sql.
Admin: /admin/lunch.php (preço, limite por quentinha, cardápio e pedidos) e /admin/payment-methods.php (Pix/cartão).
Público: /pedido/.
API: POST /api/lunch/orders.
O preço de R$ 20,00 inicial é apenas demonstrativo: configure o preço real antes de disponibilizar o pedido. O cardápio começa vazio e precisa ser preenchido pelo administrador.
A quantidade pode ser de 1 a 20, cada quentinha tem escolhas próprias, validadas novamente no servidor. Os pedidos mantêm uma cópia dos nomes escolhidos, mesmo se o cardápio mudar.
Pix e cartão são pagos no momento da entrega, sem gateway e sem cobrança automática.
A entrega ainda não calcula distância nem taxa; confirmar com o restaurante antes de anunciar o valor final. Não utilizar em produção sem proteção antiabuso, regras de capacidade e aviso de privacidade.
