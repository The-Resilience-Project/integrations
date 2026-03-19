<?php

trait OrderResources
{
    protected $selected_year_levels = [];
    protected $engage_hub_courses = [];
    protected $student_inspire_hub_courses = [];
    protected $staff_inspire_hub_courses = [];
    protected $extend_hub_courses = [];

    protected const student_resource_keys = [
        ['foundation_qty', 'PRO18', 'Foundation'],
        ['year1_qty', 'PRO19', 'Year 1'],
        ['year2_qty', 'PRO20', 'Year 2'],
        ['year3_qty', 'PRO21', 'Year 3'],
        ['year4_qty', 'PRO22', 'Year 4'],
        ['year5_qty', 'PRO23', 'Year 5'],
        ['year6_qty', 'PRO24', 'Year 6'],
        ['year7_qty', 'PRO25', 'Year 7'],
        ['year8_qty', 'PRO26', 'Year 8'],
        ['year9_qty', 'PRO27', 'Year 9'],
        ['year10_qty', 'PRO28', 'Year 10'],
        ['year11_qty', 'PRO29', 'Year 11'],
        ['year12_qty', 'PRO30', 'Year 12'],
    ];

    protected const teacher_resource_keys = [
        ['tr_foundation_qty', 'PRO31'],
        ['tr_year1_qty', 'PRO32'],
        ['tr_year2_qty', 'PRO33'],
        ['tr_year3_qty', 'PRO34'],
        ['tr_year4_qty', 'PRO35'],
        ['tr_year5_qty', 'PRO36'],
        ['tr_year6_qty', 'PRO37'],
        ['tr_year7_qty', 'PRO38'],
        ['tr_year8_qty', 'PRO39'],
        ['tr_year9_qty', 'PRO40'],
        ['tr_year10_qty', 'PRO41'],
        ['tr_year11_qty', 'PRO42'],
        ['tr_year12_qty', 'PRO43'],
    ];

    protected const extra_resource_keys = [
        ['fence_sign_qty', 'PRO52'],
        ['reading_log_qty', 'PRO47'],
        ['gem_card_qty', 'PRO48'],
        ['primary_planner_qty', 'PRO49'],
        ['journal_21_qty', 'PRO50'],
        ['journal_6_qty', 'PRO51'],
        // no teacher planner as we will deal w it sep due to variation
    ];

    protected const teacher_planner_codes = [
        '7 Period Week to a View' => 'PRO59',
        '7 Period Day to a Page' => 'PRO55',
        '6 Period Week to a View' => 'PRO58',
        '6 Period Day to a Page' => 'PRO54',
        '5 Period Week to a View' => 'PRO57',
        '5 Period Day to a Page' => 'PRO53',
        '4 Period Week to a View' => 'PRO56',
        '4 Period Day to a Page' => 'PRO46',
    ];

    protected const quote_service_code_map = [
        '25x33805' => 'SER12', // engage
        '25x95208' => 'SER81', //engage journals discounted
        '25x94901' => 'SER65', // planners
        '25x33811' => 'SER18', // Hugh
        '25x33812' => 'SER19', // Hugh
        '25x33814' => 'SER21', // Martin
        '25x33816' => 'SER23', // TWP
        '25x33817' => 'SER24', // TWB 1 (workshop)
        '25x33818' => 'SER25', // TWB 2 (workshop)
        '25x33819' => 'SER26', // TWB 1 (webinar)
        '25x33820' => 'SER27', // TWB 2 (webinar)
        '25x33822' => 'SER29', // Inspire Hugh
        '25x33823' => 'SER30', // BRH (webinar)
        '25x33825' => 'SER32', // Connected Parenting (webinar)
        '25x110355' => 'SER104', // BRH (workshop)
        '25x182343' => 'SER117', // TWB 3 (webinar)
        '25x182379' => 'SER118', // TWB 3 (workshop)
        '25x182380' => 'SER119', // DWF (workshop)
        '25x182381' => 'SER120', // DWF (webinar)
        '25x184260' => 'SER123', // CP (webinar)
        '25x184261' => 'SER124', // BRH (comp)
        '25x184262' => 'SER125', // AC staff hugh (comp)
        '25x184263' => 'SER126', // AC staff hugh (comp)
        '25x184264' => 'SER127', // AC parent hugh (comp)
        '25x184265' => 'SER128', // AC parent martin (comp)
        '25x184277' => 'SER129', // AC parent hugh (webinar)
        '25x184278' => 'SER130', // AC parent martin (webinar)
        '25x184281' => 'SER131', // AC staff Hugh (webinar)
        '25x184282' => 'SER132', // Ac staff martin (webinar)
        '25x335331' => 'SER144', // Inspire hugh
        '25x335332' => 'SER145', // inspire martin
        '25x375583' => 'SER146', // Inspire 3/4
        '25x375586' => 'SER147', // inspire 3/4
        '25x375587' => 'SER148', // inspire 3/4
        '25x375588' => 'SER149', // inspire 3/4
        '25x95208' => 'SER81', // funded engage
        '25x198435' => 'SER136', // funded inspire
        '25x33810' => 'SER17', // live presentations with martin
        '25x168341' => 'SER115', // Inspire Existing School Martin
        '25x33815' => 'SER22', //Presentation Travel Costs
        '25x94903' => 'SER67', // Inspire: Digital Presentations with Hugh and Martin
        '25x33808' => 'SER15', // GEM inspire


    ];

