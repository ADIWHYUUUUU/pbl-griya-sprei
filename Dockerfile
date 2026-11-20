FROM dunglas/frankenphp:1-php8.4-alpine

# PostgreSQL, validasi teks, dan request API Supabase/Brevo.
RUN install-php-extensions pdo_pgsql mbstring curl \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /app
COPY . .
COPY Caddyfile /etc/caddy/Caddyfile

ENV PORT=80
EXPOSE 80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
