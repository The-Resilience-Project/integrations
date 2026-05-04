"""
School student-count lookup via myschool.edu.au.

Public API used by the TS Attendee prep flow:
    lookup_num_of_students(school_name, state) -> str | None

Also runnable as a CLI for one-off debugging:
    python student_lookup.py "Belgrave Heights Christian School" VIC
"""

from __future__ import annotations

import argparse
import json
import sys
from functools import cache
from urllib.parse import urlencode
from urllib.request import Request, urlopen

API_URL = (
    "https://myschool.edu.au/content/acara-myschool/au/en/school-search"
    "/jcr:content/root/container/school_search_result.json"
)

VALID_STATES = {"NSW", "VIC", "QLD", "SA", "WA", "TAS", "NT", "ACT"}


def fetch_schools(name: str, state: str, pages: int = 1) -> list[dict]:
    """Fetch schools matching `name` in `state`. Server returns 20 per page."""
    docs: list[dict] = []
    for page in range(1, pages + 1):
        query = urlencode(
            [
                ("FormPosted", "True"),
                ("SchoolSearchQuery", name),
                ("SchoolSector", ""),
                ("SchoolType", ""),
                ("State", state),
                ("pageNumber", str(page)),
            ]
        )
        req = Request(
            f"{API_URL}?{query}",
            headers={"User-Agent": "Mozilla/5.0", "Accept": "application/json"},
        )
        with urlopen(req, timeout=15) as resp:
            payload = json.load(resp)
        page_docs = payload.get("response", {}).get("docs", [])
        docs.extend(page_docs)
        if len(page_docs) < 20:
            break
    return docs


def fetch_typo_tolerant(name: str, state: str) -> list[dict]:
    """Fallback search: try every 4-char window of each word, then union results.

    The API only does substring matching, so a typo near the start of a word
    (e.g. 'bealgrave' for 'Belgrave') breaks a single prefix lookup. Sliding
    windows give us multiple chances to hit a substring that survives the typo.
    """
    state_uc = state.upper()
    candidates: dict[str, dict] = {}
    seen: set[str] = set()
    n = 4
    for word in name.split():
        if len(word) < n:
            continue
        for i in range(len(word) - n + 1):
            ngram = word[i : i + n].lower()
            if ngram in seen:
                continue
            seen.add(ngram)
            for s in fetch_schools(ngram, state, pages=5):
                sid = s.get("SML_ID")
                if sid and sid not in candidates and s.get("State", "").upper() == state_uc:
                    candidates[sid] = s
    return list(candidates.values())


def levenshtein(a: str, b: str) -> int:
    """Edit distance between two strings (insertions, deletions, substitutions)."""
    if len(a) < len(b):
        a, b = b, a
    if not b:
        return len(a)
    prev = list(range(len(b) + 1))
    for i, ca in enumerate(a, 1):
        curr = [i]
        for j, cb in enumerate(b, 1):
            curr.append(
                min(
                    curr[j - 1] + 1,
                    prev[j] + 1,
                    prev[j - 1] + (ca != cb),
                )
            )
        prev = curr
    return prev[-1]


def token_distance(query: str, name: str) -> int:
    """Sum of best-match Levenshtein distance for each query token against name tokens.

    Avoids penalising school names for being longer than the query (e.g.
    'Belgrave Heights Christian School' vs 'bealgrave hights') — only the words
    the user typed have to find a match in the candidate.
    """
    name_tokens = name.lower().split()
    if not name_tokens:
        return len(query)
    return sum(min(levenshtein(q, t) for t in name_tokens) for q in query.lower().split())


def pick_school(schools: list[dict], name: str, state: str) -> dict | None:
    target = name.strip().lower()
    state_uc = state.upper()
    in_state = [s for s in schools if s.get("State", "").upper() == state_uc]
    if not in_state:
        return None
    for s in in_state:
        if s.get("SchoolName", "").lower() == target:
            return s
    return min(
        in_state,
        key=lambda s: (
            token_distance(target, s.get("SchoolName", "")),
            levenshtein(target, s.get("SchoolName", "").lower()),
        ),
    )


