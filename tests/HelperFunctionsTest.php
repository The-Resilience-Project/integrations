<?php

use PHPUnit\Framework\TestCase;

class HelperFunctionsTest extends TestCase
{
    // --- get_statename ---

    public function test_get_statename_returns_full_name(): void
    {
        $this->assertSame('Victoria', get_statename('VIC'));
        $this->assertSame('New South Wales', get_statename('NSW'));
        $this->assertSame('Queensland', get_statename('QLD'));
        $this->assertSame('Tasmania', get_statename('TAS'));
        $this->assertSame('Western Australia', get_statename('WA'));
        $this->assertSame('South Australia', get_statename('SA'));
        $this->assertSame('Northern Territory', get_statename('NT'));
        $this->assertSame('Australian Capital Territory', get_statename('ACT'));
    }

    // --- country_code ---

    public function test_country_code_returns_iso_code(): void
    {
        $this->assertSame('AU', country_code('Australia'));
        $this->assertSame('NZ', country_code('New Zealand'));
        $this->assertSame('US', country_code('United States of America (the)'));
        $this->assertSame('GB', country_code('United Kingdom of Great Britain and Northern Ireland (the)'));
    }
}
