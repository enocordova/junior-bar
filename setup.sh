#!/bin/bash
# =============================================================================
# setup.sh — Configuração automática de IP local para rede interna
# =============================================================================
# Uso: ./setup.sh
# Detecta o IP da rede local, atualiza o .env e regenera o certificado SSL.
# =============================================================================

set -e

# ---------- Cores para output ----------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m'

info()    { echo -e "${BLUE}[INFO]${NC} $1"; }
success() { echo -e "${GREEN}[OK]${NC}   $1"; }
warn()    { echo -e "${YELLOW}[AVISO]${NC} $1"; }
error()   { echo -e "${RED}[ERRO]${NC}  $1"; exit 1; }

echo -e "${BOLD}"
echo "╔══════════════════════════════════════════╗"
echo "║        JuniorBar — Setup de Rede         ║"
echo "╚══════════════════════════════════════════╝"
echo -e "${NC}"

# ---------- 1. Detectar IP local ----------
detect_local_ip() {
    local ip

    # Tenta via rota padrão (mais confiável em Linux/macOS)
    ip=$(ip route get 1.1.1.1 2>/dev/null | awk '/src/ {print $7; exit}')

    # Fallback: primeira interface não-loopback
    if [[ -z "$ip" ]]; then
        ip=$(hostname -I 2>/dev/null | awk '{print $1}')
    fi

    # Fallback macOS
    if [[ -z "$ip" ]]; then
        ip=$(ifconfig 2>/dev/null | awk '/inet / && !/127.0.0.1/ {print $2; exit}')
    fi

    echo "$ip"
}

LOCAL_IP=$(detect_local_ip)

if [[ -z "$LOCAL_IP" ]]; then
    error "Não foi possível detectar o IP local. Verifique sua conexão de rede."
fi

info "IP local detectado: ${BOLD}$LOCAL_IP${NC}"

# ---------- 2. Confirmar IP e portas com o usuário ----------

# Ler portas actuais do .env (se existir), ou usar defaults
HTTPS_PORT_CURRENT=$(grep -E '^HTTPS_PORT=' .env 2>/dev/null | cut -d'=' -f2)
HTTP_PORT_CURRENT=$(grep -E '^HTTP_PORT=' .env 2>/dev/null | cut -d'=' -f2)
HTTPS_PORT="${HTTPS_PORT_CURRENT:-8443}"
HTTP_PORT="${HTTP_PORT_CURRENT:-8080}"

APP_URL="https://${LOCAL_IP}:${HTTPS_PORT}"
SOCKET_URL="https://${LOCAL_IP}:${HTTPS_PORT}"

echo ""
echo -e "  ${BOLD}IP local${NC}         → ${GREEN}$LOCAL_IP${NC}"
echo -e "  ${BOLD}Porta HTTPS${NC}      → ${GREEN}$HTTPS_PORT${NC}  (acesso: https://IP:${HTTPS_PORT})"
echo -e "  ${BOLD}VITE_SOCKET_URL${NC}  → ${GREEN}$SOCKET_URL${NC}"
echo ""
read -rp "$(echo -e "Confirmar? [S/n] ")" confirm
confirm=${confirm:-S}
if [[ ! "$confirm" =~ ^[SsYy]$ ]]; then
    echo ""
    read -rp "Digite o IP desejado (Enter para manter ${LOCAL_IP}): " CUSTOM_IP
    [[ -n "$CUSTOM_IP" ]] && LOCAL_IP="$CUSTOM_IP"
    read -rp "Porta HTTPS (Enter para manter ${HTTPS_PORT}): " CUSTOM_HTTPS
    [[ -n "$CUSTOM_HTTPS" ]] && HTTPS_PORT="$CUSTOM_HTTPS"
    HTTP_PORT=$((HTTPS_PORT - 363))  # ex: 8443→8080, 9443→9080
    APP_URL="https://${LOCAL_IP}:${HTTPS_PORT}"
    SOCKET_URL="https://${LOCAL_IP}:${HTTPS_PORT}"
fi

echo ""

# ---------- 3. Criar .env se não existir ----------
if [[ ! -f ".env" ]]; then
    if [[ -f ".env.example" ]]; then
        cp .env.example .env
        success ".env criado a partir do .env.example"
    else
        error "Arquivo .env não encontrado e .env.example também não existe."
    fi
fi

# ---------- 4. Configurar modo de produção ----------
sed -i "s|^APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" .env

# ---------- 5. Gerar BROADCAST_SECRET se ainda for o placeholder ----------
CURRENT_SECRET=$(grep -E '^BROADCAST_SECRET=' .env | cut -d'=' -f2)
if [[ -z "$CURRENT_SECRET" || "$CURRENT_SECRET" == "gerar-um-segredo-forte-aqui" ]]; then
    NEW_SECRET=$(openssl rand -hex 32)
    sed -i "s|^BROADCAST_SECRET=.*|BROADCAST_SECRET=${NEW_SECRET}|" .env
    success "BROADCAST_SECRET gerado automaticamente"