    protected const inspire_hub_staff_parent_course_codes = [
        'Martin Digital' => ['Staff Inspire Martin', 'Parent Inspire Martin'],
        'Hugh Digital' => ['Staff Inspire Hugh', 'Parent Inspire Hugh'],
        'Inspire 3' => ['Staff Inspire 3.0 Hugh and Martin', 'Parent Inspire 3.0 Hugh and Martin'],
        'Inspire 4' => ['Staff Inspire 4.0', 'Parent Inspire 4.0'],
        'Engage Only' => ['Engage Parent Pack'],
    ];

    protected const inspire_hub_year_level_course_codes = [
        'Martin Digital' => [
            'Foundation' => 'Inspire Student F-2 Martin',
            'Year 1' => 'Inspire Student F-2 Martin',
            'Year 2' => 'Inspire Student F-2 Martin',
            'Year 3' => 'Inspire Student 3-6 Martin',
            'Year 4' => 'Inspire Student 3-6 Martin',
            'Year 5' => 'Inspire Student 3-6 Martin',
            'Year 6' => 'Inspire Student 3-6 Martin',
            'Year 7' => 'Inspire Student 7-9 Martin',
            'Year 8' => 'Inspire Student 7-9 Martin',
            'Year 9' => 'Inspire Student 7-9 Martin',
            'Year 10' => 'Inspire Student 10-12 Martin',
            'Year 11' => 'Inspire Student 10-12 Martin',
            'Year 12' => 'Inspire Student 10-12 Martin',
        ],
        'Hugh Digital' => [
            'Foundation' => 'Inspire Student F-2 Hugh',
            'Year 1' => 'Inspire Student F-2 Hugh',
            'Year 2' => 'Inspire Student F-2 Hugh',
            'Year 3' => 'Inspire Student 3-6 Hugh',
            'Year 4' => 'Inspire Student 3-6 Hugh',
            'Year 5' => 'Inspire Student 3-6 Hugh',
            'Year 6' => 'Inspire Student 3-6 Hugh',
            'Year 7' => 'Inspire Student 7-9 Hugh',
            'Year 8' => 'Inspire Student 7-9 Hugh',
            'Year 9' => 'Inspire Student 7-9 Hugh',
            'Year 10' => 'Inspire Student 10-12 Hugh',
            'Year 11' => 'Inspire Student 10-12 Hugh',
            'Year 12' => 'Inspire Student 10-12 Hugh',
        ],
        'Inspire 3' => [
            'Foundation' => 'Inspire Student Year F',
            'Year 1' => 'Inspire Student Year 1',
            'Year 2' => 'Inspire Student Year 2',
            'Year 3' => 'Inspire Student Year 3',
            'Year 4' => 'Inspire Student Year 4',
            'Year 5' => 'Inspire Student Year 5',
            'Year 6' => 'Inspire Student Year 6',
            'Year 7' => 'Inspire Student Year 7',
            'Year 8' => 'Inspire Student Year 8',
            'Year 9' => 'Inspire Student Year 9',
            'Year 10' => 'Inspire Student Year 10',
            'Year 11' => 'Inspire Student Year 11',
            'Year 12' => 'Inspire Student Year 12',
        ],
        'Inspire 4' => [
            'Foundation' => 'Inspire Student Year F',
            'Year 1' => 'Inspire Student Year 1',
            'Year 2' => 'Inspire Student Year 2',
            'Year 3' => 'Inspire Student Year 3',
            'Year 4' => 'Inspire Student Year 4',
            'Year 5' => 'Inspire Student Year 5',
            'Year 6' => 'Inspire Student Year 6',
            'Year 7' => 'Inspire Student Year 7',
            'Year 8' => 'Inspire Student Year 8',
            'Year 9' => 'Inspire Student Year 9',
            'Year 10' => 'Inspire Student Year 10',
            'Year 11' => 'Inspire Student Year 11',
            'Year 12' => 'Inspire Student Year 12',
        ],
    ];

