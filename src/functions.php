<?php

/* Helper functions */
function cleanPhoneNum($phone)
{
    if (substr($phone, 0, 1) == '1') {
        $phone = substr($phone, 1);
    }
    return trim(eregi_replace('[^0-9]', '', $phone));
}

/* Debugging functions */
function get_last_update($date)
{
    if (empty($date)) {
        return 'N/A';
    }
    $now = strtotime(date('Y-m-d H:i:s'));
    $status_date = strtotime($date);
    $x = date($now - $status_date);
    $days = floor($x / (24 * 60 * 60)); // convert to days
    return $days;
}
function str_limit($value, $limit = 100, $end = '...')
{
    /*
        $str = $value;
        $len = $limit;
        $tail = max(0, $len-10);
        $trunk = substr($str, 0, $tail);
        $trunk .= strrev(preg_replace('~^..+?[\s,:]\b|^...~', $end, strrev(substr($str, $tail$len-$tail))));
        return $trunk;
        /*$string = $value;
        $max = $limit;
        $leave = $max - strlen ($rep);

        return substr_replace($string, $rep, $leave);*/

    // Take into account $end string into the limit
    $valuelen = strlen($value);
    //echo substr($value, 0, 20);
    if ($limit < $valuelen) {
        return substr($value, 0, $limit) . $end;
    }

    return  $value;
}

