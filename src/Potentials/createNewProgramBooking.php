<?php

chdir(dirname(__FILE__));
require '../init.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false]);
    exit;
}
log_debug('Program booking request received', ['request' => $_REQUEST]);

$vtod = init_vtod();
$vtconfig_url = $vtod_config['url'];
$dealArr = [];
if ($_REQUEST['potentialname']) {
    $_REQUEST['potentialname'] = urldecode($_REQUEST['potentialname']);
} else {
    $_REQUEST['potentialname'] = urldecode($_REQUEST['org_name']);
}
if ($_REQUEST['org_name']) {
    $_REQUEST['org_name'] = urldecode($_REQUEST['org_name']);
}
$dealArr['potentialname'] = 'Deal: '.$_REQUEST['potentialname'];
$dealArr['cf_potentials_numberofparticipants'] = $_REQUEST['number_of_participants'];
$dealArr['closingdate'] = date('d-m-Y');
$program_start_date = '';
if ($_REQUEST['program_start_date']) {
    $program_start_date = $_REQUEST['program_start_date'];
}
if (!$program_start_date) {
    $program_start_date = $_REQUEST['dr_program_start_date2'];
}
if (!$program_start_date) {
    $program_start_date = $_REQUEST['dr_program_start_date3'];
}
if (!$program_start_date) {
    $program_start_date = $_REQUEST['dr_program_start_date4'];
}
if (!$program_start_date) {
    $program_start_date = $_REQUEST['authen_program_start_date1'];
}
if (!$program_start_date) {
    $program_start_date = $_REQUEST['authen_program_start_date2'];
}
if (!$program_start_date) {
    $program_start_date = $_REQUEST['authen_program_start_date3'];
}
if ($program_start_date) {
    $dealArr['cf_potentials_programstartdatenew'] = $program_start_date;
}
$dealArr['pipeline'] = 'Standard';
$dealArr['sales_stage'] = 'Deal Won';
$dealArr['opportunity_type'] = 'Workplace';
$dealArr['assigned_user_id'] = '19x1';
$dealArr['cf_potentials_schooladdress'] = $_REQUEST['org_street'];
$dealArr['cf_potentials_schoolcity'] = $_REQUEST['org_city'];
$dealArr['cf_potentials_state'] = $_REQUEST['org_state'];
$dealArr['cf_potentials_schoolpostcode'] = $_REQUEST['org_postcode'];
$dealArr['cf_potentials_ponumber1'] = $_REQUEST['purchase_order_number'];
$fullName = $_REQUEST['contact_name'];
$firstName = '';
$lastName = '';
if ($fullName) {
    $listFN = explode(' ', $fullName);
    $firstName = $listFN[0];
    for ($i = 1;$i < count($listFN);$i++) {
        $lastName .= $listFN[$i].' ';
    }
    $lastName = trim($lastName, ' ');
}
$confirm_url = 'https://forms.theresilienceproject.com.au/program-booking-form/?org_name='.rawurlencode($_REQUEST['org_name']).'&contact_first_name='.rawurlencode($firstName).'&contact_last_name='.rawurlencode($lastName).'&email_address='.$_REQUEST['email_address'].'&address='.rawurlencode($_REQUEST['org_street']).'&city='.rawurlencode($_REQUEST['org_city']).'&state='.rawurlencode($_REQUEST['org_state']).'&zip_code='.$_REQUEST['org_postcode'];

