# =============================================================================
# Cmstack-Laravel - LOCAL DEVELOPMENT image only.
#
# This Dockerfile is a developer convenience for running the app locally via
# docker compose. It is NOT used in production. Production deploys to Hostinger
# (traditional PHP-FPM shared hosting) with `composer install --no-dev` and
# pre-built Vite assets. Nothing here is a runtime dependency of the app.
#
# For local dev the application code is bind-mounted (see docker-compose.yml),
# so we deliberately do NOT COPY the app code or any secrets into the image.
# =============================================================================

# ---- Composer binary (used to copy the composer executable into the app image)
FROM composer:2 AS composer

# ---- Application image -------------------------------------------------------
FROM php:8.3-fpm AS app

# System dependencies required to build PHP extensions used by the app.
#  - libmagickwand-dev : imagick (image manipulation / file manager)
#  - libzip-dev        : zip
#  - libpng/jpeg/freetype/webp : gd
#  - libicu-dev        : intl
#  - libonig-dev       : mbstring deps
#  - unzip/git         : composer package installs
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libmagickwand-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libwebp-dev \
        libicu-dev \
        libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Configure + install core PHP extensions.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        gd \
        zip \
        bcmath \
        exif \
        intl \
        sockets

# Install the imagick PECL extension.
# Pin a stable release that builds against PHP 8.3 reliably.
RUN pecl install imagick \
    && docker-php-ext-enable imagick

# Bring in the Composer binary from the official image.
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Create a non-root user matching the typical host UID/GID so bind-mounted
# files keep sensible ownership. Override UID/GID at build time if needed.
ARG UID=1000
ARG GID=1000
RUN groupadd -g ${GID} appuser \
    && useradd -u ${UID} -g appuser -m -s /bin/bash appuser

WORKDIR /var/www/html

# Run php-fpm as the non-root user.
USER appuser

EXPOSE 9000

CMD ["php-fpm"]
