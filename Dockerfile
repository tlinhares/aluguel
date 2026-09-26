# AluguelPRO — deploy Coolify (Build Pack: Dockerfile)
FROM php:8.2-apache
RUN docker-php-ext-install mysqli && a2enmod rewrite headers
COPY . /var/www/html/
# roteia tudo que não é arquivo real para index.php (BASE_URL vazio na nuvem)
RUN printf '<Directory /var/www/html>\n  AllowOverride All\n  Require all granted\n</Directory>\n' > /etc/apache2/conf-available/zz-guea.conf && a2enconf zz-guea
EXPOSE 80
