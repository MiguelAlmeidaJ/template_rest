# CMS / Painel
Execute uma única vez: `database/migrations/2026_10_27_cms.sql` no banco do Docker antes de abrir o editor.
Abra `/admin/pages.php` para atualizar campos predefinidos de Início, Cardápio, O Restaurante, Galeria e Contato, incluindo fotografias por slot.
Abra `/admin/gallery.php` para enviar, ordenar, ocultar e exibir imagens na galeria pública.
Abra `/admin/settings.php` para alterar logo e favicon.
Uploads são armazenados em `public/assets/images/bia/uploads/`, fora do versionamento de código. Para não perder as imagens, preserve a pasta e faça backup dela e do banco.
A pasta precisa ser gravável pelo usuário do Apache (www-data no Docker); em bind mounts WSL pode exigir: `mkdir -p public/assets/images/bia/uploads && sudo chown -R $(id -u):33 public/assets/images/bia/uploads && sudo chmod -R ug+rwX public/assets/images/bia/uploads` (ajuste dono/grupo conforme seu host).
Permite JPG, PNG e WebP até 5 MB, limitado a 6000×6000, sem SVG executável. Configurar limites de upload de PHP e backups em produção.
O editor de texto trabalha com campos de conteúdo definidos por página; não edita livremente HTML, nem fornece um construtor visual arrasta-e-solta.
Fotografias do Unsplash são fallbacks somente para imagens de páginas; a galeria pública passa a usar apenas imagens adicionadas no painel.
