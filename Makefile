.PHONY: install test lint fix analyse check serve

install: ## Install dependencies
	composer install

test: ## Run all tests
	vendor/bin/phpunit

lint: ## Check formatting (dry run)
	vendor/bin/php-cs-fixer fix --dry-run --diff

fix: ## Apply formatting fixes
	vendor/bin/php-cs-fixer fix

analyse: ## Run static analysis
	vendor/bin/phpstan analyse --memory-limit=512M

check: lint analyse test ## Run all checks (lint + analyse + test)

serve: ## Start local dev server
	php -S localhost:8000 -t src/

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

.DEFAULT_GOAL := help
