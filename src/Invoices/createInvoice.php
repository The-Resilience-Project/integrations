<?php

chdir(dirname(__FILE__));
require '../init.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL  & ~(E_STRICT | E_NOTICE));/**/

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');

$vtod = init_vtod();
$vtconfig_url = $vtod_config['url'];

// $serviceNo = 'SER111';
// $sql  = "SELECT * FROM `boru_services` WHERE `service_no` = ? LIMIT 1";
// $dataCheck = $dbh->getSingle($sql, array($serviceNo));
// print_r( $dataCheck );
// echo 3333;
// $dataRecord = (array) json_decode($dataCheck['data']);
// print_r( $dataRecord );exit;
log_debug('Invoice creation request received', ['request' => $_REQUEST]);

define('SECTION_1', 'Display on Invoice');
define('SLEEP_SECTION', 0);
define('SLEEP_ITEM', 0);

$cf_invoice_ponumber1 = $_REQUEST[55];
$shipping_handling_charge = $_REQUEST[62];
/*[10] => 33
    [11] => 1
    [12] => 2
    [13] => 3
    [14] => 4
    [15] => 5
    [16] => 6
    [17] => 7
    [18] => 8
    [19] => 9
    [20] => 10
    [21] => 44

    [30] => 3333
    [31] => 11
    [32] => 12
    [33] => 13
    [34] => 14
    [35] => 15
    [36] => 16
    [37] => 17
    [38] => 18
    [39] => 19
    [40] => 20
    [41] => 222*/
$address_mapping = [
    'bill_street' => '42_1',
//     "bill_pobox"=>"",
    'bill_city' => '42_3',
    'bill_state' => '42_4',
    'bill_code' => '42_5',
    'bill_country' => '42_6',


    'ship_street' => '43_1',
//     "ship_pobox"=>"",
    'ship_city' => '43_3',
    'ship_state' => '43_4',
    'ship_code' => '43_5',
    'ship_country' => '43_6',

// [47] =>
// [43_1] =>
// [43_2] =>
// [43_3] =>
// [43_4] =>
// [43_5] =>
// [43_6] =>
];
$copy_address = ['ship_street' => 'bill_street',
    'ship_city' => 'bill_city',
    'ship_state' => 'bill_state',
    'ship_code' => 'bill_code',
    'ship_country' => 'bill_country',];

// mapping to form id
$student = [10 => 'Foundation Student Journal',
    11 => 'Year 1 Student Journal',
    12 => 'Year 2 Student Journal',
    13 => 'Year 3 Student Journal',
    14 => 'Year 4 Student Journal',
    15 => 'Year 5 Student Journal',
    16 => 'Year 6 Student Journal',
    17 => 'Year 7 Student Journal',
    18 => 'Year 8 Student Journal',
    19 => 'Year 9 Student Journal',
    20 => 'Year 10 Student Journal',
    21 => 'Year 11 Student Journal',
    63 => 'Year 12 Student Journal',
];


$teacher = [30 => 'Foundation Hard Copy Teacher Resource',
    31 => 'Year 1 Hard Copy Teacher Resource',
    32 => 'Year 2 Hard Copy Teacher Resource',
    33 => 'Year 3 Hard Copy Teacher Resource',
    34 => 'Year 4 Hard Copy Teacher Resource',
    35 => 'Year 5 Hard Copy Teacher Resource',
    36 => 'Year 6 Hard Copy Teacher Resource',
    37 => 'Year 7 Hard Copy Teacher Resource',
    38 => 'Year 8 Hard Copy Teacher Resource',
    39 => 'Year 9 Hard Copy Teacher Resource',
    40 => 'Year 10 Hard Copy Teacher Resource',
    41 => 'Year 11 Hard Copy Teacher Resource',
    64 => 'Year 12 Hard Copy Teacher Resource',
];


// section_no	:	2
// section_name	:	Section 2nd

$student_mapping_service = [10 => 'PRO18',
    11 => 'PRO19',
    12 => 'PRO19',
    13 => 'SER8',
    14 => 'SER8',
    15 => 'SER8',
    16 => 'SER8',
    17 => 'SER8',
    18 => 'SER8',
    19 => 'SER8',
    20 => 'SER8',
    21 => 'SER8'];

