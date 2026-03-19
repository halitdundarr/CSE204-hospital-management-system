FROM php:8.2-apache

# This project uses mysqli via includes/db_connect.php.
RUN docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /var/www/html