@cache
def lookup_school(school_name: str, state: str) -> dict | None:
    """Find the best-matching school record for `school_name` in `state`.

    Falls back to a typo-tolerant search if the initial query yields no
    in-state results. Returns the school dict or None.
    """
    state_uc = state.upper()
    schools = fetch_schools(school_name, state_uc)
    school = pick_school(schools, school_name, state_uc)
    if school is None:
        schools = fetch_typo_tolerant(school_name, state_uc)
        school = pick_school(schools, school_name, state_uc)
    return school


def lookup_school_details(school_name: str, state: str) -> dict | None:
    """Return enrichment details for the best-matching school.

    Args:
        school_name: Name of the school (organisation).
        state: Australian state/territory code (e.g. "VIC", "NSW").

    Returns:
        Dict with keys:
            - students      (int | None): None if unavailable (sub-campus).
            - matched_name  (str): Name of the matched school.
            - distance      (int): Levenshtein distance between the input
                                   name and the matched name (lowercased).
        Or None if the school could not be matched, the input is invalid,
        or the lookup fails. Errors are logged to stderr; the function
        never raises or exits.
    """
    if not school_name:
        return None
    state_uc = (state or "").upper()
    if state_uc not in VALID_STATES:
        return None

    try:
        school = lookup_school(school_name, state_uc)
    except Exception as e:  # noqa: BLE001 — network/parsing errors shouldn't kill the loop
        print(
            f"  ⚠️  lookup failed for {school_name!r} ({state_uc}): {e}",
            file=sys.stderr,
        )
        return None

    if school is None:
        return None

    matched_name = school.get("SchoolName", "")
    students = school.get("Students")
    return {
        # Sub-campus records report Students == 0 — treat as unavailable.
        "students": None if students in (0, None) else students,
        "matched_name": matched_name,
        "distance": levenshtein(school_name.strip().lower(), matched_name.lower()),
    }


def lookup_num_of_students(school_name: str, state: str) -> str | None:
    """Convenience wrapper around lookup_school_details that returns just
    the student count as a string, or None if unavailable."""
    details = lookup_school_details(school_name, state)
    if details is None or details["students"] is None:
        return None
    return str(details["students"])


def main() -> int:
    parser = argparse.ArgumentParser(description="Look up student count for a school.")
    parser.add_argument("name", help="School name")
    parser.add_argument("state", help=f"State (one of {sorted(VALID_STATES)})")
    args = parser.parse_args()

    state_uc = args.state.upper()
    if state_uc not in VALID_STATES:
        print(
            f"Invalid state '{args.state}'. Must be one of: {', '.join(sorted(VALID_STATES))}",
            file=sys.stderr,
        )
        return 1

    print(f"Searching for {args.name!r} in {state_uc}...", file=sys.stderr)
    try:
        school = lookup_school(args.name, state_uc)
    except Exception as e:  # noqa: BLE001
        print(f"Request failed: {e}", file=sys.stderr)
        return 2

    response: dict = {"query": {"name": args.name, "state": state_uc}}

    if school is None:
        response["error"] = f"No schools found for {args.name!r} in {state_uc}."
        print(json.dumps(response, indent=2))
        return 1

    matched_name = school.get("SchoolName", "")
    distance = levenshtein(args.name.strip().lower(), matched_name.lower())
    students = school.get("Students")
    students_str = "n/a (sub-campus)" if students in (0, None) else f"{students:,}"

    response["data"] = {
        "school": matched_name,
        "suburb": f"{school.get('Suburb')}, {school.get('State')} {school.get('Postcode')}",
        "sector": school.get("Sector_Desc"),
        "type": school.get("Type_Desc"),
        "students": students_str,
    }
    if distance > 0:
        response["distance"] = distance

    print(json.dumps(response, indent=2))
    return 0


if __name__ == "__main__":
    sys.exit(main())
