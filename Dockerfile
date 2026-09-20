FROM php:8.2-cli

# Install dependency sistem & driver PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_pgsql

# Install Node.js untuk build frontend Vue/Vite
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy seluruh source code
COPY . .

# Install paket PHP & frontend
RUN composer install --no-dev --optimize-autoloader
RUN npm install && npm run build

# Port default Render
EXPOSE 10000

# Jalankan server
CMD php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