    protected const hub_year_level_course_codes = [
        'Foundation' => '2025 Foundation',
        'Year 1' => '2025 Year 1',
        'Year 2' => '2025 Year 2',
        'Year 3' => '2025 Year 3',
        'Year 4' => '2025 Year 4',
        'Year 5' => '2025 Year 5',
        'Year 6' => '2025 Year 6',
        'Year 7' => '2025 Year 7',
        'Year 8' => '2025 Year 8',
        'Year 9' => '2025 Year 9',
        'Year 10' => '2025 Year 10',
        'Year 11' => '2025 Year 11',
        'Year 12' => '2025 Year 12',
    ];

    protected $manual_price = [
        'PRO50' => 12, // 21 day journal 20% off
        'PRO51' => 25.45, // 6 month journal 20% off
    ];


    protected const engage_planners_id = '25x94901';
    protected const engage_journals_id = '25x33805';
    protected const engage_journals_discounted_id = '25x95208';

    protected const shipping_service_code = 'SER111';
    protected const teacher_resource_service_code = 'SER101';

    protected $comment = '';

    protected function get_invoice_items($quote_items)
    {
        log_debug('Processing quote items for invoice', ['quote_items' => $quote_items]);
        $product_items = [];
        $service_items = [];

        // student resources
        $total_students = 0;
        // $is_journals = array_search(self::engage_journals_id, array_column($quote_items, 'productid'));
        $selected_year_levels = [];
        foreach (self::student_resource_keys as [$key, $code, $year_level]) {
            if ($this->isset_data($key)) {
                array_push($product_items, [
                    'qty' => $this->data[$key],
                    'code' => $code,
                    'section_name' => 'Student Journals',
                    'section_no' => 2,
                ]);
                $total_students += $this->data[$key];
                array_push($this->selected_year_levels, $year_level);
            }
        }



        // teacher resources
        $total_teacher_resources = 0;
        foreach (self::teacher_resource_keys as [$key, $code]) {
            if ($this->isset_data($key)) {
                array_push($product_items, [
                    'qty' => $this->data[$key],
                    'code' => $code,
                    'section_name' => 'Teacher Resources',
                    'section_no' => 3,
                ]);
                $total_teacher_resources += $this->data[$key];
            }
        }
        if ($total_teacher_resources > 0) {
            array_push($service_items, [
                'qty' => $total_teacher_resources,
                'code' => self::teacher_resource_service_code,
                'section_name' => 'Display on Invoice',
                'section_no' => 1,
            ]);
        }

        // extra resources
        foreach (self::extra_resource_keys as [$key, $code]) {
            if ($this->isset_data($key)) {
                array_push($product_items, [
                    'qty' => $this->data[$key],
                    'code' => $code,
                    'section_name' => 'Extra Resources',
                    'section_no' => 4,
                ]);
            }
            if ($code === 'PRO48') {
                $gem_qty = floatval($this->data[$key]);
                if ($gem_qty >= 100 and $gem_qty < 250) {
                    $this->manual_price['PRO48'] = 16.36;
                } elseif ($gem_qty >= 250 and $gem_qty < 500) {
                    $this->manual_price['PRO48'] = 15.45;
                } elseif ($gem_qty >= 500) {
                    $this->manual_price['PRO48'] = 14.55;
                }
            }
        }

        // teacher planner - slightly different due to variation
        if ($this->isset_data('teacher_planner_qty')) {
            $teacher_planner_type = explode(' - ', $this->data['teacher_planner_type'])[0];
            $teacher_planner_code = self::teacher_planner_codes[$teacher_planner_type];
            array_push($product_items, [
                'qty' => $this->data['teacher_planner_qty'],
                'code' => $teacher_planner_code,
                'section_name' => 'Extra Resources',
                'section_no' => 4,
            ]);
        }

        $is_first_invoice = $this->is_first_invoice();

        // copy items from quote
        foreach ($quote_items as $quote_item) {
            if (!$quote_item) {
                continue;
            }
            $discount_percent = 0;
            // if(isset($quote_item->discounted_unit_selling_price)){
            //     if($quote_item->discounted_unit_selling_price !== ""){
            //         $listprice = floatval($quote_item->listprice);
            //         if($listprice > 0){
            //             $discount_dollar = floatval($quote_item->discounted_unit_selling_price);
            //             $discount_percent = (($listprice - $discount_dollar)/$listprice) * 100;
            //         }
            //     }
            // }
            $this->manual_price[self::quote_service_code_map[$quote_item->productid]] = $quote_item->listprice;
            if ($quote_item->productid === self::engage_journals_id) {
                // engage, if journals and more than one journal
                if ($total_students > 0) {
                    array_push($service_items, [
                        'qty' => $total_students,
                        'code' => self::quote_service_code_map[$quote_item->productid],
                        'section_name' => 'Display on Invoice',
                        'section_no' => 1,
                        'discount' => $discount_percent,
                    ]);
                }
            } elseif ($quote_item->productid === self::engage_journals_discounted_id) {
                // engage discounted, if journals and more than one journal
                if ($total_students > 0) {
                    array_push($service_items, [
                        'qty' => $total_students,
                        'code' => self::quote_service_code_map[$quote_item->productid],
                        'section_name' => 'Display on Invoice',
                        'section_no' => 1,
                        'discount' => $discount_percent,
                    ]);
                }
            } elseif ($quote_item->productid === self::engage_planners_id) {
                // do nothing if planners
            } elseif ($is_first_invoice) {
                log_debug('Processing first invoice item', ['product_id' => $quote_item->productid, 'service_code' => self::quote_service_code_map[$quote_item->productid]]);
                // extend and inspire
                if (isset(self::quote_service_code_map[$quote_item->productid])) {
                    array_push($service_items, [
                        'qty' => $quote_item->quantity,
                        'code' => self::quote_service_code_map[$quote_item->productid],
                        'section_name' => 'Display on Invoice',
                        'section_no' => 1,
                        'discount' => $discount_percent,
                    ]);
                } else {
                    $this->comment .= 'Failed to copy over ' . $quote_item->productid . '. ';
                }
            } else {
                log_debug('Skipping item on subsequent invoice', ['product_id' => $quote_item->productid, 'service_code' => self::quote_service_code_map[$quote_item->productid]]);
            }
        }

        // shipping
        array_push($service_items, [
            'qty' => 1,
            'code' => self::shipping_service_code,
            'section_name' => 'Display on Invoice',
            'section_no' => 1,
        ]);
        $this->manual_price['SER111'] = $this->data['shipping'];

        log_debug('Invoice service and product items prepared', [
            'service_items' => $service_items,
            'product_items' => $product_items,
        ]);
        $invoice_items = [];


        // services
        $services = $this->get_services(array_column($service_items, 'code'));
        foreach ($service_items as $item) {
            $code = $item['code'];
            $service = $this->find_service_by_code($services, $code);

            $invoice_item = [
                'productid' => $service->id,
                'quantity' => $item['qty'],
                'listprice' => $this->manual_price[$code] ?? $service->unit_price,
                'tax5' => '10',
                'cf_invoice_xerocode' => $service->cf_services_xerocode,
                'section_name' => $item['section_name'],
                'section_no' => $item['section_no'],
                'cf_invoice_xerotrackingoption' => $service->cf_services_xerotrackingoption,
                'cf_invoice_xerotrackingname' => $service->cf_services_xerotrackingname,
                'cf_invoice_salesaccount' => $service->cf_services_salesaccount,
                'xero_account' => $service->xero_account,
            ];
            if (isset($item['discount'])) {
                $invoice_item['discount_percent'] = $item['discount'];
            }

            array_push($invoice_items, $invoice_item);
        }

        //products
        $products = $this->get_products(array_column($product_items, 'code'));
        foreach ($product_items as $item) {
            $code = $item['code'];
            $product = $products[array_search($code, array_column($products, 'product_no'))];

            $invoice_item = [
                'productid' => $product->id,
                'quantity' => $item['qty'],
                'listprice' => $this->manual_price[$code] ?? $product->unit_price,
                'tax5' => '10',
                'cf_invoice_xerocode' => $product->cf_products_xerocode,
                'section_name' => $item['section_name'],
                'section_no' => $item['section_no'],
                'cf_invoice_xerotrackingoption' => $product->cf_products_xerotrackingoption,
                'cf_invoice_xerotrackingname' => $product->cf_products_xerotrackingname,
                'cf_invoice_salesaccount' => $product->cf_products_salesaccount,
                'xero_account' => $product->xero_account,
            ];


            array_push($invoice_items, $invoice_item);
        }
        log_debug('Final invoice items ready', ['invoice_items' => $invoice_items]);

        return $invoice_items;
    }

