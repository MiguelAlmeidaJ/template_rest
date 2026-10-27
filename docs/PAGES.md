# Páginas da demonstração

- / — início da marca (versão anterior preservada)
- /cardapio — catálogo dinâmico com carrinho e checkout de retirada ou entrega
- /reservas — formulário de solicitação e horários disponíveis
- /admin/ — login administrativo e resumo de pedidos/reservas

Execute scripts/create_admin.php via terminal para criar usuário. Não existe senha padrão.

Limitações: gateway de pagamento somente simula e não marca pagamentos como pagos; painel apenas consulta pedidos/reservas; entrega calcula taxa com zona cadastrada (sem roteirização). Ambiente exige HTTPS, proteção contra tentativas de login e configuração segura para produção.
