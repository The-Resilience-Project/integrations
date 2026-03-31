<?php

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

class ShipStationOrder
{
    protected $vtod;
    protected $shipstation;

    protected $invoice_no;
    protected $invoice_id;
    protected $invoice_data;

    protected $total_weight = 0;
    protected $items = [];
    protected $shipping_price = 0;

    protected $contact_email = '';
    protected $account_name = '';

    protected $ship_to = [];
    protected $bill_to = [];

    protected $ss_payload = [];
    protected $ss_response = '';

    public const bulk_order_store_id = '380683';
    public const school_25_store_id = '809689';
    public const school_26_store_id = '823800';

    public const item_sections = ['Student Journals', 'Teacher Resources', 'Extra Resources'];
    public const teacher_sem = ['25x662672', '25x662669', '25x662668', '25x662673'];


    protected $store_id = self::school_25_store_id;
    protected $shipstation_order_id = '';


    public function __construct($request_invoice_id)
    {
        $this->pushlog('-----------------Create Shipment For Invoice:'.$request_invoice_id.'-----------------');
        $this->pushlog('Request invoice ID type: ' . gettype($request_invoice_id));
        $this->pushlog('Request invoice ID empty check: ' . (empty($request_invoice_id) ? 'EMPTY' : 'NOT EMPTY'));

        $this->vtod = init_vtod();
        if (is_string($this->vtod)) {
            $this->pushlog('ERROR: vtod is a string, not an object: ' . $this->vtod);
            return;
        }
        $this->invoice_id = $request_invoice_id;
        if (!str_contains($request_invoice_id, '16x')) {
            $this->invoice_id = '16x' . $request_invoice_id;
        }
        $this->pushlog('-----------------Inv ID:'.$this->invoice_id.'-----------------');


        $authKey = base64_encode('ee461e28fa134604913ce39bf4d9806c:a28ed173ae0449029c83ebba9623e254'); // Shipstation API Key:API Secret
        $this->shipstation = new shipstation($authKey);
        $this->shipstation->decode = true;
        $this->shipstation->print = false;
    }

