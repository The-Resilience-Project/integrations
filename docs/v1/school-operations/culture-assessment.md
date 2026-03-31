# Culture Assessment

## POST /api/submit_ca.php

### Request

| Parameter | Required | Description |
|---|---|---|
| `school_account_no` | Yes | School account number for quote lookup |
| `organisation_id` | Yes | Vtiger organisation ID (with or without `3x` prefix) |
| `VP01`-`VP04` | Yes | Vision and Practice zero-set questions (Yes/No) |
| `VP11`-`VP14` | Yes | Vision and Practice one-set questions (Yes/No) |
| `ET01`-`ET04` | Yes | Explicit Teaching zero-set questions |
| `ET11`-`ET14` | Yes | Explicit Teaching one-set questions |
| `HB01`-`HB04` | Yes | Habit Building zero-set questions |
| `HB11`-`HB14` | Yes | Habit Building one-set questions |
| `SC01`-`SC03` | Yes | Staff Capacity zero-set questions |
| `SC11`-`SC13` | Yes | Staff Capacity one-set questions |
| `SW01`-`SW02` | Yes | Staff Wellbeing zero-set questions |
| `SW11`-`SW12` | Yes | Staff Wellbeing one-set questions |
| `FC01`-`FC02` | Yes | Family Capacity zero-set questions |
| `FC11`-`FC12` | Yes | Family Capacity one-set questions |
| `P01`-`P02` | Yes | Partnerships zero-set questions |
| `P11`-`P12` | Yes | Partnerships one-set questions |
| `concern_1` | Yes | Top concern #1 |
| `concern_2` | Yes | Top concern #2 |
| `concern_3` | Yes | Top concern #3 |
| `class_structure` | Yes | How classes are structured |
| `responsible_for_wellbeing` | Yes | Who is responsible for wellbeing |
| `past_programs` | No | Past wellbeing programs |
| `alongside_programs` | No | Programs running alongside TRP |

### Control Flow

```mermaid
flowchart TD
    A[POST request received] --> B[Create SchoolVTController]
    B --> C[create_culture_assessment called]
    C --> D["getQuoteWithAccountNo: look up quote contact<br/>using school_account_no + '2026 School Partnership Program'"]
    D --> E[Normalise organisation_id with '3x' prefix]
    E --> F[Score each domain using get_score]
    F --> G{Scoring logic per domain}
    G --> H["Any zero-set answer = 'No'? --> Emerging"]
    G --> I["All zero-set = 'Yes', any one-set = 'No'? --> Established"]
    G --> J["All answers = 'Yes'? --> Excelling"]
    H --> K[Build assessment request body with 7 domain scores + all individual booleans]
    I --> K
    J --> K
    K --> L["createAssessment webhook:<br/>name = '2026 Wellbeing Culture Assessment'<br/>orgType = 'School - New', contactId from quote"]
    L --> M[Store returned ca_id]
    M --> N["Build school context HTML from concerns,<br/>class_structure, responsible_for_wellbeing,<br/>past_programs, alongside_programs"]
    N --> O["createOrUpdateSEIP webhook:<br/>seipName = '2026 SEIP',<br/>wellbeingCultureAssessmentId = ca_id,<br/>caCompleted = today's date,<br/>schoolContext = HTML,<br/>yearsWithTrp = '1st year'"]
    O --> P[Return success]
```

### Scoring Logic

Each of the 7 domains has two sets of questions:
- **Zero-set** (e.g., VP01-VP04): If any answer is "No", the domain score is **Emerging**
- **One-set** (e.g., VP11-VP14): If all zero-set answers are "Yes" but any one-set answer is "No", the score is **Established**
- If all answers in both sets are "Yes", the score is **Excelling**

### Scenarios

**Standard submission** -- All question fields are provided. Seven domain scores are calculated, the assessment record is created in the CRM, and the SEIP record is updated with the assessment ID, completion date, and school context narrative.
