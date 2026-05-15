"""
Import engine — reusable functions for building request bodies and sending contacts to vTiger.

Extracted from import_leads.py so both CLI and web UI can share the same logic.
"""

import time

import httpx

# API endpoint URLs
#
# "TS Attendee" is the v2 endpoint — wire-compatible with TsAttendeeRequest
# at src/api-v2/Domain/TsAttendeeRequest.php. The other two are v1.
ENDPOINTS = {
    "Prize Pack": "https://theresilienceproject.com.au/resilience/api/prize_pack.php",
    "Enquiry": "https://theresilienceproject.com.au/resilience/api/enquiry.php",
    "TS Attendee": (
        "https://theresilienceproject.com.au/resilience/api/v2/schools/ts/upload-attendees"
    ),
}


def get_field(contact_data, index):
    """Safely get field value from contact_data by index.

    Returns the string value if the index is valid and the field is non-empty,
    otherwise None.
    """
    if index is not None and len(contact_data) > index and contact_data[index]:
        return contact_data[index]
    return None


def build_request_body(contact_data, column_mapping, service_type, source_form):
    """Build the POST body dict for a single contact row.

    Args:
        contact_data: List of field values for one row.
        column_mapping: Dict mapping field names to column indices.
        service_type: One of "School", "Workplace", "Early Years".
        source_form: The source form name string.

    Returns:
        Dict suitable for POSTing to the vTiger API.
    """
    body = {
        "service_type": service_type,
        "source_form": source_form,
        "contact_first_name": get_field(contact_data, column_mapping.get("first_name")),
        "contact_last_name": get_field(contact_data, column_mapping.get("last_name")),
        "contact_email": get_field(contact_data, column_mapping.get("email")),
    }

    # Add organisation based on service type
    org_value = get_field(contact_data, column_mapping.get("org"))
    if service_type == "School":
        body["school_name_other_selected"] = True
        body["school_name_other"] = org_value
    elif service_type == "Workplace":
        body["workplace_name_other_selected"] = True
        body["workplace_name_other"] = org_value
    elif service_type == "Early Years":
        body["service_name_other_selected"] = True
        body["earlyyears_name_other"] = org_value

    # Add optional fields if they exist
    num_of_students = get_field(contact_data, column_mapping.get("num_of_students"))
    if num_of_students:
        body["num_of_employees"] = num_of_students

    job_title = get_field(contact_data, column_mapping.get("job_title"))
    if job_title:
        body["job_title"] = job_title

    phone = get_field(contact_data, column_mapping.get("phone"))
    if phone:
        body["contact_phone"] = phone.replace("'", "")

    state = get_field(contact_data, column_mapping.get("state"))
    if state:
        body["state"] = state

    enquiry = get_field(contact_data, column_mapping.get("enquiry"))
    if enquiry:
        body["enquiry"] = enquiry

    return body


def build_ts_attendee_body(contact_data, column_mapping):
    """Build the POST body for a single TS Attendee row.

    Field names match TsAttendeeRequest::fromFormData() on the server.
    `org` and `state` are required by the endpoint; everything else is optional.

    When `vtiger_org_name` is populated (the prep step found a closer-matching
    vTiger Account whose name differs from the input org), it wins over `org`
    so captureCustomerInfo matches the existing record instead of creating a
    duplicate. A blank `vtiger_org_name` means either the vTiger match equalled
    the input org or there was no vTiger match — in both cases `org` is right.
    """
    school_name = get_field(contact_data, column_mapping.get("vtiger_org_name")) or get_field(
        contact_data, column_mapping.get("org")
    )
    body = {
        "contact_email": get_field(contact_data, column_mapping.get("email")),
        "contact_first_name": get_field(contact_data, column_mapping.get("first_name")),
        "contact_last_name": get_field(contact_data, column_mapping.get("last_name")),
        "school_name": school_name,
        "state": get_field(contact_data, column_mapping.get("state")),
    }

    num_of_students = get_field(contact_data, column_mapping.get("num_of_students"))
    if num_of_students:
        body["num_of_students"] = num_of_students

    job_title = get_field(contact_data, column_mapping.get("job_title"))
    if job_title:
        body["job_title"] = job_title

    phone = get_field(contact_data, column_mapping.get("phone"))
    if phone:
        body["contact_phone"] = phone.replace("'", "")

    return body


def send_contact(body, api_endpoint, max_retries=5, base_delay=2.0):
    """POST a single contact to vTiger with retry on 429 rate limiting.

    Args:
        body: The request body dict.
        api_endpoint: The full URL to POST to.
        max_retries: Maximum number of retries on 429 responses.
        base_delay: Base delay in seconds between retries (doubles each retry).

    Returns:
        Dict with keys: status_code (int), email (str), success (bool).
    """
    email = body.get("contact_email", "")

    for attempt in range(max_retries + 1):
        try:
            response = httpx.post(api_endpoint, data=body, timeout=None)

            # Retry on 429 rate limiting
            if response.status_code == 429 and attempt < max_retries:
                delay = base_delay * (2**attempt)
                time.sleep(delay)
                continue

            try:
                response_body = response.json()
            except Exception:
                response_body = response.text

            # Check both HTTP status and API response status
            api_success = response.status_code == 200
            if api_success and isinstance(response_body, dict):
                api_status = response_body.get("status", "")
                if api_status == "fail":
                    api_success = False

            return {
                "status_code": response.status_code,
                "email": email,
                "success": api_success,
                "request_body": body,
                "response_body": response_body,
                "endpoint": api_endpoint,
                "attempts": attempt + 1,
            }
        except httpx.HTTPError as exc:
            if attempt < max_retries:
                delay = base_delay * (2**attempt)
                time.sleep(delay)
                continue
            return {
                "status_code": 0,
                "email": email,
                "success": False,
                "error": str(exc),
                "request_body": body,
                "response_body": None,
                "endpoint": api_endpoint,
                "attempts": attempt + 1,
            }

    # Should not reach here, but safety fallback
    return {
        "status_code": 0,
        "email": email,
        "success": False,
        "error": "Max retries exceeded",
        "request_body": body,
        "response_body": None,
        "endpoint": api_endpoint,
        "attempts": max_retries + 1,
    }
