# TRP API

A serverless PHP API for The Resilience Project (TRP) that integrates with Vtiger CRM to manage enquiries, registrations, confirmations, and resource ordering for Schools, Workplaces, and Early Years programs.

## Table of Contents

- [Features](#features)
- [Technology Stack](#technology-stack)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Deployment](#deployment)
- [Development](#development)
- [API Endpoints](#api-endpoints)
- [Architecture](#architecture)
- [Logging](#logging)
- [Contributing](#contributing)

## Features

- **Multi-Service Support**: Handles Schools, Workplaces, Early Years, and Imperfects programs
- **CRM Integration**: Full integration with Vtiger CRM for customer management
- **Serverless Architecture**: Deployed on AWS Lambda for automatic scaling and cost efficiency
- **RESTful API**: Clean HTTP API endpoints for all operations
- **Comprehensive Logging**: Built-in logging for debugging and monitoring
- **Invoice & Shipment Management**: Automated invoice and shipment creation
- **Event Management**: Integration with calendar and event systems
- **Webhook Support**: Handles external webhooks (e.g., WooCommerce)

## Technology Stack

- **Runtime**: PHP 8.2
- **Platform**: AWS Lambda (via [Bref](https://bref.sh/))
- **Framework**: Serverless Framework
- **API Gateway**: AWS HTTP API Gateway
- **CRM**: Vtiger CRM (REST API integration)
- **Database**: MySQL (remote connection)
- **Email**: PHPMailer

## Prerequisites

- PHP 8.2 or higher
- [Composer](https://getcomposer.org/)
- [Serverless Framework](https://www.serverless.com/)
- AWS CLI configured with appropriate credentials
- Access to Vtiger CRM instance
- MySQL database access

## Installation

1. Clone the repository:
```bash
git clone <repository-url>
cd integrations
```

2. Install PHP dependencies:
```bash
composer install
```

3. Install Serverless Framework (if not already installed):
```bash
npm install -g serverless
```

## Configuration

1. Copy the configuration template:
```bash
cp src/config.php.example src/config.php
```

2. Update `src/config.php` with your credentials:
   - Database connection details
   - Vtiger CRM URL and access keys
   - Email server credentials
   - Other environment-specific settings

**Important**: Never commit `src/config.php` with real credentials. This file is gitignored.

## Deployment

All deployments track the git commit SHA via the `DEPLOYED_COMMIT` environment variable on each Lambda function. Always use the Makefile targets below to ensure this is set automatically.

### Deploy Entire Stack

```bash
export AWS_PROFILE=trp-integrations
make deploy
```

### Deploy Single Function

```bash
make deploy-function F=<function-name>
```

Example:
```bash
make deploy-function F=enquiry
```

### View Deployment Information

```bash
serverless info
```

### Rollback a Deployment

```bash
# List previous deployments and their timestamps
serverless deploy list

# Rollback to a specific timestamp
serverless rollback --timestamp <timestamp>
```

### Check Deployed Commit

The deployed git commit SHA is available as the `DEPLOYED_COMMIT` environment variable on every Lambda function. You can view it in the AWS console under each function's configuration, or access it in PHP via `$_ENV['DEPLOYED_COMMIT']`.

## Development

### Local Testing

For local PHP testing without serverless:
```bash
php -S localhost:8000 -t src/
```

### View Logs

Tail logs for a specific function:
```bash
serverless logs -f <function-name> -t
```

Example:
```bash
serverless logs -f enquiry -t
```

### Install New Dependencies

```bash
composer require <package-name>
```

## API Endpoints

### Core Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/enquiry.php` | POST | Submit new enquiry |
| `/api/register.php` | POST | Register for program |
| `/api/confirm.php` | POST | Confirm registration |
| `/api/qualify.php` | POST | Qualification check |
| `/api/accept_dates.php` | POST | Accept proposed dates |
| `/api/order_resources.php` | POST | Order program resources |
| `/api/order_resources_2026.php` | POST | Order resources (2026) |
| `/api/seminar_registration.php` | POST | Register for seminar |
| `/api/confirm_existing_schools.php` | POST | Confirm existing school |
| `/api/prize_pack.php` | POST | Request prize pack |
| `/api/submit_ca.php` | POST | Submit custom assessment |

### Form Detail Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/school_confirmation_form_details.php` | GET | Get school confirmation form data |
| `/api/ey_confirmation_form_details.php` | GET | Get early years confirmation form data |
| `/api/school_ltrp_details.php` | GET | Get school LTRP details |
| `/api/school_curric_ordering_details.php` | GET | Get school curriculum ordering details |

### Utility Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/calculate_shipping.php` | POST | Calculate shipping costs |
| `/api/calendly_event.php` | POST | Handle Calendly events |

### Invoice Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/Invoices/createInvoice.php` | POST | Create new invoice |
| `/Invoices/createShipment.php` | POST | Create shipment |
| `/Invoices/create_shipment_2025.php` | POST | Create 2025 shipment |
| `/Invoices/58850_updateXeroCodeInvoiceItem.php` | POST | Update Xero code |

### Potential/Deal Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/Potentials/createNewProgramBooking.php` | POST | Create new program booking |
| `/Potentials/getEventPlanned.php` | GET | Get planned events |

### Event & Webhook Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/Events/54701_sendInvitation.php` | POST | Send event invitation |
| `/Webhooks/Order.php` | POST | WooCommerce order webhook |

## Architecture

### Directory Structure

```
src/
├── api/                          # API endpoint handlers
│   ├── classes/                  # Controller classes
│   │   ├── base.php             # Base VTController class
│   │   ├── school.php           # SchoolVTController
│   │   ├── workplace.php        # WorkplaceVTController
│   │   ├── early_years.php      # EarlyYearsVTController
│   │   ├── general.php          # GeneralVTController
│   │   └── traits/              # Reusable controller traits
│   ├── utils.php                # Request/response utilities
│   ├── api_helpers.php          # Controller class imports
│   └── [endpoint].php           # Individual endpoint files
├── lib/                         # Core library classes
│   ├── class_dhvt.php          # Vtiger REST API client
│   ├── class_dhrest.php        # Generic REST client wrapper
│   └── class_dhpdo.php         # PDO database wrapper
├── Invoices/                    # Invoice and shipment endpoints
├── Potentials/                  # CRM Potentials/Deals endpoints
├── Events/                      # Event-related endpoints
├── Webhooks/                    # Webhook handlers
├── Emails/                      # PHPMailer library
├── init.php                     # Bootstraps DB, Vtiger, and logging
├── config.php                   # Configuration (credentials, URLs)
├── functions.php                # Global helper functions
└── logs/                        # Log directory
```

### Controller Pattern

The API uses a trait-based controller pattern:

1. **Endpoint File** (e.g., `src/api/enquiry.php`):
   - Handles HTTP request/response
   - Routes to appropriate controller based on `service_type`
   - Returns JSON responses

2. **Controller Classes** (e.g., `SchoolVTController`):
   - Extend `VTController` base class
   - Use traits for shared functionality
   - Handle business logic and Vtiger integration

3. **Traits** (e.g., `Enquiry`, `Registration`, `Confirmation`):
   - Encapsulate reusable business logic
   - Mixed into controller classes
   - Promote code reuse across service types

### Service Types

The API supports multiple service types with dedicated controllers:

- **School**: School Partnership Programs
- **Workplace**: Workplace programs
- **Early Years**: Early Years programs
- **Imperfects**: Imperfects program
- **General**: Fallback for other enquiries

### Initialization Flow

Every endpoint includes `src/init.php` which:

1. Loads configuration from `config.php`
2. Initializes database connection (`$dbh` via `class_dhpdo.php`)
3. Initializes Vtiger client (`$vtod` via `class_dhvt.php`)
4. Loads global helper functions
5. Sets up logging functions

### Vtiger Integration

The `dhvt` class provides:

- **Authentication**: Automatic session management with challenge-response
- **CRUD Operations**: `create()`, `retrieve()`, `update()`
- **Queries**: `query()` for custom SQL-like queries
- **Relations**: `retrieveAllRelated()`, `addRelated()`

Example usage:
```php
global $vtod;

// Create a record
$contact = $vtod->create('Contacts', [
    'firstname' => 'John',
    'lastname' => 'Doe',
    'email' => 'john@example.com'
]);

// Query records
$results = $vtod->query("SELECT * FROM Contacts WHERE email='john@example.com';");

// Update a record
$contact['phone'] = '+61 123 456 789';
$vtod->update($contact);
```

## Logging

All endpoints should use the provided logging functions:

```php
log_info("Operation started", ['service_type' => 'School']);
log_debug("Debug information", ['variable' => $value]);
log_warning("Warning message", ['context' => 'value']);
log_error("Error occurred", ['error' => $errorMessage]);
log_exception($exception, ['endpoint' => 'enquiry']);
```

Logs are sent to CloudWatch when running on Lambda.

## Contributing

### Adding a New Endpoint

1. Create endpoint file in `src/api/` (e.g., `new_endpoint.php`)
2. Include required files:
   ```php
   require dirname(__FILE__)."/utils.php";
   require dirname(__FILE__)."/api_helpers.php";
   require dirname(__FILE__)."/../init.php";
   ```
3. Implement endpoint logic with proper error handling and logging
4. Add function definition to `serverless.yml` under `functions:`
5. Deploy the new function

### Error Handling Best Practices

Always wrap operations in try-catch blocks:

```php
try {
    log_info("Starting operation", ['context_key' => 'value']);
    // ... operation code
    send_response(['status' => 'success', 'data' => $result]);
} catch (Exception $e) {
    log_exception($e, ['endpoint' => 'endpoint_name']);
    send_response(['status' => 'fail', 'message' => $e->getMessage()], 500);
}
```

### Security Considerations

- Never commit credentials in `src/config.php`
- Validate and sanitize all user inputs
- Use prepared statements for database queries
- Implement proper CORS headers
- Follow OWASP security best practices

## Lambda Environment Notes

- **No Persistent Filesystem**: Use CloudWatch for logs
- **Temporary Storage**: `/tmp` directory available (10GB, cleared between cold starts)
- **Timeout**: Functions timeout after 29 seconds (API Gateway limit is 30s)
- **Cold Starts**: First request after deployment may be slower

## Year Versioning

The API maintains year-specific functionality:

- Current year: **2026**
- Year-specific endpoints exist for historical data tracking
- When creating new year versions, update deal names, quote names, and invoice names in controller classes

## Support

For issues, questions, or contributions, please contact the development team or create an issue in the repository.

## License

[Specify your license here]