$student_mapping_service_no = ['PRO19' => 'Year 1 Student Journal',
'PRO18' => 'Foundation Student Journal',
'PRO29' => 'Year 11 Student Journal',
'PRO28' => 'Year 10 Student Journal',
'PRO27' => 'Year 9 Student Journal',
'PRO26' => 'Year 8 Student Journal',
'PRO25' => 'Year 7 Student Journal',
'PRO24' => 'Year 6 Student Journal',
'PRO22' => 'Year 4 Student Journal',
'PRO21' => 'Year 3 Student Journal',
'PRO30' => 'Year 12 Student Journal',
'PRO23' => 'Year 5 Student Journal',
'PRO20' => 'Year 2 Student Journal'];


$teacher_mapping_service = [
    30 => 'SER101',
    31 => 'SER101',
    32 => 'SER101',
    33 => 'SER101',
    34 => 'SER101',
    35 => 'SER101',
    36 => 'SER101',
    37 => 'SER101',
    38 => 'SER101',
    39 => 'SER101',
    40 => 'SER101',
    41 => 'SER101',
];

$teacher_mapping_service_no = ['PRO44' => 'Senior Student Hard Copy Teacher Resource',
'PRO43' => 'Year 12 Hard Copy Teacher Resource',
'PRO42' => 'Year 11 Hard Copy Teacher Resource',
'PRO41' => 'Year 10 Hard Copy Teacher Resource',
'PRO40' => 'Year 9 Hard Copy Teacher Resource',
'PRO39' => 'Year 8 Hard Copy Teacher Resource',
'PRO38' => 'Year 7 Hard Copy Teacher Resource',
'PRO37' => 'Year 6 Hard Copy Teacher Resource',
'PRO36' => 'Year 5 Hard Copy Teacher Resource',
'PRO35' => 'Year 4 Hard Copy Teacher Resource',
'PRO34' => 'Year 3 Hard Copy Teacher Resource',
'PRO33' => 'Year 2 Hard Copy Teacher Resource',
'PRO32' => 'Year 1 Hard Copy Teacher Resource',
'PRO31' => 'Foundation Hard Copy Teacher Resource'];

// mapping to $listselectedyearlevels
$student_selectedyearlevels = [10 => 'Foundation',
    11 => 'Year 1',
    12 => 'Year 2',
    13 => 'Year 3',
    14 => 'Year 4',
    15 => 'Year 5',
    16 => 'Year 6',
    17 => 'Year 7',
    18 => 'Year 8',
    19 => 'Year 9',
    20 => 'Year 10',
    21 => 'Year 11',
    63 => 'Year 12',
];

$description = $serviceArr2 = $listselectedyearlevels = [];
$total_student = 0;
foreach ($student as $k => $service) {
    if ($_REQUEST[$k] > 0) {
        $description[] = sprintf('%s : %d', $service, $_REQUEST[$k])   ;
        $serviceArr2[] = ['serviceNo' => array_search($service, $student_mapping_service_no) , 'qty' => $_REQUEST[$k]] ;
        $total_student += $_REQUEST[$k];

        $listselectedyearlevels[] = $student_selectedyearlevels[$k];
    }
}
$commentInspire = implode(PHP_EOL, $description) ;

//section_no	:	3
// section_name	:	Section 3nd

$description = $serviceArr3 = [];
$total_teacher = 0;
foreach ($teacher as $k => $service) {
    if ($_REQUEST[$k] > 0) {
        $description[] = sprintf('%s : %d', $service, $_REQUEST[$k])   ;
        $serviceArr3[] = ['serviceNo' => array_search($service, $teacher_mapping_service_no) , 'qty' => $_REQUEST[$k]] ;
        $total_teacher += $_REQUEST[$k];
    }
}
$commentEngage = implode(PHP_EOL, $description) ;

$new_service_items = [
    ['serviceNo' => 'SER111' ,  'qty' => 1, 'name' => 'Shipping and handling curriculum'],
    ['serviceNo' => 'SER12', 'name' => 'Engage' ,  'qty' => $total_student ],
    //$total_student +  $total_teacher
];
$new_product_items = [
    ['serviceNo' => 'PRO44', 'name' => 'Hard Copy Teacher Resources' ,  'qty' => $total_teacher ],

//     array('serviceNo' ,  'qty' ),

];
$grandTotal = 0;
$lineItem = [];
$quoteId = $_REQUEST['59'];

$quoteId = null;


