"""
Unit tests for column_mapper module.
"""

import sys
import tempfile
from pathlib import Path

# Add src directory to path
sys.path.insert(0, str(Path(__file__).parent.parent / "src"))

from column_mapper import (
    detect_column_mapping,
    detect_column_mapping_from_headers,
    detect_email,
    detect_enquiry,
    detect_first_name,
    detect_job_title,
    detect_last_name,
    detect_num_of_students,
    detect_organisation,
    detect_phone,
    detect_state,
    has_header_row,
    normalize_header,
)


class TestNormalizeHeader:
    """Tests for normalize_header function."""

    def test_normalize_basic(self):
        assert normalize_header("First Name") == "first name"

    def test_normalize_with_whitespace(self):
        assert normalize_header("  Email Address  ") == "email address"

    def test_normalize_mixed_case(self):
        assert normalize_header("OrGaNiSaTiOn") == "organisation"


class TestDetectFirstName:
    """Tests for detect_first_name function."""

    def test_standard_first_name(self):
        headers = ["First Name", "Last Name", "Email"]
        assert detect_first_name(headers) == 0

    def test_lowercase_first_name(self):
        headers = ["first name", "last name", "email"]
        assert detect_first_name(headers) == 0

    def test_underscore_first_name(self):
        headers = ["first_name", "last_name", "email"]
        assert detect_first_name(headers) == 0

    def test_just_first(self):
        headers = ["First", "Last", "Email"]
        assert detect_first_name(headers) == 0

    def test_given_name(self):
        headers = ["Given Name", "Surname", "Email"]
        assert detect_first_name(headers) == 0

    def test_first_name_not_first_column(self):
        headers = ["Email", "First Name", "Last Name"]
        assert detect_first_name(headers) == 1

    def test_no_first_name(self):
        headers = ["Email", "Phone", "Organisation"]
        assert detect_first_name(headers) is None


class TestDetectLastName:
    """Tests for detect_last_name function."""

    def test_standard_last_name(self):
        headers = ["First Name", "Last Name", "Email"]
        assert detect_last_name(headers) == 1

    def test_surname(self):
        headers = ["First Name", "Surname", "Email"]
        assert detect_last_name(headers) == 1

    def test_family_name(self):
        headers = ["Given Name", "Family Name", "Email"]
        assert detect_last_name(headers) == 1

    def test_no_last_name(self):
        headers = ["First Name", "Email", "Phone"]
        assert detect_last_name(headers) is None


class TestDetectEmail:
    """Tests for detect_email function."""

    def test_standard_email(self):
        headers = ["First Name", "Last Name", "Email"]
        assert detect_email(headers) == 2

    def test_email_address(self):
        headers = ["First Name", "Last Name", "Email Address"]
        assert detect_email(headers) == 2

    def test_lowercase_email(self):
        headers = ["first name", "last name", "email"]
        assert detect_email(headers) == 2

    def test_hyphenated_email(self):
        headers = ["First Name", "Last Name", "E-mail"]
        assert detect_email(headers) == 2

    def test_no_email(self):
        headers = ["First Name", "Last Name", "Phone"]
        assert detect_email(headers) is None


class TestDetectOrganisation:
    """Tests for detect_organisation function."""

    def test_organisation(self):
        headers = ["First Name", "Last Name", "Email", "Organisation"]
        assert detect_organisation(headers) == 3

    def test_organization_us_spelling(self):
        headers = ["First Name", "Last Name", "Email", "Organization"]
        assert detect_organisation(headers) == 3

    def test_school(self):
        headers = ["First Name", "Last Name", "Email", "School"]
        assert detect_organisation(headers) == 3

    def test_workplace(self):
        headers = ["First Name", "Last Name", "Email", "Workplace"]
        assert detect_organisation(headers) == 3

    def test_company(self):
        headers = ["First Name", "Last Name", "Email", "Company"]
        assert detect_organisation(headers) == 3

    def test_no_organisation(self):
        headers = ["First Name", "Last Name", "Email"]
        assert detect_organisation(headers) is None


