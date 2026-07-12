# =============================================================================
# AgenticCms-Laravel - convenience targets for local development (Docker).
#
# The Docker stack is a LOCAL DEV convenience only; production deploys to
# Hostinger / a VPS without Docker (see README "Deployment").
#
#   make dev     one-shot bootstrap (= setup) then follow logs — the primary entry
#   make setup   first-time bootstrap: up + composer + key + migrate/seed + build
#   make up      start the Docker stack (detached)
#   make down    stop the stack (keeps the database volume)
#   make reset   wipe the DB volume and re-bootstrap from scratch
#   make fresh   rebuild the DB from scratch (migrate:fresh --seed)
#   make test    run the PHPUnit suite inside the container
#   make build   build front-end assets on the host (Vite)
#   make shell   open a shell in the app container
#   make logs    tail container logs
# =============================================================================

# Run artisan/composer inside the running app container.
DC      := docker compose
APP     := $(DC) exec -T app

# Host port published by this Docker stack (web). `make kill` releases the stack's own
# containers (so a re-`up` can rebind the port) and warns if a NON-Docker process is
# squatting the port — it never kills foreign processes for you.
HOST_PORTS := 8080

.DEFAULT_GOAL := help

.PHONY: help dev up down reset logs seed migrate test kill clean setup fresh build key link shell dusk

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

dev: setup ## One-shot local dev: bootstrap the stack, then follow container logs
	$(DC) logs -f

setup: kill ## First-time bootstrap: bring up Docker, install deps, migrate+seed, build assets
	@test -f .env || cp .env.example .env
	$(DC) up -d --build
	$(APP) composer install
	$(APP) php artisan key:generate
	$(APP) php artisan migrate --seed
	$(APP) php artisan storage:link || true
	npm install
	npm run build
	@echo ""
	@echo "  AgenticCms-Laravel is up:  http://localhost:8080"
	@echo "  Admin panel:      http://localhost:8080/agentic-cms-laravel-admin"
	@echo "  Login: admin / agentic-cmsadmin123"

up: kill ## Start the Docker stack
	$(DC) up -d

kill: ## Release this stack's host ports (down its own containers; warn on foreign holders)
	@$(DC) down --remove-orphans 2>/dev/null || true
	@for p in $(HOST_PORTS); do \
	  pid=$$(lsof -ti:$$p -sTCP:LISTEN 2>/dev/null); \
	  if [ -n "$$pid" ]; then \
	    echo "⚠ port $$p still held by a non-Docker process (PID $$pid) — free it with: kill $$pid"; \
	  fi; \
	done

down: ## Stop the Docker stack (keeps the DB volume)
	$(DC) down

reset: ## Wipe the DB volume and re-bootstrap from scratch
	$(DC) down -v
	$(MAKE) dev

fresh: ## Drop and rebuild the database, then reseed
	$(APP) php artisan migrate:fresh --seed

test: ## Run the test suite inside the container (in-memory SQLite)
	$(APP) php artisan test

dusk: ## Run Laravel Dusk browser/e2e tests (host Chrome -> dedicated dusk DB). Pass ARGS=... to filter.
	bash scripts/dusk.sh $(ARGS)

build: ## Build front-end assets on the host (Vite production build)
	npm run build

key: ## Generate the application key
	$(APP) php artisan key:generate

migrate: ## Run database migrations
	$(APP) php artisan migrate

seed: ## Run database seeders
	$(APP) php artisan db:seed

link: ## Create the public/storage symlink
	$(APP) php artisan storage:link

shell: ## Open a bash shell in the app container
	$(DC) exec app bash

logs: ## Tail container logs
	$(DC) logs -f

clean: ## Stop the stack AND remove the database volume (destroys data)
	$(DC) down -v
