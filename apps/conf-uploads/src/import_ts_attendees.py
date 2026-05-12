"""
TS Attendee Importer

Posts each row of a manually-reviewed TSV to the v2 endpoint:
    POST /api/v2/schools/ts/upload-attendees

Pipeline:
    raw TSV
      → prepare_ts_attendee.py (fetches num_of_students from myschool.edu.au)
      → *_fetched_student_nums.tsv
      → manual review in a spreadsheet (verify did_you_mean rows, prune bad data)
      → THIS SCRIPT
      → vTiger (contact + org tagged 2026/2027 {STATE} TS Attendee)

The reviewed file just needs the standard headers (first_name, last_name,
email, org, state, plus optional num_of_students). did_you_mean and distance
columns are ignored if present.

Modes:
    --dry-run / DRY_RUN=true     Show payloads, no API calls
    --test=N  / TEST_MODE=true   Send only the first N rows (default 1)
"""

import json
import os
import sys
from pathlib import Path

from column_mapper import detect_column_mapping
from file_handler import list_upload_files, read_contacts_from_file, select_file
from import_engine import ENDPOINTS, build_ts_attendee_body, send_contact

TS_ATTENDEE_ENDPOINT = ENDPOINTS["TS Attendee"]

REQUIRED_FIELDS = ["first_name", "last_name", "email", "org", "state"]


def main() -> int:
    dry_run = os.getenv("DRY_RUN", "false").lower() == "true"
    test_mode = os.getenv("TEST_MODE", "false").lower() == "true"
    test_limit = int(os.getenv("TEST_LIMIT", "1"))

    file_path = None
    for arg in sys.argv[1:]:
        if arg.startswith("--"):
            if arg == "--dry-run":
                dry_run = True
            elif arg.startswith("--test="):
                test_mode = True
                test_limit = int(arg.split("=")[1])
        else:
            file_path = Path(arg)

    if file_path is None:
        files = list_upload_files()
        if not files:
            print("\n⚠️  No TSV files found in uploads/.")
            print("Run the prep step first: make prepare-ts-attendee")
            return 1
        file_path = select_file(files)
        if file_path is None:
            print("No file selected. Exiting.")
            return 0
    elif not file_path.exists():
        print(f"Error: File '{file_path}' does not exist.")
        return 1

    print(f"\n📂 Processing file: {file_path.name}")

    column_mapping, _headers = detect_column_mapping(file_path)
    if not column_mapping:
        print("\n⚠️  Error: No headers detected.")
        print("Expected standard headers (first_name, last_name, email, org, state, ...)")
        return 1

    missing = [f for f in REQUIRED_FIELDS if f not in column_mapping]
    if missing:
        print(f"\n⚠️  Error: Missing required fields: {', '.join(missing)}")
        print(f"Required: {', '.join(REQUIRED_FIELDS)}")
        print("\nIf this is a freshly-prepared file, did you delete a column during review?")
        return 1

    contacts = read_contacts_from_file(file_path)
    total_in_file = len(contacts)

    if test_mode:
        contacts = contacts[:test_limit]
        print(f"🧪 TEST MODE: {len(contacts)} of {total_in_file} row(s) will be sent")

    print(f"Found {len(contacts)} attendee(s) to process.")

    if dry_run:
        print("\n⚠️  DRY RUN MODE: No data will be sent to the API")

    confirm = (
        input(
            f"\nProceed with {'simulating' if dry_run else 'uploading'} "
            f"{len(contacts)} attendee(s) to {TS_ATTENDEE_ENDPOINT}? (y/n): "
        )
        .strip()
        .lower()
    )
    if confirm != "y":
        print("Upload cancelled.")
        return 0

    print(f"\nStarting {'simulation' if dry_run else 'upload'}...\n")

    success_count = 0
    fail_count = 0
    fail_rows = []

    for i, contact_data in enumerate(contacts):
        body = build_ts_attendee_body(contact_data, column_mapping)
        email = body["contact_email"] or "(no email)"

        if dry_run:
            print(f"Row {i}. [DRY RUN] Would send:")
            print(f"         {json.dumps(body, indent=2)}\n")
            continue

        result = send_contact(body, TS_ATTENDEE_ENDPOINT)
        ok = bool(result.get("success"))
        if ok:
            success_count += 1
            marker = ""
        else:
            fail_count += 1
            fail_rows.append({"row": i, "email": email, "result": result})
            marker = " -------"
        print(f"Row {i}. Response: {result['status_code']}{marker} - {email}")

    if dry_run:
        print(f"\n✅ Dry run complete — simulated {len(contacts)} attendee(s).")
        return 0

    print(f"\n✅ Upload complete — {success_count} ok, {fail_count} failed.")
    if fail_rows:
        print("\nFailed rows:")
        for f in fail_rows:
            response_body = f["result"].get("response_body")
            print(f"  Row {f['row']} ({f['email']}): {response_body}")

    return 0 if fail_count == 0 else 2


if __name__ == "__main__":
    sys.exit(main())
