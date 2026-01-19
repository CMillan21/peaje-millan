#!/bin/bash

echo "🚀 Iniciando despliegue de la Prueba Técnica..."

# 1. Instalar dependencias PHP (necesario si no existe la carpeta vendor)
echo "📦 Instalando dependencias de Composer..."
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# 2. CONFIGURACIÓN DEL ENTORNO (CAMBIO IMPORTANTE)
# Aquí forzamos la copia exacta de tu .env.example al .env
echo "📄 Sobreescribiendo archivo .env con la configuración maestra..."
cp .env.example .env

# 3. Reiniciar contenedores
echo "🐳 Levantando contenedores..."
./vendor/bin/sail down -v
./vendor/bin/sail up -d

# 4. Esperar a MySQL
echo "Esperando a que la Base de Datos inicie (10s)..."
sleep 10

# 5. Comandos de Laravel
echo "Configurando claves y base de datos..."
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear

echo "🗄️ Migrando base de datos desde cero..."
./vendor/bin/sail artisan migrate:fresh --force

# 6. Frontend
echo "Compilando Frontend..."
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

echo "¡PROYECTO LISTO!"
echo "DIsponible en: http://localhost"