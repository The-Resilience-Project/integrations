"""
File handling utilities for reading TSV contact files.
"""

import csv
from pathlib import Path


def list_upload_files(uploads_dir="uploads", exclude_prepared=False):
    """
    List all TSV files in the uploads directory.

    Args:
        uploads_dir: Directory to search for files
        exclude_prepared: If True, exclude files ending with _prepared.tsv
    """
    upload_path = Path(uploads_dir)
    if not upload_path.exists():
        print(f"Error: '{uploads_dir}' directory does not exist.")
        print(f"Creating '{uploads_dir}' directory...")
        upload_path.mkdir(parents=True, exist_ok=True)
        return []

    files = list(upload_path.glob("*.tsv"))

    if exclude_prepared:
        files = [f for f in files if not f.stem.endswith("_prepared")]

    return sorted(files)


def select_file(files):
    """Prompt user to select a file from the list."""
    if not files:
        print("\nNo TSV files found in the uploads directory.")
        return None

    print("\nAvailable files:")
    for idx, file in enumerate(files, 1):
        print(f"  {idx}. {file.name}")

    while True:
        try:
            choice = input(f"\nSelect file (1-{len(files)}) or 'q' to quit: ").strip()
            if choice.lower() == "q":
                return None

            idx = int(choice) - 1
            if 0 <= idx < len(files):
                return files[idx]
            else:
                print(f"Please enter a number between 1 and {len(files)}")
        except ValueError:
            print("Invalid input. Please enter a number.")
        except KeyboardInterrupt:
            print("\nCancelled.")
            return None


def read_contacts_from_file(file_path):
    """Read contacts from a TSV file."""
    contacts = []

    with open(file_path, encoding="utf-8") as f:
        # Try to detect if there's a header row
        first_line = f.readline().strip()
        f.seek(0)

        # Check if first line looks like a header (contains common header words)
        has_header = any(
            word in first_line.lower()
            for word in ["name", "email", "first", "last", "organisation", "organization", "school"]
        )

        reader = csv.reader(f, delimiter="\t")

        if has_header:
            next(reader)  # Skip header row

        for row in reader:
            if row and len(row) > 0:  # Skip empty rows
                contacts.append(row)

    return contacts
