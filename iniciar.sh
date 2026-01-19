#!/bin/bash

echo "Iniciando despliegue de la Prueba Técnica..."

# 1. Instalar dependencias
echo "📦 Instalando dependencias de Composer..."
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    composer install --ignore-platform-reqs

# 2. Configurar entorno
echo "Sobreescribiendo archivo .env con la configuración maestra..."
cp .env.example .env

# 3. Reiniciar contenedores
echo "🐳 Levantando contenedores..."
./vendor/bin/sail down -v
./vendor/bin/sail up -d

# 4. Esperar a MySQL
echo "⏳ Esperando a que la Base de Datos inicie (10s)..."
sleep 10

# 5. Comandos de Laravel
echo "🔑 Configurando proyecto..."
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan config:clear

# CAMBIO: Primero migramos (creamos las tablas)
echo "🗄️ Migrando base de datos desde cero..."
./vendor/bin/sail artisan migrate:fresh --force

# CAMBIO: Ahora sí podemos limpiar la caché sin errores
echo "🧹 Limpiando caché..."
./vendor/bin/sail artisan cache:clear

# 6. Frontend
echo "Compilando Frontend..."
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

echo "✅ ¡PROYECTO LISTO!"
echo "➡️  Visita: http://localhost"