if ($quoteId) {
    $quoteRecord = $vtod->retrieve($quoteId);
    if ($quoteRecord) {
        $account_id = $quoteRecord['account_id'];
        $lineItems = $quoteRecord['LineItems'];
        foreach ($lineItems as $key => $value) {

            $lineItems[$key]['section_name'] = SECTION_1 ;

            //             if(  strpos($value['product_name'], 'Inspire:') !==FALSE ) {
            //                 $lineItems[$key]['comment'] = $commentInspire;
            //                 $lineItems[$key]['description'] = $commentInspire;

            //             }

            if ($value['productid'] == '25x94901' || strpos($value['product_name'], 'Engage: Teaching and Learning Program') !== false) {

                //                 $lineItems[$key]['comment'] = $commentEngage;
                //                 $lineItems[$key]['description'] = $commentEngage;
                $lineItems[$key]['quantity'] = $total_student;
            }
        }

        // add new line items
        $grandTotal = $quoteRecord['hdnGrandTotal'] ;
        $lineItem = $lineItems;

    }
} else { //no quote
    $quoteRecord = [];

}

foreach ($new_service_items as $k => $service) {
    $qtyS = floatval($service['qty']);
    if ($qtyS == 0) {
        continue;
    }
    $serviceNo = $service['serviceNo'];

    // $sql  = "SELECT * FROM `boru_services` WHERE `service_no` = ? LIMIT 1";
    // $dataCheck = $dbh->getSingle($sql, array($serviceNo));


    $query_service  = sprintf("SELECT * FROM Services WHERE service_no='%s' LIMIT 1; ", $serviceNo);
    $dataRecord = $vtod->query($query_service)[0];

    // $dataRecord= array();
    // if (!empty($dataCheck['serviceid'])){
    //     $dataRecord = (array) json_decode($dataCheck['data']);

    // }
    if (isset($dataRecord['id'])) {
        $serviceid = $dataRecord['id'];
        if ($serviceNo == 'SER111') {
            $price = floatval($shipping_handling_charge);
        } else {
            $price = (float)$dataRecord['unit_price'];
        }
        if ($dataRecord['taxclass'] == 'on') {
            $tax_GST = $price * 0.1;
        } else {
            $tax_GST = 0;
        }
        $tax_GST = 0;
        $unit_price = $price + $tax_GST;
        // if item = shipping > work for task 58476
        //                 if (){

        //                 }

        if ($serviceid) {
            $lineItem[] = [
                'section_no' => 1,
                'section_name' => SECTION_1,
                'productid' => $serviceid,
                'quantity' => $qtyS,
                'listprice' => $unit_price,
            ];
            $grandTotal += floatval($qtyS) * floatval($unit_price);
        }
    }
}

foreach ($new_product_items as $k => $service) {
    $qtyS = floatval($service['qty']);
    if ($qtyS == 0) {
        continue;
    }
    $serviceNo = $service['serviceNo'];

    // $sql  = "SELECT * FROM `boru_products` WHERE `product_no` = ? LIMIT 1";
    // $dataCheck = $dbh->getSingle($sql, array($serviceNo));
    // $dataRecord= array();
    // if (!empty($dataCheck['productid'])){
    //     $dataRecord = (array) json_decode($dataCheck['data']);

    // }

    $query_service  = sprintf("SELECT * FROM Products WHERE product_no='%s' LIMIT 1; ", $serviceNo);
    $dataRecord = $vtod->query($query_service)[0];

    if (isset($dataRecord['id'])) {
        $serviceid = $dataRecord['id'];
        $price = (float)$dataRecord['unit_price'];
        if ($dataRecord['taxclass'] == 'on') {
            $tax_GST = $price * 0.1;
        } else {
            $tax_GST = 0;
        }
        $tax_GST = 0;
        $unit_price = $price ; //+ $tax_GST; exclude gst
        $cf_products_xerocode = $dataRecord['cf_products_xerocode'];

        if ($serviceid) {
            $lineItem[] = [
                'section_no' => 1,
                'section_name' => SECTION_1,
                'productid' => $serviceid,
                'quantity' => $qtyS,
                'listprice' => $unit_price,
                'cf_invoice_xerocode' => $cf_products_xerocode,
            ];
            $grandTotal += $qtyS * $unit_price;
        }
    }
}


