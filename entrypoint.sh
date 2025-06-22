#!/bin/bash

echo "🔄 Menunggu database PostgreSQL siap..."

# Tunggu PostgreSQL siap menerima koneksi
until pg_isready -h db -p 5432 -U postgres; do
  sleep 2
done

echo "✅ Database siap. Melanjutkan..."

# ✅ Set permission untuk Laravel
echo "🔧 Mengatur permission untuk storage dan bootstrap/cache..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Install dependency jika belum ada vendor
if [ ! -d "vendor" ]; then
  echo "📦 Menjalankan composer install..."
  composer install
fi

# Generate APP_KEY jika belum ada
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
  echo "🔑 APP_KEY belum ada. Menjalankan key:generate..."
  php artisan key:generate
else
  echo "✅ APP_KEY sudah tersedia."
fi

# Jalankan migrasi
php artisan migrate --force

# Jalankan PHP-FPM
exec php-fpm
