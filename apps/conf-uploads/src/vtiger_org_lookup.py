"""
vTiger Account lookup by school name.

Queries vTiger's REST API (using the same VTIGER_* credentials as api_server)
and returns the closest-matching Account by Levenshtein distance.

Used by the TS Attendee prep flow so reviewers can see, for each row, which
vTiger org the upload will likely match — and spot duplicates before they're
created.

CLI debug mode:
    python vtiger_org_lookup.py "Oyster Bay Public School"
"""

from __future__ import annotations

import argparse
import json
import os
import re
import sys
from functools import cache
from pathlib import Path
from urllib.parse import urlparse

import httpx
from dotenv import load_dotenv

from student_lookup import levenshtein, token_distance

# Load apps/conf-uploads/.env so VTIGER_* creds are available whether this
# module is imported (from prepare_ts_attendee) or run directly as a CLI.
load_dotenv(Path(__file__).resolve().parent.parent / ".env")

# `accountname` LIKE '%TOKEN%' — pulled from the longest word in the input
# name. Short words (≤3 chars: "St", "the", "of") match too broadly to be
# useful as a query seed.
MIN_TOKEN_LEN = 4

# Cap on rows returned per LIKE query — protects us from pathologically broad
# matches (e.g. searching "School" against thousands of orgs).
QUERY_LIMIT = 200


def _credentials() -> tuple[str, str, str] | None:
    """Return (base_url, username, password) or None if not all configured."""
    base_url = os.getenv("VT_URL")
    username = os.getenv("VT_USERNAME")
    password = os.getenv("VT_ACCESSKEY")
    if not all([base_url, username, password]):
        return None
    return base_url, username, password  # type: ignore[return-value]


def _ui_origin(base_url: str) -> str:
    """Derive the public vTiger UI origin from the REST base URL.

    e.g. 'https://x.od2.vtiger.com/restapi/v1/vtiger/default' -> 'https://x.od2.vtiger.com'
    """
    parsed = urlparse(base_url)
    return f"{parsed.scheme}://{parsed.netloc}"


def _record_url(base_url: str, record_id: str) -> str:
    """Build a UI link to an Accounts record."""
    return f"{_ui_origin(base_url)}/view/detail?module=Accounts&id={record_id.removeprefix('3x')}&viewtype=summary&relatedtab=Summary&relationid=__summary__"


def _pick_search_tokens(name: str) -> list[str]:
    """Return all distinctive words (>= MIN_TOKEN_LEN), longest first.

    Querying every word and unioning the results gives us multiple shots at
    pulling the right record into the candidate set. A single longest-word
    query is brittle when the longest word is also common: "College" or
    "Canberra" alone saturates LIMIT and truncates the actual match out.
    """
    words = re.findall(r"[A-Za-z]+", name)
    seen: set[str] = set()
    tokens: list[str] = []
    for w in words:
        if len(w) < MIN_TOKEN_LEN:
            continue
        lw = w.lower()
        if lw in seen:
            continue
        seen.add(lw)
        tokens.append(w)
    tokens.sort(key=len, reverse=True)
    return tokens


def _escape(value: str) -> str:
    """Escape single quotes for vTiger SQL-style query string."""
    return value.replace("'", "''")


def _query_accounts(
    token: str, creds: tuple[str, str, str], debug: bool = False
) -> list[dict]:
    """Run a LIKE query against the Accounts module. Returns raw match list."""
    base_url, username, password = creds
    query = (
        f"SELECT id, accountname FROM Accounts "
        f"WHERE accountname LIKE '%{_escape(token)}%' LIMIT {QUERY_LIMIT};"
    )
    url = f"{base_url.rstrip('/')}/query"
    if debug:
        print(f"  [debug] url: {url}", file=sys.stderr)
        print(f"  [debug] query: {query}", file=sys.stderr)
    resp = httpx.get(
        url,
        params={"query": query},
        auth=(username, password),
        timeout=30,
    )
    resp.raise_for_status()
    body = resp.json()
    if debug:
        print(f"  [debug] response: {json.dumps(body)[:500]}", file=sys.stderr)
    return body.get("result", []) or []


