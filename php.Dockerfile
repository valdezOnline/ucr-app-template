FROM php:8.5.12-cli

# Install system dependencies and PHP extensions needed by the app.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    netcat-openbsd \
    git \
    unzip \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libwebp-dev \
    libxpm-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP drivers and image support required by Laravel and QR code generation.
# pdo_mysql is required for Laravel database sessions/auth flows in this app.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm \
    && docker-php-ext-install pdo_mysql gd

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer