<?php

/**
 * Gravity Forms field ID constants.
 *
 * Maps human-readable names to field IDs within each form on
 * forms.theresilienceproject.com.au. Use these constants instead of
 * hardcoding field IDs throughout the codebase.
 *
 * Grouped by form. Only fields referenced in functions.php are listed here;
 * for a complete list, query the GF REST API:
 *   GET /wp-json/gf/v2/forms/{form_id}
 */

/*--------------------------------------------------------------------------
 * Form 76 — New Schools Confirmation 2026
 *------------------------------------------------------------------------*/
const FIELD_NEW_CONF_SELECT_SCHOOL       = 226;
const FIELD_NEW_CONF_CANT_FIND_SCHOOL    = 227; // checkbox
const FIELD_NEW_CONF_SCHOOL_NAME         = 229;
const FIELD_NEW_CONF_DEAL_CONFIRMED      = 183; // hidden
const FIELD_NEW_CONF_NUM_STUDENTS        = 219;
const FIELD_NEW_CONF_ENGAGE              = 221;
const FIELD_NEW_CONF_SOURCE_FORM         = 233;
const FIELD_NEW_CONF_SERVICE_TYPE        = 234;
const FIELD_NEW_CONF_DEAL_ORG_TYPE       = 246;

/*--------------------------------------------------------------------------
 * Form 80 — Existing Schools Confirmation 2026
 *------------------------------------------------------------------------*/
const FIELD_EXIST_CONF_SELECT_SCHOOL     = 5;
const FIELD_EXIST_CONF_CANT_FIND_SCHOOL  = 7;  // checkbox
const FIELD_EXIST_CONF_SCHOOL_NAME       = 8;
const FIELD_EXIST_CONF_DEAL_CONFIRMED    = 49; // hidden
const FIELD_EXIST_CONF_FREE_TRAVEL       = 61; // hidden
const FIELD_EXIST_CONF_FACE_TO_FACE      = 62; // hidden
const FIELD_EXIST_CONF_FUNDED_2026       = 118; // hidden
const FIELD_EXIST_CONF_SCHOOL_TYPE       = 117;
const FIELD_EXIST_CONF_NUM_STUDENTS      = 27;
const FIELD_EXIST_CONF_LESSON_FORMAT     = 29;  // Journals/Planners
const FIELD_EXIST_CONF_PRIMARY_STUDENTS  = 127;
const FIELD_EXIST_CONF_SECONDARY_STUDENTS = 128;
const FIELD_EXIST_CONF_ENGAGE            = 54;
const FIELD_EXIST_CONF_INSPIRE           = 55;
const FIELD_EXIST_CONF_EXTEND_SUMMARY    = 111;
const FIELD_EXIST_CONF_TOTAL_EXCL        = 15;  // total excluding travel
const FIELD_EXIST_CONF_PLANNER_PRICE     = 121;

/*--------------------------------------------------------------------------
 * Form 29 — Early Years Confirmation 2025
 *------------------------------------------------------------------------*/
const FIELD_EY_CONF_SELECT_SERVICE       = 233;
const FIELD_EY_CONF_CANT_FIND_SERVICE    = 235; // checkbox
const FIELD_EY_CONF_DEAL_CONFIRMED       = 183; // hidden (same ID as form 76, different form)
const FIELD_EY_CONF_NUM_CHILDREN         = 210;
const FIELD_EY_CONF_ENGAGE               = 44;

/*--------------------------------------------------------------------------
 * Form 86 — LTRP and Culture Assessment 2026
 *------------------------------------------------------------------------*/
const FIELD_LTRP_ACCOUNT_ID              = 13;
const FIELD_LTRP_ORG_ID                  = 67;
const FIELD_LTRP_SCHOOL                  = 3;
const FIELD_LTRP_LTRP_WATCHED           = 10;
const FIELD_LTRP_STATE                   = 14;
const FIELD_LTRP_PARTICIPANTS            = 89;
const FIELD_LTRP_CA_COMPLETED            = 85;
const FIELD_LTRP_ERROR                   = 86;
const FIELD_LTRP_WELCOME_HEADING         = 18;
// Culture assessment scoring fields (used for emphasis styling)
const FIELD_LTRP_SCORE_FORTNIGHT         = 28;
const FIELD_LTRP_SCORE_WEEK              = 32;
const FIELD_LTRP_SCORE_WEEKLY            = 37;
const FIELD_LTRP_SCORE_DAILY             = 41;
const FIELD_LTRP_SCORE_SOME              = 39;
const FIELD_LTRP_SCORE_MOST              = 43;
const FIELD_LTRP_SCORE_SEMESTERLY        = 47;
const FIELD_LTRP_SCORE_TERMLY            = 49;
const FIELD_LTRP_SCORE_SOME_CAPS         = 48;
const FIELD_LTRP_SCORE_ALL               = 51;
const FIELD_LTRP_SCORE_YEAR              = 53;
const FIELD_LTRP_SCORE_SEMESTER          = 55;
const FIELD_LTRP_SCORE_TERMLY_2          = 58;
const FIELD_LTRP_SCORE_FORTNIGHTLY       = 60;
const FIELD_LTRP_SCORE_EACH_SEMESTER     = 61;

