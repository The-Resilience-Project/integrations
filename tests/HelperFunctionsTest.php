<?php

use PHPUnit\Framework\TestCase;

class HelperFunctionsTest extends TestCase
{
    // -- str_limit --

    public function test_str_limit_short_string_unchanged(): void
    {
        $this->assertEquals('hello', str_limit('hello', 10));
    }

    public function test_str_limit_truncates_long_string(): void
    {
        $this->assertEquals('hello...', str_limit('hello world', 5));
    }

    public function test_str_limit_exact_length_unchanged(): void
    {
        $this->assertEquals('hello', str_limit('hello', 5));
    }

    public function test_str_limit_custom_end(): void
    {
        $this->assertEquals('hel--', str_limit('hello world', 3, '--'));
    }

    public function test_str_limit_empty_string(): void
    {
        $this->assertEquals('', str_limit('', 10));
    }

    // -- get_statename --

    public function test_get_statename_vic(): void
    {
        $this->assertEquals('Victoria', get_statename('VIC'));
    }

    public function test_get_statename_nsw(): void
    {
        $this->assertEquals('New South Wales', get_statename('NSW'));
    }

    public function test_get_statename_qld(): void
    {
        $this->assertEquals('Queensland', get_statename('QLD'));
    }

    public function test_get_statename_all_states(): void
    {
        $expected = [
            'ACT' => 'Australian Capital Territory',
            'NSW' => 'New South Wales',
            'NT' => 'Northern Territory',
            'QLD' => 'Queensland',
            'SA' => 'South Australia',
            'TAS' => 'Tasmania',
            'VIC' => 'Victoria',
            'WA' => 'Western Australia',
        ];

        foreach ($expected as $code => $name) {
            $this->assertEquals($name, get_statename($code));
        }
    }

    // -- country_code --

    public function test_country_code_australia(): void
    {
        $this->assertEquals('AU', country_code('Australia'));
    }

    public function test_country_code_new_zealand(): void
    {
        $this->assertEquals('NZ', country_code('New Zealand'));
    }

    public function test_country_code_united_states(): void
    {
        $this->assertEquals('US', country_code('United States of America (the)'));
    }

    // -- calculatePriceExcludeMaintenance --

    public function test_calculate_price_labor_and_materials(): void
    {
        $items = [
            ['section_name' => 'Labor', 'listprice' => '100.00', 'quantity' => '2'],
            ['section_name' => 'Materials', 'listprice' => '50.00', 'quantity' => '3'],
        ];

        $result = calculatePriceExcludeMaintenance($items);

        $this->assertEquals(350.0, $result['total']);
        $this->assertEquals(0, $result['maintain_total']);
    }

    public function test_calculate_price_with_maintenance(): void
    {
        $items = [
            ['section_name' => 'Labor', 'listprice' => '100.00', 'quantity' => '1'],
            ['section_name' => 'Maintenance Labor', 'listprice' => '80.00', 'quantity' => '2'],
            ['section_name' => 'Maintenance Travel', 'listprice' => '30.00', 'quantity' => '1'],
        ];

        $result = calculatePriceExcludeMaintenance($items);

        $this->assertEquals(100.0, $result['total']);
        $this->assertEquals(190.0, $result['maintain_total']);
    }

    public function test_calculate_price_travel_included(): void
    {
        $items = [
            ['section_name' => 'Travel', 'listprice' => '200.00', 'quantity' => '1'],
        ];

        $result = calculatePriceExcludeMaintenance($items);

        $this->assertEquals(200.0, $result['total']);
    }

    public function test_calculate_price_empty_items(): void
    {
        $result = calculatePriceExcludeMaintenance([]);

        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['maintain_total']);
    }

    public function test_calculate_price_unknown_section_ignored(): void
    {
        $items = [
            ['section_name' => 'Display on Invoice', 'listprice' => '500.00', 'quantity' => '1'],
        ];

        $result = calculatePriceExcludeMaintenance($items);

        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['maintain_total']);
    }

    // -- groupSystemNameLineItems --

    public function test_group_system_name_marks_consecutive_materials(): void
    {
        $items = [
            ['section_name' => 'Materials', 'cf_quotes_systemname' => 'GroupA'],
            ['section_name' => 'Materials', 'cf_quotes_systemname' => 'GroupA'],
            ['section_name' => 'Materials', 'cf_quotes_systemname' => 'GroupB'],
        ];

        $result = groupSystemNameLineItems($items);

        $this->assertEquals('', $result[0]['cf_quotes_processgroup']);
        $this->assertEquals('1', $result[1]['cf_quotes_processgroup']);
        $this->assertEquals('', $result[2]['cf_quotes_processgroup']);
    }

    public function test_group_system_name_ignores_non_materials(): void
    {
        $items = [
            ['section_name' => 'Labor', 'cf_quotes_systemname' => 'GroupA'],
            ['section_name' => 'Labor', 'cf_quotes_systemname' => 'GroupA'],
        ];

        $result = groupSystemNameLineItems($items);

        $this->assertArrayNotHasKey('cf_quotes_processgroup', $result[0]);
    }

    public function test_group_system_name_empty_array(): void
    {
        $result = groupSystemNameLineItems([]);

        $this->assertEquals([], $result);
    }

    // -- get_last_update --

    public function test_get_last_update_empty_returns_na(): void
    {
        $this->assertEquals('N/A', get_last_update(''));
    }

    public function test_get_last_update_null_returns_na(): void
    {
        $this->assertEquals('N/A', get_last_update(null));
    }

    public function test_get_last_update_today_returns_zero(): void
    {
        $today = date('Y-m-d H:i:s');
        $this->assertEquals(0, get_last_update($today));
    }
}
