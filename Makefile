.PHONY: install test lint fix analyse check serve deploy deploy-function seed teardown teardown-dry

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
	php -S localhost:8000 router.php

deploy: ## Deploy entire stack with commit tracking
	@if [ "$$AWS_PROFILE" != "trp-integrations" ]; then \
		echo "Error: AWS_PROFILE must be set to 'trp-integrations'"; \
		echo "Run: export AWS_PROFILE=trp-integrations"; \
		exit 1; \
	fi
	DEPLOYED_COMMIT=$$(git rev-parse --short HEAD) serverless deploy

deploy-function: ## Deploy single function (usage: make deploy-function F=enquiry)
	@if [ "$$AWS_PROFILE" != "trp-integrations" ]; then \
		echo "Error: AWS_PROFILE must be set to 'trp-integrations'"; \
		echo "Run: export AWS_PROFILE=trp-integrations"; \
		exit 1; \
	fi
	DEPLOYED_COMMIT=$$(git rev-parse --short HEAD) serverless deploy function -f $(F)

seed: ## Create test data in Vtiger CRM
	bash postman/setup_test_data.sh

teardown: ## Delete test data from Vtiger CRM
	bash postman/teardown_test_data.sh

teardown-dry: ## Preview test data teardown (no deletions)
	bash postman/teardown_test_data.sh --dry-run

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

.DEFAULT_GOAL := help