fi

# ---------- 6. Gerar APP_KEY se vazio ----------
CURRENT_KEY=$(grep -E '^APP_KEY=' .env | cut -d'=' -f2)
if [[ -z "$CURRENT_KEY" ]]; then
    NEW_KEY="base64:$(openssl rand -base64 32)"
    sed -i "s|^APP_KEY=.*|APP_KEY=${NEW_KEY}|" .env
    success "APP_KEY gerada automaticamente"
fi

# ---------- 7. Gerar credenciais dos usuários (só se ainda forem placeholder) ----------
upsert_env() {
    local key="$1" val="$2"
    if grep -qE "^${key}=" .env; then
        local cur
        cur=$(grep -E "^${key}=" .env | cut -d'=' -f2)
        if [[ -z "$cur" || "$cur" == "mudar-em-producao" ]]; then
            sed -i "s|^${key}=.*|${key}=${val}|" .env
        fi
    else
        [[ -n "$(tail -c1 .env)" ]] && echo "" >> .env
        echo "${key}=${val}" >> .env
    fi
}

ADMIN_PASS_CURRENT=$(grep -E '^SEED_ADMIN_PASSWORD=' .env 2>/dev/null | cut -d'=' -f2)
if [[ -z "$ADMIN_PASS_CURRENT" || "$ADMIN_PASS_CURRENT" == "mudar-em-producao" ]]; then
    ADMIN_PASS=$(openssl rand -base64 12 | tr -d '/+=' | head -c 12)
    GARCOM_PASS=$(openssl rand -base64 12 | tr -d '/+=' | head -c 12)
    COZINHA_PASS=$(openssl rand -base64 12 | tr -d '/+=' | head -c 12)
    upsert_env "SEED_ADMIN_EMAIL"      "admin"
    upsert_env "SEED_ADMIN_PASSWORD"   "$ADMIN_PASS"
    upsert_env "SEED_GARCOM_EMAIL"     "garcom"
    upsert_env "SEED_GARCOM_PASSWORD"  "$GARCOM_PASS"
    upsert_env "SEED_COZINHA_EMAIL"    "cozinha"
    upsert_env "SEED_COZINHA_PASSWORD" "$COZINHA_PASS"
    success "Credenciais dos usuários geradas automaticamente"
else
    ADMIN_PASS=$(grep -E '^SEED_ADMIN_PASSWORD=' .env | cut -d'=' -f2)
    GARCOM_PASS=$(grep -E '^SEED_GARCOM_PASSWORD=' .env | cut -d'=' -f2)
    COZINHA_PASS=$(grep -E '^SEED_COZINHA_PASSWORD=' .env | cut -d'=' -f2)
fi

# ---------- 6. Atualizar URLs no .env ----------
sed -i "s|^APP_URL=.*|APP_URL=${APP_URL}|" .env
sed -i "s|^VITE_SOCKET_URL=.*|VITE_SOCKET_URL=${SOCKET_URL}|" .env

# Atualizar portas
if grep -qE '^HTTPS_PORT=' .env; then
    sed -i "s|^HTTPS_PORT=.*|HTTPS_PORT=${HTTPS_PORT}|" .env
else
    [[ -n "$(tail -c1 .env)" ]] && echo "" >> .env
    echo "HTTPS_PORT=${HTTPS_PORT}" >> .env
fi
if grep -qE '^HTTP_PORT=' .env; then
    sed -i "s|^HTTP_PORT=.*|HTTP_PORT=${HTTP_PORT}|" .env
else
    echo "HTTP_PORT=${HTTP_PORT}" >> .env
fi

# Garantir que ALLOWED_ORIGIN está presente e atualizado
if grep -qE '^ALLOWED_ORIGIN=' .env; then
    sed -i "s|^ALLOWED_ORIGIN=.*|ALLOWED_ORIGIN=${APP_URL}|" .env
else
    # Garante newline antes de acrescentar ao final do arquivo
    [[ -n "$(tail -c1 .env)" ]] && echo "" >> .env
    echo "ALLOWED_ORIGIN=${APP_URL}" >> .env
fi

success ".env atualizado com IP ${LOCAL_IP}"

# ---------- 7. Regenerar certificado SSL com SAN do IP real ----------
SSL_DIR="docker/nginx/ssl"
mkdir -p "$SSL_DIR"

info "Gerando certificado SSL para IP ${LOCAL_IP}..."

# Arquivo de configuração temporário com Subject Alternative Name
SSL_CONF=$(mktemp /tmp/ssl-XXXXXX.cnf)
cat > "$SSL_CONF" <<EOF
[req]
default_bits       = 2048
prompt             = no
default_md         = sha256
distinguished_name = dn
x509_extensions    = v3_req

[dn]
C  = BR
ST = Local
L  = Local
O  = JuniorBar
CN = ${LOCAL_IP}

[v3_req]
subjectAltName = @alt_names
keyUsage       = digitalSignature, keyEncipherment
extendedKeyUsage = serverAuth