    protected function get_hub_courses($inspire)
    {
        // engage
        foreach ($this->selected_year_levels as $year_level) {
            $inspire_year_level = self::hub_year_level_course_codes[$year_level];
            if (!in_array($inspire_year_level, $this->engage_hub_courses)) {
                array_push($this->engage_hub_courses, $inspire_year_level);
            }
        }
        log_debug('Processing hub courses', [
            'inspire' => $inspire,
            'shipping_address' => $this->data['shipping_address'],
        ]);
        // staff inspire
        if (empty($inspire)) {
            log_debug('No inspire program, using Engage Only');
            $this->staff_inspire_hub_courses = self::inspire_hub_staff_parent_course_codes['Engage Only'];
            return;
        }

        $this->staff_inspire_hub_courses = self::inspire_hub_staff_parent_course_codes[$inspire];

        // student inspire
        foreach ($this->selected_year_levels as $year_level) {
            $inspire_year_level = self::inspire_hub_year_level_course_codes[$inspire][$year_level];
            if (!in_array($inspire_year_level, $this->student_inspire_hub_courses)) {
                array_push($this->student_inspire_hub_courses, $inspire_year_level);
            }
        }

        // extend
    }

    protected function is_first_invoice()
    {
        $request_body = [
            'invoiceName' => $this->previous_invoice_name,
        ];
        if ($this->isset_data('school_account_no')) {
            $request_body['organisationAccountNo'] = $this->data['school_account_no'];
            $invoice_response = $this->post_request_to_vt('getInvoicesFromAccountNo', $request_body, true);
        } else {
            $request_body['organisationName'] = $this->data['school_name_other'];
            $invoice_response = $this->post_request_to_vt('getInvoicesFromOrgName', $request_body, true);
        }

        return count($invoice_response->result) == 0;
    }

