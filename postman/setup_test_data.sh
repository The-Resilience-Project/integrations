#!/bin/bash
#
# Creates test organisations and contacts in Vtiger CRM for Postman testing.
# Run once to set up test data. Check for existing data before re-running.
#
# Usage: bash postman/setup_test_data.sh

set -euo pipefail

API_BASE="https://theresilienceproject.od2.vtiger.com/restapi/v1/vtiger/default"
AUTH="maddie@theresilienceproject.com.au:EKCC5OlQjHZjoOMh"
ASSIGNED_USER="19x58"  # Ian Newmarch

# --- Helpers ---

create_record() {
    local element_type="$1"
    local element_json="$2"

    response=$(curl -s -u "$AUTH" -X POST "$API_BASE/create" \
        --data-urlencode "elementType=$element_type" \
        --data-urlencode "element=$element_json")

    success=$(echo "$response" | python3 -c "import sys,json; print(json.load(sys.stdin).get('success', False))")
    if [ "$success" != "True" ]; then
        echo "FAILED to create $element_type:"
        echo "$response" | python3 -m json.tool
        exit 1
    fi

    echo "$response" | python3 -c "import sys,json; r=json.load(sys.stdin)['result']; print(r['id'])"
}

# --- Check for existing test data ---

echo "Checking for existing POSTMAN TEST organisations..."
existing=$(curl -s -u "$AUTH" \
    "$API_BASE/query?query=SELECT%20id,accountname%20FROM%20Accounts%20WHERE%20accountname%20LIKE%20%27POSTMAN%20TEST%25%27%3B" \
    | python3 -c "import sys,json; r=json.load(sys.stdin)['result']; print(len(r))")

if [ "$existing" -gt 0 ]; then
    echo "WARNING: Found $existing existing POSTMAN TEST organisations."
    echo "Delete them first or this will create duplicates."
    read -p "Continue anyway? (y/N) " confirm
    if [ "$confirm" != "y" ]; then
        echo "Aborted."
        exit 0
    fi
fi

# --- User IDs for assignee testing ---

MADDIE="19x1"
EMMA="19x15"

# --- Create 5 School Organisations ---

echo ""
echo "Creating organisations..."

school1_id=$(create_record "Accounts" "{
    \"accountname\": \"POSTMAN TEST - Bayside Primary\",
    \"assigned_user_id\": \"$ASSIGNED_USER\",
    \"cf_accounts_organisationtype\": \"School\",
    \"cf_accounts_statenew\": \"VIC\",
    \"cf_accounts_totalstudents\": \"320\",
    \"cf_accounts_schoolcategory\": \"Government\"
}")
echo "  School 1: $school1_id - POSTMAN TEST - Bayside Primary (VIC, Gov, 320, assigned: Ian)"

school2_id=$(create_record "Accounts" "{
    \"accountname\": \"POSTMAN TEST - Northside Secondary\",
    \"assigned_user_id\": \"$ASSIGNED_USER\",
    \"cf_accounts_organisationtype\": \"School\",
    \"cf_accounts_statenew\": \"NSW\",
    \"cf_accounts_totalstudents\": \"850\",
    \"cf_accounts_schoolcategory\": \"Catholic\"
}")
echo "  School 2: $school2_id - POSTMAN TEST - Northside Secondary (NSW, Catholic, 850, assigned: Ian)"

school3_id=$(create_record "Accounts" "{
    \"accountname\": \"POSTMAN TEST - Riverside College\",
    \"assigned_user_id\": \"$ASSIGNED_USER\",
    \"cf_accounts_organisationtype\": \"School\",
    \"cf_accounts_statenew\": \"QLD\",
    \"cf_accounts_totalstudents\": \"480\",
    \"cf_accounts_schoolcategory\": \"Independent\"
}")
echo "  School 3: $school3_id - POSTMAN TEST - Riverside College (QLD, Independent, 480, assigned: Ian)"

school4_id=$(create_record "Accounts" "{
    \"accountname\": \"POSTMAN TEST - Maddie Assigned School\",
    \"assigned_user_id\": \"$MADDIE\",
    \"cf_accounts_organisationtype\": \"School\",
    \"cf_accounts_statenew\": \"NSW\",
    \"cf_accounts_totalstudents\": \"600\",
    \"cf_accounts_schoolcategory\": \"Government\"
}")
echo "  School 4: $school4_id - POSTMAN TEST - Maddie Assigned School (NSW, Gov, 600, assigned: Maddie)"

school5_id=$(create_record "Accounts" "{
    \"accountname\": \"POSTMAN TEST - Emma Assigned School\",
    \"assigned_user_id\": \"$EMMA\",
    \"cf_accounts_organisationtype\": \"School\",
    \"cf_accounts_statenew\": \"NSW\",
    \"cf_accounts_totalstudents\": \"750\",
    \"cf_accounts_schoolcategory\": \"Catholic\"
}")
echo "  School 5: $school5_id - POSTMAN TEST - Emma Assigned School (NSW, Catholic, 750, assigned: Emma)"

# --- Create Contacts ---

echo ""
echo "Creating contacts..."

# Bayside Primary (3 contacts)
c1=$(create_record "Contacts" "{\"firstname\":\"Test Primary\",\"lastname\":\"One\",\"email\":\"ian.newmarch+postman-bayside1@theresilienceproject.com.au\",\"title\":\"Wellbeing Coordinator\",\"account_id\":\"$school1_id\",\"assigned_user_id\":\"$ASSIGNED_USER\",\"cf_contacts_organisationtype\":\"School\"}")
echo "  $c1 - Test Primary One (Bayside, Wellbeing Coordinator)"