if ($_REQUEST['org_name']) {
    $org_name = str_replace("'s", "\'s", $_REQUEST['org_name']);
    $org_name = str_replace("'", "\'", $_REQUEST['org_name']);
    $query_org  = sprintf("SELECT * FROM Accounts WHERE accountname='%s' LIMIT 1; ", $org_name);
    $res_org = $vtod->query($query_org);
    if (isset($res_org[0]['id'])) {
        $dealArr['related_to'] = $res_org[0]['id'];
        $orgP = $vtod->retrieve($res_org[0]['id']);
        $orgP['cf_accounts_confirmationurl'] = $confirm_url;
        //        $orgP['cf_accounts_organisationtype'] = 'School';
        $orgP['cf_accounts_address'] = $_REQUEST['org_street'];
        $orgP['cf_accounts_city'] = $_REQUEST['org_city'];
        $orgP['cf_accounts_statenew'] = $_REQUEST['org_state'];
        $orgP['cf_accounts_zipcode'] = $_REQUEST['org_postcode'];
        $orgP['cf_accounts_country'] = $_REQUEST['org_country'];
        $orgPJson = json_encode($orgP);
        $org_Params = [
            'sessionName' => $vtod->sessionId,
            'operation' => 'update',
            'element' => $orgPJson,
        ];
        $dataOrgP = $vtod->curlPost($vtconfig_url . 'webservice.php', $org_Params);
    } else {
        #create new Org
        $orgArr = [];
        $orgArr['accountname'] = $_REQUEST['org_name'];
        $orgArr['cf_accounts_organisationtype'] = 'School';
        $orgArr['assigned_user_id'] = '19x1';
        $orgArr['cf_accounts_confirmationurl'] = $confirm_url;
        $orgArr['cf_accounts_address'] = $_REQUEST['org_street'];
        $orgArr['cf_accounts_city'] = $_REQUEST['org_city'];
        $orgArr['cf_accounts_statenew'] = $_REQUEST['org_state'];
        $orgArr['cf_accounts_zipcode'] = $_REQUEST['org_postcode'];
        $orgArr['cf_accounts_country'] = $_REQUEST['org_country'];
        $objectOrgJson = json_encode($orgArr);
        $orgParams = [
            'sessionName' => $vtod->sessionId,
            'operation' => 'create',
            'element' => $objectOrgJson,
            'elementType' => 'Accounts',
        ];
        $dataOrg = $vtod->curlPost($vtconfig_url . 'webservice.php', $orgParams);
        $resultOrg = json_decode($dataOrg);
        if ($resultOrg->result->id) {
            $dealArr['related_to'] = $resultOrg->result->id;
        }
    }
}
if ($_REQUEST['email_address']) {
    $query_contact = sprintf("SELECT * FROM Contacts WHERE email='%s' LIMIT 1; ", $_REQUEST['email_address']);
    $res_contact = $vtod->query($query_contact);
    if (isset($res_contact[0]['id'])) {
        $dealArr['contact_id'] = $res_contact[0]['id'];
        $contactP = $vtod->retrieve($res_contact[0]['id']);
        $contactP['cf_contacts_confirmationurlnew'] = $confirm_url;
        $contactP['firstname'] = $firstName;
        $contactP['lastname'] = $lastName;
        $contactP['cf_contacts_customtitle'] = $_REQUEST['job_title'];
        $contactP['cf_contacts_confirmationurlnew'] = $confirm_url;
        //        $contactP['cf_contacts_confirmationform'] = 'New School';
        if ($dealArr['related_to']) {
            $contactP['account_id'] = $dealArr['related_to'];
        }
        $contactP['cf_contacts_address'] = $_REQUEST['org_street'];
        $contactP['cf_contacts_city'] = $_REQUEST['org_city'];
        $contactP['cf_contacts_statenew'] = $_REQUEST['org_state'];
        $contactP['cf_contacts_zipcode'] = $_REQUEST['org_postcode'];
        $contactP['cf_contacts_country'] = $_REQUEST['org_country'];
        $contactJson = json_encode($contactP);
        $contactParams = [
            'sessionName' => $vtod->sessionId,
            'operation' => 'update',
            'element' => $contactJson,
        ];
        $dataContactP = $vtod->curlPost($vtconfig_url . 'webservice.php', $contactParams);
    } else {
        #create new Contact
        $contactArr = [];
        $contactArr['firstname'] = $firstName;
        $contactArr['lastname'] = $lastName;
        $contactArr['lastname'] = $lastName;
        $contactArr['email'] = $_REQUEST['email_address'];
        $contactArr['cf_contacts_confirmationurlnew'] = $confirm_url;
        $contactArr['cf_contacts_customtitle'] = $_REQUEST['job_title'];
        if ($dealArr['related_to']) {
            $contactArr['account_id'] = $dealArr['related_to'];
        }
        $contactArr['contacttype'] = 'Sales Qualified Lead';
        $contactArr['assigned_user_id'] = '19x1';
        log_debug('Creating contact', ['contact_data' => $contactArr]);
        $objectContactJson = json_encode($contactArr);
        $contactParams = [
            'sessionName' => $vtod->sessionId,
            'operation' => 'create',
            'element' => $objectContactJson,
            'elementType' => 'Contacts',
        ];
        $dataContact = $vtod->curlPost($vtconfig_url . 'webservice.php', $contactParams);
        $resultContact = json_decode($dataContact);
        if ($resultContact->result->id) {
            $dealArr['contact_id'] = $resultContact->result->id;
        }
    }
}

