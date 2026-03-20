<?php

class LineItemCalculator
{
    public const EXTEND_PAYLOAD_OPTIONS = [
        'teacher_wellbeing_program',
        'twb_1_online_only',
        'twb_1_workshop_paid',
        'twb_1_workshop_free',
        'twb_2_online_only',
        'twb_2_workshop_paid',
        'twb_2_workshop_free',
        'twb_3_online_only',
        'twb_3_workshop_paid',
        'twb_3_workshop_free',
        'dwf_online_only',
        'dwf_workshop_paid',
        'dwf_workshop_free',
        'brh_online_only',
        'brh_workshop_paid',
        'brh_workshop_free',
        'feeling_ace',
        'connected_parenting',
    ];

    public const EXTEND_CODE_MAP = [
        'Teacher Wellbeing Program' => 'SER23',
        'Wellbeing Webinar 1 (Self)' => 'SER26',
        'Wellbeing Workshop 1 (Self)' => 'SER24',
        'Wellbeing Webinar 2 (Others)' => 'SER27',
        'Wellbeing Workshop 2 (Others)' => 'SER25',
        'Wellbeing Webinar 3 (Success)' => 'SER117',
        'Wellbeing Workshop 3 (Success)' => 'SER118',
        'Family Digital Wellbeing Webinar' => 'SER120',
        'Family Digital Wellbeing Workshop' => 'SER119',
        'Building Resilience at Home Webinar' => 'SER30',
        'Building Resilience at Home Workshop' => 'SER104',
        'Hugh Parent Webinar' => 'SER160',
        'Martin Parent Webinar' => 'SER161',
        'Connected Parenting Webinar' => 'SER32',
    ];

    private function hasValue(array $data, string $key): bool
    {
        return isset($data[$key]) and !empty($data[$key]);
    }

    /**
     * Calculate line items for a new school confirmation.
     *
     * @return array{items: array, inspire: string}
     */
    public function calculateNewSchoolItems(array $data): array
    {
        $engage_code = 'SER12';
        $inspire = 'Inspire 1';

        $inspire_code = 'SER157';
        $using_mhf = $this->hasValue($data, 'mental_health_funding') ? $data['mental_health_funding'] === 'Yes' : false;
        $is_small_school = $this->hasValue($data, 'num_of_students') ? $data['num_of_students'] <= 200 : false;

        if ((!$using_mhf) and $is_small_school) {
            if ($data['num_of_students'] > 100) {
                $inspire_code = 'SER158';
            } else {
                $inspire_code = 'SER159';
            }
        }

        $items = [
            [
                'qty' => 1,
                'code' => $inspire_code,
                'duration' => 1,
                'section_name' => 'Display on Invoice',
                'section_no' => 1,
            ],
            [
                'qty' => $data['participating_num_of_students'],
                'code' => $engage_code,
                'duration' => 1,
                'section_name' => 'Display on Invoice',
                'section_no' => 1,
            ],
        ];

        return [
            'items' => $items,
            'inspire' => $inspire,
        ];
    }

    /**
     * Calculate line items for an existing school confirmation.
     *
     * @return array{items: array, inspire: string, engage: array, extend: array, billing_note: string}
     */
    public function calculateExistingSchoolItems(array $data, array $orgDetails): array
    {
        $journal_qty = 0;
        $planner_qty = 0;

        if ($data['school_type'] === 'Primary') {
            $journal_qty = $data['participating_num_of_students'];
        } elseif ($data['school_type'] === 'Secondary') {
            if ($data['secondary_engage'] === 'Journals') {
                $journal_qty = $data['participating_num_of_students'];
            } else {
                $planner_qty = $data['participating_num_of_students'];
            }
        } else {
            if ($data['secondary_engage'] === 'Journals') {
                $journal_qty = $data['participating_num_of_students'];
            } else {
                $journal_qty = $data['participating_journal_students'];
                $planner_qty = $data['participating_planner_students'];
            }
        }

        $items = [];
        $engage = [];

        if ($journal_qty) {
            $items[] = [
                'qty' => $journal_qty,
                'code' => 'SER12',
                'duration' => 1,
                'section_name' => 'Display on Invoice',
                'section_no' => 1,
            ];
            $engage[] = 'Journals';
        }
        if ($planner_qty) {
            $items[] = [
                'qty' => $planner_qty,
                'code' => 'SER65',
                'duration' => 1,
                'section_name' => 'Display on Invoice',
                'section_no' => 1,
            ];
            $engage[] = 'Planners';
        }

        $inspire = '';
        $billing_note = '';

        if ($data['inspire_added'] === 'Yes') {
            $inspire = 'Inspire 2';
            if ($orgDetails['cf_accounts_2025inspire'] === 'Inspire 3') {
                $inspire = 'Inspire 3';
            } elseif ($orgDetails['cf_accounts_2025inspire'] === 'Inspire 4') {
                $inspire = 'Inspire 4';
            }

            $inspire_code = 'SER147';
            $using_mhf = $this->hasValue($data, 'mental_health_funding') ? $data['mental_health_funding'] === 'Yes' : false;
            $num_of_students_provided = false;
            $num_of_students = 0;
            if ($this->hasValue($data, 'num_of_students_1')) {
                $num_of_students_provided = true;
                $num_of_students = $data['num_of_students_1'];
            } elseif ($this->hasValue($data, 'num_of_students_2')) {
                $num_of_students_provided = true;
                $num_of_students = $data['num_of_students_2'];
            }
            $is_small_school = $num_of_students_provided && $num_of_students <= 200;

            if ($using_mhf) {
                $inspire_code = 'SER146';
            } elseif ((!$using_mhf) and $is_small_school) {
                if ($num_of_students > 100) {
                    $inspire_code = 'SER148';
                } elseif ($num_of_students <= 100) {
                    $inspire_code = 'SER149';
                }
            }

            $additional = 0;
            if (!$using_mhf && $this->hasValue($data, 'inspire_year_levels') && $data['inspire_year_levels'] === 'Primary and Secondary') {
                $additional = 1000;
                $billing_note = 'Additional $1000 for P-12 Inspire';
            }

            $items[] = [
                'qty' => 1,
                'code' => $inspire_code,
                'duration' => 1,
                'section_name' => 'Display on Invoice',
                'section_no' => 1,
                'additional' => $additional,
            ];
        }

        $extend_options = [];

        foreach (self::EXTEND_PAYLOAD_OPTIONS as $extend_payload_option) {
            if ($this->hasValue($data, $extend_payload_option)) {
                $current_selected_extends = explode(', ', $data[$extend_payload_option]);
                foreach ($current_selected_extends as $current_extend) {
                    $formatted_extend = str_replace('One', '1', $current_extend);
                    $formatted_extend = str_replace('Two', '2', $formatted_extend);
                    $formatted_extend = str_replace('Three', '3', $formatted_extend);
                    $formatted_extend = substr($formatted_extend, 0, strpos($formatted_extend, '$'));

                    $extend_options[] = $formatted_extend;

                    $items[] = [
                        'qty' => 1,
                        'code' => self::EXTEND_CODE_MAP[$formatted_extend],
                        'duration' => 1,
                        'section_name' => 'Display on Invoice',
                        'section_no' => 1,
                    ];
                }
            }
        }

        return [
            'items' => $items,
            'inspire' => $inspire,
            'engage' => $engage,
            'extend' => $extend_options,
            'billing_note' => $billing_note,
        ];
    }
}