    public function create()
    {
        $this->pushlog('=== Starting create() method ===');

        if (is_string($this->vtod)) {
            $this->pushlog('ERROR: Failed to create SS order - unable to init vtod: ' . $this->vtod);
            $this->pushlog('---------------------------------------------------------------------');
            http_response_code(500);
            echo json_encode(['success' => 'false', 'msg' => 'Failed to init vtod: ' . $this->vtod]);
            exit;
        }

        if (!$this->invoice_id || $this->invoice_id == '16x') {
            $this->pushlog('ERROR: Failed to create SS order - no invoice ID or invalid ID');
            $this->pushlog("Invoice ID value: '" . $this->invoice_id . "'");
            $this->pushlog('---------------------------------------------------------------------');
            http_response_code(500);
            echo json_encode(['success' => 'false', 'msg' => 'No Invoice ID or invalid ID: ' . $this->invoice_id]);
            exit;
        }

        $this->pushlog('Attempting to retrieve invoice: ' . $this->invoice_id);
        $this->invoice_data = $this->vtod->retrieve($this->invoice_id);
        $this->pushlog('Invoice data retrieved, checking if valid...');
        $this->pushlog('Invoice data type: ' . gettype($this->invoice_data));
        if (is_array($this->invoice_data)) {
            $this->pushlog('Invoice data has ' . count($this->invoice_data) . ' fields');
            $this->pushlog("Invoice data 'id' field: " . (isset($this->invoice_data['id']) ? $this->invoice_data['id'] : 'NOT SET'));
        }

        if (!$this->invoice_data['id']) {
            $this->pushlog('Failed to create SS order - failed to find invoice in VT');
            $this->pushlog('Invoice ID searched: ' . $this->invoice_id);
            $this->pushlog('Invoice data returned: ' . json_encode($this->invoice_data));
            $this->pushlog('---------------------------------------------------------------------');
            http_response_code(500);
            echo json_encode(['success' => 'false', 'msg' => 'Failed to find invoice in VT with ID: ' . $this->invoice_id]);
            exit;
        }

        $this->pushlog('Found invoice data in VT');
        $this->pushlog('Invoice No: ' . $this->invoice_data['invoice_no']);
        $this->pushlog('Invoice Subject: ' . $this->invoice_data['subject']);
        $this->invoice_id = $this->invoice_data['id'];
        if (str_contains($this->invoice_data['subject'], 'Workplace')) {
            // workplace store
            $this->store_id = self::bulk_order_store_id;
            $this->shipstation_order_id = '2'.$this->invoice_data['invoice_no'];
            $this->pushlog('Using WORKPLACE store (ID: ' . $this->store_id . ')');
        } elseif (str_contains($this->invoice_data['subject'], '2026')) {
            // 2026 store
            $this->store_id = self::school_26_store_id;
            $this->shipstation_order_id = $this->invoice_data['invoice_no'];
            $this->pushlog('Using 2026 SCHOOL store (ID: ' . $this->store_id . ')');
        } else {
            // 2025 store
            $this->store_id = self::school_25_store_id;
            $this->shipstation_order_id = $this->invoice_data['invoice_no'];
            $this->pushlog('Using 2025 SCHOOL store (ID: ' . $this->store_id . ')');
        }
        $this->pushlog('ShipStation Order Number: ' . $this->shipstation_order_id);

        // check if order needs to be created
        if ($this->count_line_items() == 0) {
            echo json_encode(['success' => 'true', 'orderData' => '', 'msg' => 'No need to create order in Ship Station']);
            exit;
        }

        // Calculate weight BEFORE checking if order exists, so we can see what the weight would be
        try {
            $this->format_items();
            $this->pushlog('-- Formatted Items');

            $this->set_account_data();
            $this->pushlog('-- Set Account Data');

            $this->format_ship_to();
            $this->pushlog('-- Formatted Ship To');

            $this->format_bill_to();
            $this->pushlog('-- Formatted Bill To');
        } catch (Exception $e) {
            $error = $e->getMessage();
            $this->pushlog('Error formatting data: '. $error . "\n");
            http_response_code(500);
            echo json_encode(['success' => 'false','msg' => $error]);
            exit;
        }

        // check if order already in SS (after calculating weight so we can see it in logs)
        if ($this->is_order_in_SS()) {
            $this->pushlog("Order already in SS - but here's what the weight would have been:");
            $this->pushlog('Calculated total weight: ' . $this->total_weight . 'g');
            $this->pushlog('Number of items: ' . count($this->items));
            $this->pushlog('-------------------------------------------------------------------');
            echo json_encode([
                'success' => 'true',
                'orderData' => '',
                'msg' => 'Order already in Ship Station',
                'calculated_weight_grams' => $this->total_weight,
                'item_count' => count($this->items),
            ]);
            exit;
        }

        try {
            $this->format_ss_payload();
            $this->pushlog('SS Payload');
            $this->pushlog($this->ss_payload);
        } catch (Exception $e) {
            $error = $e->getMessage();
            $this->pushlog('Error formatting SS payload: '. $error . "\n");
            http_response_code(500);
            echo json_encode(['success' => 'false','msg' => $error]);
            exit;
        }

        $ss_response = $this->shipstation->post('/orders/createorder', $this->ss_payload);

        if (!isset($ss_response['orderId'])) {
            $this->pushlog('Failed to create SS order');
            $this->pushlog($ss_response);


            http_response_code(500);
            echo json_encode(['success' => 'false','msg' => 'Failed to create order in SS: '. print_r($ss_response['data'], 1)]);
            exit;
        }

        $this->pushlog('Created SS order'.$ss_response['orderId']);
        $this->pushlog($ss_response);


        if ($this->invoice_data['cf_invoice_holduntil']) {
            $hold_response = $this->shipstation->post('/orders/holduntil', [
              'orderId' => $ss_response['orderId'],
              'holdUntilDate' => $this->invoice_data['cf_invoice_holduntil'],
            ]);
            $this->pushlog('Hold response');
            $this->pushlog($hold_response);

            if (isset($hold_response['success']) and $hold_response['success']) {
                //
            } else {
                $this->pushlog('Failed to hold order');

                http_response_code(500);
                echo json_encode(['success' => 'false','msg' => 'Failed to hold order in SS']);
                exit;

            }
        }


        $this->pushlog('-------------------------------------------------------------------');

        echo json_encode(['success' => 'true','orderData' => $ss_response['orderId'], 'msg' => '']);
        exit;


    }