sleep(SLEEP_SECTION);
log_debug('Processing student journals section', ['service_array' => $serviceArr2]);
// for section 2
foreach ($serviceArr2 as $k => $service) {
    $qtyS = floatval($service['qty']);
    if ($qtyS == 0) {
        continue;
    }
    $serviceNo = $service['serviceNo'];
    $query_service  = sprintf("SELECT * FROM Products WHERE product_no='%s' LIMIT 1; ", $serviceNo);
    $dataRecord = $vtod->query($query_service)[0];

    // $sql  = "SELECT * FROM `boru_products` WHERE `product_no` = ? LIMIT 1";
    // $dataCheck = $dbh->getSingle($sql, array($serviceNo));
    // $dataRecord= array();
    // if (!empty($dataCheck['productid'])){
    //     $dataRecord = (array) json_decode($dataCheck['data']);

    // }

    $service_xerocode = '';
    if (isset($dataRecord['id'])) {
        $serviceid = $dataRecord['id'];
        $service_xerocode = $dataRecord['cf_services_xerocode'];
        $price = (float)$dataRecord['unit_price'];
        if ($dataRecord['taxclass'] == 'on') {
            $tax_GST = $price * 0.1;
        } else {
            $tax_GST = 0;
        }
        $unit_price = $price + $tax_GST;
        if ($serviceid) {
            $lineItem[] = [
                'section_no' => 2,
                'section_name' => 'Student Journals',
                'productid' => $serviceid,
                'quantity' => $qtyS,
                'listprice' => $unit_price,
                //                         'cf_quotes_xerocode' => $service_xerocode,
            //                         'duration' => '1'
            ];
            $grandTotal += $qtyS * $unit_price;
        }
    }
    sleep(SLEEP_ITEM);
}

sleep(SLEEP_SECTION);
// for section 3
foreach ($serviceArr3 as $k => $service) {
    $qtyS = $service['qty'];
    $serviceNo = $service['serviceNo'];
    //             $query_service  = sprintf("SELECT * FROM Products WHERE product_no='%s' LIMIT 1; ",$serviceNo);
    //             $res_service = $vtod->query($query_service);

    $sql  = 'SELECT * FROM `boru_products` WHERE `product_no` = ? LIMIT 1';
    $dataCheck = get_db()->getSingle($sql, [$serviceNo]);
    $dataRecord = [];
    if (!empty($dataCheck['productid'])) {
        $dataRecord = (array) json_decode($dataCheck['data']);

    }


    $service_xerocode = '';
    if (isset($dataRecord['id'])) {
        $serviceid = $dataRecord['id'];
        $service_xerocode = $dataRecord['cf_services_xerocode'];
        $price = (float)$dataRecord['unit_price'];
        if ($dataRecord['taxclass'] == 'on') {
            $tax_GST = $price * 0.1;
        } else {
            $tax_GST = 0;
        }
        $unit_price = $price + $tax_GST;
        if ($serviceid) {
            $lineItem[] = [
                'section_no' => 3,
                'section_name' => 'Teacher Resources',
                'productid' => $serviceid,
                'quantity' => $qtyS,
                'listprice' => $unit_price,

            ];
            $grandTotal += $qtyS * $unit_price;
        }
    }
    sleep(SLEEP_ITEM);
}

