"""
TS Attendee TSV Enrichment Tool

Enriches TSV files for the TS Attendee upload flow with student-count data
fetched from myschool.edu.au.

Required input columns (header detection is flexible — see column_mapper):
- first_name
- last_name
- email
- school_name  (mapped to `org`)
- school_state (mapped to `state`)

For each row, looks up the school's student count via student_lookup and
writes a `*_fetched_student_nums.tsv` file with these added columns:
- num_of_students
- did_you_mean   (populated only when the matched school name differs from input)
- distance       (Levenshtein distance between input and matched name, lowercased)
- myschool_id      (myschool SML_ID — useful as a stable key for review)
- myschool_url   (link to the matched school's myschool.edu.au profile)

Rows whose `distance` exceeds REVIEW_DISTANCE_THRESHOLD are flagged with
⚠️ in the console and counted in the summary; review those rows before
passing the file on to the upload step.
"""

import csv
import sys
from pathlib import Path

from column_mapper import detect_column_mapping
from file_handler import list_upload_files, select_file
from student_lookup import lookup_school_details

REQUIRED_FIELDS = ["first_name", "last_name", "email", "org", "state"]
OUTPUT_FIELDS = [
    "first_name",
    "last_name",
    "email",
    "org",
    "state",
    "num_of_students",
    "did_you_mean",
    "distance",
    "myschool_id",
    "myschool_url",
]

# Levenshtein distance above this threshold flags the row for manual review.
# Tuned against observed data: typo corrections sit at distance ≤ 2, while
# wrong-school matches start at distance ≥ 6.
REVIEW_DISTANCE_THRESHOLD = 5


def prepare_ts_attendee(input_file: Path, output_file: Path) -> None:
    column_mapping, headers = detect_column_mapping(input_file)

    if not column_mapping:
        print("\n⚠️  No headers detected in file.")
        print("The file should have a header row with column names.")
        sys.exit(1)

    print("\n📋 Detected column mapping:")
    print("-" * 60)
    for idx, header in enumerate(headers):
        field_name = next((f for f, ci in column_mapping.items() if ci == idx), None)
        if field_name:
            print(f"  Column {idx}: '{header}' → {field_name}")
        else:
            print(f"  Column {idx}: '{header}' → (will be removed)")
    print("-" * 60)

    missing = [f for f in REQUIRED_FIELDS if f not in column_mapping]
    if missing:
        print(f"\n⚠️  Missing required fields: {', '.join(missing)}")
        print("Required: first_name, last_name, email, " "org (school name), state (school state)")
        sys.exit(1)

    confirm = input("\nProceed with this mapping? (y/n): ").strip().lower()
    if confirm != "y":
        print("Cancelled.")
        sys.exit(0)

    with open(input_file, encoding="utf-8") as f:
        reader = csv.reader(f, delimiter="\t")
        next(reader)  # Skip header
        rows = list(reader)

    matched_count = 0
    no_match_count = 0
    mismatch_count = 0
    no_count_count = 0
    review_count = 0
    total = len(rows)

    print(f"\n🔎 Fetching student counts for {total} row(s)...")

    with open(output_file, "w", encoding="utf-8", newline="") as f:
        writer = csv.writer(f, delimiter="\t")
        writer.writerow(OUTPUT_FIELDS)

        def get(row: list[str], field: str) -> str:
            idx = column_mapping.get(field)
            if idx is None or idx >= len(row):
                return ""
            return row[idx].strip()

        for i, row in enumerate(rows, 1):
            first_name = get(row, "first_name")
            last_name = get(row, "last_name")
            email = get(row, "email")
            org = get(row, "org")
            state = get(row, "state")

            details = lookup_school_details(org, state)

            num_of_students = ""
            did_you_mean = ""
            distance = ""
            myschool_id = ""
            myschool_url = ""

            if details is None:
                no_match_count += 1
                print(f"  [{i}/{total}] {org!r} ({state}) → no match")
            else:
                matched_count += 1
                students = details["students"]
                matched_name = details["matched_name"]
                d = details["distance"]
                distance = str(d)
                myschool_id = details.get("myschool_id") or ""
                myschool_url = details.get("myschool_url") or ""

                if students is None:
                    no_count_count += 1
                    num_of_students = ""
                else:
                    num_of_students = str(students)

                is_review = d > REVIEW_DISTANCE_THRESHOLD
                if is_review:
                    review_count += 1

                students_str = num_of_students or "n/a"
                review_marker = " ⚠️  REVIEW" if is_review else ""
                if d > 0:
                    mismatch_count += 1
                    did_you_mean = matched_name
                    print(
                        f"  [{i}/{total}] {org!r} ({state}) → {students_str} "
                        f"(did you mean: {matched_name!r}, distance={d}){review_marker}"
                    )
                else:
                    print(f"  [{i}/{total}] {org!r} ({state}) → {students_str}")

            writer.writerow(
                [
                    first_name,
                    last_name,
                    email,
                    org,
                    state,
                    num_of_students,
                    did_you_mean,
                    distance,
                    myschool_id,
                    myschool_url,
                ]
            )

    print(f"\n✅ Fetched file created: {output_file}")
    print(f"   Rows processed: {total}")
    print(f"   Matched: {matched_count}")
    if mismatch_count:
        print(f"   Of those, name mismatches (see did_you_mean): {mismatch_count}")
    if review_count:
        print(
            f"   ⚠️  Flagged for review (distance > {REVIEW_DISTANCE_THRESHOLD}): " f"{review_count}"
        )
    if no_count_count:
        print(f"   Matched but no count available (sub-campus): {no_count_count}")
    if no_match_count:
        print(f"   No match: {no_match_count}")
    print(
        f"\nReview the fetched file — especially rows with distance > {REVIEW_DISTANCE_THRESHOLD} —"
    )
    print("before passing it on to the upload step.")


if __name__ == "__main__":
    file_path = None
    for arg in sys.argv[1:]:
        if not arg.startswith("--"):
            file_path = Path(arg)

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

    output_path = file_path.parent / f"{file_path.stem}_fetched_student_nums{file_path.suffix}"

    if output_path.exists():
        confirm = (
            input(f"\n⚠️  File '{output_path.name}' already exists. Overwrite? (y/n): ")
            .strip()
            .lower()
        )
        if confirm != "y":
            print("Cancelled.")
            sys.exit(0)

    prepare_ts_attendee(file_path, output_path)
