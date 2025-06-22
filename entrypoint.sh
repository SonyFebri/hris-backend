#!/bin/bash

echo "🔄 Menunggu database PostgreSQL siap..."

# Tunggu PostgreSQL siap menerima koneksi
until pg_isready -h "$DB_HOST" -p 5432 -U "$DB_USERNAME"; do
  sleep 2
done

echo "✅ Database siap. Melanjutkan..."

# Install dependency
composer install

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
