FROM php:8.4-apache

# Dependensi sistem dan ekstensi PHP.
RUN apt-get update && apt-get install -y --no-install-recommends \
    libonig-dev \
    libzip-dev \
    libssl-dev \
    pkg-config \
    unzip \
    && docker-php-ext-install mbstring bcmath zip \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Aktifkan routing Laravel melalui Apache.
RUN a2enmod rewrite

# --- TAMBAHAN BARIS DI BAWAH INI UNTUK MENGHILANGKAN WARNING AH00558 ---
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
# ----------------------------------------------------------------------

# Document root Laravel.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# Gunakan port default Render.
RUN sed -ri 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -ri 's/:80>/:10000>/g' \
        /etc/apache2/sites-available/*.conf

WORKDIR /var/www/html

# Composer.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Salin aplikasi Laravel.
COPY . .

# Instal dependency produksi.
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Siapkan direktori writable Laravel.
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000