    private function is_order_in_SS()
    {
        $this->pushlog('Getting order '.$this->shipstation_order_id);
        $ss_response = $this->shipstation->get('/orders/?orderNumber='.$this->shipstation_order_id.'&storeId='.$this->store_id.'&orderStatus=on_hold&orderStatus=shipped&orderStatus=awaiting_shipment');
        $number_of_orders = $ss_response['total'];
        $this->pushlog(print_r($ss_response, 1));
        return (int)$number_of_orders > 0;
    }

    private function format_ss_payload()
    {
        $this->ss_payload['orderNumber'] = $this->shipstation_order_id;
        $this->ss_payload['orderDate'] = date('Y-m-d');
        $this->ss_payload['orderStatus'] = $this->invoice_data['cf_invoice_holduntil'] ? 'on_hold' : 'awaiting_shipment';
        if ($this->invoice_data['cf_invoice_shipby']) {
            $this->ss_payload['shipByDate'] = $this->invoice_data['cf_invoice_shipby'];
        }

        $this->ss_payload['billTo'] = $this->bill_to;
        $this->ss_payload['shipTo'] = $this->ship_to;
        $this->ss_payload['items'] = $this->items;

        $this->ss_payload['carrierCode'] = 'star_track';
        $this->ss_payload['serviceCode'] = 'express';
        $this->ss_payload['packageCode'] = 'startrack_carton';
        $this->ss_payload['shippingAmount'] = $this->shipping_price;
        $this->ss_payload['customerEmail'] = $this->contact_email;
        $this->ss_payload['weight'] = [
            'value' => $this->total_weight,
            'units' => 'grams',
        ];
        $this->ss_payload['dimensions'] = [
            'units' => 'centimeters',
            'length' => 35.00,
            'width' => 23.00,
            'height' => 22.00,
        ];
        $this->ss_payload['advancedOptions'] = [
            'storeId' => $this->store_id,
        ];
    }


    private function set_account_data()
    {
        if ($this->invoice_data['account_id']) {
            $this->pushlog('Retrieving account data for: ' . $this->invoice_data['account_id']);
            $account_data = $this->vtod->retrieve($this->invoice_data['account_id']);
            if ($account_data['accountname']) {
                $this->account_name = $account_data['accountname'];
                $this->pushlog('Account name: ' . $this->account_name);
            }
        }
    }

    private function format_bill_to()
    {
        $this->bill_to['name'] = $this->account_name;
        $this->bill_to['company'] = '';
        $this->bill_to['street1'] = $this->invoice_data['bill_street'];
        $this->bill_to['city'] = $this->invoice_data['bill_city'];
        $this->bill_to['state'] = $this->invoice_data['bill_state'];
        $this->bill_to['postalCode'] = $this->invoice_data['bill_code'];
        $this->bill_to['country'] = 'AU';
        $this->bill_to['residential'] = null;
    }

    private function format_ship_to()
    {
        $this->ship_to['company'] = $this->account_name;
        $this->ship_to['street1'] = $this->invoice_data['ship_street'];
        $this->ship_to['city'] = $this->invoice_data['ship_city'];
        $this->ship_to['state'] = $this->invoice_data['ship_state'];
        $this->ship_to['postalCode'] = $this->invoice_data['ship_code'];
        $this->ship_to['country'] = 'AU';
        $this->ship_to['residential'] = false;
        $this->ship_to['addressVerified'] = 'Address validated successfully';

        if (empty($this->invoice_data['contact_id'])) {
            $this->pushlog('No contact ID on invoice');
            return;
        }

        $this->pushlog('Retrieving contact data for: ' . $this->invoice_data['contact_id']);
        $contact_data = $this->vtod->retrieve($this->invoice_data['contact_id']);
        if ($contact_data['phone']) {
            $this->ship_to['phone'] = $contact_data['phone'];
        }
        if ($contact_data['email']) {
            $this->contact_email = $contact_data['email'];
            $this->pushlog('Contact email: ' . $this->contact_email);
        }
        if ($contact_data['firstname']) {
            $this->ship_to['name'] = $contact_data['firstname'];
        }
        if ($contact_data['lastname']) {
            $this->ship_to['name'] .= ' '.$contact_data['lastname'];
        }
        $this->pushlog('Ship to name: ' . $this->ship_to['name']);

    }

