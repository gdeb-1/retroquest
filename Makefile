DOCKER_COMPOSE = docker compose
PHP_CONT = php
EXEC = $(DOCKER_COMPOSE) exec $(PHP_CONT)
CONSOLE = $(EXEC) php bin/console

.DEFAULT_GOAL := help

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

# Docker
up: ## Start the containers
	$(DOCKER_COMPOSE) up -d

down: ## Stop and remove the containers
	$(DOCKER_COMPOSE) down

stop: ## Stop the containers
	$(DOCKER_COMPOSE) stop

restart: ## Restart the containers
	$(DOCKER_COMPOSE) restart

sh: ## Access the PHP container shell
	$(EXEC) sh

#Assets
build-assets: ## Build the assets
	$(EXEC) npm run build

watch-assets: ## Watch the assets
	$(EXEC) npm run watch