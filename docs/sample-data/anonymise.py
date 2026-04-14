#!/usr/bin/env python3
"""
Anonymise Vtiger CRM sample data exports for vendor sharing.

Replaces real names, school names, and Zoom links with realistic fakes.
Maintains consistency: same real value → same fake value across all files.
Preserves all other data (stages, types, dates, tags, etc.) as-is.
"""

import csv
import hashlib
import os
import re
import sys

# ── Fake data pools ──────────────────────────────────────────────────

FIRST_NAMES = [
    "Alex", "Jordan", "Taylor", "Morgan", "Casey", "Riley", "Quinn", "Avery",
    "Cameron", "Drew", "Hayden", "Jamie", "Kendall", "Logan", "Peyton", "Reese",
    "Sage", "Skyler", "Blake", "Charlie", "Dakota", "Emery", "Finley", "Harper",
    "Indigo", "Jules", "Kai", "Lane", "Marley", "Noel", "Oakley", "Parker",
    "Remy", "Shay", "Tatum", "Val", "Winter", "Zion", "Arden", "Bay",
    "Corin", "Devon", "Ellis", "Frankie", "Glenn", "Holly", "Ira", "Jesse",
    "Kit", "Lake", "Marlo", "Nico", "Olive", "Phoenix", "Rain", "Sam",
    "Toni", "Uri", "Vera", "Wren", "Xen", "Yael", "Zara", "Ash",
    "Beau", "Cedar", "Darcy", "Eden", "Flynn", "Grey", "Haven", "Ivy",
    "Jude", "Kira", "Lark", "Mika", "Neve", "Orion", "Pip", "Rowan",
    "Sloane", "Thea", "Uma", "Voss", "Willow", "Xiomara", "Yuki", "Zephyr",
    "Alder", "Briar", "Clover", "Dale", "Echo", "Fable", "Greer", "Hart",
]

LAST_NAMES = [
    "Anderson", "Bennett", "Clarke", "Dawson", "Edwards", "Fletcher", "Graham",
    "Harrison", "Irving", "Jensen", "Kemp", "Lawson", "Mitchell", "Norton",
    "Oliver", "Palmer", "Quinn", "Reynolds", "Stewart", "Thompson", "Underwood",
    "Vaughan", "Walsh", "Yates", "Zimmerman", "Abbott", "Barker", "Carlisle",
    "Donovan", "Ellison", "Fairfax", "Gibson", "Holden", "Ingram", "Jarvis",
    "Kirby", "Lambert", "Mercer", "Nash", "Osborne", "Preston", "Radcliffe",
    "Sinclair", "Thornton", "Upton", "Vickers", "Whitmore", "York", "Ainsley",
    "Blackwood", "Chambers", "Drake", "Everett", "Forbes", "Gallagher", "Hale",
    "Irvine", "Jasper", "Knox", "Linden", "Marsh", "Neville", "Oakes",
    "Pemberton", "Randall", "Saxon", "Townsend", "Urquhart", "Vale", "Weston",
    "Yardley", "Zane", "Archer", "Brooke", "Cross", "Delaney", "Emerson",
    "Ford", "Grant", "Heath", "Ivory", "Joyce", "Kent", "Locke", "Monroe",
]

SCHOOL_PREFIXES = [
    "Greenfield", "Riverside", "Oakwood", "Hillcrest", "Sunnyvale", "Lakeside",
    "Northview", "Southgate", "Westwood", "Eastridge", "Pinewood", "Maplewood",
    "Cedarbrook", "Willowbank", "Ashford", "Birchwood", "Elmwood", "Ferndale",
    "Hawthorn", "Ivy", "Juniper", "Kingsley", "Larchmont", "Meadowbrook",
    "Newbury", "Orchid", "Poplar", "Rosewood", "Silverdale", "Thornbury",
    "Valley", "Windermere", "Yarrow", "Zenith", "Aurora", "Beacon",
    "Clearwater", "Driftwood", "Evergreen", "Falcon", "Golden", "Harbour",
    "Islander", "Jasmine", "Kestrel", "Lighthouse", "Moorland", "Nightingale",
    "Outlook", "Panorama", "Quayside", "Ridge", "Starling", "Tideway",
    "Upland", "Venture", "Wattle", "Yarraside", "Acacia", "Banksia",
    "Coastal", "Dune", "Eagle", "Fern", "Gumtree", "Heathland",
]

SCHOOL_TYPES = [
    "Primary School", "Secondary College", "P-12 College", "Grammar School",
    "Community College", "Public School", "High School", "College",
    "Academy", "Christian College", "Catholic College", "Anglican College",
    "Lutheran College", "State School", "P-9 College",
]

SUBURBS = [
    "Springfield", "Clearview", "Brookfield", "Ridgemont", "Sunbury",
    "Millford", "Ashton", "Fairview", "Glendale", "Hartwell",
    "Ivanhoe", "Kingston", "Langford", "Montrose", "Newport",
    "Oakdale", "Parkville", "Richmond", "Stratford", "Thornton",
    "Waverly", "Yarraville", "Altona", "Brighton", "Carlton",
    "Dandenong", "Essendon", "Footscray", "Geelong", "Heidelberg",
]

