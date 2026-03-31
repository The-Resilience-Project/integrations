"""
Flask JSON API for the conference uploads web UI.

Wraps the existing import engine, column mapper, and file handler
so the React frontend can drive the same workflow via HTTP.
"""

import os
import subprocess
import sys
import uuid
from pathlib import Path

# Ensure src/ is on the import path so sibling modules resolve correctly
sys.path.insert(0, str(Path(__file__).resolve().parent))

from dotenv import load_dotenv
from flask import Flask, jsonify, request
from flask_cors import CORS

load_dotenv(Path(__file__).resolve().parent.parent / ".env")

from column_mapper import detect_column_mapping_from_headers
from file_handler import list_upload_files, read_contacts_from_file
from import_engine import ENDPOINTS, build_request_body, send_contact

app = Flask(__name__)
CORS(app)

PROJECT_ROOT = Path(__file__).resolve().parent.parent
UPLOADS_DIR = PROJECT_ROOT / "uploads"


@app.route("/api/upload", methods=["POST"])
def upload_file():
    """Accept a TSV file upload, save it, and return detected column mapping."""
    if "file" not in request.files:
        return jsonify({"error": "No file provided"}), 400

    file = request.files["file"]
    if not file.filename:
        return jsonify({"error": "Empty filename"}), 400

    # Use the original filename — overwrites if it already exists
    safe_name = file.filename
    save_path = UPLOADS_DIR / safe_name
    UPLOADS_DIR.mkdir(parents=True, exist_ok=True)
    file.save(save_path)

    # Detect column mapping
    mapping, headers = detect_column_mapping_from_file(save_path)

    # Read first 20 rows for preview
    contacts = read_contacts_from_file(save_path)
    preview_rows = contacts[:20]

    return jsonify(
        {
            "file_path": str(save_path),
            "filename": safe_name,
            "column_mapping": mapping,
            "headers": headers,
            "preview_rows": preview_rows,
            "total_rows": len(contacts),
        }
    )


@app.route("/api/files", methods=["GET"])
def list_files():
    """List existing TSV files in uploads/."""
    files = list_upload_files(str(UPLOADS_DIR))
    return jsonify(
        {
            "files": [
                {"name": f.name, "path": str(f)}
                for f in files
            ]
        }
    )


@app.route("/api/detect", methods=["POST"])
def detect():
    """Detect column mapping and headers for an existing file."""
    data = request.get_json()
    if not data or "file_path" not in data:
        return jsonify({"error": "file_path required"}), 400

    file_path = Path(data["file_path"])
    if not file_path.exists():
        return jsonify({"error": "File not found"}), 404

    mapping, headers = detect_column_mapping_from_file(file_path)
    contacts = read_contacts_from_file(file_path)

    return jsonify(
        {
            "file_path": str(file_path),
            "filename": file_path.name,
            "column_mapping": mapping,
            "headers": headers,
            "preview_rows": contacts[:20],
            "total_rows": len(contacts),
        }
    )


@app.route("/api/preview", methods=["POST"])
def preview():
    """Return first 20 rows with the given column mapping applied."""
    data = request.get_json()
    if not data or "file_path" not in data or "column_mapping" not in data:
        return jsonify({"error": "file_path and column_mapping required"}), 400

    file_path = Path(data["file_path"])
    if not file_path.exists():
        return jsonify({"error": "File not found"}), 404

    column_mapping = {k: int(v) for k, v in data["column_mapping"].items() if v is not None}
    contacts = read_contacts_from_file(file_path)
    preview_rows = contacts[:20]

    # Build mapped preview (show field name → value for each row)
    mapped_rows = []
    for row in preview_rows:
        mapped = {}
        for field, idx in column_mapping.items():
            mapped[field] = row[idx] if idx < len(row) else ""
        mapped_rows.append(mapped)

    return jsonify(
        {
            "mapped_rows": mapped_rows,
            "raw_rows": preview_rows,
            "total_rows": len(contacts),
        }
    )


