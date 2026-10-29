# syntax=docker/dockerfile:1
# PHP for the servers: FrankenPHP (Caddy + PHP). The code is not copied in, it is mounted
# from the clone (compose.prod.yaml, compose.preprod.yaml) and set up by `castor deploy`.
FROM dunglas/frankenphp:1-php8.4

WORKDIR /app

# pcntl: the Messenger worker catches the SIGTERM of a restart and ends its current message.
RUN install-php-extensions @composer apcu imagick intl opcache pcntl pdo_pgsql zip \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Written here rather than copied: nothing from the clone reaches the build (.dockerignore).
COPY <<EOF $PHP_INI_DIR/conf.d/app.ini
; Phone photos weigh several MB: App\Vich\UploadedImage limits the pixels, not the bytes.
upload_max_filesize = 20M
post_max_size = 25M
; The worker's --memory-limit (compose) stays below it, so Messenger stops between messages.
memory_limit = 256M
; Symfony's production settings.
opcache.memory_consumption = 256
opcache.max_accelerated_files = 20000
realpath_cache_size = 4096K
realpath_cache_ttl = 600
EOF

# "prod", or "preprod" (.env.preprod.local): any other name turns APP_DEBUG on and skips the
# when@prod / when@preprod blocks (composer.json, extra.runtime.prod_envs).
ENV APP_ENV=prod
