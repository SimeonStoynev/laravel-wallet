FROM php:8.4-fpm

WORKDIR /var/www/html

# Arguments
ARG user=www-data
ARG uid=1000

# Install system dependencies including curl for Node.js setup
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    supervisor

# Install Node.js 20.x from NodeSource
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Install PHP extensions required for Laravel 12
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Set proper user permissions
RUN groupmod -o -g ${uid} www-data && \
    usermod -o -u ${uid} -g www-data www-data

USER ${user}

EXPOSE 9000
CMD ["php-fpm"]
