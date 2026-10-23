
FROM php:8.4-apache-bookworm

# Pilih apakah dependency development diperlukan.
# Default false cocok untuk image deployment.
ARG INSTALL_DEV_DEPENDENCIES=false

# Instal dependensi sistem dan Microsoft ODBC Driver 18.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        gnupg \
        libonig-dev \
        libzip-dev \
        unzip \
        unixodbc-dev \
    && curl -fsSL \
        https://packages.microsoft.com/config/debian/12/packages-microsoft-prod.deb \
        -o /tmp/packages-microsoft-prod.deb \
    && dpkg -i /tmp/packages-microsoft-prod.deb \
    && rm /tmp/packages-microsoft-prod.deb \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends \
        msodbcsql18 \
    && docker-php-ext-install \
        mbstring \
        bcmath \
        zip \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && apt-get purge -y --auto-remove \
        curl \
        gnupg \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

# Aktifkan mod_rewrite untuk routing Laravel.
RUN a2enmod rewrite

# Arahkan Apache ke direktori public Laravel.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri \
    's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# Instal Composer.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Salin source code Laravel.
COPY . .

# Instal dependency sesuai kebutuhan lingkungan.
RUN if [ "$INSTALL_DEV_DEPENDENCIES" = "true" ]; then \
        composer install \
            --no-interaction \
            --prefer-dist; \
    else \
        composer install \
            --no-dev \
            --no-interaction \
            --prefer-dist \
            --optimize-autoloader; \
    fi

# Siapkan direktori Laravel dan permission.
RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data \
        storage bootstrap/cache

EXPOSE 80
