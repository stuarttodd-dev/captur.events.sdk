FROM php:8.3-cli

WORKDIR /src

RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    $PHPIZE_DEPS \
    && pecl install pcov \
    && docker-php-ext-enable pcov \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY . .
RUN composer install --working-dir=/src --no-interaction

CMD ["php", "-a"]