def _pick_best(matches: list[dict], school_name: str) -> dict | None:
    """Pick the closest account by (token_distance, full Levenshtein).

    Full-string Levenshtein over-weights shared suffix words like "Public
    School" and biases against length differences — so "Jamison HS" loses
    to "Smithton High School" purely because "Smithton" is closer to
    "Jamison" in chars than "HS" is to "High School" in length.

    token_distance (sum of best per-query-token Levenshtein, copied from
    student_lookup) naturally weights distinctive words higher: "Marist"
    matching "Marist" contributes 0 while "Canberra" vs "Bendigo"
    contributes ~5, so the right "Marist ... Canberra" candidate beats a
    "Marist College, Bendigo" sibling.

    Full Levenshtein is kept as the tiebreaker.
    """
    if not matches:
        return None
    target = school_name.strip().lower()
    return min(
        matches,
        key=lambda m: (
            token_distance(target, (m.get("accountname") or "").lower()),
            levenshtein(target, (m.get("accountname") or "").lower()),
        ),
    )


@cache
def lookup_org(school_name: str, debug: bool = False) -> dict | None:
    """Find the closest-matching vTiger Account for a school name.

    Args:
        school_name: Canonical school name to search for (typically the
            myschool-matched name from `lookup_school_details`).
        debug: When true, log the query, response, and pick to stderr.

    Returns:
        Dict with `org_name`, `org_id`, `org_url`, `distance`, or None if
        credentials are missing, the query fails, or no matches are found.
    """
    if not school_name:
        return None
    creds = _credentials()
    if creds is None:
        return None

    tokens = _pick_search_tokens(school_name)
    if not tokens:
        if debug:
            print(
                f"  [debug] no usable search token in {school_name!r}", file=sys.stderr
            )
        return None
    if debug:
        print(f"  [debug] search tokens: {tokens!r}", file=sys.stderr)

    target = school_name.strip().lower()
    seen_ids: set[str] = set()
    matches: list[dict] = []
    for token in tokens:
        try:
            batch = _query_accounts(token, creds, debug=debug)
        except Exception as e:  # noqa: BLE001 — network/auth shouldn't kill the row
            print(
                f"  ⚠️  vTiger org lookup failed for {school_name!r} "
                f"(token {token!r}): {e}",
                file=sys.stderr,
            )
            continue
        for m in batch:
            mid = m.get("id")
            if mid and mid not in seen_ids:
                seen_ids.add(mid)
                matches.append(m)
        # Early-exit on an exact match — no need to query remaining tokens.
        best_so_far = _pick_best(matches, school_name)
        if (
            best_so_far is not None
            and levenshtein(target, (best_so_far.get("accountname") or "").lower()) == 0
        ):
            if debug:
                print("  [debug] exact match found, stopping early", file=sys.stderr)
            break

    if debug:
        print(f"  [debug] {len(matches)} unique candidate(s)", file=sys.stderr)

    best = _pick_best(matches, school_name)
    if best is None:
        return None

    org_id = best.get("id") or ""
    org_name = best.get("accountname") or ""
    return {
        "org_name": org_name,
        "org_id": org_id,
        "org_url": _record_url(creds[0], org_id) if org_id else "",
        "distance": levenshtein(school_name.strip().lower(), org_name.lower()),
    }


def main() -> int:
    parser = argparse.ArgumentParser(
        description="Look up the closest vTiger Account for a school name."
    )
    parser.add_argument("name", help="School name to search for")
    args = parser.parse_args()

    if _credentials() is None:
        print(
            "VTIGER_BASE_URL / VTIGER_USERNAME / VTIGER_PASSWORD not all set "
            "in environment.",
            file=sys.stderr,
        )
        return 1

    result = lookup_org(args.name, debug=True)
    if result is None:
        print(json.dumps({"query": args.name, "match": None}, indent=2))
        return 1
    print(json.dumps({"query": args.name, "match": result}, indent=2))
    return 0


if __name__ == "__main__":
    sys.exit(main())
