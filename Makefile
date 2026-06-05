DOCKER_COMPOSE = docker compose
PHP_CONT = php
EXEC = $(DOCKER_COMPOSE) exec $(PHP_CONT)
CONSOLE = $(EXEC) php bin/console

.DEFAULT_GOAL := help

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

#BRANCHES
datatable: ## go to datatable branch. 
	git switch datatable-backend-paginated
	$(MAKE) build-assets

messages: ## go to symfony messages branch. 
	git switch symfony-messages
# 	$(MAKE) build-assets

messages-with-workflow: 
	git switch symfony-messages-workflow

hub: ## go to the hub branch
	git switch hub

hub-recreate: ## go to hub branch and recreate database
	$(MAKE) hub
	$(MAKE) build-assets
	$(MAKE) clear-cache
	$(MAKE) db-recreate
	$(MAKE) db-fixtures

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

init: ## Init the project
	$(EXEC) composer install
	$(MAKE) db-init
	$(MAKE) init-assets

#Assets
init-assets: ## Init the assets
	$(EXEC) php bin/console assets:install --symlink public
	$(EXEC) npm install
	$(EXEC) npm run build

build-assets: ## Build the assets
	$(EXEC) npm run build

watch-assets: ## Watch the assets
	$(EXEC) npm run watch

#Database
db-generate-migration: ## Generate a migration
	$(CONSOLE) make:migration

db-migrate: ## Apply the migration
	$(CONSOLE) doctrine:migrations:migrate

db-drop: ## Drop the database
	$(CONSOLE) doctrine:database:drop --force

db-create: ## Create the database
	$(CONSOLE) doctrine:database:create --if-not-exists

db-fixtures: ## Load fixtures
	$(CONSOLE) doctrine:fixtures:load --append

db-init: ## Init the database
	$(MAKE) db-create
	$(MAKE) db-migrate
	$(MAKE) db-fixtures

db-recreate: ## Recreate the database
	$(MAKE) db-drop
	$(MAKE) db-init

# Tests
test-db-init: ## Init the test database
	$(CONSOLE) doctrine:database:drop --env=test --force --if-exists
	$(CONSOLE) doctrine:database:create --env=test --if-not-exists
	$(CONSOLE) doctrine:migrations:migrate --env=test --no-interaction

test: ## Run the tests
	$(MAKE) test-db-init
	$(EXEC) php vendor/bin/phpunit

#cache : 
clear-cache: ## Clear the cache
	$(EXEC) php bin/console cache:pool:clear --all

