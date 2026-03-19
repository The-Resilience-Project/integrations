<?php

/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL  & ~(E_STRICT|E_NOTICE) );
*/
chdir(dirname(__FILE__));
require_once('shipstation.php');

require '../init.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, GET, POST');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false]);
    exit;
}
$strKey = '384c7039e4914a31935861a6e52b4974:fed4f771714b48919e7cdfa2d94411db'; // Shipstation API Key:API Secret
$authKey = base64_encode($strKey);

$vtod = init_vtod();
$invoiceId = $_REQUEST['id'];
$st = new shipstation($authKey);
$st->decode = true;
$st->print = false;

$orderData = '';
pushlog('-----------------Create Shipment For Invoice:'.$invoiceId.'-----------------');
$shipmentData = '';
if ($invoiceId) {
    $invoiceData = $vtod->retrieve($invoiceId);
    pushlog('InvoiceData');
    pushlog($invoiceData);
    if ($invoiceData['id']) {
        // $sqlBSV = "SELECT * FROM `boru_shipment_invoice` WHERE `invoice_id` = ? LIMIT 1";
        // $resBSV = $dbh->runFetchAll($sqlBSV, array($invoiceData['id']));
        // pushlog("BoruShipmentInvoice Table");
        // pushlog($resBSV);
        $shipAmount = 0;
        #create Order
        $lineItems = $invoiceData['LineItems'];
        pushlog('lineItems');
        pushlog($lineItems);
        $items = [];
        $totalWeight = 0;
        if (count($lineItems) > 0) {
            foreach ($lineItems as $key => $val) {
                $qty = (int)$val['quantity'];
                if (strpos($val['product_name'], 'Shipping costs') !== false) {
                    $shipAmount = $val['netprice'];
                }
                if ($val['section_name'] != 'Student Journals' && $val['section_name'] != 'Teacher Resources') {
                    continue;
                }
                $productData = $vtod->retrieve($val['productid']);
                $skuProduct = '';
                if ($productData['id'] && $productData['cf_products_weight']) {
                    $weightProduct = $productData['cf_products_weight'];
                    $skuProduct = $productData['cf_products_skunumber'];
                }
                $totalWeight += (float)$weightProduct * $qty;
                $items[] = [
                    'name' => $val['product_name'],
                    'quantity' => $qty,
                    'unitPrice' => $val['netprice'],
                    'sku' => $skuProduct,
                ];
            }
        }
        $contactPhone = $contactName = $contactEmail = '';
        if ($invoiceData['contact_id']) {
            $contactData = $vtod->retrieve($invoiceData['contact_id']);
            if ($contactData['phone']) {
                $contactPhone = $contactData['phone'];
            }
            if ($contactData['email']) {
                $contactEmail = $contactData['email'];
            }
            if ($contactData['firstname']) {
                $contactName .= $contactData['firstname'];
            }
            if ($contactData['lastname']) {
                $contactName .= ' '.$contactData['lastname'];
            }
        }
        $accountName = '';
        if ($invoiceData['account_id']) {
            $accountData = $vtod->retrieve($invoiceData['account_id']);
            if ($accountData['accountname']) {
                $accountName = $accountData['accountname'];
            }
        }
        $data['orderNumber'] = $invoiceData['invoice_no'];
        $data['orderDate'] = date('Y-m-d');
        $data['orderStatus'] = 'awaiting_shipment';

        $billing_to = [];
        $billing_to['name'] = $accountName;
        $billing_to['company'] = '';
        $billing_to['street1'] = $invoiceData['bill_street'];
        $billing_to['city'] = $invoiceData['bill_city'];
        $billing_to['state'] = $invoiceData['bill_state'];
        $billing_to['postalCode'] = $invoiceData['bill_code'];
        $billing_to['country'] = 'AU';
        $billing_to['residential'] = null;

        $ship_to = [];
        $ship_to['name'] = $contactName;
        $ship_to['company'] = $accountName;
        $ship_to['street1'] = $invoiceData['ship_street'];
        $ship_to['city'] = $invoiceData['ship_city'];
        $ship_to['state'] = $invoiceData['ship_state'];
        $ship_to['postalCode'] = $invoiceData['ship_code'];
        $ship_to['phone'] = $contactPhone;
        $ship_to['country'] = 'AU';
        $ship_to['residential'] = false;
        $ship_to['addressVerified'] = 'Address validated successfully';
        $data['billTo'] = $billing_to;
        $data['shipTo'] = $ship_to;
        $data['items'] = $items;


        $data['carrierCode'] = 'star_track';
        $data['serviceCode'] = 'express';
        $data['packageCode'] = 'startrack_carton';
        $data['shippingAmount'] = $shipAmount;
        $data['customerEmail'] = $contactEmail;
        $data['weight'] = [
            'value' => $totalWeight,
            'units' => 'grams',
        ];
        $data['dimensions'] = [
            'units' => 'inches',
            'length' => 6.00,
            'width' => 6.00,
            'height' => 6.00,
        ];
        $data['advancedOptions'] = [
            'storeId' => 791256,
        ];
        pushlog('data');
        pushlog($data);
        $orderData = $st->post('/orders/createorder', $data);
        pushlog('orderData');
        pushlog($orderData);
        if (isset($orderData['orderId'])) {
            $sql = 'INSERT INTO `boru_shipment_invoice` (`invoice_id`,`order_id`,`shipment_id`,`order_data`,`shipment_data`,`created_time`) VALUES (?,?,?,?,?,NOW())';
            get_db()->run($sql, [$invoiceData['id'], $orderData['orderId'], '', json_encode($orderData), '']);
            /*
            $dataLabel['orderId'] = $orderData['orderId'];
            $dataLabel["carrierCode"] = "australia_post";
            $dataLabel["serviceCode"] = "au_post_express_post_signature_3J55";
            $dataLabel["packageCode"] = "package";
            $dataLabel["weight"] = array(
                "value" => 1,
                "unit" => "grams"
            );
            $shipmentId = '';
            $shipmentData = $st->post("/orders/createlabelfororder", $dataLabel);
            if(isset($shipmentData['shipmentId'])) $shipmentId = $shipmentData['shipmentId'];
            $sql = "INSERT INTO `boru_shipment_invoice` (`invoice_id`,`order_id`,`shipment_id`,`order_data`,`shipment_data`,`created_time`) VALUES (?,?,?,?,?,NOW())";
            get_db()->run($sql, array($invoiceData['id'], $orderData['orderId'], $shipmentId, json_encode($orderData), json_encode($shipmentData)));
            */
        }

        echo json_encode(['success' => true,'orderData' => $orderData]);
        exit;
    } else {
        echo json_encode(['success' => false]);
        exit;
    }
} else {
    echo json_encode(['success' => false]);
    exit;
}
pushlog('-----------------End Create Shipment For Invoice:'.$invoiceId.'-----------------');
function getStateCode($lookup)
{
    $states = [
        'ACT' => 'Australian Capital Territory',
        'NSW' => 'New South Wales',
        'NT' => 'Northern Territory',
        'QLD' => 'Queensland',
        'SA' => 'South Australia',
        'TAS' => 'Tasmania',
        'VIC' => 'Victoria',
        'WA' => 'Western Australia',
    ];

    $stateCode = array_search($lookup, $states);
    return $stateCode;
}

function pushlog($var)
{
    global $debug;
    if (!$debug) {
        return;
    }

    if (is_array($var) || is_object($var)) {
        log_debug('Create shipment', ['data' => $var]);
    } else {
        log_debug('Create shipment: ' . $var);
    }
}
