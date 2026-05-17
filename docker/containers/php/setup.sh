#!/bin/sh

set -e

echo "Ajustando permissoes"
chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage

chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "Instalando dependencias"
if [ ! -f vendor/autoload.php ]; then
    mkdir -p vendor
    composer config --global process-timeout 0
    COMPOSER_PROCESS_TIMEOUT=1200 COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader || {
        echo "Falha na instalacao das dependencias"
        exit 1
    }
else
    echo "Dependencias ja instaladas"
fi

if [ ! -f .env ]; then
    cp .env.example .env
    echo "Gerando chave da aplicacao"
    composer run gen-app-key
fi

echo "Preparando banco de dados..."
composer run post-create-project-cmd

echo "Configurando filas no RabbitMQ..."
php artisan queue:setup || {
    echo "Falha na configuracao do RabbitMQ. O servico esta acessivel e configurado?"
    exit 1
}

if [ "$#" -eq 0 ]; then
    set -- php-fpm
fi

echo "Iniciando comando: $*"
exec "$@"
