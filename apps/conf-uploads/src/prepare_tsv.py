"""
TSV Preparation Tool

This script helps prepare TSV files for import by:
1. Detecting column mappings from headers
2. Creating a cleaned copy with only supported columns
3. Allowing you to manually massage the data before importing

Supported fields:
- name (single full name field)
- first_name
- last_name
- job_title
- org (organisation)
- email
- phone
- state
- num_of_students
- quality
- enquiry
"""

import csv
import sys
from pathlib import Path

from column_mapper import detect_column_mapping
from file_handler import list_upload_files, select_file


def prepare_tsv(input_file: Path, output_file: Path) -> None:
    """
    Create a cleaned copy of the TSV with only supported columns.

    Args:
        input_file: Path to the original TSV file
        output_file: Path where the cleaned TSV will be saved
    """
    # Detect column mapping
    column_mapping, headers = detect_column_mapping(input_file)

    if not column_mapping:
        print("\n⚠️  No headers detected in file.")
        print("The file should have a header row with column names.")
        print("Exiting without creating prepared file.")
        sys.exit(1)

    # Define supported fields in the order we want them
    supported_fields = [
        "first_name",
        "last_name",
        "email",
        "org",
        "job_title",
        "phone",
        "state",
        "num_of_students",
        "quality",
        "enquiry",
    ]

    # Show detected mapping
    print("\n📋 Detected column mapping from original file:")
    print("-" * 60)
    for idx, header in enumerate(headers):
        # Find which field this column maps to
        field_name = None
        for field, col_idx in column_mapping.items():
            if col_idx == idx:
                field_name = field
                break

        if field_name:
            print(f"  Column {idx}: '{header}' → {field_name}")
        else:
            print(f"  Column {idx}: '{header}' → (will be removed)")
    print("-" * 60)

    # Ask if user wants to modify the mapping
    print("\nWould you like to:")
    print("  1. Accept this mapping and continue")
    print("  2. Modify the column mapping")
    print("  3. Cancel")

    choice = input("\nEnter choice (1, 2, or 3): ").strip()

    if choice == "3":
        print("Cancelled.")
        sys.exit(0)
    elif choice == "2":
        # Allow user to modify mapping
        print("\n🔧 Modify column mapping")
        print("=" * 60)
        print("For each supported field:")
        print("  - Enter a column number to map to it")
        print("  - Enter 'x' or 'none' to unmap the field")
        print("  - Press Enter to keep current mapping\n")

        # Show available columns
        print("Available columns:")
        for idx, header in enumerate(headers):
            print(f"  {idx}: {header}")
        print()

        # Build new mapping
        new_mapping = {}
        for field in supported_fields:
            current_value = column_mapping.get(field)
            if current_value is not None:
                prompt = f"{field} [currently: {current_value} - '{headers[current_value]}']: "
            else:
                prompt = f"{field} [not mapped]: "

            user_input = input(prompt).strip().lower()

            if user_input:
                # Check if user wants to unmap the field
                if user_input in ["x", "none", "unmap", "remove"]:
                    # Don't add to new_mapping - effectively unmaps it
                    print(f"  → {field} unmapped (will not be included in prepared file)")
                    continue  # Skip to next field

                # Try to parse as column number
                try:
                    col_idx = int(user_input)
                    if 0 <= col_idx < len(headers):
                        new_mapping[field] = col_idx
                        print(f"  → {field} mapped to column {col_idx}: '{headers[col_idx]}'")
                    else:
                        print("  ⚠️  Invalid column number. Keeping previous mapping.")
                        if current_value is not None:
                            new_mapping[field] = current_value
                except ValueError:
                    print("  ⚠️  Invalid input. Keeping previous mapping.")
                    if current_value is not None:
                        new_mapping[field] = current_value
            elif current_value is not None:
                # Keep current mapping if user pressed Enter
                new_mapping[field] = current_value
                print(f"  → {field} kept at column {current_value}: '{headers[current_value]}'")

        column_mapping = new_mapping

        # Show updated mapping
        print("\n📋 Updated column mapping:")
        print("-" * 60)
        for field, col_idx in column_mapping.items():
            print(f"  {field} → Column {col_idx}: '{headers[col_idx]}'")
        print("-" * 60)

        confirm = input("\nProceed with this mapping? (y/n): ").strip().lower()
        if confirm != "y":
            print("Cancelled.")
            sys.exit(0)

    # Determine which fields are present
    present_fields = [f for f in supported_fields if f in column_mapping]

    if not present_fields:
        print("\n⚠️  No supported fields selected.")
        print("Cannot create prepared file.")
        sys.exit(1)

    print(f"\n✅ Found {len(present_fields)} supported field(s)")
    print(f"📝 Will create prepared file with columns: {', '.join(present_fields)}")

    # Read all data from input file
    with open(input_file, encoding="utf-8") as f:
        reader = csv.reader(f, delimiter="\t")
        next(reader)  # Skip header row
        rows = list(reader)

    # Write cleaned data to output file
    with open(output_file, "w", encoding="utf-8", newline="") as f:
        writer = csv.writer(f, delimiter="\t")

        # Write header row with only supported fields
        writer.writerow(present_fields)

        # Write data rows with only the mapped columns
        for row in rows:
            cleaned_row = []
            for field in present_fields:
                col_idx = column_mapping[field]
                # Safely get the value, defaulting to empty string if index out of range
                value = row[col_idx] if col_idx < len(row) else ""
                cleaned_row.append(value)
            writer.writerow(cleaned_row)

    print(f"\n✅ Prepared file created: {output_file}")
    print(f"   Original rows: {len(rows)}")
    print(f"   Columns: {len(present_fields)}")
    print("\nYou can now:")
    print("1. Open the prepared file to review/massage the data")
    print("2. Run import_leads.py with the prepared file when ready")


if __name__ == "__main__":
    # Parse command-line arguments
    file_path = None
    for arg in sys.argv[1:]:
        if not arg.startswith("--"):
            file_path = Path(arg)

    # If no file path provided, use interactive selection
    if file_path is None:
        files = list_upload_files(exclude_prepared=True)
        file_path = select_file(files)

        if file_path is None:
            print("No file selected. Exiting.")
            sys.exit(0)
    elif not file_path.exists():
        print(f"Error: File '{file_path}' does not exist.")
        sys.exit(1)

    print(f"\n📂 Processing file: {file_path}")

    # Create output filename: original_name_prepared.tsv
    output_path = file_path.parent / f"{file_path.stem}_prepared{file_path.suffix}"

    # Check if output file already exists
    if output_path.exists():
        confirm = (
            input(f"\n⚠️  File '{output_path.name}' already exists. Overwrite? (y/n): ")
            .strip()
            .lower()
        )
        if confirm != "y":
            print("Cancelled.")
            sys.exit(0)

    # Run preparation
    prepare_tsv(file_path, output_path)