function readExcelFiles($uploads_dir, $quote, $flg_type = 'create', $checkPartNoExist = false)
{
    include_once dirname(__FILE__).'/lib/PHPExcel/PHPExcel.php';
    $vtod = init_vtod();
    $quoteLineItem = [];
    if (file_exists($uploads_dir)) {
        $excelReader = PHPExcel_IOFactory::createReaderForFile($uploads_dir);
        $excelReader->setLoadAllSheets();
        $excelObj = $excelReader->load($uploads_dir);
        $worksheet = $excelObj->getSheet(0);

        if ($flg_type == 'create') {
            $arr_quotes = [];
            if ($quote['record_id']) {
                $opty_detail = $vtod->retrieve('5x'.$quote['record_id']);
                $account_id = '';
                $assigned_user_id = '';
                $opty_name = '';
                if (count($opty_detail) > 0) {
                    $account_id = $opty_detail['related_to'];
                    $assigned_user_id = $opty_detail['assigned_user_id'];
                    if ($opty_detail['cf_potentials_opportunityname']) {
                        $opty_name = $opty_detail['cf_potentials_opportunityname'];
                    } else {
                        $opty_name = $opty_detail['potentialname'];
                    }
                    if ($opty_detail['potentialname']) {
                        $arr_quotes['cf_quotes_3tprojectid'] = $opty_detail['potentialname'];
                    }
                    if ($opty_detail['cf_potentials_opportunityname']) {
                        $arr_quotes['subject'] = $opty_detail['cf_potentials_opportunityname'];
                    }
                    if ($opty_detail['contact_id']) {
                        $arr_quotes['contact_id'] = $opty_detail['contact_id'];
                    }
                    if ($opty_detail['related_to']) {
                        $arr_quotes['account_id'] = $opty_detail['related_to'];
                    }
                    if ($opty_detail['cf_potentials_primepoc']) {
                        $arr_quotes['cf_quotes_billingcontact'] = $opty_detail['cf_potentials_primepoc'];
                    }
                    if ($opty_detail['cf_potentials_prime']) {
                        $arr_quotes['cf_quotes_blllingorg'] = $opty_detail['cf_potentials_prime'];
                    }
                }
                //                $arr_quotes["account_id"] = $account_id;
            }
            #task_id: 46425
            //            if($quote['account_id']) $arr_quotes["account_id"] = $quote['account_id'];
            $arr_quotes['quotestage'] = 'Created';
            $arr_quotes['bill_street'] = '123 some ln';
            $arr_quotes['bill_city'] = 'Chicago';
            $arr_quotes['bill_state'] = 'Illinois';
            $arr_quotes['bill_code'] = '60603';
            $arr_quotes['ship_street'] = '321 some ln';
            $arr_quotes['ship_city'] = 'Chicago';
            $arr_quotes['ship_state'] = 'Illinois';
            $arr_quotes['ship_code'] = '60603';

            $part_number = '';
            $desc = '';
            $qty = '';
            $cos_col = '';
            $lead_time_col = '';
            //            $mrf_list_price_col = '';
            $margin_percentage = $quote['margin_percentage'];
            for ($i = $quote['header_row'] + 1; $i <= $quote['last_row']; $i++) {
                if ($quote['mfr_part_number']) {
                    $part_number = $worksheet->getCell($quote['mfr_part_number'] . $i)->getCalculatedValue();
                }
                if ($quote['description']) {
                    $desc = $worksheet->getCell($quote['description'] . $i)->getCalculatedValue();
                }
                if ($quote['quantity']) {
                    $qty = $worksheet->getCell($quote['quantity'] . $i)->getCalculatedValue();
                }
                if ($quote['cost']) {
                    $cos_col = $worksheet->getCell($quote['cost'] . $i)->getCalculatedValue();
                }
                if ($quote['lead_time']) {
                    $lead_time_col = $worksheet->getCell($quote['lead_time'] . $i)->getCalculatedValue();
                }
                //                if($quote['mrf_list_price']) $mrf_list_price_col = $worksheet->getCell($quote['mrf_list_price'] . $i)->getCalculatedValue();
                if ($quote['manufacturer']) {
                    $manufacturer_col = (string)$worksheet->getCell($quote['manufacturer'] . $i)->getCalculatedValue();
                }
                if ($quote['vendor_part_number']) {
                    $vendor_part_number_col = (string)$worksheet->getCell($quote['vendor_part_number'] . $i)->getCalculatedValue();
                }
                if ($quote['system_phase']) {
                    $system_phase = $worksheet->getCell($quote['system_phase'] . $i)->getCalculatedValue();
                }

                $lineItem = [];
                $product_id = '';
                $list_price = '';
                if ($part_number) {
                    $query_product = sprintf("SELECT * FROM Products WHERE mfr_part_no='%s' LIMIT 1; ", $part_number);
                    $res_product = $vtod->query($query_product);
                    if (count($res_product) <= 0) {
                        #create product
                        $arr_prod = [];
                        try {
                            $arr_prod['cf_products_mfgr'] = '3x457762';
                            $arr_prod['productcode'] = $part_number;
                            $arr_prod['productname'] = urlencode($desc);
                            $arr_prod['purchase_cost'] = $cos_col;
                            $arr_prod['mfr_part_no'] = $part_number;
                            $arr_prod['cf_products_vendor'] = '3x457762';
                            $arr_prod['assigned_user_id'] = '19x1';
                            $data_prod = $vtod->create('Products', $arr_prod);
                            if ($data_prod['id']) {
                                $product_id = $data_prod['id'];
                            }
                            $list_price = $cos_col;
                        } catch (Exception $e) {
                        }

                    } else {
                        $product_id = $res_product[0]['id'];
                    }

                    if ($product_id) {
                        $lineItem['productid'] = $product_id;
                    }

                    $lineItem['cf_quotes_partnumber'] = $part_number;
                    $lineItem['cf_quotes_mfrpartno'] = $part_number;
                    //                    $lineItem['cf_quotes_mfrlistprice'] = $mrf_list_price_col;
                    $lineItem['cf_quotes_manufacturer'] = $manufacturer_col;
                    $lineItem['cf_quotes_vendorpartnumber'] = $vendor_part_number_col;
                    $lineItem['cf_quotes_leadtime'] = $lead_time_col;
                    $lineItem['cf_quotes_systemname'] = $system_phase;
                    $lineItem['section_no'] = '2';
                    $lineItem['section_name'] = 'Materials';
                    $lineItem['quantity'] = (int)$qty;
                    $lineItem['purchase_cost'] = (float)$cos_col * (int)$qty;
                    $lineItem['listprice'] = $cos_col * 100 / (100 - $margin_percentage);
                    $lineItem['netprice'] = (float)$lineItem['listprice'] * (int)$qty;
                    $lineItem['listprice'] = $cos_col;
                    $lineItem['netprice'] = (float)$cos_col * (float)$qty;

                    $quoteLineItem[] = $lineItem;
                }
            }
            $arr_quotes['potential_id'] = '5x' . $quote['record_id']; //change
            $arr_quotes['subject'] = $opty_name;
            $arr_quotes['created_user_id'] = $vtod->userId;
            $arr_quotes['assigned_user_id'] = $assigned_user_id;
            $arr_quotes['LineItems'] = $quoteLineItem;
        } else {
            $quote_id = '13x'.$quote['record_id'];
            //task_id=46423
            $from_uid = $_POST['from_uid'];
            $tmp = explode('x', $from_uid);
            if (count($tmp) <= 1) {
                $from_uid = '19x'.$from_uid;
            }

            $arr_quotes = $vtod->retrieve($quote_id);
            $quote_subject = $arr_quotes['subject'];
            $currentLineItem = $arr_quotes['LineItems'];
            $arr_new_Item = [];
            $part_number = '';
            $unit_sell_col = '';
            //            $mrf_list_price_col = '';
            $desc = '';
            for ($i = $quote['header_row'] + 1; $i <= $quote['last_row']; $i++) {
                if ($quote['mfr_part_number']) {
                    $part_number = (string)$worksheet->getCell(strtoupper($quote['mfr_part_number']) . $i)->getCalculatedValue();
                }
                if ($quote['description']) {
                    $desc = $worksheet->getCell($quote['description'] . $i)->getCalculatedValue();
                }
                if ($quote['quantity']) {
                    $qty_col = $worksheet->getCell($quote['quantity'] . $i)->getCalculatedValue();
                }
                if ($quote['cost']) {
                    $cost_col = $worksheet->getCell(strtoupper($quote['cost']) . $i)->getCalculatedValue();
                }
                if ($quote['lead_time']) {
                    $lead_time_col = $worksheet->getCell(strtoupper($quote['lead_time']) . $i)->getCalculatedValue();
                }
                //                if($quote['mrf_list_price']) $mrf_list_price_col = (string)$worksheet->getCell($quote['mrf_list_price'] . $i)->getCalculatedValue();
                if ($quote['manufacturer']) {
                    $manufacturer_col = (string)$worksheet->getCell($quote['manufacturer'] . $i)->getCalculatedValue();
                }
                if ($quote['vendor_part_number']) {
                    $vendor_part_number_col = (string)$worksheet->getCell($quote['vendor_part_number'] . $i)->getCalculatedValue();
                }
                //                if($quote['system_phase']) $system_phase_col = $worksheet->getCell($quote['system_phase'] . $i)->getCalculatedValue();

                if ($part_number) {
                    $arr_new_Item[$part_number]['cf_quotes_partnumber'] = $part_number;
                    $arr_new_Item[$part_number]['description'] = urlencode($desc);
                    $arr_new_Item[$part_number]['purchase_cost'] = $cost_col;
                    $arr_new_Item[$part_number]['quantity'] = (int)$qty_col;
                    $arr_new_Item[$part_number]['margin_percentage'] = $quote['margin_percentage'];
                    $arr_new_Item[$part_number]['lead_time'] = $quote['lead_time'];
                    //                    $arr_new_Item[$part_number]['mrf_list_price_col'] = $mrf_list_price_col;
                    $arr_new_Item[$part_number]['cf_quotes_manufacturer'] = $manufacturer_col;
                    $arr_new_Item[$part_number]['cf_quotes_vendorpartnumber'] = $vendor_part_number_col;
                }
            }
            $array_mfr_part_match = $array_mfr_part_match_items = [];
            if (!empty($currentLineItem)) {

                $array_pn_not_exist = [];
                foreach ($currentLineItem as $key => $cItem) {
                    $oldprice = (float)$cItem['listprice'];

                    if ($arr_new_Item[$cItem['cf_quotes_partnumber']]) {
                        //                        $currentLineItem[$key]['cf_quotes_mfrlistprice'] = $arr_new_Item[$cItem['cf_quotes_partnumber']]['mrf_list_price_col'];
                        $currentLineItem[$key]['lead_time'] = $arr_new_Item[$cItem['cf_quotes_partnumber']]['lead_time'];
                        $currentLineItem[$key]['cf_quotes_unitcost'] = (float)$arr_new_Item[$cItem['cf_quotes_partnumber']]['purchase_cost'];
                        $currentLineItem[$key]['quantity'] = (int)$arr_new_Item[$cItem['cf_quotes_partnumber']]['quantity'];
                        $currentLineItem[$key]['product_name'] = $arr_new_Item[$cItem['cf_quotes_partnumber']]['description'];
                        $currentLineItem[$key]['margin_percentage'] = $arr_new_Item[$cItem['cf_quotes_partnumber']]['margin_percentage'];
                        $currentLineItem[$key]['cf_quotes_manufacturer'] = $arr_new_Item[$cItem['cf_quotes_partnumber']]['cf_quotes_manufacturer'];
                        $currentLineItem[$key]['cf_quotes_vendorpartnumber'] = $arr_new_Item[$cItem['cf_quotes_partnumber']]['cf_quotes_vendorpartnumber'];
                        $currentLineItem[$key]['purchase_cost'] = (float)$arr_new_Item[$cItem['cf_quotes_partnumber']]['purchase_cost'] * (int)$arr_new_Item[$cItem['cf_quotes_partnumber']]['quantity'];
                        $currentLineItem[$key]['listprice'] = (float)$arr_new_Item[$cItem['cf_quotes_partnumber']]['purchase_cost'] * 100 / (100 - (float)$arr_new_Item[$cItem['cf_quotes_partnumber']]['margin_percentage']);
                        $currentLineItem[$key]['netprice'] = $currentLineItem[$key]['listprice'] * (int)$arr_new_Item[$cItem['cf_quotes_partnumber']]['quantity'];
                        //                        $currentLineItem[$key]['cf_quotes_systemname'] = $arr_new_Item[$cItem['cf_quotes_partnumber']]['system_phase_col'];

                        $newPrice = $currentLineItem[$key]['listprice'];
                        $array_mfr_part_match[] = $cItem['cf_quotes_partnumber'];
                        $array_mfr_part_match_items[] = [ 'item' => $cItem, 'quote_id' => $quote_id, 'productid' => $cItem['productid'], 'oldprice' => $oldprice, 'newPrice' => (float)$newPrice, 'vendor' => $quote['vendor'], 'uid' => $from_uid ];

                    }
                }

            }

            $arr_new_Item_tmp = $arr_new_Item;
            foreach ($arr_new_Item_tmp as $k => $nItem) {
                if (in_array($k, $array_mfr_part_match)) {
                    unset($arr_new_Item_tmp[$k]);
                }

            }

            if ($checkPartNoExist && count($arr_new_Item_tmp) > 0) {
                $message_alert = '<div>';
                foreach ($arr_new_Item_tmp as $k => $pd) {
                    $message_alert .= '<p>Product MFR Part Number '.$pd['cf_quotes_partnumber'].' in XLSX does not match any products in Quote '.$quote_subject.'</p>';
                }

                $message_alert .= '</div>';
                return $message_alert;
            }

            #task_id: 46578
            $arr_Product = [];


            if (count($arr_new_Item_tmp) > 0 && !$checkPartNoExist) {
                foreach ($arr_new_Item_tmp as $kItem => $vItem) {
                    if ($kItem) {
                        $query_product = sprintf("SELECT * FROM Products WHERE mfr_part_no='%s' LIMIT 1; ", $kItem);
                        $res_product = $vtod->query($query_product);

                        if (count($res_product) <= 0) {
                            #create product
                            $arr_prod = [];
                            try {
                                $arr_prod['cf_products_mfgr'] = '3x457762';
                                $arr_prod['productcode'] = $kItem;
                                if ($vItem['description'] == '') {
                                    $arr_prod['productname'] = urlencode('TBD - Product Created on ' . date('Y-m-d'));
                                } else {
                                    $arr_prod['productname'] = $desc;
                                }
                                $arr_prod['purchase_cost'] = $vItem['purchase_cost'];
                                $arr_prod['mfr_part_no'] = $vItem['cf_quotes_partnumber'];
                                $arr_prod['cf_products_vendor'] = '3x457762';
                                $arr_prod['vendor_id'] = $quote['account_id'];
                                $arr_prod['assigned_user_id'] = '19x1';


                                $data_prod = $vtod->create('Products', $arr_prod);
                                if ($data_prod['id']) {
                                    $product_id = $data_prod['id'];
                                }
                            } catch (Exception $e) {
                            }
                        } else {
                            $product_id = $res_product[0]['id'];
                        }
                        if ($product_id) {
                            //                            if($vItem['mrf_list_price_col']) {
                            //                                #task_id: 46425 - update MFR List Price in Product
                            //                                $productRecord = $vtod->retrieve($product_id, true);
                            //                                $productRecord['unit_price'] =  $vItem['mrf_list_price_col'];
                            //                                $vtod->update($productRecord);
                            //                                sleep(1);
                            //                            }
                            $arr_Product[$kItem] = $product_id;
                            $lineItem['productid'] = $product_id;
                            $lineItem['cf_quotes_partnumber'] = $kItem;
                            $lineItem['cf_quotes_mfrpartno'] = $kItem;
                            //                            $lineItem['cf_quotes_mfrlistprice'] = $vItem['mrf_list_price_col'];
                            $lineItem['cf_quotes_manufacturer'] = (string)str_replace('&', '%26', $vItem['cf_quotes_manufacturer']);
                            $lineItem['cf_quotes_vendorpartnumber'] = $vItem['cf_quotes_vendorpartnumber'];
                            $lineItem['cf_quotes_leadtime'] = $vItem['lead_time'];
                            $lineItem['section_no'] = '2';
                            $lineItem['section_name'] = 'Materials';
                            $lineItem['quantity'] = (int)$vItem['quantity'];
                            $lineItem['purchase_cost'] = (float)$vItem['purchase_cost'] * (int)$vItem['quantity'];
                            $lineItem['listprice'] = (float)$vItem['purchase_cost'] * 100 / (100 - $vItem['margin_percentage']);
                            $lineItem['netprice'] = (float)$lineItem['listprice'] * (int)$vItem['quantity'];

                            array_push($currentLineItem, $lineItem);
                        }
                    }
                }
            }

            //task_id=46423
            if (count($array_mfr_part_match) < count($arr_new_Item)) {
                foreach ($arr_new_Item as $key => $item) {
                    if (in_array($key, $array_mfr_part_match)) {
                        continue;
                    }
                    $newPrice = floatval($item['purchase_cost']) ;
                    $vendor_id = $quote['vendor'];
                    if ($arr_Product[$key]) {
                        $array_mfr_part_match_items[] = [ 'item' => $item, 'quote_id' => $quote_id, 'productid' => $arr_Product[$key], 'oldprice' => 0, 'newPrice' => $newPrice, 'vendor' => $vendor_id, 'uid' => $from_uid ];
                    } else {
                        $array_mfr_part_match_items[] = ['item' => $item, 'quote_id' => $quote_id, 'productid' => null, 'oldprice' => 0, 'newPrice' => $newPrice, 'vendor' => $vendor_id, 'uid' => $from_uid];
                    }
                }
            }

            $arr_quotes['LineItems'] = $currentLineItem;
        }
    }
    $arr_quotes['cf_quotes_contractmodification'] = 'No';

    return ['quote' => $arr_quotes, 'audit' => $array_mfr_part_match_items];
}

