#!/bin/bash
#
# Deletes POSTMAN TEST organisations and their contacts from Vtiger CRM.
# Contacts are deleted first (to avoid orphaned records), then organisations.
#
# Usage: bash postman/teardown_test_data.sh
#        bash postman/teardown_test_data.sh --dry-run   # preview only, no deletions
#        bash postman/teardown_test_data.sh --yes        # skip confirmation
#

set -euo pipefail

DRY_RUN=false
AUTO_YES=false
for arg in "$@"; do
    case "$arg" in
        --dry-run) DRY_RUN=true ;;
        --yes) AUTO_YES=true ;;
    esac
done

API_BASE="https://theresilienceproject.od2.vtiger.com/restapi/v1/vtiger/default"
AUTH="maddie@theresilienceproject.com.au:EKCC5OlQjHZjoOMh"

# --- Helpers ---

query_ids() {
    local query="$1"
    local encoded
    encoded=$(python3 -c "import urllib.parse, sys; print(urllib.parse.quote(sys.argv[1]))" "$query")
    curl -s -u "$AUTH" "$API_BASE/query?query=$encoded" \
        | python3 -c "import sys,json; [print(r['id']) for r in json.load(sys.stdin)['result']]"
}

delete_record() {
    local id="$1"
    response=$(curl -s -u "$AUTH" -X POST "$API_BASE/delete" \
        --data-urlencode "id=$id")

    success=$(echo "$response" | python3 -c "import sys,json; print(json.load(sys.stdin).get('success', False))")
    if [ "$success" != "True" ]; then
        echo "  FAILED to delete $id:"
        echo "$response" | python3 -m json.tool
        return 1
    fi
    return 0
}

# --- Find test organisations ---

echo "Searching for POSTMAN TEST organisations..."
org_ids=$(query_ids "SELECT id FROM Accounts WHERE accountname LIKE 'POSTMAN TEST%';")

if [ -z "$org_ids" ]; then
    echo "No POSTMAN TEST organisations found. Nothing to clean up."
    exit 0
fi

org_count=$(echo "$org_ids" | wc -l | tr -d ' ')
echo "Found $org_count POSTMAN TEST organisation(s)."

# --- Find contacts linked to test organisations ---

echo ""
echo "Searching for contacts linked to test organisations..."
contact_ids=""
for org_id in $org_ids; do
    ids=$(query_ids "SELECT id FROM Contacts WHERE account_id = '$org_id';")
    if [ -n "$ids" ]; then
        contact_ids="${contact_ids}${ids}"$'\n'
    fi
done
contact_ids=$(echo "$contact_ids" | sed '/^$/d')

contact_count=0
if [ -n "$contact_ids" ]; then
    contact_count=$(echo "$contact_ids" | wc -l | tr -d ' ')
fi

echo "Found $contact_count contact(s) to delete."

# --- Confirm ---

echo ""
echo "This will delete:"
echo "  - $contact_count contact(s)"
echo "  - $org_count organisation(s)"

if [ "$DRY_RUN" = true ]; then
    echo ""
    echo "[DRY RUN] No records were deleted."
    exit 0
fi

if [ "$AUTO_YES" != true ]; then
    read -p "Continue? (y/N) " confirm
    if [ "$confirm" != "y" ]; then
        echo "Aborted."
        exit 0
    fi
fi

# --- Delete contacts first ---

deleted=0
failed=0

if [ -n "$contact_ids" ]; then
    echo ""
    echo "Deleting contacts..."
    for id in $contact_ids; do
        if delete_record "$id"; then
            echo "  Deleted contact $id"
            ((deleted++))
        else
            ((failed++))
        fi
    done
fi

# --- Delete organisations ---

echo ""
echo "Deleting organisations..."
for id in $org_ids; do
    if delete_record "$id"; then
        echo "  Deleted organisation $id"
        ((deleted++))
    else
        ((failed++))
    fi
done

# --- Summary ---

echo ""
echo "=========================================="
echo "  Teardown Complete"
echo "=========================================="
echo "  Deleted: $deleted record(s)"
if [ "$failed" -gt 0 ]; then
    echo "  Failed:  $failed record(s)"
fi
