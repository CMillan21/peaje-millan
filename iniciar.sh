#!/bin/bash

# 1. Instalar dependencias usando un contenedor temporal
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# 2. Copiar el archivo de entorno si no existe
if [ ! -f .env ]; then
    cp .env.example .env
fi

# 3. Levantar los contenedores
./vendor/bin/sail up -d

# 4. Generar clave y migrar
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

echo "✅ ¡Proyecto listo! Visita http://localhost"