function create_vtcmauditrecords($lineItem, $quote_id, $product_id, $oldPrice, $newPrice, $vendor_id, $from_uid)
{
    global $vtod,$from_uid;
    $assigned_user_id = ($from_uid) ? $from_uid : $vtod->userId;

    $relatedType = 'vtcmauditrecords';

    $map_projects['assigned_user_id'] = null;
    $map_projects['created_user_id'] = null;

    $map_projects['createdtime'] =  null;
    $map_projects['modifiedtime'] =  null;
    $map_projects['id'] =  null;
    $map_projects['label'] =  null;
    $map_projects['modifiedby'] =  null;
    $map_projects['cf_vtcmauditrecords_product'] = $product_id;
    $map_projects['cf_vtcmauditrecords_quote'] = $quote_id;

    $fld_vtcmauditrecordsname = isset($lineItem['cf_quotes_partnumber']) ? $lineItem['cf_quotes_partnumber'] : $lineItem[0];

    $map_projects['fld_vtcmauditrecordsname'] = $fld_vtcmauditrecordsname;


    //$map_projects["cf_vtcmauditrecords_vendorname"] = $vendor_id;
    //$vendor_id
    $map_projects['cf_vtcmauditrecords_vendor'] = $vendor_id;

    $map_projects['fld_beforevalue'] = ($oldPrice > 0) ? $oldPrice : '0';
    $map_projects['fld_aftervalue'] = $newPrice;

    $map_projects['cf_vtcmauditrecords_beforevalue'] = ($oldPrice > 0) ? $oldPrice : '0';
    $map_projects['cf_vtcmauditrecords_aftervalue'] = $newPrice;

    $map_projects['assigned_user_id'] = $assigned_user_id;
    $map_projects['created_user_id'] = $assigned_user_id;
    $record = [];
    //$field_ignore = array_keys($map_projects);

    foreach ($map_projects as $field => $relfield) {
        if (is_null($relfield)) {
            continue;
        }
        $record[$field] = $relfield;
    }

    $data = $vtod->create($relatedType, $record, false, 300);

    return $data;

}

