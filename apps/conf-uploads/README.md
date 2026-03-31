# Conference Leads Import Tool

A Python tool for batch importing conference leads (delegates, enquiries, prize packs) into The Resilience Project's vTiger CRM. The tool processes tab-separated data from spreadsheets and submits them via HTTP POST requests to vTiger API endpoints.

## Quick Start

```bash
# Setup
make init
source .venv/bin/activate

# Prepare data (REQUIRED first step)
make prepare

# Validate with dry run
make proof

# Test with one row
make test-run ROWS=1

# Run full import
make run
```

## Documentation

📚 **[Development Guide](DEVELOPMENT.md)** - Setup, running scripts, testing, and making changes

📋 **[Workflow Guide](WORKFLOW.md)** - Business logic, supported columns, and vTiger integration

## Project Overview

### What It Does

1. **Prepares TSV files** - Automatically detects columns, maps fields, and creates cleaned copies with only supported data
2. **Validates data** - Dry run mode to verify mappings before uploading
3. **Imports to vTiger** - Batch uploads contacts and organisations via API endpoints
4. **Tracks results** - Monitors upload status and provides verification steps

### Key Features

- **Automatic column detection** - Flexible header matching for common field names
- **Interactive mapping** - Review and customise column mappings during preparation
- **File validation** - Enforces preparation workflow (only accepts `*_prepared.tsv` files)
- **Dry run mode** - Test configuration without making API calls
- **Test uploads** - Upload first N rows to verify before full import
- **Comprehensive testing** - 66 unit tests covering all functionality

### Supported Fields

| Field | Required | Variations |
|-------|----------|------------|
| first_name | Yes | First Name, first_name, First, Given Name |
| last_name | Yes | Last Name, last_name, Last, Surname |
| email | Yes | Email, Email Address, E-mail |
| org | Yes | Organisation, School, Workplace, Company |
| num_of_students | No | Number of Students, # Students |
| job_title | No | Job Title, Position, Title |
| phone | No | Phone, Mobile, Telephone |
| state | No | State, State/Territory |
| enquiry | No | Enquiry, Comments, Notes |
| quality | No | Quality |

## Requirements

- Python 3
- Virtual environment (created automatically with `make init`)

## Common Commands

```bash
# Setup
make init              # Create venv and install dependencies

# Data workflow
make prepare           # Prepare TSV file (REQUIRED first step)
make proof            # Dry run mode (no API calls)
make test-run ROWS=1  # Test with first N rows
make run              # Run full import

# Development
make test             # Run unit tests
make test-cov         # Run tests with coverage
make lint             # Check code style
make format           # Check code formatting
make fix              # Fix all linting and formatting issues

# Utilities
make list             # List available TSV files
make recent           # Show recently added files
make help             # Show all commands
```

## Workflow Summary

1. **Receive data** from Ash/Monica (Google Sheets)
2. **Download as TSV** and place in `uploads/` folder
3. **Run prepare** - `make prepare` (REQUIRED)
   - Reviews and customises column mappings
   - Creates `*_prepared.tsv` with only supported columns
4. **Configure vTiger** - Ensure source form exists in picklists
5. **Dry run** - `make proof` to validate
6. **Test upload** - `make test-run ROWS=1` to verify
7. **Full import** - `make run` to process all rows
8. **Verify** - Check counts in vTiger match expectations

## Project Structure

```
.
├── src/                       # Source code
│   ├── import_leads.py       # Main upload script
│   ├── prepare_tsv.py        # TSV preparation tool
│   ├── file_handler.py       # File handling utilities
│   └── column_mapper.py      # Column mapping detection
├── tests/                     # Unit tests
│   ├── test_column_mapper.py
│   └── test_file_handler.py
├── uploads/                   # Data files (TSV)
├── api/                       # vTiger API (PHP)
├── Makefile                   # Task automation
├── requirements.txt           # Python dependencies
├── pyproject.toml             # Code quality configuration
├── .pre-commit-config.yaml    # Pre-commit hooks
├── DEVELOPMENT.md             # Development guide
├── WORKFLOW.md               # Business logic guide
└── README.md                 # This file
```

## Need Help?

- **Development tasks** → See [DEVELOPMENT.md](DEVELOPMENT.md)
- **Business workflow** → See [WORKFLOW.md](WORKFLOW.md)
- **All commands** → Run `make help`
