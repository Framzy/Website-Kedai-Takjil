
FROM php:8.4-apache

# Instal dependency sistem dan ekstensi PHP.
RUN apt-get update && apt-get install -y --no-install-recommends \
    libonig-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        bcmath \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Aktifkan mod_rewrite untuk routing Laravel.
RUN a2enmod rewrite

# Atur document root Apache ke folder public Laravel.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# Salin source code backend.
COPY . .

# Instal Composer.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Instal dependency produksi Laravel.
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# Siapkan direktori yang perlu ditulis oleh Laravel.
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
