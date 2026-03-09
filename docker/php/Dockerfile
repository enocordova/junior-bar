# =============================================================================
# Stage 1: Compilar assets frontend (Vite + Tailwind)
# =============================================================================
FROM node:18-alpine AS frontend-builder

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources/ ./resources/

RUN npm run build

# =============================================================================
# Stage 2: Aplicação PHP
# =============================================================================
FROM php:8.3-fpm

# 1. Instalar dependências do sistema
# Adicionámos libzip-dev e libicu-dev para suportar zip e intl
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libsocket++-dev \
    libzip-dev \
    libicu-dev

# 2. Limpar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# 3. Configurar e Instalar extensões do PHP
# Adicionámos 'zip' e 'intl' na lista
RUN docker-php-ext-configure intl
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd sockets zip intl

# 4. Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Corrigir erro de "Dubious Ownership" do Git
RUN git config --global --add safe.directory /var/www

# 6. Definir diretório
WORKDIR /var/www

# 7. Permissões
RUN chown -R www-data:www-data /var/www

# 8. Copiar assets compilados pelo stage frontend
COPY --from=frontend-builder /app/public/build /var/www/public/build

# 9. Entrypoint (migrations + seed + cache na inicialização)
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]