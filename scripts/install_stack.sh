#!/usr/bin/env bash
set -euo pipefail

# Instalador de referência para Ubuntu/Debian
# Ajuste DOMAIN, EMAIL e paths antes de usar em produção.

DOMAIN="bitmeback.com.br"
WWW_DOMAIN="www.bitmeback.com.br"
EMAIL="joao@bitmeback.com.br"
WEBROOT="/var/www/materiais-opme"
APACHE_CONF="/etc/apache2/sites-available/materiais_opme.conf"
PHP_VERSION="8.4"

if [[ $EUID -ne 0 ]]; then
  echo "Execute como root."
  exit 1
fi

echo "Atualizando pacotes..."
apt-get update
apt-get install -y ca-certificates curl gnupg lsb-release apt-transport-https software-properties-common

echo "Preparando repositório do PHP 8.4 (se necessário)..."
if ! apt-cache show php8.4-cli >/dev/null 2>&1; then
  add-apt-repository -y ppa:ondrej/php
  apt-get update
fi

echo "Instalando Apache, PHP, MariaDB e Certbot..."
apt-get install -y apache2 mariadb-server \
  php${PHP_VERSION}-cli php${PHP_VERSION}-fpm php${PHP_VERSION}-mysql \
  php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-curl \
  php${PHP_VERSION}-zip php${PHP_VERSION}-intl php${PHP_VERSION}-gd \
  certbot python3-certbot-apache

a2enmod rewrite ssl headers proxy_fcgi setenvif
systemctl enable --now apache2 mariadb php${PHP_VERSION}-fpm

echo "Criando diretório web..."
mkdir -p "${WEBROOT}"
chown -R www-data:www-data "${WEBROOT}"
chmod 750 "${WEBROOT}"

echo "Copie o backend para ${WEBROOT} e ajuste permissões dos arquivos /root/*.conf com cuidado."

echo "Instalando vhost Apache..."
cat > "${APACHE_CONF}" <<EOF
<VirtualHost *:80>
    ServerName ${DOMAIN}
    ServerAlias ${WWW_DOMAIN}
    DocumentRoot ${WEBROOT}/public

    <Directory ${WEBROOT}/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \\${APACHE_LOG_DIR}/materiais_opme_error.log
    CustomLog \\${APACHE_LOG_DIR}/materiais_opme_access.log combined
</VirtualHost>
EOF

a2ensite materiais_opme.conf
systemctl reload apache2

echo "Emitindo certificado com Certbot..."
certbot --apache -d "${DOMAIN}" -d "${WWW_DOMAIN}" -m "${EMAIL}" --agree-tos --redirect --non-interactive

echo "Instalação base concluída."
