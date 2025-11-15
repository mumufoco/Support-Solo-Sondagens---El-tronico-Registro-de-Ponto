# Multi-stage Dockerfile for Brazilian Electronic Timesheet System
# Stage 1: PHP-FPM with CodeIgniter 4
FROM php:8.2-fpm-alpine AS php-app

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apk add --no-cache \
    bash \
    curl \
    git \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    mysql-client \
    nginx \
    supervisor

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        intl \
        mbstring \
        opcache \
        exif

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/writable

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Copy PHP configuration
COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Expose port
EXPOSE 9000

CMD ["php-fpm"]

# Stage 2: DeepFace Python API
FROM python:3.11-slim AS deepface-api

WORKDIR /app

# Install system dependencies
RUN apt-get update && apt-get install -y \
    gcc \
    g++ \
    cmake \
    libglib2.0-0 \
    libsm6 \
    libxext6 \
    libxrender-dev \
    libgomp1 \
    libgl1-mesa-glx \
    && rm -rf /var/lib/apt/lists/*

# Copy requirements and install Python packages
COPY deepface_api/requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copy DeepFace API
COPY deepface_api/ /app/

# Create directories
RUN mkdir -p /app/faces /app/logs

# Expose port
EXPOSE 5000

# Run Flask app
CMD ["python", "app.py"]

# Stage 3: Nginx Web Server
FROM nginx:alpine AS nginx-server

# Copy Nginx configuration
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy static assets
COPY public /var/www/html/public

# Expose port
EXPOSE 80 443

CMD ["nginx", "-g", "daemon off;"]
