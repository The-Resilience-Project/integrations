"""
Australian postcode → state code lookup.

Used by the TS Attendee prep flow when the input TSV has a postcode column
but no explicit state column.

Range reference:
- 0200–0299 ACT (legacy/PO box)
- 0800–0999 NT
- 1000–1999 NSW (large-volume mail; some Sydney business)
- 2000–2599 NSW
- 2600–2618 ACT (Canberra core)
- 2619–2899 NSW
- 2900–2920 ACT (Tuggeranong, Woden)
- 2921–2999 NSW
- 3000–3999 VIC
- 4000–4999 QLD
- 5000–5999 SA
- 6000–6999 WA
- 7000–7999 TAS
- 8000–8999 VIC (business)
- 9000–9999 QLD (business)
"""

from __future__ import annotations


def state_for_postcode(postcode: str | int | None) -> str | None:
    """Map an Australian postcode to its state code (NSW/VIC/QLD/SA/WA/TAS/NT/ACT).

    Handles 3-digit NT postcodes that lose their leading zero in spreadsheets
    (e.g. "871" → NT). Returns None for empty / unrecognised values.
    """
    if postcode is None:
        return None
    try:
        pc = int(str(postcode).strip())
    except ValueError:
        return None

    if 200 <= pc <= 299:
        return "ACT"
    if 800 <= pc <= 999:
        return "NT"
    if 1000 <= pc <= 2599:
        return "NSW"
    if 2600 <= pc <= 2618:
        return "ACT"
    if 2619 <= pc <= 2899:
        return "NSW"
    if 2900 <= pc <= 2920:
        return "ACT"
    if 2921 <= pc <= 2999:
        return "NSW"
    if 3000 <= pc <= 3999 or 8000 <= pc <= 8999:
        return "VIC"
    if 4000 <= pc <= 4999 or 9000 <= pc <= 9999:
        return "QLD"
    if 5000 <= pc <= 5999:
        return "SA"
    if 6000 <= pc <= 6999:
        return "WA"
    if 7000 <= pc <= 7999:
        return "TAS"
    return None