if ($quoteId) {
    $quoteRecord['amount'] = (float)$grandTotal;
    $quoteRecord['amount_currency_value'] = (float)$grandTotal;
    $quoteRecord['LineItems'] = $lineItem;
    $quoteRecord['hdnSubTotal'] = (float)$grandTotal;
    $quoteRecord['hdnGrandTotal'] = (float)$grandTotal;

    log_debug('Creating invoice from quote', ['quote_id' => $quoteRecord['id'], 'grand_total' => $grandTotal]);

    $quoteRecord['quote_id'] = $quoteRecord['id'];
    unset($quoteRecord['id']);
    unset($quoteRecord['source']);
    $invoiceArr  = $quoteRecord;
    unset($invoiceArr['assigned_user_id']);
    unset($invoiceArr['createdtime']);



    sleep(SLEEP_SECTION);

    // get deal record
    if ($quoteRecord['potential_id']) {
        $dealRecord = $vtod->retrieve($quoteRecord['potential_id']);

        if ($dealRecord['cf_potentials_billingcontact']) {
            $invoiceArr['cf_invoice_billingcontact'] = $dealRecord['cf_potentials_billingcontact'];
            //                 $contactBillingRecord = $vtod->retrieve($dealRecord['cf_potentials_billingcontact']);
            //                 if($contactBillingRecord) {
            //                     $invoiceArr['cf_invoice_billingcontact'] = $contactBillingRecord['id'];
            //                 }
        }
    }

    // update bill address
    foreach ($address_mapping as $f => $k) {
        $invoiceArr[$f] = $_REQUEST[$k];
        if (strpos($f, 'country') !== false) {
            $invoiceArr[$f] = country_code($_REQUEST[$k]);
        }
        if (strpos($f, 'state') !== false) {
            $invoiceArr[$f] = get_statename($_REQUEST[$k]);
        }

    }

    if ($_REQUEST['50_1'] == 'Yes') {
        foreach ($copy_address as $f => $v) {
            $invoiceArr[$f] = $invoiceArr[$v];
        }


    }


    $invoiceArr['cf_invoice_ponumber1'] = $cf_invoice_ponumber1;

    $invoiceArr['invoicestatus'] = 'Pending';
    $invoiceArr['invoicedate'] = '31-01-2024';
    $invoiceArr['duedate'] = '08-02-2024'; // date('d-m-Y', strtotime('+8 days'));
    //         $invoiceArr["hdnS_H_Amount"] = $shipping_handling_charge;
    //             $invoiceArr["shipping_&_handling"] = $shipping_handling_charge;

    $invoiceArr['cf_invoice_selectedyearlevels']     = implode(' |##| ', $listselectedyearlevels);


    $objectInvoiceJson = json_encode($invoiceArr);
    $invoiceParams = [
        'sessionName' => $vtod->sessionId,
        'operation' => 'create',
        'element' => $objectInvoiceJson,
        'elementType' => 'Invoice',
    ];
    $dataInvoice = $vtod->curlPost($vtconfig_url . 'webservice.php', $invoiceParams);
    $resultInvoice = json_decode($dataInvoice, 1);

    if (!empty($account_id) &&  $resultInvoice['success'] == 1) {
        log_info('Invoice created successfully from quote', [
            'invoice_id' => $resultInvoice['result']['id'] ?? 'unknown',
            'assigned_to' => $invoiceArr['assigned_user_id'] ?? null,
            'contact_id' => $invoiceArr['contact_id'] ?? null,
            'billing_contact' => $invoiceArr['cf_invoice_billing_contact'] ?? null,
            'grand_total' => $grandTotal,
        ]);
        $orgRecord = $vtod->retrieve($account_id);
        if (count($listselectedyearlevels) > 0) {
            $orgRecord['cf_accounts_selectedyearlevels']     = implode(' |##| ', $listselectedyearlevels);
        }

        // if (empty($orgRecord['cf_accounts_curriculumordered'])){
        //     $orgRecord['cf_accounts_curriculumordered'] = date('d-m-Y', time());
        // }
        $orgRecordJson = json_encode($orgRecord);
        $org_Params = [
            'sessionName' => $vtod->sessionId,
            'operation' => 'update',
            'element' => $orgRecordJson,
        ];
        $dataOrgP = $vtod->curlPost($vtconfig_url . 'webservice.php', $org_Params);
    } else {
        log_error('Invoice creation failed from quote', [
            'result' => $resultInvoice,
            'account_id' => $account_id ?? null,
        ]);
    }
} else {
    $quoteRecord['amount'] = (float)$grandTotal;
    $quoteRecord['amount_currency_value'] = (float)$grandTotal;
    $quoteRecord['LineItems'] = $lineItem;
    $quoteRecord['hdnSubTotal'] = (float)$grandTotal;
    $quoteRecord['hdnGrandTotal'] = (float)$grandTotal;

    log_debug('Creating invoice without quote', ['grand_total' => $grandTotal]);
    $invoiceArr  = $quoteRecord;

    sleep(SLEEP_SECTION);


    // update bill address
    foreach ($address_mapping as $f => $k) {
        $invoiceArr[$f] = $_REQUEST[$k];
        if (strpos($f, 'country') !== false) {
            $invoiceArr[$f] = country_code($_REQUEST[$k]);
        }
        if (strpos($f, 'state') !== false) {
            $invoiceArr[$f] = get_statename($_REQUEST[$k]);
        }
    }
    if ($_REQUEST['50_1'] == 'Yes') {
        foreach ($copy_address as $f => $v) {
            $invoiceArr[$f] = $invoiceArr[$v];
        }
    }
    //task_id=58907
    $account_id = $_REQUEST[70];
    if (!empty($account_id)) {
        if (strpos($account_id, 'ACC') !== false) {
            $query_org  = sprintf("SELECT * FROM Accounts WHERE account_no='%s' LIMIT 1; ", $account_id);
            $res_org = $vtod->query($query_org);
            if (isset($res_org[0]['id'])) {
                $account_id = $res_org[0]['id'];
            }
        }
        if (strpos($account_id, '3x') === false) {
            $account_id = '3x'.$account_id;
        }
        try {
            $orgRecord = $vtod->retrieve($account_id);
            if (is_array($orgRecord)) {
                $invoiceArr['account_id'] = $orgRecord['id'];
            }

        } catch (Exception $e) {
        }
    }

    // $account_id = $_REQUEST[70];
    // $query_org  = sprintf("SELECT * FROM Accounts WHERE account_no='%s' LIMIT 1; ",$account_id);
    // $res_org = $vtod->query($query_org);
    // $id_org = $res_org["id"];

    $query_quote  = "SELECT * FROM Quotes WHERE account_id='".$invoiceArr['account_id'] ."' AND subject LIKE '%2024 School Partnership Program%' LIMIT 1; ";
    $res_quote = $vtod->query($query_quote);
    // $quoteId = $res_quote["id"];
    log_debug('Retrieved quote for invoice', ['quote' => $res_quote[0] ?? null]);
    $invoiceArr['contact_id'] = $res_quote[0]['contact_id'];
    $invoiceArr['cf_invoice_billingcontact'] = $res_quote[0]['cf_quotes_billingcontactname'];
    $invoiceArr['quote_id'] = $res_quote[0]['id'];
    $invoiceArr['potential_id'] = $res_quote[0]['potential_id'];
    $invoiceArr['cf_invoice_ponumber1'] = $cf_invoice_ponumber1;
    $invoiceArr['subject'] = '2024 School Partnership Program';
    $invoiceArr['assigned_user_id'] = $res_quote[0]['assigned_user_id'];
    $invoiceArr['invoicestatus'] = 'Pending';
    $invoiceArr['invoicedate'] = date('d-m-Y');
    $invoiceArr['duedate'] = date('d-m-Y', strtotime('+8 days'));
    //         $invoiceArr["hdnS_H_Amount"] = $shipping_handling_charge;
    //             $invoiceArr["shipping_&_handling"] = $shipping_handling_charge;

    $invoiceArr['cf_invoice_selectedyearlevels']     = implode(' |##| ', $listselectedyearlevels);


    $objectInvoiceJson = json_encode($invoiceArr);
    $invoiceParams = [
        'sessionName' => $vtod->sessionId,
        'operation' => 'create',
        'element' => $objectInvoiceJson,
        'elementType' => 'Invoice',
    ];
    $dataInvoice = $vtod->curlPost($vtconfig_url . 'webservice.php', $invoiceParams);
    $resultInvoice = json_decode($dataInvoice, 1);

    if (!empty($orgRecord['id']) &&  $resultInvoice['success'] == 1) {
        log_info('Invoice created successfully without quote', [
            'invoice_id' => $resultInvoice['result']['id'] ?? 'unknown',
            'account_id' => $account_id,
            'grand_total' => $grandTotal,
        ]);

        if (count($listselectedyearlevels) > 0) {
            $orgRecord['cf_accounts_selectedyearlevels']     = implode(' |##| ', $listselectedyearlevels);
        }

        // if (empty($orgRecord['cf_accounts_curriculumordered'])){
        //     $orgRecord['cf_accounts_curriculumordered'] = date('d-m-Y', time());
        // }
        $orgRecordJson = json_encode($orgRecord);
        $org_Params = [
            'sessionName' => $vtod->sessionId,
            'operation' => 'update',
            'element' => $orgRecordJson,
        ];
        $dataOrgP = $vtod->curlPost($vtconfig_url . 'webservice.php', $org_Params);
    } else {
        log_error('Invoice creation failed without quote', [
            'result' => $resultInvoice,
            'account_id' => $account_id ?? null,
        ]);
    }
}
