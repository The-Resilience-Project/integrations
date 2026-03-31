# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

**IMPORTANT: Always use Australian English spelling (e.g., "organisation" not "organization", "colour" not "color", etc.) in all code, documentation, and comments.**

## Overview

Python tool for batch importing conference leads (delegates, enquiries, prize packs) into vTiger CRM. Processes tab-separated data from spreadsheets and submits via HTTP POST to The Resilience Project's vTiger API endpoints.

## Common Commands

```bash
# Setup
make init                          # Create .venv + install dependencies

# Data preparation & import
make prepare                       # Interactive TSV preparation (required before import)
make run                           # Run the import script (interactive)
make run-file FILE=path/to/file    # Import a specific file

# Validation (no API calls)
make proof                         # Dry run — shows what would be sent
make test-run ROWS=1               # Upload only first N rows for testing

# Unit tests
make test                          # Run pytest
make test-cov                      # Run pytest with coverage report

# Code quality
make check                         # Run ruff + black checks (no changes)
make fix                           # Auto-fix formatting and lint issues
make lint                          # Ruff only (check)
make format                        # Black only (check)
```

## Code Style

- **Black** formatter: line-length=100, target Python 3.9
- **Ruff** linter: rules E, W, F, I, N, UP, B, C4, SIM
- **Pre-commit hooks** run black, ruff, and pytest on commit (`make pre-commit-install` to set up)

## Architecture

### Source Layout

- `src/` — Python source code
  - `import_leads.py` — Main import script; reads prepared TSV, prompts for config, POSTs to vTiger
  - `prepare_tsv.py` — Data preparation; detects columns, allows interactive customisation, writes `*_prepared.tsv`
  - `column_mapper.py` — Column detection engine; flexible header matching for 10 supported fields
  - `file_handler.py` — File I/O utilities; lists/filters TSV files, reads contact data
- `tests/` — pytest unit tests for column_mapper and file_handler
- `uploads/` — Working directory for TSV data files

### Data Flow

```
Raw TSV file (from Google Sheets)
  → make prepare (prepare_tsv.py + column_mapper.py)
  → *_prepared.tsv (cleaned, only supported columns)
  → make run (import_leads.py + file_handler.py)
  → HTTP POST to vTiger API (prize_pack.php or enquiry.php)
  → vTiger CRM (Contact + Organisation records)
```

The import script **only accepts `*_prepared.tsv` files** — this enforces the preparation step.

### Service Types

Three service types determine how organisation fields map to vTiger:
- **School** → `school_name_other` + `school_name_other_selected`
- **Workplace** → `workplace_name_other` + `workplace_name_other_selected`
- **Early Years** → `earlyyears_name_other` + `service_name_other_selected`

### API Endpoints

- `prize_pack.php` — for delegates and prize pack leads
- `enquiry.php` — for enquiry leads (requires disabling email workflow in vTiger first)

### Execution Modes

- **Full mode** (default) — uploads all rows
- **Dry run** (`DRY_RUN=true` / `make proof`) — shows request bodies, no API calls
- **Test mode** (`TEST_MODE=true` / `make test-run`) — uploads only first N rows

## Source Form Naming Convention

Format: `{Conference Name} {Conference Type} {Year}` (e.g., `NSWPDPN Delegate 2026`)

The source form value must exactly match the picklist value in vTiger for both:
- Contacts module → "Forms Completed" field
- Organisation module → "2026 sales events" field

## Pre-Upload Checklist

1. Source form exists in both vTiger picklists (Contacts and Organisation)
2. Source form names are identical in both picklists
3. For enquiry uploads: email workflow is disabled
4. Service type matches the upload (School/Workplace/Early Years)
5. First row test completed and verified in vTiger (`make test-run ROWS=1`)

## Post-Upload Verification

1. Filter contacts by "Forms Completed" in vTiger
2. Verify total count matches number of data rows
3. Count mismatches usually indicate duplicate emails (expected vTiger behaviour)
