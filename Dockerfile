FROM php:8.2-cli

RUN docker-php-ext-install pdo pdo_mysql

COPY server/ /app/

WORKDIR /app

EXPOSE 10000

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-10000} -t /app"]
