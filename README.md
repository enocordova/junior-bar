# JuniorBar — Sistema de Pedidos e KDS

Sistema de gerenciamento de pedidos em tempo real para restaurantes e bares.
Roda completamente na rede local do estabelecimento, **sem necessidade de internet**.

---

## O que o sistema oferece

| Tela | Endereço | Para quem |
|---|---|---|
| **Painel Admin** | `/admin` | Configuração geral do sistema |
| **Garçom** | `/garcom` | Registrar pedidos pelas mesas |
| **Cozinha (KDS)** | `/cozinha` | Ver e confirmar pedidos em tempo real |
| **Gerente** | `/gerente` | Acompanhar movimento e relatórios |

---

## Requisitos

Apenas um computador (pode ser um mini-PC) com:

- **Docker Desktop** instalado → [docker.com/get-started](https://www.docker.com/get-started/)
- **`make`** instalado (usado para todos os comandos do dia a dia)
- **`git`** instalado (para clonar o repositório)
- **`openssl`** instalado (usado pelo `setup.sh` para gerar chaves e certificados)
- Conectado na rede Wi-Fi ou cabo do estabelecimento
- ~2 GB de memória RAM disponível

Os demais dispositivos (celulares, tablets, TVs) precisam apenas de um **navegador** e estar na mesma rede.

### Linux (Ubuntu/Debian)

```bash
sudo apt update && sudo apt install -y make git openssl
```

### macOS

```bash
# make e git já vêm com as ferramentas de linha de comando do Xcode
xcode-select --install
# openssl via Homebrew (se necessário)
brew install openssl
```

### Windows

No Windows, o `setup.sh` precisa rodar dentro do **WSL2** (Windows Subsystem for Linux). O Docker Desktop para Windows já usa WSL2 internamente.

1. Instale o WSL2: abra o PowerShell como Administrador e rode `wsl --install`
2. Reinicie o computador
3. Instale o **Docker Desktop** → [docker.com/get-started](https://www.docker.com/get-started/)
   - Durante a instalação, certifica-te que a opção **"Use WSL 2 instead of Hyper-V"** está marcada
   - Após instalar, abre o Docker Desktop e aguarda ficar ativo (ícone na barra de tarefas)
4. Abra o terminal **Ubuntu** (instalado junto com WSL2)
5. Instale as ferramentas necessárias: `sudo apt update && sudo apt install -y make git openssl`
6. Clone e configure o sistema:
   ```bash
   git clone https://github.com/enocordova/junior-bar.git juniorbar
   cd juniorbar
   ./setup.sh
   ```

---

## Instalação

### 1. Baixar o sistema

```bash
git clone https://github.com/enocordova/junior-bar.git juniorbar
cd juniorbar
```

### 2. Configurar e subir

```bash
./setup.sh
```

O script vai:
- Detectar o IP do computador na rede local automaticamente
- Gerar as chaves de segurança
- Gerar o certificado SSL para conexão segura
- Gerar as senhas de acesso para cada perfil de usuário
- Perguntar se deseja subir o sistema imediatamente
- Aguardar o sistema ficar pronto e exibir as credenciais de acesso

### 3. Acessar

Após o setup, acesse no navegador de qualquer dispositivo da rede:

```
https://192.168.X.X:8443/login
```

> O endereço exato e as credenciais serão exibidos pelo script ao final.

**Aviso de certificado:** O navegador vai exibir um alerta de segurança por ser um certificado auto-assinado. Clique em **"Avançado"** → **"Continuar para o site"**. Isso é normal e esperado em redes locais.

### Credenciais iniciais

O script gera senhas únicas e aleatórias na primeira instalação e as exibe no terminal. Elas ficam salvas no arquivo `.env`:

| Perfil | Login | Senha |
|---|---|---|
| Admin / Gerente | `admin` | *(gerada pelo setup.sh)* |
| Garçom | `garcom` | *(gerada pelo setup.sh)* |
| Cozinha | `cozinha` | *(gerada pelo setup.sh)* |

> As senhas podem ser alteradas a qualquer momento pelo painel admin.

---

## Uso diário

> **Regra de ouro:** usa sempre `make <comando>` em vez de `docker exec ...` diretamente.
> Isto garante que os comandos correm dentro do container correto e evita problemas de permissões.

### Verificar se está tudo a correr

```bash
make ps
```

Deves ver 4 containers `Up`: `juniorbar_app`, `juniorbar_nginx`, `juniorbar_db`, `juniorbar_socket`.

### Ligar o sistema (após reboot do PC)

```bash
make up
```

> Se o Docker Desktop estiver configurado para iniciar com o Windows/macOS, o sistema sobe automaticamente com o computador.

### Desligar o sistema

```bash
make down
```

### Aceder no navegador

Use o IP exibido pelo `setup.sh` no final da instalação:

```
https://<IP-DO-SERVIDOR>:8443/login
```

Exemplos típicos:

| Rede | URL |
|---|---|
| Hotspot / cabo direto | `https://172.20.10.X:8443/login` |
| Wi-Fi do estabelecimento | `https://192.168.X.X:8443/login` |

> O aviso "Not secure" é normal — o certificado é autoassinado. Clica em **Avançado → Continuar**.

---

## Comandos do dia a dia (Makefile)

```bash
make help          # Lista todos os comandos disponíveis

# Containers
make up            # Inicia os containers
make down          # Para os containers
make restart       # Reinicia os containers
make rebuild       # Reconstrói a imagem Docker e inicia (usar após mudar Dockerfile)
make logs          # Ver logs em tempo real (todos)
make logs-app      # Ver logs da app PHP em tempo real
make logs-nginx    # Ver logs do Nginx em tempo real
make ps            # Estado dos containers

# Base de dados e cache
make cache         # Reconstrói cache (config + route + view)
make cache-clear   # Limpa todo o cache
make migrate       # Corre as migrations
make migrate-fresh # ⚠️  Apaga TUDO e recria do zero (perde dados!)
make seed          # Corre os seeders (cria utilizadores iniciais)

# Utilitários
make shell         # Abre terminal dentro do container da app
make shell-db      # Abre o MySQL dentro do container
make fix-perms     # Corrige permissões (se der erro 500 de ficheiros)
make tinker        # Abre o REPL interativo do Laravel
make queue         # Processa a fila de jobs manualmente

make artisan CMD="route:list"   # Qualquer comando artisan
```

---

## Após editar código

```bash
make cache-clear
# Recarrega a página — não precisa reiniciar containers
```

## Após editar o `.env`

```bash
make restart
```

## Atualizar o sistema (nova versão via git)

```bash
make deploy
```

O comando `deploy` faz automaticamente: `git pull` → `composer install` → reconstrução do cache → migrations → correção de permissões.

---

## Se o IP da rede mudar

O IP pode mudar se ligares a outro Wi-Fi ou reiniciares o router. Para corrigir, basta correr o script novamente:

```bash
cd juniorbar && ./setup.sh
```

O script faz **tudo automaticamente**:
- Deteta o novo IP da rede
- Atualiza o `.env` (`APP_URL`, `VITE_SOCKET_URL`, `ALLOWED_ORIGIN`)
- Regenera o certificado SSL com o novo IP
- Reinicia os containers

> Se tiveres várias interfaces de rede e o IP detetado não for o correto, o script pergunta e podes escrever o IP manualmente.

---

## Backup dos dados

Os dados ficam no volume Docker `dbdata` e **persistem entre reinicializações**. Para backup manual:

```bash
# Fazer backup
docker exec juniorbar_db mysqldump -u junior -p restaurante > backup-$(date +%Y%m%d).sql

# Restaurar backup
docker exec -i juniorbar_db mysql -u junior -p restaurante < backup-YYYYMMDD.sql
```

Será pedida a senha do banco (definida no `.env` como `DB_PASSWORD`).

---

## Solução de problemas

**O sistema não abre no navegador**
```bash
make ps            # Ver estado dos containers
make logs-nginx    # Ver erros do servidor web
make logs-app      # Ver erros do Laravel
```

**Erro 500 genérico**
```bash
make cache-clear   # Limpa cache
make fix-perms     # Corrige permissões (causa mais comum)
```

**Ver erros em tempo real**
```bash
make logs-app
```

**Pedidos não aparecem na cozinha em tempo real**
```bash
make logs          # Ver logs do socket server
```

**Reiniciar tudo do zero** ⚠️ apaga todos os dados
```bash
make down
docker compose down -v
./setup.sh
```

---

## Arquitetura

```
Dispositivos na rede (celular, tablet, TV)
              ↓  Wi-Fi / Cabo
         Nginx :8443  (HTTPS)
           ↙           ↘
     Laravel/PHP    WebSocket (Socket.io)
          ↓
       MySQL DB
```

Toda comunicação é interna. Nenhum dado sai da rede do estabelecimento.
