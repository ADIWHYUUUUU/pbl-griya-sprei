FROM dunglas/frankenphp:1-php8.3-bookworm

WORKDIR /app
RUN install-php-extensions pdo_pgsql curl

COPY . /app
COPY Caddyfile /etc/caddy/Caddyfile

ENV PORT=80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]