    protected function create_invoice()
    {
        log_info('Starting invoice creation process');

        $quote = $this->get_quote();
        $deal = $this->get_deal();
        $org = $this->get_org();
        $assignee = $org->assigned_user_id;

        if ($quote) {
            log_info('Using quote data for invoice', [
                'quote_id' => $quote->id,
                'deal_id' => $quote->potential_id,
            ]);
            $quote_id = $quote->id;
            $deal_id = $quote->potential_id;
            $billing_contact = $quote->cf_quotes_billingcontactname;
            $org_id = $quote->account_id;
            $contact = $quote->contact_id;
            // $assignee = $quote->assigned_user_id;
            $line_items = $quote->lineItems;
        } else {
            log_info('Using deal data for invoice (no quote found)', [
                'deal_id' => $deal->id,
            ]);
            // get deal instead
            $deal_id = $deal->id;
            $billing_contact = $deal->cf_potentials_billingcontact;
            $org_id = $deal->related_to;
            $contact = $deal->contact_id;
            // $assignee = $deal->assigned_user_id;
            $line_items = $deal->lineItems;
        }

        log_debug('Processing invoice line items', ['line_items_count' => count($line_items)]);
        $invoice_line_items = $this->get_invoice_items($line_items);

        $this->get_hub_courses($deal->cf_potentials_presentations);

        $invoice_date = max(new DateTime('now'), DateTime::createFromFormat('Y-m-d', '2025-01-31'));
        $due_date = clone $invoice_date;
        $due_date = date_add($due_date, new DateInterval('P2W'));

        $shipping_address = $this->data['shipping_address'];
        if ($this->isset_data('shipping_address_2')) {
            $shipping_address .= ' '. $this->data['shipping_address_2'];
        }

        $billing_address = '';

        if ($this->isset_data('billing_address')) {
            $billing_address = $this->data['billing_address'];
        } else {
            $billing_address = $this->data['shipping_address'];
        }
        if ($this->isset_data('billing_address_2')) {
            $billing_address .= ' '. $this->data['billing_address_2'];
        } else {
            $billing_address .= ' '. $this->data['shipping_address_2'];
        }

        $status = '';
        if ($deal->sales_stage === 'Deal Won' or $deal->sales_stage === 'Closed INV' or $deal->sales_stage === 'Prepaid') {
            $status = 'Auto Created';
        } else {
            $status = 'Unconfirmed Deal';
        }


        $request_body = [
            'subject' => $this->previous_invoice_name,
            'invoiceDate' => date_format($invoice_date, 'd/m/Y'),
            'contactId' => $contact,
            'assignee' => $assignee,
            'dealId' => $deal_id,
            'theQuoteId' => $quote_id,
            'organisationId' => $org_id,
            'dueDate' => date_format($due_date, 'd/m/Y'),
            'status' => $status,
            'poNumber' => $this->data['po_number'],
            'selectedYearLevels' => $this->selected_year_levels,
            'engageHubCourses' => $this->engage_hub_courses,
            'studentInspireHubCourses' => $this->student_inspire_hub_courses,
            'teacherInspireHubCourses' => $this->staff_inspire_hub_courses,
            // "extendHubCourses" => $this->extend_hub_courses,
            'billingAddress' => $billing_address,
            'billingSuburb' => $this->isset_data('billing_suburb') ? $this->data['billing_suburb'] : $this->data['shipping_suburb'],
            'billingPostcode' => $this->isset_data('billing_postcode') ? $this->data['billing_postcode'] : $this->data['shipping_postcode'],
            'billingState' => $this->isset_data('billing_state') ? $this->data['billing_state'] : $this->data['shipping_state'],
            'shippingAddress' => $shipping_address,
            'shippingSuburb' => $this->data['shipping_suburb'],
            'shippingPostcode' => $this->data['shipping_postcode'],
            'shippingState' => $this->data['shipping_state'],
            'orderPlacedBy' => $this->data['contact_first_name'] . ' ' . $this->data['contact_last_name'] . $this->comment,
        ];

        if ($billing_contact) {
            $request_body['billingContactId'] = $billing_contact;
        }

        if ($this->isset_data('ship_by')) {
            $request_body['shipBy'] = $this->data['ship_by'];
        }

        if ($this->isset_data('hold_until')) {
            $request_body['holdUntil'] = $this->data['hold_until'];
        }

        log_info('Sending invoice creation request to VTiger', [
            'invoice_name' => $this->previous_invoice_name,
            'deal_id' => $deal_id,
            'org_id' => $org_id,
            'status' => $status,
            'po_number' => $this->data['po_number'] ?? 'none',
        ]);

        $response = $this->post_request_with_line_items('createInvoice', $request_body, $invoice_line_items);

        if ($response && isset($response->success) && $response->success) {
            log_info('Invoice created successfully in VTiger', [
                'invoice_id' => $response->result->id ?? 'unknown',
                'invoice_name' => $this->previous_invoice_name,
            ]);
        } else {
            log_error('Invoice creation failed in VTiger', [
                'invoice_name' => $this->previous_invoice_name,
                'response' => $response,
            ]);
        }
    }


