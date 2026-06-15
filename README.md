# Materiais Opme Backend

Estrutura inicial do backend para gestão de fornecedores, credenciais e configurações do fluxo Materiais Opme.

## Artefatos iniciais
- `config/app.php` - configurações centrais do sistema.
- `config/backend.php` - leitura do arquivo externo `/root/.materiais_opme_backend.conf`.
- `config/routes.php` - mapa inicial de rotas.
- `public/index.php` - front controller.
- `public/.htaccess` - rewrite e proteção básica.
- `resources/views/` - telas HTML básicas do backend.
- `scripts/seed_admin.sql` - seed inicial do usuário administrador.
- `scripts/generate_password_hash.php` - ajuda para gerar hash seguro de senha.
- `scripts/install_stack.sh` - instalador base de Apache/PHP/MariaDB/Certbot.
- `docs/INSTALL.md` - guia de instalação base.
- `apache/materiais_opme.conf` - exemplo de vhost Apache com HTTPS.
- `app/Services/VendorFileService.php` - leitura e escrita do arquivo legado de fornecedores.

## Próximos passos sugeridos
1. Criar a camada de conexão com MariaDB.
2. Implementar autenticação com CSRF e sessão segura.
3. Criar telas de login, dashboard e gestão de fornecedores.
4. Desenvolver serviço de leitura/escrita dos arquivos legados.
