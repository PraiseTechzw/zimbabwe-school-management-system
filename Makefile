COMPOSE := docker compose

.PHONY: help up down build logs shell test lint analyse security-audit audit assets npm-audit migrate fresh verify

help:
	@grep -E '^[a-zA-Z_-]+:' Makefile | cut -d: -f1

up: ## Start the Docker development environment
	$(COMPOSE) up -d --build

down: ## Stop containers (data volume is preserved)
	$(COMPOSE) down

build: ## Rebuild the application image
	$(COMPOSE) build app

logs: ## Follow container logs
	$(COMPOSE) logs -f

shell: ## Open a shell in the application container
	$(COMPOSE) exec app sh

migrate: ## Run database migrations inside the container
	$(COMPOSE) exec app php artisan migrate

fresh: ## Recreate the development database (destructive)
	$(COMPOSE) exec app php artisan migrate:fresh

test: ## Run the Pest suite
	$(COMPOSE) exec -T app php artisan test

lint: ## Check PHP formatting with Pint
	$(COMPOSE) exec -T app ./vendor/bin/pint --test

analyse: ## Run PHPStan with Larastan
	$(COMPOSE) exec -T app ./vendor/bin/phpstan analyse --no-progress --memory-limit=1G

security-audit: ## Audit installed Composer dependencies
	$(COMPOSE) exec -T app composer audit --locked

audit: security-audit ## Alias for security-audit

assets: ## Install npm dependencies and build assets (host Node 24)
	npm ci
	npm run build

npm-audit: ## Audit npm dependencies (host Node 24)
	npm audit --audit-level=high

verify: lint analyse security-audit test ## Run all Docker quality gates