c2=$(create_record "Contacts" "{\"firstname\":\"Test Billing\",\"lastname\":\"One\",\"email\":\"ian.newmarch+postman-bayside2@theresilienceproject.com.au\",\"title\":\"Business Manager\",\"account_id\":\"$school1_id\",\"assigned_user_id\":\"$ASSIGNED_USER\",\"cf_contacts_organisationtype\":\"School\"}")
echo "  $c2 - Test Billing One (Bayside, Business Manager)"

c3=$(create_record "Contacts" "{\"firstname\":\"Test Teacher\",\"lastname\":\"One\",\"email\":\"ian.newmarch+postman-bayside3@theresilienceproject.com.au\",\"title\":\"Classroom Teacher\",\"account_id\":\"$school1_id\",\"assigned_user_id\":\"$ASSIGNED_USER\",\"cf_contacts_organisationtype\":\"School\"}")
echo "  $c3 - Test Teacher One (Bayside, Classroom Teacher)"

# Northside Secondary (3 contacts)
c4=$(create_record "Contacts" "{\"firstname\":\"Test Primary\",\"lastname\":\"Two\",\"email\":\"ian.newmarch+postman-northside1@theresilienceproject.com.au\",\"title\":\"Head of Wellbeing\",\"account_id\":\"$school2_id\",\"assigned_user_id\":\"$ASSIGNED_USER\",\"cf_contacts_organisationtype\":\"School\"}")
echo "  $c4 - Test Primary Two (Northside, Head of Wellbeing)"

c5=$(create_record "Contacts" "{\"firstname\":\"Test Billing\",\"lastname\":\"Two\",\"email\":\"ian.newmarch+postman-northside2@theresilienceproject.com.au\",\"title\":\"Finance Officer\",\"account_id\":\"$school2_id\",\"assigned_user_id\":\"$ASSIGNED_USER\",\"cf_contacts_organisationtype\":\"School\"}")
echo "  $c5 - Test Billing Two (Northside, Finance Officer)"

c6=$(create_record "Contacts" "{\"firstname\":\"Test Ambassador\",\"lastname\":\"Two\",\"email\":\"ian.newmarch+postman-northside3@theresilienceproject.com.au\",\"title\":\"Student Ambassador\",\"account_id\":\"$school2_id\",\"assigned_user_id\":\"$ASSIGNED_USER\",\"cf_contacts_organisationtype\":\"School\"}")
echo "  $c6 - Test Ambassador Two (Northside, Student Ambassador)"

# Riverside College (2 contacts)
c7=$(create_record "Contacts" "{\"firstname\":\"Test Primary\",\"lastname\":\"Three\",\"email\":\"ian.newmarch+postman-riverside1@theresilienceproject.com.au\",\"title\":\"Deputy Principal\",\"account_id\":\"$school3_id\",\"assigned_user_id\":\"$ASSIGNED_USER\",\"cf_contacts_organisationtype\":\"School\"}")
echo "  $c7 - Test Primary Three (Riverside, Deputy Principal)"

c8=$(create_record "Contacts" "{\"firstname\":\"Test Billing\",\"lastname\":\"Three\",\"email\":\"ian.newmarch+postman-riverside2@theresilienceproject.com.au\",\"title\":\"Accounts Officer\",\"account_id\":\"$school3_id\",\"assigned_user_id\":\"$ASSIGNED_USER\",\"cf_contacts_organisationtype\":\"School\"}")
echo "  $c8 - Test Billing Three (Riverside, Accounts Officer)"

# Maddie Assigned School (1 contact)
c9=$(create_record "Contacts" "{\"firstname\":\"Test Primary\",\"lastname\":\"Four\",\"email\":\"ian.newmarch+postman-maddie1@theresilienceproject.com.au\",\"title\":\"Wellbeing Lead\",\"account_id\":\"$school4_id\",\"assigned_user_id\":\"$MADDIE\",\"cf_contacts_organisationtype\":\"School\"}")
echo "  $c9 - Test Primary Four (Maddie Assigned, Wellbeing Lead)"

# Emma Assigned School (1 contact)
c10=$(create_record "Contacts" "{\"firstname\":\"Test Primary\",\"lastname\":\"Five\",\"email\":\"ian.newmarch+postman-emma1@theresilienceproject.com.au\",\"title\":\"Principal\",\"account_id\":\"$school5_id\",\"assigned_user_id\":\"$EMMA\",\"cf_contacts_organisationtype\":\"School\"}")
echo "  $c10 - Test Primary Five (Emma Assigned, Principal)"

# --- Summary ---

echo ""
echo "=========================================="
echo "  Test Data Created Successfully"
echo "=========================================="
echo ""
echo "ORGANISATIONS:"
echo "  School 1: $school1_id - POSTMAN TEST - Bayside Primary (assigned: Ian)"
echo "  School 2: $school2_id - POSTMAN TEST - Northside Secondary (assigned: Ian)"
echo "  School 3: $school3_id - POSTMAN TEST - Riverside College (assigned: Ian)"
echo "  School 4: $school4_id - POSTMAN TEST - Maddie Assigned School (assigned: Maddie)"
echo "  School 5: $school5_id - POSTMAN TEST - Emma Assigned School (assigned: Emma)"
echo ""
echo "CONTACTS:"
echo "  Bayside:        $c1 (Primary), $c2 (Billing), $c3 (Teacher)"
echo "  Northside:      $c4 (Primary), $c5 (Billing), $c6 (Ambassador)"
echo "  Riverside:      $c7 (Primary), $c8 (Billing)"
echo "  Maddie School:  $c9 (Primary)"
echo "  Emma School:    $c10 (Primary)"
echo ""
echo "Query account numbers with:"
echo "  curl -s -u '$AUTH' '$API_BASE/query?query=SELECT id,accountname,account_no FROM Accounts WHERE accountname LIKE %27POSTMAN TEST%25%27;'"