@app.route("/api/proof", methods=["POST"])
def proof():
    """Dry run — return all built request bodies without sending."""
    data = request.get_json()
    required = ["file_path", "column_mapping", "service_type", "source_form", "endpoint_type"]
    missing = [f for f in required if f not in data]
    if missing:
        return jsonify({"error": f"Missing fields: {', '.join(missing)}"}), 400

    file_path = Path(data["file_path"])
    if not file_path.exists():
        return jsonify({"error": "File not found"}), 404

    column_mapping = {k: int(v) for k, v in data["column_mapping"].items() if v is not None}
    contacts = read_contacts_from_file(file_path)

    bodies = []
    for i, contact_data in enumerate(contacts):
        body = build_request_body(
            contact_data, column_mapping, data["service_type"], data["source_form"]
        )
        bodies.append({"row": i, "body": body})

    return jsonify({"bodies": bodies, "total_rows": len(bodies)})


@app.route("/api/import-one", methods=["POST"])
def import_one():
    """Import a single contact row. Called once per row by the frontend."""
    data = request.get_json()
    required = ["file_path", "column_mapping", "service_type", "source_form", "endpoint_type", "row"]
    missing = [f for f in required if f not in data]
    if missing:
        return jsonify({"error": f"Missing fields: {', '.join(missing)}"}), 400

    file_path = Path(data["file_path"])
    if not file_path.exists():
        return jsonify({"error": "File not found"}), 404

    endpoint_type = data["endpoint_type"]
    api_endpoint = ENDPOINTS.get(endpoint_type)
    if not api_endpoint:
        return jsonify({"error": f"Unknown endpoint type: {endpoint_type}"}), 400

    row_index = int(data["row"])
    column_mapping = {k: int(v) for k, v in data["column_mapping"].items() if v is not None}
    contacts = read_contacts_from_file(file_path)

    if row_index >= len(contacts):
        return jsonify({"error": f"Row {row_index} out of range (file has {len(contacts)} rows)"}), 400

    contact_data = contacts[row_index]
    body = build_request_body(
        contact_data, column_mapping, data["service_type"], data["source_form"]
    )
    result = send_contact(body, api_endpoint)
    result["row"] = row_index

    return jsonify(result)


@app.route("/api/vtiger/picklist", methods=["GET"])
def vtiger_picklist():
    """Fetch picklist values from vTiger for a given module and field."""
    base_url = os.getenv("VTIGER_BASE_URL")
    username = os.getenv("VTIGER_USERNAME")
    password = os.getenv("VTIGER_PASSWORD")

    if not all([base_url, username, password]):
        return jsonify({"error": "vTiger credentials not configured in .env"}), 503

    module = request.args.get("module", "Contacts")
    field = request.args.get("field", "cf_contacts_formscompleted")

    try:
        import httpx

        url = f"{base_url}/describe?elementType={module}"
        resp = httpx.get(url, auth=(username, password), timeout=15)
        resp.raise_for_status()
        data = resp.json()

        # Find the requested field in the module description
        fields = data.get("result", {}).get("fields", [])
        for f in fields:
            if f.get("name") == field:
                picklist = f.get("type", {}).get("picklistValues", [])
                values = [p.get("value") for p in picklist if p.get("value")]
                return jsonify({"module": module, "field": field, "values": values})

        return jsonify(
            {"error": f"Field '{field}' not found in {module}", "available_fields": [
                f["name"] for f in fields if f.get("type", {}).get("picklistValues")
            ]}
        ), 404
    except httpx.HTTPStatusError as exc:
        return jsonify({"error": f"vTiger API error: {exc.response.status_code}"}), 502
    except Exception as exc:
        return jsonify({"error": str(exc)}), 500


