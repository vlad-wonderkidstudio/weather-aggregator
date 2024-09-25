FROM php:8.3-fpm

RUN apt-get update --fix-missing \
    && apt-get install -y curl wget zip unzip git \
    && rm -rf /var/lib/apt/lists/*

# Install system dependencies.
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    git \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql

# Install Composer globally.
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer


# Create a directory for your Laravel application.
WORKDIR /usr/share/nginx/www

# PHPRedis
RUN pecl install redis

CMD cd /usr/share/nginx/www \
    && cp -R -u -p ./.env.example ./.env \
    && composer install --no-interaction --optimize-autoloader \
    && php artisan migrate \
    && php artisan l5-swagger:generate \
    && php-fpm --nodaemonize

# KAFKA

# DEPENDENCIES
# RUN apt-get update --fix-missing \
#     && apt-get install -y python3 \
#     && rm -rf /var/lib/apt/lists/* \
#     && ln -s /usr/bin/python3 /usr/bin/python

# RUN git clone --depth 1 https://github.com/edenhill/librdkafka.git \
#     && ( \
#         cd librdkafka \
#         && ./configure \
#         && make \
#         && make install \
#     ) \
#     && pecl install rdkafka \
#     && echo "extension=rdkafka.so" > /usr/local/etc/php/conf.d/rdkafka.ini
