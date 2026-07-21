FROM php:8.3-cli-alpine

RUN apk add --no-cache sqlite libzip-dev icu-dev oniguruma-dev \
 && docker-php-ext-install intl zip \
 && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload -o && cp .env.example .env

# Coolify-friendly boot:
#  - APP_KEY: use the env var if set in the platform; otherwise generate once.
#  - Seed only on first boot (fresh volume/container). Reset manually with:
#      php artisan migrate:fresh --seed --force
#  - Healthcheck endpoint: /up (Laravel built-in).
EXPOSE 8000
CMD sh -c '\
  if [ -z "$APP_KEY" ]; then php artisan key:generate --force; fi; \
  if [ ! -s database/database.sqlite ]; then touch database/database.sqlite; FIRST_BOOT=1; fi; \
  php artisan migrate --force; \
  if [ -n "$FIRST_BOOT" ]; then php artisan db:seed --force; fi; \
  exec php artisan serve --host=0.0.0.0 --port=${PORT:-8000}'