$dealArr['cf_potentials_orgtype'] = 'Workplace';

// mapping values
$serviceArr = [];
$inspire = [];
$engage = [];
$extend = [];
if ($_REQUEST['dr_package1']) {
    $serviceArr['dr_package1'] = [
        'serviceNo' => (strpos($_REQUEST['dr_package1_user'], 'Hugh') !== false) ? 'SER36' : 'SER36',
        'user' => $_REQUEST['dr_package1_user'],
        'qty' => 1,
        'add' => 1,

    ];
    if (strpos($_REQUEST['dr_package1_user'], 'Hugh') !== false) {
        $inspire[] = 'Workplace DR Hugh';
    } else {
        $inspire[] = 'Workplace DR Martin';
    }
    $engage[] = 'DR DWS';
    $engage[] = '21-Day Journal';

}
if ($_REQUEST['dr_package2']) {
    $serviceArr['dr_package2'] = [
        'serviceNo' => (strpos($_REQUEST['dr_package2_user'], 'Hugh') !== false) ? 'SER36' : 'SER36',
        'user' => $_REQUEST['dr_package2_user'],
        'qty' => 1,
    ];
    if (strpos($_REQUEST['dr_package2_user'], 'Hugh') !== false) {
        $inspire[] = 'Workplace DR Hugh';
    } else {
        $inspire[] = 'Workplace DR Martin';
    }
    $engage[] = 'DR DWS';
}
if ($_REQUEST['dr_presentation']) {
    $serviceArr['dr_presentation'] = [
        'serviceNo' => (strpos($_REQUEST['dr_package2_user'], 'Hugh') !== false) ? 'SER38' : 'SER39',
        'user' => $_REQUEST['dr_presentation_user'],
        'qty' => 1,
    ];
    if (strpos($_REQUEST['dr_package2_user'], 'Hugh') !== false) {
        $inspire[] = 'Workplace DR Hugh';
    } else {
        $inspire[] = 'Workplace DR Martin';
    }
}
if ($_REQUEST['dr_wellbeing']) {
    $serviceArr['dr_wellbeing'] = [
        'serviceNo' => 'SER110',
        'qty' => 1,
    ];
    $engage[] = 'DR DWS';
}
if ($_REQUEST['authen_package1']) {
    $serviceArr['authen_package1'] = [
        'serviceNo' => (strpos($_REQUEST['authen_package1_user'], 'Hugh') !== false) ? 'SER109' : 'SER109',
        'user' => $_REQUEST['authen_package1_user'],
        'qty' => 1,
    ];
    if (strpos($_REQUEST['authen_package1_user'], 'Hugh') !== false) {
        $inspire[] = 'Workplace AC Hugh';
    } else {
        $inspire[] = 'Workplace AC Martin';
    }
    $engage[] = 'AC DWS';

}
if ($_REQUEST['authen_presentation']) {
    $serviceArr['authen_presentation'] = [
        'serviceNo' => (strpos($_REQUEST['authen_package1_user'], 'Hugh') !== false) ? 'SER44' : 'SER45',
        'user' => $_REQUEST['authen_presentation_user'],
        'qty' => 1,
    ];
    if (strpos($_REQUEST['authen_presentation_user'], 'Hugh') !== false) {
        $inspire[] = 'Workplace AC Hugh';
    } else {
        $inspire[] = 'Workplace AC Martin';
    }
}
if ($_REQUEST['authen_wellbeing']) {
    $serviceArr['authen_wellbeing'] = [
        'serviceNo' => 'SER139',
        'qty' => 1,
    ];
    $engage[] = 'AC DWS';
}
$grandTotal = 0;
$lineItem = [];
foreach ($serviceArr as $k => $service) {
    $qtyS = $service['qty'];
    $serviceNo = $service['serviceNo'];
    if ($service['add']) {
        $query_service  = sprintf("SELECT * FROM Services WHERE service_no='%s' LIMIT 1; ", 'SER7');
        $res_service = $vtod->query($query_service);
        $service_xerocode = '';
        if (isset($res_service[0]['id'])) {
            $serviceid = $res_service[0]['id'];
            $service_xerocode = $res_service[0]['cf_services_xerocode'];
            $unit_price = 12;
            if ($serviceid) {
                $lineItem[] = [
                    'productid' => $serviceid,
                    'quantity' => (int)$_REQUEST['number_of_participants'],
                    'listprice' => $unit_price,
                    'cf_quotes_xerocode' => $service_xerocode,
                    'duration' => '1',
                ];
                $grandTotal += $qtyS * $unit_price;
            }
        }
    }
    $query_service  = sprintf("SELECT * FROM Services WHERE service_no='%s' LIMIT 1; ", $serviceNo);
    $res_service = $vtod->query($query_service);
    $service_xerocode = '';
    if (isset($res_service[0]['id'])) {
        $serviceid = $res_service[0]['id'];
        $service_xerocode = $res_service[0]['cf_services_xerocode'];
        $unit_price = (float)$res_service[0]['unit_price'];
        if ($serviceid) {
            $lineItem[] = [
                'productid' => $serviceid,
                'quantity' => $qtyS,
                'listprice' => $unit_price,
                'cf_quotes_xerocode' => $service_xerocode,
                'duration' => '1',
            ];
            $grandTotal += $qtyS * $unit_price;
        }
    }
}
$inspire = array_unique($inspire);
$engage = array_unique($engage);
$extend = array_unique($extend);
$dealArr['cf_potentials_presentations'] = (empty($inspire)) ? '' : implode(' |##| ', $inspire);
$dealArr['cf_potentials_curriculum'] = (empty($engage)) ? '' : implode(' |##| ', $engage);
$dealArr['cf_potentials_additionaloptions'] = (empty($extend)) ? '' : implode(' |##| ', $extend);

