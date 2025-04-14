# Use PHP 8.1 with Apache
FROM php:8.1-apache

# Enable Apache rewrite module
RUN a2enmod rewrite

# Install mysqli extension for MySQL
RUN docker-php-ext-install mysqli

# Copy files to Apache root
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Set folder permissions (optional)
RUN chown -R www-data:www-data /var/www/html

# Expose Apache port
EXPOSE 80