function create_document($data, $file_path = null)
{
    global $vtod;
    $moduleName = 'Documents';
    $dataJson = json_encode($data);
    $sessionId = $vtod->sessionId;
    $ws_url = $vtod->url;
    $params = [
        'sessionName' => $sessionId,
        'operation' => 'create',
        'element' => $dataJson,
        'elementType' => $moduleName,
    ];
    if ($file_path && file_exists($file_path)) {
        $file_name = basename($file_path);
        $file_type = mime_content_type($file_path);
        $params['filename'] = curl_file_create($file_path, $file_type, $file_name);
    }
    $response = $vtod->parse(dhrest::post($ws_url, $params, false, 360));
    if (!$response || !is_array($response)) {
        return false;
    }
    $id = isset($response['id']) ? $response['id'] : false;
    return $id;
}

function create_linked_document($document_name, $file_path, $related_id)
{
    global $vtod;
    $userId = $vtod->userId;
    $documentData = [
        'notes_title' => $document_name,
        'assigned_user_id' => $userId,
        'notecontent' => '',
        'filelocationtype' => 'I',
        'filestatus' => 1,
    ];
    $documentId = create_document($documentData, $file_path);
    if (!$documentId) {
        return false;
    }
    if ($related_id) {
        $vtod->addRelated($related_id, $documentId);
    }
    return $documentId;
}

function create_quote_document($wsQuoteId, $quote_subject, $file_upload_path = '')
{
    $document_name = $quote_subject . ' ' . date('Ymd');
    return create_linked_document($document_name, $file_upload_path, $wsQuoteId);
}