    protected function get_quote()
    {
        $school_identifier = $this->isset_data('school_account_no') ? $this->data['school_account_no'] : $this->data['school_name_other'];
        log_debug('Looking up quote in VTiger', [
            'quote_name' => $this->previous_quote_name,
            'school' => $school_identifier,
        ]);

        $response = null;
        $request_body = [
            'name' => $this->previous_quote_name,
        ];
        if ($this->isset_data('school_account_no')) {
            $request_body['organisationAccountNo'] = $this->data['school_account_no'];
            $response = $this->post_request_to_vt('getQuoteWithAccountNo', $request_body, true);
        } else {
            $request_body['organisationName'] = $this->data['school_name_other'];
            $response = $this->post_request_to_vt('getQuoteWithName', $request_body, true);
        }

        if (!empty($response) and !empty($response->result) and !empty($response->result[0])) {
            log_info('Quote found in VTiger', [
                'quote_id' => $response->result[0]->id,
                'quote_name' => $this->previous_quote_name,
            ]);
            return $response->result[0];
        }
        log_debug('No quote found in VTiger', ['quote_name' => $this->previous_quote_name]);
        return null;
    }

    protected function get_deal()
    {
        $school_identifier = $this->isset_data('school_account_no') ? $this->data['school_account_no'] : $this->data['school_name_other'];
        log_debug('Looking up deal in VTiger', [
            'deal_name' => $this->previous_deal_name,
            'school' => $school_identifier,
        ]);

        $response = null;
        $request_body = [
            'dealName' => $this->previous_deal_name,
        ];
        if ($this->isset_data('school_account_no')) {
            $request_body['organisationAccountNo'] = $this->data['school_account_no'];
            $response = $this->post_request_to_vt('getDealDetailsFromAccountNo', $request_body, true);
        } else {
            $request_body['organisationName'] = $this->data['school_name_other'];
            $response = $this->post_request_to_vt('getDealDetails', $request_body, true);
        }

        if (!empty($response) and !empty($response->result) and !empty($response->result[0])) {
            log_info('Deal found in VTiger', [
                'deal_id' => $response->result[0]->id,
                'deal_name' => $this->previous_deal_name,
                'sales_stage' => $response->result[0]->sales_stage ?? 'unknown',
            ]);
            return $response->result[0];
        }
        log_debug('No deal found in VTiger', ['deal_name' => $this->previous_deal_name]);
        return null;
    }

