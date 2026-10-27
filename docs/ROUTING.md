# Routing (Apache / Docker / PHP built-in server)

- The Apache DocumentRoot MUST point to public/ and mod_rewrite must be enabled.
- Existing directories (cardapio/, galeria/, contato/, o-restaurante/, admin/) must not be rewritten to the API front controller.
- public/.htaccess now checks -f and -d, then rewrites only missing paths to public/index.php.
- public/router.php mirrors that routing for PHP's built-in development server.
- Examples: GET /cardapio/, /galeria/, /contato/, /o-restaurante/, /admin/, /admin/payments.php, /api/health.
- Docker with a bind mount reflects changes immediately. Pull main and run docker compose ... restart web if Apache has cached an old configuration.
- If directory URLs still serve the API, check which vhost DocumentRoot is mounted and that AllowOverride All and DirectoryIndex index.php are active.
- Login page at /admin/ requires an admin created using scripts/create_admin.php; payment page also requires the payment migration.
