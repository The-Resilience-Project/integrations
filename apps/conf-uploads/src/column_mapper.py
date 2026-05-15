"""
Column mapping detection for TSV files.

This module provides functionality to automatically detect column mappings
from TSV file headers, matching them to expected field names.
"""

import csv
from pathlib import Path
from typing import Optional


def normalize_header(header: str) -> str:
    """Normalize a header string for matching."""
    return header.strip().lower()


def detect_first_name(headers: list[str]) -> Optional[int]:
    """Detect the first name column index."""
    normalized = [normalize_header(h) for h in headers]
    for i, h in enumerate(normalized):
        if h == "first_name":
            return i
        if "first" in h and "name" in h:
            return i
        if h in ["first", "firstname", "given name", "forename"]:
            return i
    return None


def detect_last_name(headers: list[str]) -> Optional[int]:
    """Detect the last name column index."""
    normalized = [normalize_header(h) for h in headers]
    for i, h in enumerate(normalized):
        if h == "last_name":
            return i
        if "last" in h and "name" in h:
            return i
        if h in ["last", "lastname", "surname", "family name"]:
            return i
    return None


def detect_email(headers: list[str]) -> Optional[int]:
    """Detect the email column index."""
    normalized = [normalize_header(h) for h in headers]
    for i, h in enumerate(normalized):
        if "email" in h or "e-mail" in h:
            return i
    return None


def detect_organisation(headers: list[str]) -> Optional[int]:
    """Detect the organisation column index."""
    normalized = [normalize_header(h) for h in headers]
    for i, h in enumerate(normalized):
        # Check for exact match first
        if h == "org":
            return i
        # Then check for partial matches
        if any(
            word in h
            for word in ["organi", "school", "workplace", "company", "business", "institution"]
        ):
            return i
    return None


def detect_num_of_students(headers: list[str]) -> Optional[int]:
    """Detect the number of students/employees column index."""
    normalized = [normalize_header(h) for h in headers]
    for i, h in enumerate(normalized):
        if h == "num_of_students":
            return i
        has_number = any(word in h for word in ["number", "num", "#", "no.", "count"])
        has_target = any(word in h for word in ["student", "employee", "staff", "pupil"])
        if has_number and has_target:
            return i
    return None


def detect_job_title(headers: list[str]) -> Optional[int]:
    """Detect the job title column index."""
    normalized = [normalize_header(h) for h in headers]
    for i, h in enumerate(normalized):
        if h == "job_title":
            return i
        if any(word in h for word in ["job", "title", "position", "role"]):
            return i
    return None


def detect_phone(headers: list[str]) -> Optional[int]:
    """Detect the phone column index."""
    normalized = [normalize_header(h) for h in headers]
    for i, h in enumerate(normalized):
        # Check for phone-related words and avoid matching "contact email" or similar
        if any(word in h for word in ["phone", "mobile", "tel", "contact"]) and "email" not in h:
            return i
    return None


def detect_state(headers: list[str]) -> Optional[int]:
    """Detect the state column index."""
    normalized = [normalize_header(h) for h in headers]
    for i, h in enumerate(normalized):
        if h in ["state", "state/territory", "province", "region"]:
            return i
        if "state" in h or "territory" in h:
            return i
    return None


def detect_full_name(headers: list[str]) -> Optional[int]:
    """Detect a single name column (e.g. 'Attendee Name:', 'Full Name').

    Only used as a fallback for inputs that don't have separate first/last
    name columns. Skips headers already claimed by detect_first_name,
    detect_last_name, or detect_organisation.
    """
    normalized = [normalize_header(h) for h in headers]
    for i, h in enumerate(normalized):
        if any(
            skip in h
            for skip in [
                "first",
                "last",
                "surname",
                "school",
                "organi",
                "workplace",
                "company",
                "myschool_name",
                "myschool name",
            ]
        ):
            continue
        if "name" in h:
            return i
    return None


def detect_postcode(headers: list[str]) -> Optional[int]:
    """Detect the postcode column."""
    normalized = [normalize_header(h) for h in headers]
    for i, h in enumerate(normalized):
        if "postcode" in h or "post code" in h or "postal code" in h or h == "zip":
            return i
    return None


