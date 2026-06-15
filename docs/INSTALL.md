# Instalação do backend Materiais Opme

Este documento resume a instalação base do stack:
- Apache
- PHP 8.4
- MariaDB
- HTTPS via Let’s Encrypt

## Pré-requisitos
- Servidor Ubuntu/Debian com acesso root
- Domínio apontando para o servidor
- Porta 80 e 443 liberadas

## Passos
1. Copie o projeto para:
   - `/var/www/materiais-opme`
2. Ajuste o arquivo:
   - `/root/.materiais_opme_backend.conf`
3. Gere o hash do admin:
   - `php -r 'echo password_hash("SENHA_FORTE", PASSWORD_DEFAULT), PHP_EOL;'`
4. Execute o seed SQL:
   - `mysql -u root -p < /root/materiais_opme_schema.sql`
   - depois rode o seed do admin, se quiser aplicar o arquivo `scripts/seed_admin.sql`
5. Instale o stack usando o script:
   - `bash /root/materiais_opme_backend/scripts/install_stack.sh`

## Observações de segurança
- Os arquivos em `/root/` devem ser acessíveis apenas pela camada necessária.
- O backend deve expor apenas `public/` ao navegador.
- HTTPS deve ser obrigatório.
- Senhas devem ser armazenadas apenas com hash.

## Ajustes recomendados antes de produção
- Trocar `seu-dominio.exemplo` no vhost.
- Confirmar a versão do PHP disponível na distro.
- Revisar permissões dos arquivos sensíveis.
- Validar se o usuário do Apache pode ler os arquivos legados com segurança.
