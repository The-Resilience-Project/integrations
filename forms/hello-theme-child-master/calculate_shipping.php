<?php

class CurricShipping
{
    protected $public_calc = true;
    protected $curriculum_mapping = [];
    protected $extra_resc_mapping = [];
    protected $teacher_planner_code = '';
    protected $teacher_planner_num;

    protected $senior_planner_code = '';
    protected $senior_planner_num;

    protected const box_weight = 13000;

    protected const username = '8ec1498b-1c04-485a-b642-e7d9a1d4df26';
    protected const password = 'yX4kEYsbJoikUVqE6SBR';

    protected const base_box = [
        'product_id' => 'EXP',
        'length' => '35',
        'height' => '23',
        'width' => '22',
        'weight' => 14,
        'packaging_type' => 'CTN',
    ];

    protected const public_student_mapping = [
        'PRO18' => 'input_10_3', // foundation student
        'PRO19' => 'input_11_3',
        'PRO20' => 'input_12_3',
        'PRO21' => 'input_13_3',
        'PRO22' => 'input_14_3',
        'PRO23' => 'input_15_3',
        'PRO24' => 'input_16_3',
        'PRO25' => 'input_17_3',
        'PRO26' => 'input_18_3',
        'PRO27' => 'input_19_3',
        'PRO28' => 'input_20_3',
        'PRO29' => 'input_21_3',
        'PRO30' => 'input_22_3',

        'PRO31' => 'input_24_3', // foundation teacher
        'PRO32' => 'input_66_3',
        'PRO33' => 'input_67_3',
        'PRO34' => 'input_68_3',
        'PRO35' => 'input_69_3',
        'PRO36' => 'input_70_3',
        'PRO37' => 'input_71_3',
        'PRO38' => 'input_72_3',
        'PRO39' => 'input_73_3',
        'PRO40' => 'input_74_3',
        'PRO41' => 'input_75_3',
        'PRO42' => 'input_76_3',
        'PRO43' => 'input_77_3',
    ];

    protected const public_extra_mapping = [
        'PRO52' => 'input_115_3', // fence sign
        'PRO47' => 'input_28_3', // reading log
        'PRO48' => 'input_29_3', // gem cards
        'PRO64' => 'input_178_3', // emo cards
        'PRO49' => 'input_36_3', // primary planner
        'PRO50' => 'input_37_3', // 21 day
        'PRO51' => 'input_38_3', // 6 month
    ];

    protected const public_teacher_planner_mapping = [
        '7 Period Week to a View' => 'PRO59',
        '7 Period Day to a Page' => 'PRO55',
        '6 Period Week to a View' => 'PRO58',
        '6 Period Day to a Page' => 'PRO54',
        '5 Period Week to a View' => 'PRO57',
        '5 Period Day to a Page' => 'PRO53',
        '4 Period Week to a View' => 'PRO56',
        '4 Period Day to a Page' => 'PRO46',
    ];

    protected const public_senior_planner_mapping = [
        'Small' => 'PRO60',
        'Large' => 'PRO63',
    ];

    protected const internal_student_mapping = [
        'PRO18' => 'input_6', // foundation student
        'PRO19' => 'input_7',
        'PRO20' => 'input_8',
        'PRO21' => 'input_9',
        'PRO22' => 'input_10',
        'PRO23' => 'input_11',
        'PRO24' => 'input_12',
        'PRO25' => 'input_13',
        'PRO26' => 'input_14',
        'PRO27' => 'input_15',
        'PRO28' => 'input_16',
        'PRO29' => 'input_17',
        'PRO30' => 'input_18',

        'PRO31' => 'input_21', // foundation teacher
        'PRO32' => 'input_22',
        'PRO33' => 'input_23',
        'PRO34' => 'input_24',
        'PRO35' => 'input_25',
        'PRO36' => 'input_26',
        'PRO37' => 'input_27',
        'PRO38' => 'input_33',
        'PRO39' => 'input_32',
        'PRO40' => 'input_30',
        'PRO41' => 'input_29',
        'PRO42' => 'input_31',
        'PRO43' => 'input_28',
    ];

    protected const internal_extra_mapping = [
        'PRO52' => 'input_42', // fence sign
        'PRO47' => 'input_36', // reading log
        'PRO48' => 'input_40', // gem cards
        'PRO49' => 'input_41', // primary planner
        'PRO50' => 'input_38', // 21 day
        'PRO51' => 'input_37', // 6 month
    ];

    protected const product_codes = ['PRO18','PRO19','PRO20','PRO21','PRO22','PRO23','PRO24','PRO25','PRO26','PRO27','PRO28','PRO29','PRO30','PRO31','PRO32','PRO33','PRO34','PRO35','PRO36','PRO37','PRO38','PRO39','PRO40','PRO41','PRO42','PRO43','PRO52','PRO47','PRO48', 'PRO64', 'PRO49','PRO50','PRO51','PRO59','PRO55','PRO58','PRO54','PRO57','PRO53','PRO56','PRO46', 'PRO60', 'PRO63'];

