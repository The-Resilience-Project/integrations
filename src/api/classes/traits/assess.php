<?php

trait Assess
{
    protected function convert_data_to_bool($key)
    {
        if ($this->isset_data($key) && $this->data[$key] === 'Yes') {
            return true;
        }
        return false;
    }

    protected function get_score($zeroset, $oneset)
    {
        $zeroset_contains_any_no = false;
        foreach ($zeroset as $key) {
            if ($this->data[$key] === 'No') {
                $zeroset_contains_any_no = true;
            }
        }

        if ($zeroset_contains_any_no) {
            return 'Emerging';
        }

        $oneset_contains_any_no = false;
        foreach ($oneset as $key) {
            if ($this->data[$key] === 'No') {
                $oneset_contains_any_no = true;
            }
        }

        if ($oneset_contains_any_no) {
            return 'Established';
        }
        return 'Excelling';

    }
    protected function create_assessment($quote_contact)
    {
        $request_body = [
            'organisationId' => $this->organisation_id,
            'assessmentName' => '2026 Wellbeing Culture Assessment',
            'contactId' => $quote_contact,
            'orgType' => 'School - New',

            'visionAndPractice' => $this->get_score(['VP01', 'VP02', 'VP03', 'VP04'], ['VP11', 'VP12', 'VP13', 'VP14']),
            'explicitTeaching' => $this->get_score(['ET01', 'ET02', 'ET03', 'ET04'], ['ET11', 'ET12', 'ET13', 'ET14']),
            'habitBuilding' => $this->get_score(['HB01', 'HB02', 'HB03', 'HB04'], ['HB11', 'HB12', 'HB13', 'HB14']),
            'staffCapacity' => $this->get_score(['SC01', 'SC02', 'SC03'], ['SC11', 'SC12', 'SC13']),
            'staffWellbeing' => $this->get_score(['SW01', 'SW02'], ['SW11', 'SW12']),
            'familyCapacity' => $this->get_score(['FC01', 'FC02'], ['FC11', 'FC12']),
            'partnerships' => $this->get_score(['P01', 'P02'], ['P11', 'P12']),

            // <!-- VISION AND PRACTICE -->
            'VP01' => $this->convert_data_to_bool('VP01'),
            'VP02' => $this->convert_data_to_bool('VP02'),
            'VP03' => $this->convert_data_to_bool('VP03'),
            'VP04' => $this->convert_data_to_bool('VP04'),
            'VP11' => $this->convert_data_to_bool('VP11'),
            'VP12' => $this->convert_data_to_bool('VP12'),
            'VP13' => $this->convert_data_to_bool('VP13'),
            'VP14' => $this->convert_data_to_bool('VP14'),

           //  <!-- EXPLICIT TEACHING -->
            'ET01' => $this->convert_data_to_bool('ET01'),
            'ET02' => $this->convert_data_to_bool('ET02'),
            'ET03' => $this->convert_data_to_bool('ET03'),
            'ET04' => $this->convert_data_to_bool('ET04'),
            'ET11' => $this->convert_data_to_bool('ET11'),
            'ET12' => $this->convert_data_to_bool('ET12'),
            'ET13' => $this->convert_data_to_bool('ET13'),
            'ET14' => $this->convert_data_to_bool('ET14'),

            // <!-- HABIT BUILDING -->
            'HB01' => $this->convert_data_to_bool('HB01'),
            'HB02' => $this->convert_data_to_bool('HB02'),
            'HB03' => $this->convert_data_to_bool('HB03'),
            'HB04' => $this->convert_data_to_bool('HB04'),
            'HB11' => $this->convert_data_to_bool('HB11'),
            'HB12' => $this->convert_data_to_bool('HB12'),
            'HB13' => $this->convert_data_to_bool('HB13'),
            'HB14' => $this->convert_data_to_bool('HB14'),

            // <!-- STAFF CAPACITY -->
            'SC01' => $this->convert_data_to_bool('SC01'),
            'SC02' => $this->convert_data_to_bool('SC02'),
            'SC03' => $this->convert_data_to_bool('SC03'),
            'SC11' => $this->convert_data_to_bool('SC11'),
            'SC12' => $this->convert_data_to_bool('SC12'),
            'SC13' => $this->convert_data_to_bool('SC13'),

            // <!-- STAFF WELLBEING -->
            'SW01' => $this->convert_data_to_bool('SW01'),
            'SW02' => $this->convert_data_to_bool('SW02'),
            'SW11' => $this->convert_data_to_bool('SW11'),
            'SW12' => $this->convert_data_to_bool('SW12'),

            // <!-- FAMILY CAPACITY -->
            'FC01' => $this->convert_data_to_bool('FC01'),
            'FC02' => $this->convert_data_to_bool('FC02'),
            'FC11' => $this->convert_data_to_bool('FC11'),
            'FC12' => $this->convert_data_to_bool('FC12'),

            // <!-- PARTNERSHIPS -->
            'P01' => $this->convert_data_to_bool('P01'),
            'P02' => $this->convert_data_to_bool('P02'),
            'P11' => $this->convert_data_to_bool('P11'),
            'P12' => $this->convert_data_to_bool('P12'),
        ];

        $response = $this->post_request_to_vt('createAssessment', $request_body);
        $response_data = $response->result;


        return $response_data->id;

    }
    protected function update_seip_with_ca($ca_id)
    {
        $school_context = '';
        $school_context .= '<b>What are your top three concerns?</b><br/>1.' . $this->data['concern_1'] . '<br/>2. ' . $this->data['concern_2'] . '<br/> 3.' . $this->data['concern_3'] . '<br/><br/>';
        $school_context .= '<b>How are your classes structured?</b><br/>' . $this->data['class_structure']. '<br/><br/>';
        $school_context .= '<b>Who is responsible for wellbeing?</b><br/>' . $this->data['responsible_for_wellbeing']. '<br/><br/>';
        if ($this->isset_data('past_programs')) {
            $school_context .= '<b>Which mental health and wellbeing programs has your school run in the past?</b><br/>' . $this->data['past_programs']. '<br/><br/>';
        }
        if ($this->isset_data('alongside_programs')) {
            $school_context .= '<b>Which mental health and wellbeing programs are you planning to run alongside The Resilience Project?</b><br/>' . $this->data['alongside_programs']. '<br/><br/>';
        }


        $request_body = [
            'organisationId' => $this->organisation_id,
            'seipName' => $this->seip_name,
            'wellbeingCultureAssessmentId' => $ca_id,
            'caCompleted' => date('d/m/Y'),
            'schoolContext' => $school_context,
        ];

        if (get_class($this) === 'SchoolVTController') {
            $request_body['yearsWithTrp'] = '1st year';
        }

        $response = $this->post_request_to_vt('createOrUpdateSEIP', $request_body);
    }

    protected function get_quote_contact()
    {
        $request_body = [
            'organisationAccountNo' => $this->data['school_account_no'],
            'name' => $this->quote_name,
        ];

        $response = $this->post_request_to_vt('getQuoteWithAccountNo', $request_body, true);

        $quote = $response->result[0];
        return $quote->contact_id;

    }


    public function create_culture_assessment()
    {
        try {
            $quote_contact = $this->get_quote_contact();
            $this->organisation_id = str_contains($this->data['organisation_id'], '3x') ? $this->data['organisation_id'] : '3x'.$this->data['organisation_id'];
            $ca_id = $this->create_assessment($quote_contact);
            $this->update_seip_with_ca($ca_id);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
