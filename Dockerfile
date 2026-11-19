FROM php:8.3-cli

# Ekstensi untuk koneksi PostgreSQL (Supabase)
RUN apt-get update \
    && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . .

# Render menyediakan variabel PORT
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8000} -t public"]