function get_upload_file_path($filename = '')
{
    $upload_dir = dirname(__FILE__) . '/uploads/';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }
    if (!$filename) {
        return $upload_dir;
    }
    return $upload_dir. $filename;
}

function get_upload_file_url($file_name)
{
    global $link_vtod;
    return $link_vtod . 'uploads/' . $file_name;
}

function get_upload_file_path_sub_folder($filename = '', $sub_folder)
{
    $upload_dir = dirname(__FILE__) . '/uploads/'.$sub_folder.'/';
    if (!is_dir($upload_dir)) {
        @mkdir($upload_dir, 0777, true);
    }
    if (!$filename) {
        return $upload_dir;
    }
    return $upload_dir. $filename;
}

function get_upload_file_url_sub_folder($file_name, $sub_folder)
{
    global $link_vtod;
    return $link_vtod . 'uploads/' .$sub_folder.'/'. $file_name;
}

function get_ws_record_id($record_id, $module)
{
    $prefix = [
        'Quotes' => '13x',
        'Documents' => '15x',
        'Users' => '19x',
        'Project' => '31x',
        'ProjectTask' => '30x',
        'ProjectMilestone' => '29x',
        'vtcmprojectphases' => '88x',
        'vtcmlaborresources' => '86x',
        'PurchaseOrder' => '14x',
    ];
    $wsModuleId = isset($prefix[$module]) ? $prefix[$module] : '';
    return $wsModuleId . $record_id;
}

function get_record_id($wsId)
{
    $split = explode('x', $wsId);
    return isset($split[1]) ? $split[1] : $split[0];
}

function get_array_value($array, $key, $def = '')
{
    return isset($array[$key]) ? $array[$key] : $def;
}

function response_json($response)
{
    echo json_encode($response);
    exit;
}

function create_excel_file($file_path, $excelData)
{
    require_once dirname(__FILE__) . '/lib/PHPExcel/PHPExcel.php';
    $objPHPExcel = new PHPExcel();
    foreach ($excelData as $tab => $sheetData) {
        if ($tab > 0) {
            $objPHPExcel->createSheet();
        }
        $objPHPExcel->setActiveSheetIndex($tab);
        $objPHPExcel->getActiveSheet()->setTitle($sheetData['title']);

        $position = 1;
        foreach ($sheetData['data'] as $row) {
            $column = 'A';
            foreach ($row as $item) {
                $objPHPExcel->getActiveSheet()->SetCellValue($column . $position, $item);
                $column++;
            }
            $position++;
        }
        foreach (range('A', $objPHPExcel->getActiveSheet()->getHighestDataColumn()) as $col) {
            $objPHPExcel->getActiveSheet()
                ->getColumnDimension($col)
                ->setAutoSize(true);
        }
    }
    $objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
    $objWriter->save($file_path);
    return true;
}

function retrieve_all_related($wsId, $relatedModuleName)
{
    global $vtod;
    $relatedLabel = get_module_label($relatedModuleName);
    $relatedLabel = rawurlencode($relatedLabel);
    return $vtod->retrieveAllRelated($wsId, $relatedLabel, $relatedModuleName);
}

function get_module_label($moduleName)
{
    $translates = [
        'ProjectTask' => 'Project Tasks',
        'ProjectMilestone' => 'Project Milestones',
        'vtcmlaborresources' => 'Labor Resources',
        'Calendar' => 'Activities',
    ];
    return isset($translates[$moduleName]) ? $translates[$moduleName] : $moduleName;
}

function sendMail($from, $reply_to, $to_email, $subject, $body, $file_path = '', $file_name = '')
{
    global $mail_config;

    if (empty($from)) {
        $from = $mail_config['mail_from'];
    }

    require_once 'Emails/class.phpmailer.php';
    $mail = new PHPMailer();
    $mail->ClearAddresses();
    $mail->ClearAttachments();
    $mail->Subject = $subject;

    $mail->Body    = $body;
    $mail->IsSMTP();

    $mail->Host = $mail_config['mail_server'];
    $mail->SMTPAuth = true;
    $mail->Username = $mail_config['mail_server_username'];
    $mail->Password = $mail_config['mail_server_password'];

    $mail->From = $from;
    $mail->FromName = $from;
    $mail->AddReplyTo($reply_to);

    // to email
    if (is_array($to_email)) {
        foreach ($to_email as $key => $email) {
            if ($key == 0) {
                $mail->AddAddress($email);
            } else {
                $mail->addCC($email);
            }
        }
    }
    // attachment
    if ($file_path) {
        if ($file_name) {
            $mail->addAttachment($file_path, $file_name);
        } else {
            $mail->addAttachment($file_path);
        }
    }

    $mail->WordWrap = 50;
    $mail->Port = 465;
    $mail->IsHTML(true);
    $mail->AltBody = 'This is the body when user views in plain text format';


    //echo "<pre>"; print_r($mail); die;

    if ($mail->Send()) {
        return true;
    } else {
        return 'Mailer Error: ' . $mail->ErrorInfo;
    }
}

function allModuleEmail()
{
    return ['Contacts','Accounts'];
}

function getVendors($vtod)
{
    $limit = 200;
    // count total vendors
    $query     = 'SELECT COUNT(*) FROM Vendors;';
    $rs        = $vtod->query($query);

    if (is_array($rs) && (isset($rs[0]['count']) && $rs[0]['count'] > 0)) {
        $count = intval($rs[0]['count']);
    }
    if ($count <= $limit) {
        $page = 1;
    } else {
        $page = ceil($count / $limit);
    }

    // get all vendors
    $arr_vendors = [];
    for ($i = 0;$i < $page; $i++) {
        $offset = $i * $limit;
        $query_vendor  = sprintf("SELECT * FROM Vendors LIMIT $offset,$limit;");
        $res_vendor = $vtod->query($query_vendor);

        if (count($res_vendor) > 0) {
            foreach ($res_vendor as $key => $vendor) {
                $arr_vendors[$vendor['id']] = $vendor['vendorname'];
            }
        }
    }
    return $arr_vendors;
}

