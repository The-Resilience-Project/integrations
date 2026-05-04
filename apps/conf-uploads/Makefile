.PHONY: help setup install prepare prepare-ts-attendee run run-file proof test-run test-run-file clean test lint lint-fix format format-fix check fix pre-commit-install pre-commit-run web-install web api

# Default target
help:
	@echo "Available commands:"
	@echo ""
	@echo "Setup:"
	@echo "  make setup      - Create virtual environment"
	@echo "  make install    - Install dependencies"
	@echo "  make init       - Setup + install in one command"
	@echo ""
	@echo "Data Preparation:"
	@echo "  make prepare              - Prepare/clean a TSV file (removes unsupported columns)"
	@echo "  make prepare-ts-attendee  - Prepare a TS Attendee TSV (looks up num_of_students)"
	@echo ""
	@echo "Running:"
	@echo "  make run        - Run the import script (interactive mode)"
	@echo "  make run-file FILE=<path>  - Run with specific file"
	@echo ""
	@echo "Testing & Validation:"
	@echo "  make proof      - Dry run mode (no API calls, show what would be sent)"
	@echo "  make proof-file FILE=<path>  - Dry run with specific file"
	@echo "  make test-run [ROWS=n]  - Test mode (upload only first n rows, default=1)"
	@echo "  make test-run-file FILE=<path> [ROWS=n]  - Test with specific file"
	@echo ""
	@echo "Testing:"
	@echo "  make test       - Run unit tests"
	@echo "  make test-cov   - Run tests with coverage report"
	@echo ""
	@echo "Utilities:"
	@echo "  make list       - List available TSV files"
	@echo "  make recent     - Show recently added TSV files"
	@echo "  make clean      - Remove virtual environment and cache files"
	@echo ""
	@echo "Web UI:"
	@echo "  make web-install  - Install frontend dependencies"
	@echo "  make web          - Start both servers (frontend + API)"
	@echo "  make api          - Start just the Flask API server"
	@echo ""
	@echo "Code Quality:"
	@echo "  make lint       - Check code style with ruff (no changes)"
	@echo "  make lint-fix   - Fix linting issues with ruff"
	@echo "  make format     - Check code formatting with black (no changes)"
	@echo "  make format-fix - Fix code formatting with black"
	@echo "  make check      - Run all checks (lint + format)"
	@echo "  make fix        - Fix all issues (format + lint)"
	@echo "  make pre-commit-install - Install pre-commit hooks"
	@echo "  make pre-commit-run     - Run pre-commit on all files"

# Create virtual environment
setup:
	@echo "Creating virtual environment..."
	python3 -m venv .venv
	@echo "Virtual environment created!"
	@echo "Activate it with: source .venv/bin/activate"

# Install dependencies
install:
	@echo "Installing dependencies..."
	.venv/bin/pip install -r requirements.txt
	@echo "Dependencies installed!"

# Setup and install in one command
init: setup install
	@echo "Setup complete! Run 'source .venv/bin/activate' to activate the virtual environment."

# Prepare TSV file (clean/filter columns)
prepare:
	@echo "Starting TSV preparation..."
	.venv/bin/python3 src/prepare_tsv.py

# Prepare TS Attendee TSV (looks up num_of_students per row)
prepare-ts-attendee:
	@echo "Starting TS Attendee TSV preparation..."
	.venv/bin/python3 src/prepare_ts_attendee.py

# Run the import script
run:
	@echo "Starting lead import script..."
	.venv/bin/python3 src/import_leads.py

# Run with specific file
run-file:
	@if [ -z "$(FILE)" ]; then \
		echo "Error: Please specify a file with FILE=path/to/file.tsv"; \
		exit 1; \
	fi
	@echo "Importing from $(FILE)..."
	.venv/bin/python3 src/import_leads.py $(FILE)

# Proof mode (dry run) - shows what would be sent without actually sending
proof:
	@echo "Starting DRY RUN mode (no API calls will be made)..."
	DRY_RUN=true .venv/bin/python3 src/import_leads.py

