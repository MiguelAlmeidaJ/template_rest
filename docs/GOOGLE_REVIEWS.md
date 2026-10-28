# Avaliações do Google na página inicial

Integração oficial com Google Places API (New), usando campos googleMapsUri e reviews.
1. Crie uma chave da Google Maps Platform para a Places API (New), habilite faturamento na conta Google Cloud e configure restrições apropriadas da chave para chamadas feitas pelo servidor.
2. Obtenha e valide o Place ID do Restaurante da Bia (Google Place ID, não um link de pesquisa).
3. Em .env configure GOOGLE_PLACES_API_KEY e GOOGLE_PLACE_ID. Não publique credenciais no Git.
4. Verifique se o PHP tem allow_url_fopen e acesso HTTPS de saída.
5. Acesse a home. A seção só aparece se a API devolver avaliações com texto e exatamente cinco estrelas.

**Limitação oficial:** Places API (New) devolve até 5 avaliações totais por consulta; o filtro 5 estrelas pode resultar em menos. Não é possível prometer 10 ou buscar a totalidade através desse endpoint. Exibir todas exigiria alternativa aprovada (por exemplo, Business Profile API com autorização do proprietário e requisitos próprios).
O resultado é solicitado durante o carregamento da home, sem armazenamento de avaliações, respeitando restrições de cache e atribuição. Dependendo do volume, cada visualização gera uso faturável da API; avaliar estratégia de exibição/custos compatível com as políticas antes de produção.
A seção inclui créditos dos autores, fotos quando fornecidas, link à avaliação e atribuição Google Maps. Validar termos, política de privacidade e requisitos atuais de uso e logotipo oficiais antes da publicação pública.