    public function __construct($is_public_calc = true)
    {
        $this->public_calc = $is_public_calc;
        if ($is_public_calc) {
            $this->curriculum_mapping = self::public_student_mapping;
            $this->extra_resc_mapping = self::public_extra_mapping;

            $this->teacher_planner_num = floatval(rgpost('input_143_3'));
            $teacher_planner_type = explode(' - ', rgpost('input_144'))[0];
            $this->teacher_planner_code = self::public_teacher_planner_mapping[$teacher_planner_type];

            $this->senior_planner_num = floatval(rgpost('input_175_3'));
            $senior_planner_type = explode(' (', rgpost('input_176'))[0];
            $this->senior_planner_code = self::public_senior_planner_mapping[$senior_planner_type];

        } else {
            $this->curriculum_mapping = self::internal_student_mapping;
            $this->extra_resc_mapping = self::internal_extra_mapping;

            $this->teacher_planner_num = floatval(rgpost('input_39'));
            // $planner_type = rgpost("input_44");
            $this->teacher_planner_code = rgpost('input_44');
        }
    }

    protected function get_product_weights()
    {
        $request_header = [];
        $request_header[] = 'token: 0riYjXnTEbxgOVK0iIsJkqEx';
        $request_header[] = 'Content-Type: application/json';

        $request_method = 'GET';

        $request_handle = curl_init('https://theresilienceproject.od2.vtiger.com/restapi/vtap/webhook/getProducts');
        curl_setopt_array($request_handle, [
            CURLOPT_CUSTOMREQUEST => $request_method,
            CURLOPT_POSTFIELDS => json_encode(['productNumbers' => self::product_codes]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $request_header,
        ]);

        $response = curl_exec($request_handle);
        $json_response = json_decode($response, true);
        return $json_response['result'];
    }


    public function get_shipping_price()
    {
        $request_header = ['Account-Number: 10168525', 'Content-Type: application/json'];
        // "Authorisation: Basic OGVjMTQ5OGItMWMwNC00ODVhLWI2NDItZTdkOWExZDRkZjI2OnlYNGtFWXNiSm9pa1VWcUU2U0JS"
        $consignment = $this->get_boxes_for_consignment();

        if (count($consignment) == 0) {
            return 0;
        }

        $destination_suburb;
        $destination_state;
        $destination_postcode;

        if ($this->public_calc) {
            $destination_suburb = rgpost('input_99_3');
            $destination_state = rgpost('input_100');
            $destination_postcode = rgpost('input_99_5');
        } else {
            $destination_suburb = rgpost('input_1_3');
            $destination_state = rgpost('input_4');
            $destination_postcode = rgpost('input_1_5');
        }

        $request_body = [
            'shipments' => [
                'from' => [
                    'suburb' => 'SEAFORD',
                    'state' => 'VIC',
                    'postcode' => '3198',
                ],
                'to' => [
                    'suburb' => $destination_suburb,
                    'state' => $destination_state,
                    'postcode' => $destination_postcode,
                ],
                'items' => $consignment,
            ],
        ];


        $request_handle = curl_init('https://digitalapi.auspost.com.au/shipping/v1/prices/shipments');
        curl_setopt_array($request_handle, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => json_encode($request_body),
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $request_header,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => self::username .':'.self::password,
        ]);
        $response = curl_exec($request_handle);
        $json_response = json_decode($response, true);
        if (!isset($json_response['shipments'])) {
            error_log(print_r($request_body, 1));
            error_log(print_r($json_response, 1));
            return 0;
        }

        return $json_response['shipments'][0]['shipment_summary']['total_cost_ex_gst'];
    }


    protected function get_boxes_for_consignment()
    {
        $products = $this->get_product_weights();

        $student_resources_weight = 0;
        $extra_resources_weight = 0;

        foreach ($products as $product) {
            if (isset($this->curriculum_mapping[$product['product_no']])) {
                $qty = floatval(rgpost($this->curriculum_mapping[$product['product_no']]));
                if ($qty > 0) {
                    $student_resources_weight += $qty * floatval($product['cf_products_weight']);
                }
            } elseif (isset($this->extra_resc_mapping[$product['product_no']])) {
                $qty = floatval(rgpost($this->extra_resc_mapping[$product['product_no']]));
                if ($qty > 0) {
                    $extra_resources_weight += $qty * floatval($product['cf_products_weight']);
                }
            } elseif ($this->teacher_planner_code === $product['product_no']) {
                if ($this->teacher_planner_num > 0) {
                    $extra_resources_weight += $this->teacher_planner_num * floatval($product['cf_products_weight']);
                }
            } elseif ($this->senior_planner_code === $product['product_no']) {
                if ($this->senior_planner_num > 0) {
                    $extra_resources_weight += $this->senior_planner_num * floatval($product['cf_products_weight']);
                }
            }
        }


        $number_of_student_boxes = ceil($student_resources_weight / self::box_weight);
        $number_of_extra_boxes = ceil($extra_resources_weight / self::box_weight);

        $student_boxes_arr = array_fill(0, $number_of_student_boxes, self::base_box);
        $extra_boxes_arr = array_fill(0, $number_of_extra_boxes, self::base_box);
        return array_merge($student_boxes_arr, $extra_boxes_arr);

    }
}
