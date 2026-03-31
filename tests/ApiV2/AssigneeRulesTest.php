<?php

use ApiV2\Config\UserIds;
use ApiV2\Domain\Schools\AssigneeRules;
use PHPUnit\Framework\TestCase;

class AssigneeRulesTest extends TestCase
{
    // --- resolveEnquiryAssignee ---

    public function test_enquiry_returns_laura_when_org_assignee_null(): void
    {
        $this->assertSame(UserIds::LAURA, AssigneeRules::resolveEnquiryAssignee(null, 'VIC'));
    }

    public function test_enquiry_returns_org_assignee_when_not_maddie(): void
    {
        $this->assertSame(UserIds::VICTOR, AssigneeRules::resolveEnquiryAssignee(UserIds::VICTOR, 'VIC'));
    }

    public function test_enquiry_returns_brendan_for_nsw_when_maddie(): void
    {
        $this->assertSame(UserIds::BRENDAN, AssigneeRules::resolveEnquiryAssignee(UserIds::MADDIE, 'NSW'));
    }

    public function test_enquiry_returns_brendan_for_qld_when_maddie(): void
    {
        $this->assertSame(UserIds::BRENDAN, AssigneeRules::resolveEnquiryAssignee(UserIds::MADDIE, 'QLD'));
    }

    public function test_enquiry_returns_laura_for_vic_when_maddie(): void
    {
        $this->assertSame(UserIds::LAURA, AssigneeRules::resolveEnquiryAssignee(UserIds::MADDIE, 'VIC'));
    }

    public function test_enquiry_returns_laura_for_sa_when_maddie(): void
    {
        $this->assertSame(UserIds::LAURA, AssigneeRules::resolveEnquiryAssignee(UserIds::MADDIE, 'SA'));
    }

    // --- resolveContactAssignee ---

    public function test_contact_returns_org_assignee_when_not_maddie(): void
    {
        $this->assertSame(UserIds::EMMA, AssigneeRules::resolveContactAssignee(UserIds::EMMA, 'VIC'));
    }

    public function test_contact_returns_brendan_for_nsw_when_maddie(): void
    {
        $this->assertSame(UserIds::BRENDAN, AssigneeRules::resolveContactAssignee(UserIds::MADDIE, 'NSW'));
    }

    public function test_contact_returns_laura_for_vic_when_maddie(): void
    {
        $this->assertSame(UserIds::LAURA, AssigneeRules::resolveContactAssignee(UserIds::MADDIE, 'VIC'));
    }

    public function test_contact_returns_laura_when_null(): void
    {
        $this->assertSame(UserIds::LAURA, AssigneeRules::resolveContactAssignee(null, 'VIC'));
    }

    // --- resolveOrgAssignee ---

    public function test_org_returns_org_assignee_when_not_maddie(): void
    {
        $this->assertSame(UserIds::ASHLEE, AssigneeRules::resolveOrgAssignee(UserIds::ASHLEE, 'VIC'));
    }

    public function test_org_returns_brendan_for_qld_when_maddie(): void
    {
        $this->assertSame(UserIds::BRENDAN, AssigneeRules::resolveOrgAssignee(UserIds::MADDIE, 'QLD'));
    }

    public function test_org_returns_laura_for_tas_when_maddie(): void
    {
        $this->assertSame(UserIds::LAURA, AssigneeRules::resolveOrgAssignee(UserIds::MADDIE, 'TAS'));
    }

    // --- isNewSchool ---

    public function test_is_new_school_true_for_maddie(): void
    {
        $this->assertTrue(AssigneeRules::isNewSchool(UserIds::MADDIE));
    }

    public function test_is_new_school_true_for_laura(): void
    {
        $this->assertTrue(AssigneeRules::isNewSchool(UserIds::LAURA));
    }

    public function test_is_new_school_true_for_victor(): void
    {
        $this->assertTrue(AssigneeRules::isNewSchool(UserIds::VICTOR));
    }

    public function test_is_new_school_true_for_helenor(): void
    {
        $this->assertTrue(AssigneeRules::isNewSchool(UserIds::HELENOR));
    }

    public function test_is_new_school_true_for_brendan(): void
    {
        $this->assertTrue(AssigneeRules::isNewSchool(UserIds::BRENDAN));
    }

    public function test_is_new_school_false_for_dedicated_spm(): void
    {
        $this->assertFalse(AssigneeRules::isNewSchool(UserIds::EMMA));
    }

    public function test_is_new_school_false_for_ashlee(): void
    {
        $this->assertFalse(AssigneeRules::isNewSchool(UserIds::ASHLEE));
    }

    public function test_is_new_school_false_for_dawn(): void
    {
        $this->assertFalse(AssigneeRules::isNewSchool(UserIds::DAWN));
    }

    // --- resolveRegistrationReplyTo ---

    public function test_registration_reply_to_brendan_for_nsw(): void
    {
        $this->assertSame(UserIds::BRENDAN, AssigneeRules::resolveRegistrationReplyTo('NSW'));
    }

    public function test_registration_reply_to_brendan_for_qld(): void
    {
        $this->assertSame(UserIds::BRENDAN, AssigneeRules::resolveRegistrationReplyTo('QLD'));
    }

    public function test_registration_reply_to_laura_for_vic(): void
    {
        $this->assertSame(UserIds::LAURA, AssigneeRules::resolveRegistrationReplyTo('VIC'));
    }

    public function test_registration_reply_to_laura_for_null(): void
    {
        $this->assertSame(UserIds::LAURA, AssigneeRules::resolveRegistrationReplyTo(null));
    }
}