function getVendorsForProductBundle($vtod)
{
    $limit = 200;
    // count total vendors
    $query     = 'SELECT COUNT(*) FROM Vendors;';
    $rs        = $vtod->query($query);

    if (is_array($rs) && (isset($rs[0]['count']) && $rs[0]['count'] > 0)) {
        $count = intval($rs[0]['count']);
    }
    if ($count <= $limit) {
        $page = 1;
    } else {
        $page = ceil($count / $limit);
    }

    // get all vendors
    $arr_vendors = [];
    for ($i = 0;$i < $page; $i++) {
        $offset = $i * $limit;
        $query_vendor  = sprintf("SELECT * FROM Vendors LIMIT $offset,$limit;");
        $res_vendor = $vtod->query($query_vendor);

        if (count($res_vendor) > 0) {
            foreach ($res_vendor as $key => $vendor) {
                if (strpos($vendor['cf_vendors_suppliertype'], 'Subcontract Labor') === false) {
                    $arr_vendors[$vendor['id']] = $vendor['vendorname'];
                }
            }
        }
    }
    return $arr_vendors;
}

function getProductsByDesc($vtod, $mfr_part_no)
{
    $limit = 200;
    // count total products
    $query     = "SELECT COUNT(*) FROM Products WHERE productname LIKE '".$mfr_part_no."%'; ";
    $rs        = $vtod->query($query);

    if (is_array($rs) && (isset($rs[0]['count']) && $rs[0]['count'] > 0)) {
        $count = intval($rs[0]['count']);
    }
    if ($count <= $limit) {
        $page = 1;
    } else {
        $page = ceil($count / $limit);
    }

    // get all products
    $arr_products = [];
    for ($i = 0;$i < $page; $i++) {
        $offset = $i * $limit;
        $query_products  = "SELECT * FROM Products  WHERE productname LIKE '".$mfr_part_no."%' LIMIT $offset,$limit;";
        $res_products = $vtod->query($query_products);

        if (count($res_products) > 0) {
            foreach ($res_products as $key => $product) {
                //$arr_products[] = $product['productname'];
                $arr_products[] = [
                    'label' => $product['productname'],
                    'value' => $product['id'],
                    'mfr_part_no' => $product['mfr_part_no'],
                ];
            }
        }
    }

    return ['arr_products' => $arr_products];
}

function groupSystemNameLineItems($quoteLineItem)
{
    $holder_material_group = '';
    foreach ($quoteLineItem as $k => $lineItem) {
        if ($lineItem['section_name'] == 'Materials') {
            $cf_quotes_systemname = $lineItem['cf_quotes_systemname'];
            if ($cf_quotes_systemname == $holder_material_group) {
                $quoteLineItem[$k]['cf_quotes_processgroup'] = '1';
                $holder_material_group  = $cf_quotes_systemname;
            } else {
                $quoteLineItem[$k]['cf_quotes_processgroup'] =  '';
            }

            if ($cf_quotes_systemname != '') {
                $holder_material_group = $cf_quotes_systemname;
            }
        }
    }
    return $quoteLineItem;
}
function calculatePriceExcludeMaintenance($lineItems)
{
    $total = 0;
    $maintain_total = 0;
    $section_names = ['Labor', 'Materials', 'Travel'];
    $maintain_section_names = ['Maintenance Labor', 'Maintenance Travel'];
    foreach ($lineItems as $key => $value) {
        if (in_array($value['section_name'], $section_names)) {
            $total = $total + (float)$value['listprice'] * (int)$value['quantity'];
        } elseif (in_array($value['section_name'], $maintain_section_names)) {
            $maintain_total = $maintain_total + (float)$value['listprice'] * (int)$value['quantity'];
        }
    }
    return [
        'total' => $total,
        'maintain_total' => $maintain_total,
    ];
}

function getVendorsExcludeSubcontract($vtod, $allDetail = false)
{
    $limit = 200;
    // count total vendors
    $query     = 'SELECT COUNT(*) FROM Vendors;';
    $rs        = $vtod->query($query);

    if (is_array($rs) && (isset($rs[0]['count']) && $rs[0]['count'] > 0)) {
        $count = intval($rs[0]['count']);
    }
    if ($count <= $limit) {
        $page = 1;
    } else {
        $page = ceil($count / $limit);
    }

    // get all vendors
    $arr_vendors = [];
    for ($i = 0;$i < $page; $i++) {
        sleep(1);
        $offset = $i * $limit;
        $query_vendor  = sprintf("SELECT * FROM Vendors LIMIT $offset,$limit;");
        $res_vendor = $vtod->query($query_vendor);

        if (count($res_vendor) > 0) {
            foreach ($res_vendor as $key => $vendor) {
                if (strpos($vendor['cf_vendors_suppliertype'], 'Subcontract Labor') === false) {
                    if ($allDetail) {
                        $arr_vendors[$vendor['id']] = $vendor;
                    } else {
                        $arr_vendors[$vendor['id']] = $vendor['vendorname'];
                    }
                }
            }
        }
    }
    return $arr_vendors;
}

