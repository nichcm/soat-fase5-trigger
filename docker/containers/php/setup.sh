#!/bin/sh

set -e

echo "🚫 Ajustando permissões"
chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "📦 Instalando dependências"
mkdir -p vendor
composer install --no-dev --optimize-autoloader || {
    echo "❌ Falha na instalação das dependências"
    exit 1
}

if [ ! -f .env ]; then
    cp .env.example .env

    echo "🔑 Gerando chave da aplicação"
    composer run gen-app-key
fi

echo "Preparando banco de dados..."
composer run post-create-project-cmd

echo "🚀 Iniciando o container"
exec php-fpm
