# Padrão de manutenção do Restaurante da Bia

O projeto usa **PHP 8.2**, JavaScript e CSS. Para todos os módulos, aplique o mesmo padrão:

1. **Camada de acesso a dados e regras** em `src/`. Páginas públicas e admin apenas recebem entradas, chamam serviços e renderizam templates.
2. **Controles de acesso no servidor**, não apenas esconder links no menu. Preserve CSRF, escaping e validação dos dados.
3. **Código legível**: PSR-12 para PHP; Prettier para JS/CSS; UTF-8/LF/4 espaços para PHP e 2 para frontend.
4. **CSS por área e responsabilidade**. Reutilize classes e evite novos estilos inline.
5. **Migrações SQL explícitas**, nunca alterar um banco existente implicitamente durante a abertura de páginas.
6. **Não mudar comportamento durante formatação**: alterações de arquitetura exigem testes, com commits separados.

## Formatar tudo localmente

```bash
composer install
npm install
composer run format:php
npm run format:front
find src public scripts -name '*.php' -print0 | xargs -0 -n1 php -l
find public -name '*.js' -print0 | xargs -0 -n1 node --check
```

O workflow `format-code.yml` executa a padronização integral ao atualizar a `main`.
Verifique a aba Actions: se as permissões de escrita do GitHub Actions estiverem bloqueadas,
a publicação automática da formatação será recusada. Nesse caso, execute os comandos
localmente e envie o commit. Código formatado não equivale a código arquiteturalmente
refatorado; refatorações de responsabilidades deverão ser revisadas por módulo com testes.