function getSubcontractorVendors($vtod, $allDetail = false)
{
    $limit = 200;
    // count total
    $query     = 'SELECT COUNT(*) FROM Vendors;';
    $rs        = $vtod->query($query);

    if (is_array($rs) && (isset($rs[0]['count']) && $rs[0]['count'] > 0)) {
        $count = intval($rs[0]['count']);
    }
    if ($count <= $limit) {
        $page = 1;
    } else {
        $page = ceil($count / $limit);
    }

    // get all
    $arr_return = [];
    for ($i = 0;$i < $page; $i++) {
        $offset = $i * $limit;
        $query  = sprintf("SELECT * FROM Vendors LIMIT $offset,$limit;");
        $result = $vtod->query($query);

        if (count($result) > 0) {
            foreach ($result as $key => $value) {
                if ($value['cf_vendors_vendorstatus'] == 'Approved'
                    && (strpos($value['cf_vendors_suppliertype'], 'Subcontract Labor') !== false
                        || strpos($value['cf_vendors_suppliertype'], 'Independent Contractor') !== false)) {
                    if ($allDetail) {
                        $arr_return[$value['id']] = $value;
                    } else {
                        $arr_return[$value['id']] = $value['vendorname'];
                    }
                }

            }
        }
    }
    return $arr_return;
}

function checkDeletedItems($quote_obj)
{
    // check retrieve lineItems empty ProductId or NOT
    $check_empty_product_id = false;
    foreach ($quote_obj['LineItems_FinalDetails'] as $key => $quote_item) {
        if ($quote_item['productDeleted'.$key]) {
            $check_empty_product_id = true;
        }
    }
    //./END check retrieve lineItems empty ProductId or NOT
    return $check_empty_product_id;
}

/**
 * TaskId - 54333
 * Author - rprajapati
 *
 * @param $date
 * @return false|string
 */
function formatedatetime($fulldate)
{
    $fulldate = convertTOSydney($fulldate);

    $date = date('l', strtotime($fulldate));
    $date .= ', '.date('d F Y h:iA', strtotime($fulldate));

    return $date;
}


