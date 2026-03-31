# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

**IMPORTANT: Always use Australian English spelling (e.g., "organisation" not "organization", "colour" not "color") in all code, documentation, and comments.**

## Overview

Serverless PHP 8.2 API on AWS Lambda (via Bref) for The Resilience Project (TRP). Integrates with Vtiger CRM to manage enquiries, registrations, confirmations, and resource ordering for Schools, Workplaces, and Early Years programs.

The repo also contains:
- `apps/conf-uploads/` — Python tool for batch importing conference leads into Vtiger CRM (see its own `CLAUDE.md`)
- `apps/dashboard/` — Next.js dashboard for monitoring Lambda functions and logs (see its own `CLAUDE.md`)

## Development Commands

```bash
# Makefile shortcuts (recommended)
make install    # composer install
make test       # Run all PHPUnit tests
make lint       # Check formatting (dry run)
make fix        # Apply formatting fixes
make analyse    # Run PHPStan static analysis
make check      # Run all checks: lint + analyse + test
make serve      # Local dev server on localhost:8000

# Run a single test file
vendor/bin/phpunit tests/SchoolAssigneeTest.php

# Run a single test method
vendor/bin/phpunit --filter test_method_name

# Deploy (requires AWS profile — always use make targets to track commit SHA)
export AWS_PROFILE=trp-integrations
make deploy                                # Entire stack (sets DEPLOYED_COMMIT automatically)
make deploy-function F=<function-name>     # Single function
serverless logs -f <function-name> -t      # Tail logs
serverless deploy list                     # List previous deployments
serverless rollback --timestamp <timestamp>  # Rollback to previous deployment
```

## CI Pipeline

GitHub Actions (`.github/workflows/ci.yml`) runs on PRs and pushes to `main`: PHP-CS-Fixer → PHPStan → PHPUnit.

## Testing

