# Pagamentos
Execute database/migrations/2026_10_27_payment_modes.sql uma única vez após schema.sql. Para novas instalações: schema.sql, migração e demo.sql, nesta ordem.
Modo manual (padrão): cartão na maquininha, Pix na entrega/retirada ou dinheiro. O recebimento é confirmado manualmente por administrador em /admin/payments.php; a operação usa transação, trava a linha, registra auditoria e não aceita duplicidade ou pedido cancelado.
Modo online: adaptadores de Mercado Pago e PagBank foram criados como interfaces que deliberadamente recusam cobranças e webhooks até integração completa, autenticação, idempotência e testes. A ativação no painel está bloqueada por segurança. Nunca usar redirect do navegador como prova de pagamento.
Próxima etapa: APIs oficiais, token exclusivamente no servidor, idempotência checkout, assinatura e reconsulta de webhook, validação de valor/moeda/status, cancelamentos, reembolsos e testes sandbox.