$dealArr['amount'] = (float)$grandTotal;
$dealArr['amount_currency_value'] = (float)$grandTotal;
$dealArr['LineItems'] = $lineItem;
$dealArr['hdnSubTotal'] = (float)$grandTotal;
$dealArr['hdnGrandTotal'] = (float)$grandTotal;

log_debug('Creating deal/potential', ['deal_data' => $dealArr]);

$objectJson = json_encode($dealArr);
$params = [
    'sessionName' => $vtod->sessionId,
    'operation' => 'create',
    'element' => $objectJson,
    'elementType' => 'Potentials',
];
$data_potential = $vtod->curlPost($vtconfig_url . 'webservice.php', $params);
log_debug('VTiger potential response', ['response' => json_decode($data_potential, true)]);
$result_arr = json_decode($data_potential);
if ($result_arr->result->id) {
    $potentialId = $result_arr->result->id;
    $quoteArr = [];
    $invoiceArr = [];
    if ($potentialId) {
        #create Contact second
        if ($_REQUEST['billing_email']) {
            $query_contact2 = sprintf("SELECT * FROM Contacts WHERE email='%s' LIMIT 1; ", $_REQUEST['billing_email']);
            $res_contact2 = $vtod->query($query_contact2);
            if (isset($res_contact2[0]['id'])) {
                $updateD = $vtod->retrieve($potentialId);
                $updateD['cf_potentials_billingcontact'] = $res_contact2[0]['id'];
                $updateDJson = json_encode($updateD);
                $updateDParams = [
                    'sessionName' => $vtod->sessionId,
                    'operation' => 'update',
                    'element' => $updateDJson,
                ];
                $dataUpdateD = $vtod->curlPost($vtconfig_url . 'webservice.php', $updateDParams);
            } else {
                #create new Contact
                $contact2Arr = [];
                $contact2Arr['firstname'] = $_REQUEST['billing_firstname'];
                $contact2Arr['lastname'] = $_REQUEST['billing_lastname'];
                $contact2Arr['cf_contacts_type'] = 'Billing';
                $contact2Arr['email'] = $_REQUEST['billing_email'];
                if ($dealArr['related_to']) {
                    $contact2Arr['account_id'] = $dealArr['related_to'];
                }
                $contact2Arr['cf_contacts_dealname'] = $potentialId;
                $contact2Arr['cf_contacts_address'] = $dealArr['cf_potentials_schooladdress'];
                $contact2Arr['cf_contacts_city'] = $_REQUEST['cf_potentials_schoolcity'];
                $contact2Arr['cf_contacts_statenew'] = $dealArr['cf_potentials_schoolstate'];
                $contact2Arr['cf_contacts_zipcode'] = $dealArr['cf_potentials_schoolpostcode'];
                $contact2Arr['contacttype'] = 'Sales Qualified Lead';
                $contact2Arr['assigned_user_id'] = '19x1';
                $contactArr['cf_contacts_type'] = 'Primary';
                $objectContact2Json = json_encode($contact2Arr);
                $contact2Params = [
                    'sessionName' => $vtod->sessionId,
                    'operation' => 'create',
                    'element' => $objectContact2Json,
                    'elementType' => 'Contacts',
                ];
                $dataContact2 = $vtod->curlPost($vtconfig_url . 'webservice.php', $contact2Params);
                $resultContact2 = json_decode($dataContact2, 1);
                if ($resultContact2['result']['id']) {
                    $dealArr['cf_potentials_billingcontact'] = $resultContact2['result']['id'];
                    $updateDealJson = json_encode($dealArr);
                    $updateDealParams = [
                        'sessionName' => $vtod->sessionId,
                        'operation' => 'update',
                        'element' => $updateDealJson,
                    ];
                    $dataUpdateDeal = $vtod->curlPost($vtconfig_url . 'webservice.php', $updateDealParams);
                }
            }
        }
        #create Invoice record
        $invoiceArr['subject'] = str_replace('Deal:', 'Invoice:', $_REQUEST['potentialname']);
        $invoiceArr['invoicestatus'] = 'Auto Created';
        $invoiceArr['invoicedate'] = date('d-m-Y');
        $invoiceArr['duedate'] = date('d-m-Y', strtotime('+8 days'));
        $invoiceArr['potential_id'] = $potentialId;
        $invoiceArr['account_id'] = $dealArr['related_to'];
        $invoiceArr['contact_id'] = $dealArr['contact_id'];
        $invoiceArr['cf_invoice_ponumber1'] = $_REQUEST['purchase_order_number'];
        if ($_REQUEST['org_street']) {
            $invoiceArr['bill_street'] = $_REQUEST['org_street'];
            $invoiceArr['ship_street'] = $_REQUEST['org_street'];
        }
        if ($_REQUEST['org_city']) {
            $invoiceArr['bill_city'] = $_REQUEST['org_city'];
            $invoiceArr['ship_city'] = $_REQUEST['org_city'];
        }
        if ($_REQUEST['org_state']) {
            $invoiceArr['bill_state'] = $_REQUEST['org_state'];
            $invoiceArr['ship_state'] = $_REQUEST['org_state'];
        }
        if ($_REQUEST['org_postcode']) {
            $invoiceArr['bill_city'] = $_REQUEST['org_postcode'];
            $invoiceArr['ship_code'] = $_REQUEST['org_postcode'];
        }
        $invoiceArr['assigned_user_id'] = '19x1';
        $invoiceArr['hdnTaxType'] = 'individual';
        $invoiceArr['LineItems'] = $lineItem;
        $objectInvoiceJson = json_encode($invoiceArr);
        $invoiceParams = [
            'sessionName' => $vtod->sessionId,
            'operation' => 'create',
            'element' => $objectInvoiceJson,
            'elementType' => 'Invoice',
        ];
        $dataInvoice = $vtod->curlPost($vtconfig_url . 'webservice.php', $invoiceParams);
        $resultInvoice = json_decode($dataInvoice, 1);
        log_debug('Invoice creation result', ['invoice_result' => $resultInvoice]);
        if ($resultInvoice['result']['id']) {
            echo json_encode(['success' => true,'message' => $potentialId]);
            exit;
        } else {
            echo json_encode(['success' => false,'message' => $resultInvoice->error->message]);
            exit;
        }
    }
    log_info('Program booking created successfully', ['potential_id' => $potentialId]);
    echo json_encode(['success' => true,'message' => $potentialId]);
    exit;
} else {
    log_error('Program booking creation failed', ['error' => $result_arr->error->message]);
    echo json_encode(['success' => false,'message' => $result_arr->error->message]);
    exit;
}