# Proof mode with specific file
proof-file:
	@if [ -z "$(FILE)" ]; then \
		echo "Error: Please specify a file with FILE=path/to/file.tsv"; \
		exit 1; \
	fi
	@echo "Starting DRY RUN mode with $(FILE)..."
	DRY_RUN=true .venv/bin/python3 src/import_leads.py $(FILE)

# Test mode - upload only first N rows (default: 1)
test-run:
	@echo "Starting TEST mode (uploading only $(or $(ROWS),1) row(s))..."
	TEST_MODE=true TEST_LIMIT=$(or $(ROWS),1) .venv/bin/python3 src/import_leads.py

# Test mode with specific file
test-run-file:
	@if [ -z "$(FILE)" ]; then \
		echo "Error: Please specify a file with FILE=path/to/file.tsv"; \
		exit 1; \
	fi
	@echo "Starting TEST mode with $(FILE) (uploading only $(or $(ROWS),1) row(s))..."
	TEST_MODE=true TEST_LIMIT=$(or $(ROWS),1) .venv/bin/python3 src/import_leads.py $(FILE)

# Clean up
clean:
	@echo "Cleaning up..."
	rm -rf .venv
	find . -type d -name "__pycache__" -exec rm -rf {} +
	find . -type f -name "*.pyc" -delete
	find . -type f -name "*.pyo" -delete
	find . -type f -name "*.orig" -delete
	@echo "Clean complete!"

# Run tests
test:
	@echo "Running tests..."
	@.venv/bin/pytest -v

# Run tests with coverage
test-cov:
	@echo "Running tests with coverage..."
	@.venv/bin/pytest --cov=src --cov-report=term-missing --cov-report=html

# Run tests in watch mode
test-watch:
	@echo "Running tests in watch mode..."
	@.venv/bin/pytest-watch

# Check code style with ruff (no changes)
lint:
	@echo "Checking code style with ruff..."
	@.venv/bin/ruff check src/

# Fix linting issues automatically
lint-fix:
	@echo "Fixing linting issues with ruff..."
	@.venv/bin/ruff check --fix src/
	@echo "✅ Linting issues fixed!"

# Check code formatting with black (no changes)
format:
	@echo "Checking code formatting with black..."
	@.venv/bin/black --check src/

# Fix formatting issues automatically
format-fix:
	@echo "Fixing code formatting with black..."
	@.venv/bin/black src/
	@echo "✅ Code formatted!"

# Run all checks (lint + format)
check: lint format
	@echo "✅ All checks passed!"

# Fix all issues (format + lint)
fix: format-fix lint-fix
	@echo "✅ All issues fixed!"

# Install pre-commit hooks
pre-commit-install:
	@echo "Installing pre-commit hooks..."
	@.venv/bin/pre-commit install
	@echo "✅ Pre-commit hooks installed!"

# Run pre-commit on all files
pre-commit-run:
	@echo "Running pre-commit on all files..."
	@.venv/bin/pre-commit run --all-files

# List available TSV files
list:
	@echo "Available TSV files in uploads/:"
	@ls -lh uploads/*.tsv 2>/dev/null || echo "No TSV files found in uploads/"

# Show recent uploads (last 5 files)
recent:
	@echo "Recent TSV files in uploads/:"
	@ls -lt uploads/*.tsv 2>/dev/null | head -6 || echo "No TSV files found in uploads/"

# Install frontend dependencies
web-install:
	@echo "Installing frontend dependencies..."
	cd web && npm install

# Start both servers (frontend + backend)
web:
	@echo "Starting Flask API on http://localhost:5001..."
	@.venv/bin/python3 src/api_server.py &
	@echo "Starting Vite dev server on http://localhost:5173..."
	@cd web && npm run dev

# Start just the API server
api:
	@echo "Starting Flask API on http://localhost:5001..."
	.venv/bin/python3 src/api_server.py
