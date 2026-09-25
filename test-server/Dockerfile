FROM php:8.4-cli

RUN docker-php-ext-install pdo_mysql

WORKDIR /app
COPY . /app

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app/public", "/app/public/router.php"]
