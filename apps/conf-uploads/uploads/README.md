# Uploads Directory

Place your TSV files here for batch importing into vTiger CRM.

## Supported File Format

- `.tsv` - Tab-separated values

**Note:** We use TSV (not CSV) because address fields and other fields may contain commas, which would break CSV parsing.

## File Structure

Your files should have the following columns (in order):

1. First Name
2. Last Name
3. Email
4. Organisation Name
5. Number of Students (optional)

## Headers

Files can optionally include a header row. The script will automatically detect and skip headers if they contain common words like "name", "email", "first", "last", "organisation", or "school".

## Usage

### Interactive Mode (recommended)
```bash
python3 import_leads.py
```

The script will:
1. List all available files in the `uploads/` directory
2. Let you select a file by number
3. Show preview of how many contacts will be uploaded
4. Ask for confirmation before proceeding

### Command-Line Mode
```bash
python3 import_leads.py uploads/NSWPDPN_Delegate_2025_EXAMPLE.tsv
```

Directly specify the file path to process.