class TestDetectNumOfStudents:
    """Tests for detect_num_of_students function."""

    def test_number_of_students(self):
        headers = ["First Name", "Last Name", "Email", "Organisation", "Number of Students"]
        assert detect_num_of_students(headers) == 4

    def test_num_students(self):
        headers = ["First Name", "Last Name", "Email", "Organisation", "Num Students"]
        assert detect_num_of_students(headers) == 4

    def test_hash_students(self):
        headers = ["First Name", "Last Name", "Email", "Organisation", "# Students"]
        assert detect_num_of_students(headers) == 4

    def test_number_of_employees(self):
        headers = ["First Name", "Last Name", "Email", "Organisation", "Number of Employees"]
        assert detect_num_of_students(headers) == 4

    def test_no_num_students(self):
        headers = ["First Name", "Last Name", "Email", "Organisation"]
        assert detect_num_of_students(headers) is None


class TestDetectJobTitle:
    """Tests for detect_job_title function."""

    def test_job_title(self):
        headers = ["First Name", "Last Name", "Email", "Job Title"]
        assert detect_job_title(headers) == 3

    def test_title(self):
        headers = ["First Name", "Last Name", "Email", "Title"]
        assert detect_job_title(headers) == 3

    def test_position(self):
        headers = ["First Name", "Last Name", "Email", "Position"]
        assert detect_job_title(headers) == 3

    def test_no_job_title(self):
        headers = ["First Name", "Last Name", "Email"]
        assert detect_job_title(headers) is None


class TestDetectPhone:
    """Tests for detect_phone function."""

    def test_phone(self):
        headers = ["First Name", "Last Name", "Email", "Phone"]
        assert detect_phone(headers) == 3

    def test_mobile(self):
        headers = ["First Name", "Last Name", "Email", "Mobile"]
        assert detect_phone(headers) == 3

    def test_telephone(self):
        headers = ["First Name", "Last Name", "Email", "Telephone"]
        assert detect_phone(headers) == 3

    def test_contact_number(self):
        headers = ["First Name", "Last Name", "Email", "Contact Number"]
        assert detect_phone(headers) == 3

    def test_no_phone(self):
        headers = ["First Name", "Last Name", "Email"]
        assert detect_phone(headers) is None


class TestDetectState:
    """Tests for detect_state function."""

    def test_state(self):
        headers = ["First Name", "Last Name", "Email", "State"]
        assert detect_state(headers) == 3

    def test_state_territory(self):
        headers = ["First Name", "Last Name", "Email", "State/Territory"]
        assert detect_state(headers) == 3

    def test_no_state(self):
        headers = ["First Name", "Last Name", "Email"]
        assert detect_state(headers) is None


class TestDetectEnquiry:
    """Tests for detect_enquiry function."""

    def test_enquiry(self):
        headers = ["First Name", "Last Name", "Email", "Enquiry"]
        assert detect_enquiry(headers) == 3

    def test_inquiry(self):
        headers = ["First Name", "Last Name", "Email", "Inquiry"]
        assert detect_enquiry(headers) == 3

    def test_comments(self):
        headers = ["First Name", "Last Name", "Email", "Comments"]
        assert detect_enquiry(headers) == 3

    def test_notes(self):
        headers = ["First Name", "Last Name", "Email", "Notes"]
        assert detect_enquiry(headers) == 3

    def test_no_enquiry(self):
        headers = ["First Name", "Last Name", "Email"]
        assert detect_enquiry(headers) is None