@app.route("/api/vtiger/lookup", methods=["POST"])
def vtiger_lookup():
    """Look up contacts in vTiger by email using batched IN queries."""
    base_url = os.getenv("VTIGER_BASE_URL")
    username = os.getenv("VTIGER_USERNAME")
    password = os.getenv("VTIGER_PASSWORD")

    if not all([base_url, username, password]):
        return jsonify({"error": "vTiger credentials not configured in .env"}), 503

    data = request.get_json()
    emails = data.get("emails", [])
    source_form = data.get("source_form", "")
    if not emails:
        return jsonify({"error": "No emails provided"}), 400

    import httpx
    import time as time_mod

    max_retries = 5
    base_delay = 2.0
    batch_size = 50  # Emails per query to keep URL length manageable

    # Initialise results — emails not found in any batch are "new"
    results = {}

    # Process in batches
    for batch_start in range(0, len(emails), batch_size):
        batch = emails[batch_start : batch_start + batch_size]

        # Build IN clause — lowercase emails for case-insensitive matching
        in_values = ",".join(
            f"'{email.lower().replace(chr(39), chr(92) + chr(39))}'" for email in batch
        )
        query = (
            f"SELECT firstname,lastname,email,cf_contacts_formscompleted "
            f"FROM Contacts WHERE email IN ({in_values}) LIMIT 100;"
        )

        matches = []
        for attempt in range(max_retries + 1):
            try:
                resp = httpx.get(
                    f"{base_url}/query",
                    params={"query": query},
                    auth=(username, password),
                    timeout=30,
                )

                if resp.status_code == 429 and attempt < max_retries:
                    time_mod.sleep(base_delay * (2**attempt))
                    continue

                resp.raise_for_status()
                resp_data = resp.json()
                matches = resp_data.get("result", [])
                print(
                    f"[vtiger lookup] Batch {batch_start // batch_size + 1}: "
                    f"queried {len(batch)} emails, got {len(matches)} matches"
                )
                break
            except Exception as exc:
                if attempt < max_retries:
                    time_mod.sleep(base_delay * (2**attempt))
                    continue
                # On final failure, mark all emails in batch as errored
                for email in batch:
                    results[email] = {"found": False, "has_tag": False, "error": str(exc)}

        # Index matches by email (lowercase for matching)
        matches_by_email = {}
        for contact in matches:
            contact_email = contact.get("email", "").lower()
            matches_by_email.setdefault(contact_email, []).append(contact)

        # Build results for each email in the batch
        for email in batch:
            if email in results:
                continue  # Already set (e.g. from error handling)

            contact_matches = matches_by_email.get(email.lower(), [])

            has_tag = False
            if source_form and contact_matches:
                for contact in contact_matches:
                    forms = contact.get("cf_contacts_formscompleted", "")
                    tags = [t.strip() for t in forms.split("|##|")]
                    if source_form in tags:
                        has_tag = True
                        break

            results[email] = {
                "found": len(contact_matches) > 0,
                "has_tag": has_tag,
                "count": len(contact_matches),
                "contacts": contact_matches,
            }

    not_found = [e for e, r in results.items() if not r.get("found")]
    if not_found:
        print(f"[vtiger lookup] Not found ({len(not_found)}): {not_found[:10]}")

    return jsonify({"results": results})


@app.route("/api/tests", methods=["POST"])
def run_tests():
    """Run pytest and return structured results."""
    venv_pytest = PROJECT_ROOT / ".venv" / "bin" / "pytest"
    pytest_cmd = str(venv_pytest) if venv_pytest.exists() else "pytest"

    result = subprocess.run(
        [pytest_cmd, "--tb=short", "-v"],
        capture_output=True,
        text=True,
        cwd=str(PROJECT_ROOT),
        timeout=60,
    )

    # Parse verbose pytest output into structured results
    tests = []
    for line in result.stdout.splitlines():
        if " PASSED" in line or " FAILED" in line or " ERROR" in line:
            # Lines look like: tests/test_foo.py::TestClass::test_name PASSED
            parts = line.rsplit(" ", 1)
            if len(parts) == 2:
                nodeid = parts[0].strip()
                status = parts[1].strip()
                tests.append({"nodeid": nodeid, "status": status})

    return jsonify(
        {
            "exit_code": result.returncode,
            "passed": sum(1 for t in tests if t["status"] == "PASSED"),
            "failed": sum(1 for t in tests if t["status"] == "FAILED"),
            "errors": sum(1 for t in tests if t["status"] == "ERROR"),
            "tests": tests,
            "output": result.stdout + result.stderr,
        }
    )


def detect_column_mapping_from_file(file_path):
    """Detect column mapping from a TSV file, returning (mapping, headers)."""
    import csv

    with open(file_path, encoding="utf-8") as f:
        first_line = f.readline().strip()
        f.seek(0)
        reader = csv.reader(f, delimiter="\t")
        headers = next(reader)

    mapping = detect_column_mapping_from_headers(headers)
    return mapping, headers


if __name__ == "__main__":
    print(f"Uploads directory: {UPLOADS_DIR}")
    app.run(debug=True, port=5001)
