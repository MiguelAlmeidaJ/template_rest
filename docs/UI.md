# Nova interface visual

Camada pública: `public/site.html`, `public/cardapio/index.php`, `public/reservas/index.php`.
Estilos públicos: `public/assets/css/site.css`.
Comportamento público: `public/assets/js/home.js`, `public/assets/js/media.js` e `public/assets/app.js` (pedidos/reservas já existentes).
Substitua fotos em `public/assets/images/`, conforme o README. Os arquivos HTML possuem `charset=utf-8`; se o MySQL importou caracteres corrompidos anteriormente, será preciso corrigir os registros antigos no banco, pois o CSS não resolve mojibake.
O painel administrativo segue usando o CSS legado `public/assets/app.css`, permitindo refatorá-lo separadamente.
