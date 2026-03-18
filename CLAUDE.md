# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Serverless PHP 8.2 API on AWS Lambda (via Bref) for The Resilience Project (TRP). Integrates with Vtiger CRM to manage enquiries, registrations, confirmations, and resource ordering for Schools, Workplaces, and Early Years programs.

## Development Commands

```bash
# Install dependencies
composer install

# Deploy entire stack (requires AWS profile)
export AWS_PROFILE=trp-integrations
serverless deploy

# Deploy a single function
serverless deploy function -f <function-name>

# View function logs
serverless logs -f <function-name> -t

# Local testing (no serverless)
php -S localhost:8000 -t src/
```

## Architecture

### Two CRM Integration Paths

The codebase communicates with Vtiger CRM in two distinct ways:

1. **VTAP Webhooks** (primary path for API endpoints): `VTController::post_request_to_vt()` in `src/api/classes/base.php` sends HTTP requests to Vtiger's webhook endpoints with token-based auth. Tokens are defined as constants in the base class.

2. **Vtiger REST API** (used by Invoice/Potential/Event endpoints): `dhvt` class in `src/lib/class_dhvt.php` uses challenge-response session auth. Global instance `$vtod` is initialised in `src/init.php`. Methods: `retrieve()`, `create()`, `update()`, `query()`, `retrieveAllRelated()`, `addRelated()`.

### Controller Pattern

All API endpoints under `src/api/` follow this pattern:

**Endpoint file** → includes `utils.php`, `api_helpers.php`, `init.php` → instantiates controller by `service_type` → calls controller method → returns JSON via `send_response()`.

**Controller hierarchy:**
- `VTController` (base) uses traits `ContactAndOrg` and `Deal` for shared CRM operations
- `SchoolVTController` / `ExistingSchoolVTController` — use traits: `Enquiry`, `Confirmation`, `Lead`, `Registration`, `OrderResources`, `AcceptDates`, `Assess`
- `WorkplaceVTController`, `EarlyYearsVTController`, `GeneralVTController` — each mix in relevant traits

Traits live in `src/api/classes/traits/` and encapsulate business logic (enquiry submission, order processing, confirmation, etc.).

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

1. Create endpoint PHP file (e.g., `src/api/new_endpoint.php`)
2. Include the three required files:
   ```php
   require dirname(__FILE__)."/utils.php";
   require dirname(__FILE__)."/api_helpers.php";
   require dirname(__FILE__)."/../init.php";
   ```
3. Implement logic using controller pattern or direct `$vtod` calls
4. Add function definition to `serverless.yml` under `functions:` with `${bref:layer.php-82-fpm}` layer

## Important Conventions

### Year Versioning
- **Current year: 2026** — deal/quote/invoice names include the year (e.g., "2026 School Partnership Program")
- Previous year endpoints exist for historical data (e.g., `order_resources_2026.php`)
- When creating new year versions, update `$deal_name`, `$quote_name`, `$invoice_name`, `$seip_name`, and `$previous_*` properties in controller classes

### Staff Assignee Constants
The base controller defines staff member constants (e.g., `MADDIE`, `LAURA`, `BRENDAN`) as Vtiger user IDs (`19xN` format). Assignee routing logic varies by service type and state.

### Configuration
- `src/config.php` contains credentials — **never commit with real values**
- Config provides `$local_config` (DB), `$vtod_config` (Vtiger), and mail settings

### Lambda Constraints
- 29-second timeout (API Gateway hard limit is 30s)
- No persistent filesystem — logs go to CloudWatch via `error_log()`
- `/tmp` for temporary storage (cleared between cold starts)
- Region: `ap-southeast-2`