function convertTOSydney($fulldate)
{
    $date = new DateTime($fulldate, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Australia/Sydney'));
    $fulldate = $date->format('Y-m-d H:i:s');

    return $fulldate;
}

/**
 * TaskId - 54333
 * Author - rprajapati
 *
 * @param $time
 * @return false|string
 */
function timetoampm($time)
{
    return date('h:iA', strtotime($time));
}

function country_code($name)
{
    $country_list = ['Afghanistan' => 'AF',
'Albania' => 'AL',
'Algeria' => 'DZ',
'American Samoa' => 'AS',
'Andorra' => 'AD',
'Angola' => 'AO',
'Anguilla' => 'AI',
'Antarctica' => 'AQ',
'Antigua and Barbuda' => 'AG',
'Argentina' => 'AR',
'Armenia' => 'AM',
'Aruba' => 'AW',
'Australia' => 'AU',
'Austria' => 'AT',
'Azerbaijan' => 'AZ',
'Bahamas (the)' => 'BS',
'Bahrain' => 'BH',
'Bangladesh' => 'BD',
'Barbados' => 'BB',
'Belarus' => 'BY',
'Belgium' => 'BE',
'Belize' => 'BZ',
'Benin' => 'BJ',
'Bermuda' => 'BM',
'Bhutan' => 'BT',
'Bolivia (Plurinational State of)' => 'BO',
'Bonaire, Sint Eustatius and Saba' => 'BQ',
'Bosnia and Herzegovina' => 'BA',
'Botswana' => 'BW',
'Bouvet Island' => 'BV',
'Brazil' => 'BR',
'British Indian Ocean Territory (the)' => 'IO',
'Brunei Darussalam' => 'BN',
'Bulgaria' => 'BG',
'Burkina Faso' => 'BF',
'Burundi' => 'BI',
'Cabo Verde' => 'CV',
'Cambodia' => 'KH',
'Cameroon' => 'CM',
'Canada' => 'CA',
'Cayman Islands (the)' => 'KY',
'Central African Republic (the)' => 'CF',
'Chad' => 'TD',
'Chile' => 'CL',
'China' => 'CN',
'Christmas Island' => 'CX',
'Cocos (Keeling) Islands (the)' => 'CC',
'Colombia' => 'CO',
'Comoros (the)' => 'KM',
'Congo (the Democratic Republic of the)' => 'CD',
'Congo (the)' => 'CG',
'Cook Islands (the)' => 'CK',
'Costa Rica' => 'CR',
'Croatia' => 'HR',
'Cuba' => 'CU',
'Curaçao' => 'CW',
'Cyprus' => 'CY',
'Czechia' => 'CZ',
"Côte d'Ivoire" => 'CI',
'Denmark' => 'DK',
'Djibouti' => 'DJ',
'Dominica' => 'DM',
'Dominican Republic (the)' => 'DO',
'Ecuador' => 'EC',
'Egypt' => 'EG',
'El Salvador' => 'SV',
'Equatorial Guinea' => 'GQ',
'Eritrea' => 'ER',
'Estonia' => 'EE',
'Eswatini' => 'SZ',
'Ethiopia' => 'ET',
'Falkland Islands (the) [Malvinas]' => 'FK',
'Faroe Islands (the)' => 'FO',
'Fiji' => 'FJ',
'Finland' => 'FI',
'France' => 'FR',
'French Guiana' => 'GF',
'French Polynesia' => 'PF',
'French Southern Territories (the)' => 'TF',
'Gabon' => 'GA',
'Gambia (the)' => 'GM',
'Georgia' => 'GE',
'Germany' => 'DE',
'Ghana' => 'GH',
'Gibraltar' => 'GI',
'Greece' => 'GR',
'Greenland' => 'GL',
'Grenada' => 'GD',
'Guadeloupe' => 'GP',
'Guam' => 'GU',
'Guatemala' => 'GT',
'Guernsey' => 'GG',
'Guinea' => 'GN',
'Guinea-Bissau' => 'GW',
'Guyana' => 'GY',
'Haiti' => 'HT',
'Heard Island and McDonald Islands' => 'HM',
'Holy See (the)' => 'VA',
'Honduras' => 'HN',
'Hong Kong' => 'HK',
'Hungary' => 'HU',
'Iceland' => 'IS',
'India' => 'IN',
'Indonesia' => 'ID',
'Iran (Islamic Republic of)' => 'IR',
'Iraq' => 'IQ',
'Ireland' => 'IE',
'Isle of Man' => 'IM',
'Israel' => 'IL',
'Italy' => 'IT',
'Jamaica' => 'JM',
'Japan' => 'JP',
'Jersey' => 'JE',
'Jordan' => 'JO',
'Kazakhstan' => 'KZ',
'Kenya' => 'KE',
'Kiribati' => 'KI',
"Korea (the Democratic People's Republic of)" => 'KP',
'Korea (the Republic of)' => 'KR',
'Kuwait' => 'KW',
'Kyrgyzstan' => 'KG',
"Lao People's Democratic Republic (the)" => 'LA',
'Latvia' => 'LV',
'Lebanon' => 'LB',
'Lesotho' => 'LS',
'Liberia' => 'LR',
'Libya' => 'LY',
'Liechtenstein' => 'LI',
'Lithuania' => 'LT',
'Luxembourg' => 'LU',
'Macao' => 'MO',
'Madagascar' => 'MG',
'Malawi' => 'MW',
'Malaysia' => 'MY',
'Maldives' => 'MV',
'Mali' => 'ML',
'Malta' => 'MT',
'Marshall Islands (the)' => 'MH',
'Martinique' => 'MQ',
'Mauritania' => 'MR',
'Mauritius' => 'MU',
'Mayotte' => 'YT',
'Mexico' => 'MX',
'Micronesia (Federated States of)' => 'FM',
'Moldova (the Republic of)' => 'MD',
'Monaco' => 'MC',
'Mongolia' => 'MN',
'Montenegro' => 'ME',
'Montserrat' => 'MS',
'Morocco' => 'MA',
'Mozambique' => 'MZ',
'Myanmar' => 'MM',
'Namibia' => 'NA',
'Nauru' => 'NR',
'Nepal' => 'NP',
'Netherlands (the)' => 'NL',
'New Caledonia' => 'NC',
'New Zealand' => 'NZ',
'Nicaragua' => 'NI',
'Niger (the)' => 'NE',
'Nigeria' => 'NG',
'Niue' => 'NU',
'Norfolk Island' => 'NF',
'Northern Mariana Islands (the)' => 'MP',
'Norway' => 'NO',
'Oman' => 'OM',
'Pakistan' => 'PK',
'Palau' => 'PW',
'Palestine, State of' => 'PS',
'Panama' => 'PA',
'Papua New Guinea' => 'PG',
'Paraguay' => 'PY',
'Peru' => 'PE',
'Philippines (the)' => 'PH',
'Pitcairn' => 'PN',
'Poland' => 'PL',
'Portugal' => 'PT',
'Puerto Rico' => 'PR',
'Qatar' => 'QA',
'Republic of North Macedonia' => 'MK',
'Romania' => 'RO',
'Russian Federation (the)' => 'RU',
'Rwanda' => 'RW',
'Réunion' => 'RE',
'Saint Barthélemy' => 'BL',
'Saint Helena, Ascension and Tristan da Cunha' => 'SH',
'Saint Kitts and Nevis' => 'KN',
'Saint Lucia' => 'LC',
'Saint Martin (French part)' => 'MF',
'Saint Pierre and Miquelon' => 'PM',
'Saint Vincent and the Grenadines' => 'VC',
'Samoa' => 'WS',
'San Marino' => 'SM',
'Sao Tome and Principe' => 'ST',
'Saudi Arabia' => 'SA',
'Senegal' => 'SN',
'Serbia' => 'RS',
'Seychelles' => 'SC',
'Sierra Leone' => 'SL',
'Singapore' => 'SG',
'Sint Maarten (Dutch part)' => 'SX',
'Slovakia' => 'SK',
'Slovenia' => 'SI',
'Solomon Islands' => 'SB',
'Somalia' => 'SO',
'South Africa' => 'ZA',
'South Georgia and the South Sandwich Islands' => 'GS',
'South Sudan' => 'SS',
'Spain' => 'ES',
'Sri Lanka' => 'LK',
'Sudan (the)' => 'SD',
'Suriname' => 'SR',
'Svalbard and Jan Mayen' => 'SJ',
'Sweden' => 'SE',
'Switzerland' => 'CH',
'Syrian Arab Republic' => 'SY',
'Taiwan (Province of China)' => 'TW',
'Tajikistan' => 'TJ',
'Tanzania, United Republic of' => 'TZ',
'Thailand' => 'TH',
'Timor-Leste' => 'TL',
'Togo' => 'TG',
'Tokelau' => 'TK',
'Tonga' => 'TO',
'Trinidad and Tobago' => 'TT',
'Tunisia' => 'TN',
'Turkey' => 'TR',
'Turkmenistan' => 'TM',
'Turks and Caicos Islands (the)' => 'TC',
'Tuvalu' => 'TV',
'Uganda' => 'UG',
'Ukraine' => 'UA',
'United Arab Emirates (the)' => 'AE',
'United Kingdom of Great Britain and Northern Ireland (the)' => 'GB',
'United States Minor Outlying Islands (the)' => 'UM',
'United States of America (the)' => 'US',
'Uruguay' => 'UY',
'Uzbekistan' => 'UZ',
'Vanuatu' => 'VU',
'Venezuela (Bolivarian Republic of)' => 'VE',
'Viet Nam' => 'VN',
'Virgin Islands (British)' => 'VG',
'Virgin Islands (U.S.)' => 'VI',
'Wallis and Futuna' => 'WF',
'Western Sahara' => 'EH',
'Yemen' => 'YE',
'Zambia' => 'ZM',
'Zimbabwe' => 'ZW',
'Åland Islands' => 'AX']    ;
    return $country_list[$name];
}

function get_statename($lookup)
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


    return $states[$lookup];
}
