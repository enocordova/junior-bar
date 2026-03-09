#!/bin/bash
# =============================================================================
# entrypoint.sh — Inicialização do container PHP/Laravel
# =============================================================================
# Executado toda vez que o container sobe. Garante que:
#  - Dependências do Composer estão instaladas
#  - O banco de dados está pronto antes de prosseguir
#  - Migrations rodam automaticamente (pula as já executadas)
#  - Seed roda apenas na primeira vez (tabela users vazia)
#  - Cache de config/rotas/views está aquecido
#  - Permissões de storage estão corretas
# =============================================================================

set -e

log() { echo "[entrypoint] $1"; }

# ---------- 1. Instalar dependências se vendor não existir ----------
if [ ! -d "/var/www/vendor" ]; then
    log "Instalando dependências do Composer..."
    composer install --no-dev --optimize-autoloader --no-interaction --quiet
fi

# ---------- 2. Permissões de storage e bootstrap/cache ----------
log "Ajustando permissões..."
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# ---------- 3. Aguardar o banco de dados estar pronto ----------
log "Aguardando banco de dados..."

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USERNAME:-root}"
DB_PASS="${DB_PASSWORD}"
DB_NAME="${DB_DATABASE:-juniorbar}"

MAX_TRIES=60
TRIES=0

until php -r "
try {
    \$pdo = new PDO(
        'mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_NAME}',
        '${DB_USER}',
        '${DB_PASS}'
    );
    exit(0);
} catch (Exception \$e) {
    exit(1);
}
" 2>/dev/null; do
    TRIES=$((TRIES + 1))
    if [ $TRIES -ge $MAX_TRIES ]; then
        echo "[entrypoint] ERRO: Banco de dados não respondeu após ${MAX_TRIES} tentativas."
        exit 1
    fi
    log "Tentativa ${TRIES}/${MAX_TRIES} — aguardando MySQL..."
    sleep 5
done

log "Banco de dados pronto."

# ---------- 4. Rodar migrations (seguro executar sempre) ----------
log "Rodando migrations..."
php artisan migrate --force --no-interaction

# ---------- 5. Seed apenas na primeira instalação ----------
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | grep -E '^[0-9]+$' | tail -1)

if [ "${USER_COUNT:-0}" = "0" ]; then
    log "Banco vazio — populando com dados iniciais..."
    php artisan db:seed --force --no-interaction
    log "Seed concluído."
else
    log "Banco já populado (${USER_COUNT} usuário(s)) — pulando seed."
fi

# ---------- 6. Aquecer cache de configuração ----------
log "Aquecendo cache..."
php artisan config:cache  --no-interaction 2>/dev/null || true
php artisan route:cache   --no-interaction 2>/dev/null || true
php artisan view:cache    --no-interaction 2>/dev/null || true

log "Sistema pronto. Iniciando PHP-FPM..."

# ---------- 7. Iniciar PHP-FPM ----------
exec php-fpm
