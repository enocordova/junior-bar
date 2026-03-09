## =============================================================================
## Junior BAR — Makefile
## Uso: make <comando>
## =============================================================================

APP = juniorbar_app
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
	docker exec $(APP) php artisan $(CMD)

cache: ## Reconstrói todo o cache (config + route + view)
	docker exec $(APP) php artisan config:cache
	docker exec $(APP) php artisan route:cache
	docker exec $(APP) php artisan view:cache

cache-clear: ## Limpa todo o cache
	docker exec $(APP) php artisan config:clear
	docker exec $(APP) php artisan route:clear
	docker exec $(APP) php artisan view:clear
	docker exec $(APP) php artisan cache:clear

migrate: ## Corre as migrations
	docker exec $(APP) php artisan migrate

migrate-fresh: ## Apaga tudo e re-cria (CUIDADO: apaga dados!)
	docker exec $(APP) php artisan migrate:fresh --seed

seed: ## Corre os seeders
	docker exec $(APP) php artisan db:seed

tinker: ## Abre o tinker interativo
	docker exec -it $(APP) php artisan tinker

queue: ## Processa a fila de jobs
	docker exec $(APP) php artisan queue:work

# -----------------------------------------------------------------------------
# PERMISSÕES (fix rápido se algo correr mal)
# -----------------------------------------------------------------------------
fix-perms: ## Corrige permissões do storage e bootstrap/cache
	docker exec -u root $(APP) chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache
	@echo "Permissões corrigidas."

# -----------------------------------------------------------------------------
# SHELL
# -----------------------------------------------------------------------------
shell: ## Abre shell dentro do container da app
	docker exec -it $(APP) bash

shell-db: ## Abre o MySQL dentro do container
	docker exec -it juniorbar_db mysql -u junior -p restaurante

# -----------------------------------------------------------------------------
# DEPLOY / ATUALIZAÇÃO
# -----------------------------------------------------------------------------
deploy: ## Atualiza o projeto sem downtime
	git pull
	docker exec $(APP) composer install --no-dev --optimize-autoloader
	$(MAKE) cache
	docker exec $(APP) php artisan migrate --force
	$(MAKE) fix-perms
	@echo "Deploy concluído."
