FROM php:8.4-fpm-alpine

# 1. Dépendances système et extensions PHP nécessaires pour Symfony
RUN apk add --no-cache \
    bash \
    icu-dev \
    libzip-dev \
    unzip \
    zip \
    npm \
    && docker-php-ext-install \
    intl \
    opcache \
    pdo_mysql \
    zip

# 2. Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 3. Gestion des permissions (évite les conflits de fichiers en dev)
ARG UID=1000
ARG GID=1000
RUN apk add --no-cache shadow && \
    usermod -u $UID www-data && \
    groupmod -g $GID www-data

USER www-data

EXPOSE 9000

CMD ["php-fpm"]