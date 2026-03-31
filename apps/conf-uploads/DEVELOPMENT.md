# Development Guide

This guide covers project setup, running scripts, testing, and making changes to the codebase.

## Requirements

Python 3

## Setup

### Quick Setup with Make

```bash
make init           # Create venv and install dependencies
source .venv/bin/activate
```

### Manual Setup

```bash
python3 -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
```

## Running the Scripts

### 1. Prepare TSV Files

**REQUIRED STEP** before importing. This creates a cleaned copy with only supported columns:

```bash
make prepare
# OR
python3 prepare_tsv.py
```

The prepare script will:
- List all unprepared TSV files in `uploads/`
- Let you select a file
- Detect column mappings from headers
- Allow you to review and modify mappings
- Create a `*_prepared.tsv` file with only supported columns

### 2. Import Leads

After preparing your file, import it into vTiger:

```bash
make run
# OR
python3 import_leads.py
```

The import script will:
- List all prepared TSV files (`*_prepared.tsv`)
- Let you select a file
- Prompt for configuration (service type, source form, API endpoint)
- Show preview of how many contacts will be uploaded
- Ask for confirmation before proceeding

### Command-Line Mode

```bash
# Prepare specific file
python3 prepare_tsv.py uploads/your_file.tsv

# Import specific file
make run-file FILE=uploads/your_file_prepared.tsv
# OR
python3 import_leads.py uploads/your_file_prepared.tsv
```

## Testing & Validation

Before doing a full upload, validate your configuration:

### Available Make Commands

```bash
make help          # Show all available commands
make setup         # Create virtual environment
make install       # Install dependencies

# Data Preparation
make prepare       # Prepare/clean a TSV file (removes unsupported columns)

# Import
make run           # Run the import script
make run-file FILE=uploads/yourfile.tsv  # Run with specific file

# Testing & Validation
make proof         # Dry run mode (shows what would be sent, no API calls)
make proof-file FILE=uploads/yourfile.tsv  # Dry run with specific file
make test-run ROWS=1  # Test mode (upload only first N rows)

# Testing
make test          # Run unit tests
make test-cov      # Run tests with coverage report

# Code Quality
make lint          # Check code style with ruff (no changes)
make lint-fix      # Fix linting issues with ruff
make format        # Check code formatting with black (no changes)
make format-fix    # Fix code formatting with black
make check         # Run all checks (lint + format)
make fix           # Fix all issues (format + lint)
make pre-commit-install  # Install git pre-commit hooks
make pre-commit-run      # Run pre-commit on all files

# Utilities
make list          # List available TSV files
make recent        # Show recently added TSV files
make clean         # Remove venv and cache files
```

### Recommended Workflow

1. **REQUIRED:** Run `make prepare` to create a cleaned TSV with only supported columns
2. Review the `*_prepared.tsv` file as needed
3. Run `make proof` to verify the data mapping is correct
4. Run `make test-run ROWS=1` to test with one actual upload
5. Verify the contact in vTiger
6. Finally run `make run` for the full upload

### Dry Run Mode (Proof)

Shows what would be sent WITHOUT making API calls:

```bash
make proof
make proof-file FILE=uploads/your_file_prepared.tsv
```

### Test Mode

Upload only first N rows (default: 1):

```bash
make test-run                    # Test with first 1 row
make test-run ROWS=5            # Test with first 5 rows
make test-run-file FILE=uploads/your_file_prepared.tsv ROWS=3
```

### Utility Commands

```bash
make list          # List all TSV files in uploads/
make recent        # Show recently added TSV files
```

## Testing

This project includes comprehensive unit tests.

### Running Tests

```bash
# Run all tests
make test

# Run tests with coverage report
make test-cov
```

Test coverage includes:
- All column detection functions (first name, last name, email, etc.)
- Header normalisation
- Full mapping detection from headers
- File-based mapping detection
- File filtering (prepared vs unprepared files)
- Edge cases and missing fields

### Test Structure

Tests are located in the `/tests` directory:
- `test_column_mapper.py` - Column detection and mapping (58 tests)
- `test_file_handler.py` - File listing and filtering (8 tests)

## Code Quality & Formatting

This project uses **black** for code formatting and **ruff** for linting.

### Quick Start

```bash
# Check everything (no changes)
make check         # Check both linting and formatting

# Fix everything
make fix           # Fix both linting and formatting issues

# Or run individually
make lint          # Check linting (no changes)
make lint-fix      # Fix linting issues

make format        # Check formatting (no changes)
make format-fix    # Fix formatting issues
```