    protected function get_org()
    {
        $school_identifier = $this->isset_data('school_account_no') ? $this->data['school_account_no'] : $this->data['school_name_other'];
        log_debug('Looking up organization in VTiger', ['school' => $school_identifier]);

        $response = null;
        if ($this->isset_data('school_account_no')) {
            $request_body['organisationAccountNo'] = $this->data['school_account_no'];
            $response = $this->post_request_to_vt('getOrgWithAccountNo', $request_body, true);
        } else {
            $request_body['organisationName'] = $this->data['school_name_other'];
            $response = $this->post_request_to_vt('getOrgWithName', $request_body, true);
        }

        if (!empty($response) and !empty($response->result) and !empty($response->result[0])) {
            log_info('Organization found in VTiger', [
                'org_id' => $response->result[0]->id,
                'org_name' => $response->result[0]->accountname ?? 'unknown',
            ]);
            return $response->result[0];
        }

        log_warning('Organization not found in VTiger', ['school' => $school_identifier]);
        return null;


    }

    public function order_resources()
    {

        try {
            $this->create_invoice();

            return true;
        } catch (Exception $e) {
            log_exception($e, [
                'method' => 'order_resources',
                'school' => $this->isset_data('school_account_no') ? $this->data['school_account_no'] : $this->data['school_name_other'] ?? 'unknown',
            ]);
            return false;
        }
    }
}