[alt_names]
IP.1  = ${LOCAL_IP}
IP.2  = 127.0.0.1
DNS.1 = localhost
EOF

openssl req -x509 -nodes -days 825 \
    -newkey rsa:2048 \
    -keyout "${SSL_DIR}/server.key" \
    -out    "${SSL_DIR}/server.crt" \
    -config "$SSL_CONF" 2>/dev/null

rm -f "$SSL_CONF"

success "Certificado SSL gerado para ${LOCAL_IP} (válido por 825 dias)"

# ---------- 8. Configurar banco de dados ----------
DB_CONN=$(grep -E '^DB_CONNECTION=' .env | cut -d'=' -f2)
if [[ "$DB_CONN" == "sqlite" || -z "$DB_CONN" ]]; then
    # Verifica se existe configuração de MySQL descomentada
    if grep -qE '^DB_HOST=' .env; then
        info "Banco de dados: MySQL configurado"
    else
        # Ativa MySQL comentando sqlite e descomentando mysql
        sed -i "s|^DB_CONNECTION=sqlite|DB_CONNECTION=mysql|" .env

        if ! grep -qE '^DB_HOST=' .env; then
            sed -i "s|^# DB_HOST=.*|DB_HOST=db|" .env
            sed -i "s|^# DB_PORT=.*|DB_PORT=3306|" .env
            sed -i "s|^# DB_DATABASE=.*|DB_DATABASE=juniorbar|" .env
            sed -i "s|^# DB_USERNAME=.*|DB_USERNAME=juniorbar|" .env

            DB_PASS=$(openssl rand -hex 16)
            sed -i "s|^# DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env
        fi
        success "Banco de dados configurado para MySQL (Docker)"
    fi
fi

# ---------- 9. Subir o sistema ----------
echo ""
read -rp "$(echo -e "${BOLD}Subir o sistema agora com Docker? [S/n]${NC} ")" start_docker
start_docker=${start_docker:-S}

if [[ "$start_docker" =~ ^[SsYy]$ ]]; then
    if ! command -v docker &>/dev/null; then
        error "Docker não encontrado. Instale o Docker Desktop e tente novamente."
    fi

    info "Iniciando containers (isso pode levar alguns minutos na primeira vez)..."
    docker compose up -d

    echo ""
    info "Aguardando sistema ficar pronto..."

    # Espera até 120s pelo Nginx responder com HTTP 200
    TIMEOUT=120
    ELAPSED=0
    HTTP_CODE=""
    until [[ "$HTTP_CODE" == "200" ]] || [[ $ELAPSED -ge $TIMEOUT ]]; do
        sleep 3
        ELAPSED=$((ELAPSED + 3))
        HTTP_CODE=$(curl -sk -o /dev/null -w "%{http_code}" "${APP_URL}/up" 2>/dev/null || echo "000")
        echo -n "."
    done
    echo ""

    if [[ "$HTTP_CODE" == "200" ]]; then
        success "Sistema no ar!"
    else
        warn "Sistema demorou mais que o esperado (HTTP ${HTTP_CODE}). Verifique com: docker compose logs"
    fi
fi

# ---------- 10. Resumo final ----------
echo ""
echo -e "${BOLD}╔══════════════════════════════════════════╗${NC}"
echo -e "${BOLD}║           Configuração concluída         ║${NC}"
echo -e "${BOLD}╚══════════════════════════════════════════╝${NC}"
echo ""
echo -e "  Acesse em qualquer dispositivo na mesma rede Wi-Fi:"
echo ""
echo -e "  ${BOLD}Painel admin:${NC}  ${GREEN}${APP_URL}/admin${NC}"
echo -e "  ${BOLD}Garçom:${NC}        ${GREEN}${APP_URL}/garcom${NC}"
echo -e "  ${BOLD}Cozinha (KDS):${NC} ${GREEN}${APP_URL}/cozinha${NC}"
echo -e "  ${BOLD}Gerente:${NC}       ${GREEN}${APP_URL}/gerente${NC}"
echo ""
echo -e "  ${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "  ${BOLD}Credenciais de acesso${NC}"
echo -e "  ${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "  ${BOLD}Admin/Gerente${NC}  login: admin    senha: ${YELLOW}${ADMIN_PASS}${NC}"
echo -e "  ${BOLD}Garçom${NC}         login: garcom   senha: ${YELLOW}${GARCOM_PASS}${NC}"
echo -e "  ${BOLD}Cozinha${NC}        login: cozinha  senha: ${YELLOW}${COZINHA_PASS}${NC}"
echo ""
echo -e "  ${YELLOW}Guarde essas senhas!${NC} Elas ficam também salvas no arquivo .env"
echo ""
echo -e "  ${YELLOW}Aviso:${NC} O navegador vai exibir alerta de certificado auto-assinado."
echo -e "  Clique em \"Avançado\" → \"Continuar para o site\"."
echo ""
echo -e "  Se o IP da rede mudar, basta rodar ${BOLD}./setup.sh${NC} novamente."
echo ""