class TestDetectColumnMappingFromHeaders:
    """Tests for detect_column_mapping_from_headers function."""

    def test_all_required_fields(self):
        headers = ["First Name", "Last Name", "Email", "Organisation"]
        mapping = detect_column_mapping_from_headers(headers)

        assert mapping["first_name"] == 0
        assert mapping["last_name"] == 1
        assert mapping["email"] == 2
        assert mapping["org"] == 3

    def test_all_fields(self):
        headers = [
            "First Name",
            "Last Name",
            "Email",
            "Organisation",
            "Number of Students",
            "Job Title",
            "Phone",
            "State",
            "Comments",
        ]
        mapping = detect_column_mapping_from_headers(headers)

        assert mapping["first_name"] == 0
        assert mapping["last_name"] == 1
        assert mapping["email"] == 2
        assert mapping["org"] == 3
        assert mapping["num_of_students"] == 4
        assert mapping["job_title"] == 5
        assert mapping["phone"] == 6
        assert mapping["state"] == 7
        assert mapping["enquiry"] == 8

    def test_different_order(self):
        headers = ["Email", "Organisation", "First Name", "Last Name"]
        mapping = detect_column_mapping_from_headers(headers)

        assert mapping["email"] == 0
        assert mapping["org"] == 1
        assert mapping["first_name"] == 2
        assert mapping["last_name"] == 3

    def test_missing_optional_fields(self):
        headers = ["First Name", "Last Name", "Email", "Organisation"]
        mapping = detect_column_mapping_from_headers(headers)

        assert "first_name" in mapping
        assert "last_name" in mapping
        assert "email" in mapping
        assert "org" in mapping
        assert "num_of_students" not in mapping
        assert "job_title" not in mapping


class TestHasHeaderRow:
    """Tests for has_header_row function."""

    def test_with_header(self):
        assert has_header_row("First Name\tLast Name\tEmail") is True

    def test_with_email_header(self):
        assert has_header_row("Email\tPhone\tOrganisation") is True

    def test_without_header(self):
        assert has_header_row("John\tDoe\tjohn@example.com") is False

    def test_empty_line(self):
        assert has_header_row("") is False


class TestDetectColumnMapping:
    """Tests for detect_column_mapping function (file-based)."""

    def test_file_with_headers(self):
        # Create temporary TSV file with headers
        with tempfile.NamedTemporaryFile(mode="w", suffix=".tsv", delete=False) as f:
            f.write("First Name\tLast Name\tEmail\tOrganisation\n")
            f.write("John\tDoe\tjohn@example.com\tAcme Corp\n")
            temp_path = Path(f.name)

        try:
            mapping, headers = detect_column_mapping(temp_path)

            assert mapping is not None
            assert headers is not None
            assert mapping["first_name"] == 0
            assert mapping["last_name"] == 1
            assert mapping["email"] == 2
            assert mapping["org"] == 3
            assert headers == ["First Name", "Last Name", "Email", "Organisation"]
        finally:
            temp_path.unlink()

    def test_file_without_headers(self):
        # Create temporary TSV file without headers
        with tempfile.NamedTemporaryFile(mode="w", suffix=".tsv", delete=False) as f:
            f.write("John\tDoe\tjohn@example.com\tAcme Corp\n")
            f.write("Jane\tSmith\tjane@example.com\tBeta Inc\n")
            temp_path = Path(f.name)

        try:
            mapping, headers = detect_column_mapping(temp_path)

            assert mapping is None
            assert headers is None
        finally:
            temp_path.unlink()

    def test_file_with_all_fields(self):
        # Create temporary TSV file with all possible fields
        with tempfile.NamedTemporaryFile(mode="w", suffix=".tsv", delete=False) as f:
            f.write(
                "First Name\tLast Name\tEmail\tSchool\tNumber of Students\tJob Title\tPhone\tState\tComments\n"
            )
            f.write(
                "John\tDoe\tjohn@example.com\tAcme School\t500\tPrincipal\t555-1234\tNSW\tTest\n"
            )
            temp_path = Path(f.name)

        try:
            mapping, headers = detect_column_mapping(temp_path)

            assert mapping is not None
            assert len(mapping) == 9
            assert "first_name" in mapping
            assert "last_name" in mapping
            assert "email" in mapping
            assert "org" in mapping
            assert "num_of_students" in mapping
            assert "job_title" in mapping
            assert "phone" in mapping
            assert "state" in mapping
            assert "enquiry" in mapping
        finally:
            temp_path.unlink()
