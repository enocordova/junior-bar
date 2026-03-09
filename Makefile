## =============================================================================
## Junior BAR — Makefile
## Uso: make <comando>
## =============================================================================

DC  = docker compose

.DEFAULT_GOAL := help

# -----------------------------------------------------------------------------
# AJUDA
# -----------------------------------------------------------------------------
help: ## Mostra esta ajuda
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-20s\033[0m %s\n", $$1, $$2}'

# -----------------------------------------------------------------------------
# DOCKER
# -----------------------------------------------------------------------------
up: ## Inicia todos os containers
	$(DC) up -d

down: ## Para todos os containers
	$(DC) down

restart: ## Reinicia todos os containers
	$(DC) restart

rebuild: ## Reconstrói a imagem e inicia
	$(DC) up -d --build

logs: ## Mostra logs em tempo real
	$(DC) logs -f

logs-app: ## Logs apenas da app (PHP-FPM)
	$(DC) logs -f app

logs-nginx: ## Logs apenas do nginx
	$(DC) logs -f nginx

ps: ## Estado dos containers
	$(DC) ps

# -----------------------------------------------------------------------------
# ARTISAN (corre sempre dentro do container)
# -----------------------------------------------------------------------------
artisan: ## Executa artisan: make artisan CMD="route:list"
	$(DC) exec app php artisan $(CMD)

cache: ## Reconstrói todo o cache (config + route + view)
	$(DC) exec app php artisan config:cache
	$(DC) exec app php artisan route:cache
	$(DC) exec app php artisan view:cache

cache-clear: ## Limpa todo o cache
	$(DC) exec app php artisan config:clear
	$(DC) exec app php artisan route:clear
	$(DC) exec app php artisan view:clear
	$(DC) exec app php artisan cache:clear

migrate: ## Corre as migrations
	$(DC) exec app php artisan migrate

migrate-fresh: ## Apaga tudo e re-cria (CUIDADO: apaga dados!)
	$(DC) exec app php artisan migrate:fresh --seed

seed: ## Corre os seeders
	$(DC) exec app php artisan db:seed

tinker: ## Abre o tinker interativo
	$(DC) exec app php artisan tinker

queue: ## Processa a fila de jobs
	$(DC) exec app php artisan queue:work

# -----------------------------------------------------------------------------
# PERMISSÕES (fix rápido se algo correr mal)
# -----------------------------------------------------------------------------
fix-perms: ## Corrige permissões do storage e bootstrap/cache
	$(DC) exec -u root app chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
	@echo "Permissões corrigidas."

# -----------------------------------------------------------------------------
# SHELL
# -----------------------------------------------------------------------------
shell: ## Abre shell dentro do container da app
	$(DC) exec app bash

shell-db: ## Abre o MySQL dentro do container
	$(DC) exec db mysql -u $${DB_USERNAME} -p$${DB_PASSWORD} $${DB_DATABASE}

# -----------------------------------------------------------------------------
# DEPLOY / ATUALIZAÇÃO
# -----------------------------------------------------------------------------
deploy: ## Atualiza o projeto sem downtime
	git pull
	$(DC) exec app composer install --no-dev --optimize-autoloader
	$(MAKE) cache
	$(DC) exec app php artisan migrate --force
	$(MAKE) fix-perms
	@echo "Deploy concluído."