    private function count_line_items()
    {
        $line_items = $this->invoice_data['LineItems'];
        $count = 0;

        foreach ($line_items as $line_item) {

            if (in_array($line_item['section_name'], self::item_sections)) {
                $count++;
            }
        }
        $this->pushlog('Shippable line items count: ' . $count);
        return $count;
    }

    private function format_items()
    {
        $line_items = $this->invoice_data['LineItems'];
        $this->pushlog('Total line items in invoice: ' . count($line_items));

        $product_ids = [];

        foreach ($line_items as $line_item) {
            $this->pushlog('Processing line item: ' . $line_item['product_name'] . ' | Section: ' . $line_item['section_name']);

            if (!in_array($line_item['section_name'], self::item_sections)) {
                $this->pushlog('-- Skipping (not in allowed sections)');
                continue;
            }
            $product_ids[] = "'".$line_item['productid']."'";
        }

        $this->pushlog('Product IDs to query: ' . count($product_ids));
        $sql = 'SELECT * FROM Products WHERE id IN ('.implode(',', $product_ids).');';
        $this->pushlog('SQL Query: ' . $sql);
        $products = $this->vtod->query($sql);
        $this->pushlog('Products retrieved from VT: ' . count($products));
        $product_map = [];

        foreach ($products as $p) {
            $product_map[$p['id']] = ['weight' => $p['cf_products_weight'], 'sku' => $p['cf_products_skunumber']];
            $this->pushlog('Product ' . $p['id'] . ' | Weight: ' . $p['cf_products_weight'] . 'g | SKU: ' . $p['cf_products_skunumber']);
        }

        $this->pushlog('---- Building ShipStation Items ----');
        foreach ($line_items as $line_item) {

            if ($line_item['product_name'] === 'Shipping costs') {
                $this->shipping_price = $line_item['netprice'];
                $this->pushlog('-- Shipping costs '. $this->shipping_price);
                continue;
            }
            if (!in_array($line_item['section_name'], self::item_sections)) {
                $this->pushlog('-- Skipping ' . $line_item['product_name'] . ' (section: ' . $line_item['section_name'] . ')');
                continue;
            }
            if (in_array($line_item['productid'], self::teacher_sem)) {
                $this->pushlog('-- Skipping ' . $line_item['product_name'] . ' (teacher SEM product)');
                continue;
            }
            $qty = (int)$line_item['quantity'];
            $product_data = $product_map[$line_item['productid']];
            $product_sku = $product_data['sku'];
            $product_weight = $product_data['weight'];
            $item_total_weight = (float)$product_weight * $qty;
            $this->total_weight += $item_total_weight;
            $this->pushlog('-- Adding: ' . $line_item['product_name'] . ' | Qty: ' . $qty . ' | Unit Weight: ' . $product_weight . 'g | Item Total: ' . $item_total_weight . 'g');
            $this->items[] = [
                'name' => $line_item['product_name'],
                'quantity' => $qty,
                'unitPrice' => $line_item['listprice'],
                'sku' => $product_sku,
            ];
        }
        $this->pushlog('---- TOTAL SHIPMENT WEIGHT: ' . $this->total_weight . 'g ----');

    }

    private function pushlog($var)
    {
        if (is_array($var) || is_object($var)) {
            log_debug('Create shipment 2025', ['data' => $var]);
        } else {
            log_debug('Create shipment 2025: ' . $var);
        }
    }
}

// Log all incoming request parameters
log_debug('Create shipment 2025: Incoming REQUEST parameters', ['data' => $_REQUEST]);
log_debug('Create shipment 2025: Incoming GET parameters', ['data' => $_GET]);
log_debug('Create shipment 2025: Incoming POST parameters', ['data' => $_POST]);
log_debug('Create shipment 2025: Request method', ['method' => $_SERVER['REQUEST_METHOD']]);

// Get the invoice ID from request
$invoice_id = isset($_REQUEST['recordid']) ? $_REQUEST['recordid'] : null;
if (empty($invoice_id) && isset($_REQUEST['id'])) {
    $invoice_id = $_REQUEST['id'];
}

log_debug('Create shipment 2025: Using invoice ID', ['invoice_id' => $invoice_id]);

$ship_station_order = new ShipStationOrder($invoice_id);
$ship_station_order->create();
