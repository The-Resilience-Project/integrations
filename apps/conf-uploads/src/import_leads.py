import json
import os
import sys
from pathlib import Path

from column_mapper import detect_column_mapping
from file_handler import list_upload_files, read_contacts_from_file, select_file
from import_engine import ENDPOINTS, build_request_body, send_contact

# Main execution
if __name__ == "__main__":
    # Check for mode flags
    dry_run = os.getenv("DRY_RUN", "false").lower() == "true"
    test_mode = os.getenv("TEST_MODE", "false").lower() == "true"
    test_limit = int(os.getenv("TEST_LIMIT", "1"))

    # Parse command-line arguments
    file_path = None
    for arg in sys.argv[1:]:
        if arg.startswith("--"):
            # Handle flags
            if arg == "--dry-run":
                dry_run = True
            elif arg.startswith("--test="):
                test_mode = True
                test_limit = int(arg.split("=")[1])
        else:
            # Assume it's a file path
            file_path = Path(arg)

    # If no file path provided, use interactive selection
    if file_path is None:
        # Only show prepared files
        all_files = list_upload_files()
        files = [f for f in all_files if f.stem.endswith("_prepared")]
        if not files:
            print("\n⚠️  No prepared files found in uploads/ directory.")
            print("\nPlease run the prepare script first:")
            print("  make prepare")
            print("  # OR")
            print("  python3 prepare_tsv.py")
            sys.exit(1)
        file_path = select_file(files)

        if file_path is None:
            print("No file selected. Exiting.")
            sys.exit(0)
    elif not file_path.exists():
        print(f"Error: File '{file_path}' does not exist.")
        sys.exit(1)

    # Check if file is a prepared file
    if not file_path.stem.endswith("_prepared"):
        print("\n⚠️  Error: This script only accepts prepared TSV files.")
        print(f"File: {file_path.name}")
        print("\nPlease run the prepare script first:")
        print("  make prepare")
        print("  # OR")
        print(f"  python3 prepare_tsv.py {file_path}")
        print(f"\nThis will create: {file_path.stem}_prepared.tsv")
        print("\nThe prepare step ensures column mapping is correct before importing.")
        sys.exit(1)

    print(f"\n📂 Processing file: {file_path.name}")

    # Detect column mapping from headers (prepared files have standardized headers)
    column_mapping, headers = detect_column_mapping(file_path)

    if not column_mapping:
        print("\n⚠️  Error: No headers detected in prepared file.")
        print(
            "Prepared files should have standardized headers (first_name, last_name, email, org, etc.)"
        )
        print("\nPlease re-run the prepare script:")
        print("  make prepare")
        sys.exit(1)

    # Check for required fields
    required_fields = ["first_name", "last_name", "email", "org"]
    missing_fields = [f for f in required_fields if f not in column_mapping]

    if missing_fields:
        print(f"\n⚠️  Error: Missing required fields in prepared file: {', '.join(missing_fields)}")
        print("\nPrepared files should have been validated during preparation.")
        print("Please re-run the prepare script:")
        print("  make prepare")
        sys.exit(1)

    # Read contacts from file
    contacts = read_contacts_from_file(file_path)

    # Apply test mode limit if enabled
    if test_mode:
        original_count = len(contacts)
        contacts = contacts[:test_limit]
        print(f"TEST MODE: Processing only {len(contacts)} of {original_count} contacts")

    print(f"Found {len(contacts)} contacts to process.")

    # Prompt for service type selection
    print("\nSelect service type:")
    print("  1. School (educational institutions)")
    print("  2. Workplace (corporate leads)")
    print("  3. Early Years (early childhood services)")

    while True:
        service_choice = input("\nEnter choice (1, 2, or 3): ").strip()
        if service_choice == "1":
            service_type = "School"
            break
        elif service_choice == "2":
            service_type = "Workplace"
            break
        elif service_choice == "3":
            service_type = "Early Years"
            break
        else:
            print("Invalid choice. Please enter 1, 2, or 3.")

    print(f"\nUsing service type: {service_type}")

    # Prompt for source form name
    print("\nEnter source form name:")
    print("  Format: {Conference Name} {Conference Type} {Year}")
    print("  Example: NSWPDPN Delegate 2025")
    source_form = input("\nSource form: ").strip()

    if not source_form:
        print("Error: Source form name cannot be empty.")
        sys.exit(1)

    print(f"Using source form: {source_form}")

    # Prompt for API endpoint selection
    print("\nSelect API endpoint:")
    print("  1. Prize Pack (for Delegates and Prize Pack leads)")
    print("  2. Enquiry (for Enquiry leads)")
    print(
        "\n⚠️  IMPORTANT: If using Enquiry, disable workflow 'New enquiry - send email to enquirer' in vTiger first!"
    )

    while True:
        endpoint_choice = input("\nEnter choice (1 or 2): ").strip()
        if endpoint_choice == "1":
            endpoint_type = "Prize Pack"
            api_endpoint = ENDPOINTS[endpoint_type]
            break
        elif endpoint_choice == "2":
            endpoint_type = "Enquiry"
            api_endpoint = ENDPOINTS[endpoint_type]
            confirm_workflow = (
                input(
                    "\n⚠️  Have you disabled workflow 'New enquiry - send email to enquirer' in vTiger? (y/n): "
                )
                .strip()
                .lower()
            )
            if confirm_workflow != "y":
                print(
                    "Please disable workflow 'New enquiry - send email to enquirer' in vTiger before proceeding."
                )
                print("You can find this in: Settings > Automation > Workflows")
                sys.exit(0)
            break
        else:
            print("Invalid choice. Please enter 1 or 2.")

    print(f"\nUsing endpoint: {endpoint_type}")

    # Show mode indicators
    if dry_run:
        print("\n⚠️  DRY RUN MODE: No data will be sent to the API")
    if test_mode:
        print(f"\n🧪 TEST MODE: Limited to {test_limit} row(s)")

    # Confirm before proceeding
    confirm = (
        input(
            f"\nProceed with {'simulating' if dry_run else 'uploading'} {len(contacts)} contacts to {endpoint_type}? (y/n): "
        )
        .strip()
        .lower()
    )
    if confirm != "y":
        print("Upload cancelled.")
        sys.exit(0)

    print(f"\nStarting {'simulation' if dry_run else 'upload'}...\n")

    for i, contact_data in enumerate(contacts):
        body = build_request_body(contact_data, column_mapping, service_type, source_form)

        # Send request to API or simulate in dry-run mode
        if dry_run:
            print(f"Row {i}. [DRY RUN] Would send to {endpoint_type}:")
            print(f"         {json.dumps(body, indent=2)}")
            print(f"         Email: {contact_data[column_mapping['email']]}\n")
        else:
            result = send_contact(body, api_endpoint)
            status_marker = "-------" if not result["success"] else ""
            print(
                f"Row {i}. Response: {result['status_code']} {status_marker}"
                f" - {contact_data[column_mapping['email']]}"
            )

    # Summary
    if dry_run:
        print(f"\n✅ Dry run complete! Simulated {len(contacts)} contact(s)")
        print("No data was sent to the API. Review the output above.")
    else:
        print(f"\n✅ Upload complete! Processed {len(contacts)} contact(s)")

        # Reminder to re-enable workflow if using Enquiry endpoint
        if endpoint_type == "Enquiry":
            print("\n" + "=" * 70)
            print("⚠️  IMPORTANT REMINDER:")
            print("Re-enable workflow 'New enquiry - send email to enquirer' in vTiger")
            print("Location: Settings > Automation > Workflows")
            print("=" * 70)
