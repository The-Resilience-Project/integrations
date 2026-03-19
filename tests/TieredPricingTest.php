<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests the tiered pricing logic for gem cards (PRO48) and emotion cards (PRO64)
 * in the OrderResources trait's get_invoice_items() method.
 */
class TieredPricingTest extends TestCase
{
    private function getPricingForData(array $extraData): array
    {
        // Provide defaults for all keys the method accesses unconditionally
        $data = array_merge([
            'shipping' => '0',
            'fence_sign_qty' => '0',
            'reading_log_qty' => '0',
            'gem_card_qty' => '0',
            'emotion_card_qty' => '0',
            'primary_planner_qty' => '0',
            'journal_21_qty' => '0',
            'journal_6_qty' => '0',
        ], $extraData);

        $controller = new class ($data) extends SchoolVTController {
            public function run_invoice_items(): array
            {
                $method = new ReflectionMethod($this, 'get_invoice_items');
                $method->invoke($this, []);

                $prop = new ReflectionProperty($this, 'manual_price');
                return $prop->getValue($this);
            }

            protected function get_services($codes, $ids = [])
            {
                $services = [];
                foreach ($codes as $code) {
                    $services[] = (object) [
                        'service_no' => $code,
                        'id' => 'fake_' . $code,
                        'unit_price' => '100',
                        'cf_services_xerocode' => 'XERO',
                        'cf_services_xerotrackingoption' => '',
                        'cf_services_xerotrackingname' => '',
                        'cf_services_salesaccount' => '',
                        'xero_account' => '',
                    ];
                }
                return $services;
            }

            protected function get_products($codes)
            {
                $products = [];
                foreach ($codes as $code) {
                    $products[] = (object) [
                        'product_no' => $code,
                        'id' => 'fake_' . $code,
                        'unit_price' => '20',
                        'cf_products_xerocode' => 'XERO',
                        'cf_products_xerotrackingoption' => '',
                        'cf_products_xerotrackingname' => '',
                        'cf_products_salesaccount' => '',
                        'xero_account' => '',
                    ];
                }
                return $products;
            }

            protected function is_first_invoice()
            {
                return true;
            }
        };

        $orgProp = new ReflectionProperty(SchoolVTController::class, 'organisation_details');
        $orgProp->setValue($controller, [
            'assigned_user_id' => '19x99',
            'cf_accounts_freetravel' => '0',
        ]);

        return $controller->run_invoice_items();
    }

    // -- Gem card (PRO48) tiered pricing --

    public function test_gem_card_no_discount_under_100(): void
    {
        $prices = $this->getPricingForData(['gem_card_qty' => '50']);

        $this->assertArrayNotHasKey('PRO48', $prices);
    }

    public function test_gem_card_tier1_100_to_249(): void
    {
        $prices = $this->getPricingForData(['gem_card_qty' => '100']);

        $this->assertEquals(16.36, $prices['PRO48']);
    }

    public function test_gem_card_tier1_at_249(): void
    {
        $prices = $this->getPricingForData(['gem_card_qty' => '249']);

        $this->assertEquals(16.36, $prices['PRO48']);
    }

    public function test_gem_card_tier2_250_to_499(): void
    {
        $prices = $this->getPricingForData(['gem_card_qty' => '250']);

        $this->assertEquals(15.45, $prices['PRO48']);
    }

    public function test_gem_card_tier2_at_499(): void
    {
        $prices = $this->getPricingForData(['gem_card_qty' => '499']);

        $this->assertEquals(15.45, $prices['PRO48']);
    }

    public function test_gem_card_tier3_500_plus(): void
    {
        $prices = $this->getPricingForData(['gem_card_qty' => '500']);

        $this->assertEquals(14.55, $prices['PRO48']);
    }

    public function test_gem_card_tier3_large_quantity(): void
    {
        $prices = $this->getPricingForData(['gem_card_qty' => '1000']);

        $this->assertEquals(14.55, $prices['PRO48']);
    }

    // -- Emotion card (PRO64) tiered pricing --

    public function test_emotion_card_no_discount_under_100(): void
    {
        $prices = $this->getPricingForData(['emotion_card_qty' => '50']);

        $this->assertArrayNotHasKey('PRO64', $prices);
    }

    public function test_emotion_card_tier1_100_to_249(): void
    {
        $prices = $this->getPricingForData(['emotion_card_qty' => '100']);

        $this->assertEquals(20.45, $prices['PRO64']);
    }

    public function test_emotion_card_tier2_250_to_499(): void
    {
        $prices = $this->getPricingForData(['emotion_card_qty' => '250']);

        $this->assertEquals(19.31, $prices['PRO64']);
    }

    public function test_emotion_card_tier3_500_plus(): void
    {
        $prices = $this->getPricingForData(['emotion_card_qty' => '500']);

        $this->assertEquals(18.17, $prices['PRO64']);
    }

    // -- Default manual prices preserved --

    public function test_default_journal_prices_preserved(): void
    {
        $prices = $this->getPricingForData([]);

        $this->assertEquals(12, $prices['PRO50']);    // 21 day journal
        $this->assertEquals(25.45, $prices['PRO51']); // 6 month journal
    }
}