### Pre-commit Hooks (Optional but Recommended)

Automatically run code quality checks and tests before each commit:

```bash
make pre-commit-install
```

This will automatically run before each commit:
- **black** - Format code
- **ruff** - Lint and fix issues
- **pytest** - Run all unit tests
- **pre-commit-hooks** - Check for trailing whitespace, file endings, merge conflicts, etc.

If any check fails, the commit will be blocked until issues are fixed.

### Commands Summary

**Check (no changes):**
```bash
make check         # Check both linting and formatting
make lint          # Check linting only
make format        # Check formatting only
```

**Fix (makes changes):**
```bash
make fix           # Fix both linting and formatting
make lint-fix      # Fix linting only
make format-fix    # Fix formatting only
```

## Project Structure

```
.
├── src/                       # Source code
│   ├── import_leads.py       # Main upload script
│   ├── prepare_tsv.py        # TSV preparation tool (clean/filter columns)
│   ├── file_handler.py       # File handling utilities (TSV reading, file selection)
│   └── column_mapper.py      # Column mapping detection logic
├── tests/                     # Unit tests
│   ├── test_column_mapper.py # Tests for column mapper
│   └── test_file_handler.py  # Tests for file handler
├── uploads/                   # Place TSV files here
│   ├── README.md             # Upload directory documentation
│   └── *.tsv                 # Your data files
├── api/                       # vTiger API integration (PHP)
├── Makefile                   # Task automation (calls scripts in src/)
├── requirements.txt           # Python dependencies
├── pyproject.toml             # Black & ruff configuration
├── .pre-commit-config.yaml    # Pre-commit hooks configuration
├── .gitignore                 # Git ignore patterns
├── CLAUDE.md                  # AI assistant instructions
├── DEVELOPMENT.md            # This file (development guide)
├── WORKFLOW.md               # Business logic and workflow documentation
└── README.md                 # Project overview
```

**Note**: All Python source code is in the `src/` directory. The Makefile calls scripts directly from `src/` (e.g., `.venv/bin/python3 src/prepare_tsv.py`).

## Making Changes

### Adding New Column Detection

1. Add detection function to `src/column_mapper.py`:
```python
def detect_your_field(headers: list[str]) -> Optional[int]:
    """Detect the your_field column index."""
    normalized = [normalize_header(h) for h in headers]
    for i, h in enumerate(normalized):
        # Check for exact match first (for prepared files)
        if h == "your_field":
            return i
        # Then check for partial matches
        if any(word in h for word in ["keyword1", "keyword2"]):
            return i
    return None
```

2. Add to `detect_column_mapping_from_headers()` in `src/column_mapper.py`:
```python
your_field = detect_your_field(headers)
if your_field is not None:
    mapping["your_field"] = your_field
```

3. Add tests to `tests/test_column_mapper.py`:
```python
class TestDetectYourField:
    def test_standard_your_field(self):
        headers = ["First Name", "Last Name", "Your Field"]
        assert detect_your_field(headers) == 2
```

### Adding New Supported Field

1. Add to `SUPPORTED_FIELDS` in `src/prepare_tsv.py`
2. Add detection function in `src/column_mapper.py`
3. Add mapping logic in `src/import_leads.py` if needed
4. Update tests

### Code Style Guidelines

- Use Australian English spelling (e.g., "organisation" not "organization")
- Run `make fix` before committing to auto-format code
- Run `make test` to ensure all tests pass
- Install pre-commit hooks with `make pre-commit-install` for automatic checks

## Common Development Tasks

### Add Dependencies

```bash
source .venv/bin/activate
pip install package_name
pip freeze > requirements.txt
```

### Run Specific Test

```bash
.venv/bin/pytest tests/test_column_mapper.py::TestDetectEmail -v
```

### Debug Mode

Add print statements or use Python debugger:
```python
import pdb; pdb.set_trace()
```

### Clean Environment

```bash
make clean  # Remove venv and cache files
make init   # Fresh setup
```

## Troubleshooting

### Virtual Environment Issues

```bash
make clean
make init
source .venv/bin/activate
```

### Test Failures

```bash
# Run with verbose output
.venv/bin/pytest -vv

# Run with print statements
.venv/bin/pytest -s
```

### Import Errors

Ensure you're in the project root and virtual environment is activated:
```bash
source .venv/bin/activate
python3 -c "import sys; print(sys.path)"
```