TITLES = [
    "Principal", "Assistant Principal", "Head of Wellbeing",
    "Wellbeing Coordinator", "Year Level Coordinator", "Deputy Principal",
    "Head of Curriculum", "Learning Specialist", "Teacher",
    "School Counsellor", "Student Wellbeing Leader", "Head of Student Services",
    "Classroom Teacher", "Leading Teacher", "Office Manager",
]


def stable_index(value: str, pool_size: int) -> int:
    """Deterministic mapping from a string to a pool index via hash."""
    h = hashlib.sha256(value.lower().strip().encode()).hexdigest()
    return int(h, 16) % pool_size


def fake_first_name(real: str) -> str:
    if not real or not real.strip():
        return real
    return FIRST_NAMES[stable_index(f"first:{real}", len(FIRST_NAMES))]


def fake_last_name(real: str) -> str:
    if not real or not real.strip():
        return real
    return LAST_NAMES[stable_index(f"last:{real}", len(LAST_NAMES))]


def fake_title(real: str) -> str:
    if not real or not real.strip():
        return real
    return TITLES[stable_index(f"title:{real}", len(TITLES))]


# Cache for org name consistency across files
_org_cache: dict[str, str] = {}


def fake_org_name(real: str) -> str:
    if not real or not real.strip():
        return real

    key = real.strip().lower()
    if key in _org_cache:
        return _org_cache[key]

    idx = stable_index(f"org:{real}", len(SCHOOL_PREFIXES))
    type_idx = stable_index(f"orgtype:{real}", len(SCHOOL_TYPES))
    suburb_idx = stable_index(f"suburb:{real}", len(SUBURBS))

    # Check if original has a suburb (contains comma)
    if "," in real:
        fake = f"{SCHOOL_PREFIXES[idx]} {SCHOOL_TYPES[type_idx]}, {SUBURBS[suburb_idx]}"
    else:
        fake = f"{SCHOOL_PREFIXES[idx]} {SCHOOL_TYPES[type_idx]}"

    _org_cache[key] = fake
    return fake


def fake_zoom_link(real: str) -> str:
    if not real or not real.strip():
        return real
    h = stable_index(f"zoom:{real}", 99999999999)
    return f"https://example-org.zoom.us/j/{h:011d}"


# ── File processors ──────────────────────────────────────────────────

def process_csv(input_path: str, output_path: str, transform_row):
    """Read CSV, apply transform to each row, write output."""
    with open(input_path, "r", encoding="utf-8-sig") as f:
        reader = csv.DictReader(f)
        fieldnames = reader.fieldnames
        rows = list(reader)

    transformed = [transform_row(row) for row in rows]

    with open(output_path, "w", encoding="utf-8", newline="") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames, quoting=csv.QUOTE_ALL)
        writer.writeheader()
        writer.writerows(transformed)

    print(f"  {os.path.basename(output_path)}: {len(transformed)} rows")


def transform_contacts(row: dict) -> dict:
    row = dict(row)
    row["Contacts First Name"] = fake_first_name(row.get("Contacts First Name", ""))
    row["Contacts Last Name"] = fake_last_name(row.get("Contacts Last Name", ""))
    row["Contacts Title"] = fake_title(row.get("Contacts Title", ""))
    return row


def transform_organisations(row: dict) -> dict:
    row = dict(row)
    row["Organisation Name"] = fake_org_name(row.get("Organisation Name", ""))
    return row


def transform_deals(row: dict) -> dict:
    row = dict(row)
    row["Deals Organisation Name"] = fake_org_name(row.get("Deals Organisation Name", ""))
    return row


def transform_events(row: dict) -> dict:
    row = dict(row)
    row["Zoom Link"] = fake_zoom_link(row.get("Zoom Link", ""))
    return row


def transform_registrations(row: dict) -> dict:
    row = dict(row)
    reg_name = row.get("Registration Name", "")
    if " | " in reg_name:
        name_part, event_part = reg_name.split(" | ", 1)
        # Split name into first/last
        parts = name_part.strip().split(" ", 1)
        if len(parts) == 2:
            fake = f"{fake_first_name(parts[0])} {fake_last_name(parts[1])}"
        else:
            fake = fake_first_name(parts[0])
        row["Registration Name"] = f"{fake} | {event_part}"
    return row


# ── Main ─────────────────────────────────────────────────────────────

def main():
    base_dir = os.path.dirname(os.path.abspath(__file__))
    out_dir = os.path.join(base_dir, "anonymised")
    os.makedirs(out_dir, exist_ok=True)

    print("Anonymising sample data...\n")

    files = [
        ("ExigoTech Contacts Export_14-04-2026_1247.csv", transform_contacts),
        ("ExigoTech Organisations Export_14-04-2026_1246.csv", transform_organisations),
        ("ExigoTech Deals Export_14-04-2026_1245.csv", transform_deals),
        ("ExigoTech Events Export_14-04-2026_1246.csv", transform_events),
        ("ExigoTech Registrations Export_14-04-2026_1246.csv", transform_registrations),
    ]

    for filename, transform in files:
        input_path = os.path.join(base_dir, filename)
        output_path = os.path.join(out_dir, filename)
        if not os.path.exists(input_path):
            print(f"  SKIP {filename} (not found)")
            continue
        process_csv(input_path, output_path, transform)

    print(f"\nDone! Anonymised files written to: {out_dir}/")
    print("\nSpot-check a few rows to verify before sharing.")


if __name__ == "__main__":
    main()