/*--------------------------------------------------------------------------
 * Form FORM_WELLBEING_CA_2026 — Standalone Wellbeing & Culture Assessment
 *------------------------------------------------------------------------*/
// TODO: replace placeholder IDs with the actual GF field IDs.
const FIELD_CA_ORG_ID            = 70; // hidden, dynamically populated from ?school_id=
const FIELD_CA_SCHOOL_NAME       = 76; // hidden, stores resolved school name for submission
const FIELD_CA_WELCOME_HEADING   = 71; // HTML field — "Welcome, {school name}"
const FIELD_CA_ERROR             = 74; // hidden — 'YES' when school_id is missing or not found, 'NO' otherwise

/*--------------------------------------------------------------------------
 * Form 89 — Curriculum Resource Ordering 2026
 *------------------------------------------------------------------------*/
const FIELD_CURRIC_SELECT_SCHOOL         = 174;
const FIELD_CURRIC_ENGAGE_COMBO          = 152; // hidden
const FIELD_CURRIC_FREE_SHIPPING         = 154; // hidden
const FIELD_CURRIC_FUNDED_SCHOOL         = 158; // hidden
const FIELD_CURRIC_NEW_SCHOOL            = 169; // hidden
const FIELD_CURRIC_ORDER_FOR_2026        = 211; // hidden
const FIELD_CURRIC_CONTACT_NAME          = 170;
const FIELD_CURRIC_SHIPPING_ADDRESS      = 99;
const FIELD_CURRIC_SHIPPING_STATE        = 100;
const FIELD_CURRIC_SAME_AS_SHIPPING      = 101; // checkbox
const FIELD_CURRIC_BILLING_ADDRESS       = 102;
const FIELD_CURRIC_BILLING_STATE         = 103;
const FIELD_CURRIC_LAST_DATE_2025        = 106;
const FIELD_CURRIC_FIRST_DATE_2026       = 107;
const FIELD_CURRIC_PO_NUMBER             = 109;
// Student curriculum year levels: fields 10-22 (Foundation through Year 12)
// Teacher resources: fields 24, 66-77 (Foundation through Year 12)
// These are used in array loops and don't need individual constants.
const FIELD_CURRIC_STUDENT_TOTAL         = 166;
const FIELD_CURRIC_TEACHER_TOTAL         = 167;
const FIELD_CURRIC_READING_LOG           = 28;  // product
const FIELD_CURRIC_STUDENT_PLANNER       = 36;  // product
const FIELD_CURRIC_GEM_CARDS             = 29;  // product
const FIELD_CURRIC_EMOTION_CARDS         = 178; // product
const FIELD_CURRIC_TEACHER_PLANNER       = 143; // product
const FIELD_CURRIC_TEACHER_PLANNER_VAR   = 144;
const FIELD_CURRIC_SENIOR_PLANNER        = 175; // product
const FIELD_CURRIC_SENIOR_PLANNER_VAR    = 176;
const FIELD_CURRIC_21DAY_JOURNAL         = 37;  // product
const FIELD_CURRIC_6MONTH_JOURNAL        = 38;  // product
const FIELD_CURRIC_TEACHER_SEMINAR       = 179; // product
const FIELD_CURRIC_TEACHER_SEMINAR_VAR   = 181;
const FIELD_CURRIC_TEACHER_SEMINAR_QTY   = 210;
const FIELD_CURRIC_FENCE_SIGN            = 115; // product
const FIELD_CURRIC_ACCEPT_CANCEL_POLICY  = 209;
const FIELD_CURRIC_SHIPPING_COST         = 161;
const FIELD_CURRIC_ORDER_SUMMARY         = 160; // html field

/*--------------------------------------------------------------------------
 * Form 72 — Event Confirmation 2025
 *------------------------------------------------------------------------*/
const FIELD_EVENT_EVENT_ID               = 1;
const FIELD_EVENT_CONTACT_ID             = 4;
const FIELD_EVENT_NAME_DISPLAY           = 19;
const FIELD_EVENT_ERROR                  = 22;
const FIELD_EVENT_DESCRIPTION            = 13;  // html field

/*--------------------------------------------------------------------------
 * Form 70 — Date Acceptance 2025
 *------------------------------------------------------------------------*/
const FIELD_DATE_SCHOOL_ID               = 20;
const FIELD_DATE_DESCRIPTION             = 25;  // html field
const FIELD_DATE_ERROR                   = 27;
const FIELD_DATE_EVENT_NOS               = 29;
const FIELD_DATE_SCHOOL_NAME             = 32;
const FIELD_DATE_PAGE_HEADING            = 33;  // html field
