FROM php:8.2-apache
RUN docker-php-ext-install pdo_mysql
COPY . /var/www/html/
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf && sed -i 's/:80/:${PORT}/' /etc/apache2/sites-available/000-default.conf
ENV PORT=8080