Tests live in `tests/` and use PHPUnit. The `tests/bootstrap.php` stubs logging functions, loads the Composer autoloader (for `ApiV2\` namespace), and loads v1 controller classes directly — avoiding `init.php` and any CRM/DB connections.

- **v1 tests** — cover pure logic via reflection (assignee routing, date calculations, scoring). Anything calling `post_request_to_vt()` or `$vtod` requires a real CRM connection and is not testable.
- **v2 tests** (`tests/ApiV2/`) — use `StubVtigerWebhookClient` (in-memory stub implementing `VtigerWebhookClientInterface`) to test handlers end-to-end without CRM connections. No reflection needed.

## Code Style

PSR-12 via PHP-CS-Fixer (`.php-cs-fixer.dist.php`). Rules: single quotes, short array syntax, trailing commas, no unused imports. A pre-commit hook auto-formats staged `.php` files and runs PHPStan before committing.

PHPStan runs at level 1 with a baseline (`phpstan-baseline.neon`). New code must not introduce additional errors.

## Architecture

### Two CRM Integration Paths

1. **VTAP Webhooks** (primary path for API endpoints): v1 uses `VTController::post_request_to_vt()` in `src/api/classes/base.php`; v2 uses `VtigerWebhookClient` in `src/api-v2/Infrastructure/`. Both send HTTP requests to Vtiger's webhook endpoints with token-based auth.

2. **Vtiger REST API** (used by Invoice/Potential/Event endpoints): `dhvt` class in `src/lib/class_dhvt.php` uses challenge-response session auth. Global instance `$vtod` is initialised in `src/init.php`. Methods: `retrieve()`, `create()`, `update()`, `query()`, `retrieveAllRelated()`, `addRelated()`.

### API v1 — Controller Pattern (`src/api/`)

**Endpoint file** → includes `utils.php`, `api_helpers.php`, `init.php` → instantiates controller by `service_type` → calls controller method → returns JSON via `send_response()`.

Service types: `School`, `Workplace`, `Early Years`, `Imperfects`, `General` (fallback).

**Controller hierarchy:**
- `VTController` (base) uses traits `ContactAndOrg` and `Deal` for shared CRM operations
- `SchoolVTController` / `ExistingSchoolVTController` — use traits: `Enquiry`, `Confirmation`, `Lead`, `Registration`, `OrderResources`, `AcceptDates`, `Assess`
- `WorkplaceVTController`, `EarlyYearsVTController`, `GeneralVTController` — each mix in relevant traits
- `ImperfectsVTController` extends `GeneralVTController` with enquiry type override

Traits live in `src/api/classes/traits/` and encapsulate business logic.

### API v2 — DDD-lite Architecture (`src/api-v2/`)

New endpoints use a domain-driven structure with PSR-4 autoloading under the `ApiV2\` namespace. Currently schools-only — the URL IS the service type (e.g., `/api/v2/schools/enquiry`). Non-school traffic stays on v1.

- **`endpoints/schools/`** — Thin HTTP handlers (CORS, method routing, error handling). Include `init.php` for logging.
- **`Application/Schools/`** — Use-case handlers (`SubmitEnquiryHandler`, `SubmitRegistrationHandler`, `SubmitPrizePackHandler`) plus `CustomerService` for shared capture/update flows.
- **`Domain/`** — Immutable value objects (`Contact`, `Organisation`, `Deal`, `Enquiry`) with `fromFormData()` factories, plus `AssigneeRules` for pure business logic (assignee routing by state, new-school detection).
- **`Infrastructure/`** — `VtigerWebhookClient` (cURL-based VTAP caller) implementing `VtigerWebhookClientInterface`. `RequestParser` for typed field extraction.
- **`Config/`** — `UserIds.php` (staff constants), `webhook_tokens.php` (endpoint auth tokens).

**v1 vs v2**: New school endpoints should use v2. The v1 controller/trait pattern remains for Workplace, Early Years, General, and existing school endpoints that haven't been migrated.

### Non-API Endpoints

Endpoints outside `src/api/` use the `$vtod` REST client directly rather than controllers:
- `src/Invoices/` — Invoice and shipment creation
- `src/Potentials/` — CRM deal operations
- `src/Events/` — Event invitations
- `src/Webhooks/` — WooCommerce order webhook

### Initialisation (`src/init.php`)

Every endpoint includes `init.php` which provides:
- `$vtod` — Vtiger REST client (always initialised)
- `get_db()` / `$dbh` — MySQL connection (lazy-loaded, call `get_db()` to ensure connection)
- Logging functions: `log_debug()`, `log_info()`, `log_warning()`, `log_error()`, `log_exception($e, $context)`
- `functions.php` — global helpers

### Request/Response Utilities (`src/api/utils.php`)

- `get_method()` — returns HTTP method
- `get_request_data()` — merges POST, JSON body, and GET params
- `send_response($response, $code)` — sends JSON response and exits

## Adding a New Endpoint

### v2 (preferred for new school endpoints)

1. Create domain VOs in `src/api-v2/Domain/` if needed (immutable, with `fromFormData()` factory)
2. Create a handler in `src/api-v2/Application/Schools/` that takes `VtigerWebhookClientInterface` and uses `CustomerService` for shared flows
3. Create endpoint file in `src/api-v2/endpoints/schools/`
4. Add function to `serverless.yml` under `# API v2 — Schools endpoints` with URL pattern `/api/v2/schools/<endpoint>`
5. **Always add**: unit tests in `tests/ApiV2/`, Postman requests in `postman/collections/v2/Schools/`, and update `docs/v2/schools.md`

### v1 (existing non-school endpoints)

1. Create endpoint PHP file (e.g., `src/api/new_endpoint.php`)
2. Include the three required files:
   ```php
   require dirname(__FILE__)."/utils.php";
   require dirname(__FILE__)."/api_helpers.php";
   require dirname(__FILE__)."/../init.php";
   ```
3. Implement logic using controller pattern or direct `$vtod` calls
4. Add function definition to `serverless.yml` with `${bref:layer.php-82-fpm}` layer

## Important Conventions

### Year Versioning
- **Current year: 2026** — deal/quote/invoice names include the year (e.g., "2026 School Partnership Program")
- Previous year endpoints exist for historical data (e.g., `order_resources_2026.php`)
- When creating new year versions, update `$deal_name`, `$quote_name`, `$invoice_name`, `$seip_name`, and `$previous_*` properties in controller classes

### Staff Assignee Constants
v1: base controller defines staff member constants (e.g., `MADDIE`, `LAURA`, `BRENDAN`) as Vtiger user IDs (`19xN` format).
v2: `ApiV2\Config\UserIds` class provides the same constants. Assignee routing lives in `ApiV2\Domain\AssigneeRules`.

### Configuration
- `src/config.php` contains credentials — **never commit with real values**
- Config provides `$local_config` (DB), `$vtod_config` (Vtiger), and mail settings

### Lambda Constraints
- 29-second timeout (API Gateway hard limit is 30s)
- No persistent filesystem — logs go to CloudWatch via `error_log()`
- `/tmp` for temporary storage (cleared between cold starts)
- Region: `ap-southeast-2`

### API Testing & Documentation
- Postman collections: `postman/collections/v1/` (56 requests) and `postman/collections/v2/Schools/` (8 requests)
- Endpoint docs: `docs/v1/` (v1 endpoints with Mermaid flowcharts) and `docs/v2/schools.md` (v2 schools endpoints)
- See `docs/v1/index.md` for v1 architecture overview