def detect_myschool_name(headers: list[str]) -> Optional[int]:
    """Detect the myschool_name column index.

    Populated by prepare_ts_attendee with the matched myschool.edu.au name
    when it differs from the input org. Carried for manual review only.
    """
    normalized = [normalize_header(h) for h in headers]
    for i, h in enumerate(normalized):
        if h in ["myschool_name", "myschool name"]:
            return i
    return None


def detect_vtiger_org_name(headers: list[str]) -> Optional[int]:
    """Detect the vtiger_org_name column index.

    Populated by prepare_ts_attendee with the closest-matching vTiger Account
    name when it differs from the input org. The upload step prefers this
    value over `org` so captureCustomerInfo matches the existing record.
    """
    normalized = [normalize_header(h) for h in headers]
    for i, h in enumerate(normalized):
        if h in ["vtiger_org_name", "vtiger org name"]:
            return i
    return None


def detect_enquiry(headers: list[str]) -> Optional[int]:
    """Detect the enquiry/comments column index."""
    normalized = [normalize_header(h) for h in headers]
    for i, h in enumerate(normalized):
        if any(
            word in h for word in ["enquiry", "inquiry", "comment", "notes", "message", "question"]
        ):
            return i
    return None


def detect_column_mapping_from_headers(headers: list[str]) -> dict[str, int]:
    """
    Detect column mapping from a list of headers.

    Args:
        headers: List of header strings from the TSV file

    Returns:
        Dictionary mapping field names to column indices
    """
    mapping = {}

    # Detect each field
    if (idx := detect_first_name(headers)) is not None:
        mapping["first_name"] = idx
    if (idx := detect_last_name(headers)) is not None:
        mapping["last_name"] = idx
    if (idx := detect_email(headers)) is not None:
        mapping["email"] = idx
    if (idx := detect_organisation(headers)) is not None:
        mapping["org"] = idx
    if (idx := detect_num_of_students(headers)) is not None:
        mapping["num_of_students"] = idx
    if (idx := detect_job_title(headers)) is not None:
        mapping["job_title"] = idx
    if (idx := detect_phone(headers)) is not None:
        mapping["phone"] = idx
    if (idx := detect_state(headers)) is not None:
        mapping["state"] = idx
    if (idx := detect_enquiry(headers)) is not None:
        mapping["enquiry"] = idx
    if (idx := detect_postcode(headers)) is not None:
        mapping["postcode"] = idx
    if (idx := detect_myschool_name(headers)) is not None:
        mapping["myschool_name"] = idx
    if (idx := detect_vtiger_org_name(headers)) is not None:
        mapping["vtiger_org_name"] = idx
    # Only fall back to a single-name column if no separate first/last
    # were found — otherwise we'd double-detect on a "School Name" column.
    if (
        "first_name" not in mapping
        and "last_name" not in mapping
        and (idx := detect_full_name(headers)) is not None
    ):
        mapping["full_name"] = idx

    return mapping


def has_header_row(first_line: str) -> bool:
    """
    Check if the first line appears to be a header row.

    Args:
        first_line: The first line of the file

    Returns:
        True if it appears to be a header row, False otherwise
    """
    normalized = first_line.lower()
    header_indicators = [
        "name",
        "email",
        "first",
        "last",
        "organisation",
        "organization",
        "school",
        "postcode",
        "attendee",
    ]
    return any(word in normalized for word in header_indicators)


def detect_column_mapping(file_path: Path) -> tuple[Optional[dict[str, int]], Optional[list[str]]]:
    """
    Detect column mapping from a TSV file.

    Args:
        file_path: Path to the TSV file

    Returns:
        Tuple of (mapping dictionary, headers list) or (None, None) if no headers detected
    """
    with open(file_path, encoding="utf-8") as f:
        first_line = f.readline().strip()

        # Check if first line looks like a header
        if not has_header_row(first_line):
            return None, None

        # Read headers
        f.seek(0)
        reader = csv.reader(f, delimiter="\t")
        headers = next(reader)

        mapping = detect_column_mapping_from_headers(headers)

        return mapping, headers
