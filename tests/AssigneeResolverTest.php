<?php

use PHPUnit\Framework\TestCase;

class AssigneeResolverTest extends TestCase
{
    private AssigneeResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new AssigneeResolver();
    }

    // -- resolveByState --

    public function test_resolve_by_state_returns_org_assignee_when_not_maddie(): void
    {
        $this->assertEquals('19x99', $this->resolver->resolveByState('19x99', 'VIC'));
    }

    public function test_resolve_by_state_returns_brendan_for_nsw(): void
    {
        $this->assertEquals(AssigneeResolver::BRENDAN, $this->resolver->resolveByState(AssigneeResolver::MADDIE, 'NSW'));
    }

    public function test_resolve_by_state_returns_brendan_for_qld(): void
    {
        $this->assertEquals(AssigneeResolver::BRENDAN, $this->resolver->resolveByState(AssigneeResolver::MADDIE, 'QLD'));
    }

    public function test_resolve_by_state_returns_laura_for_vic(): void
    {
        $this->assertEquals(AssigneeResolver::LAURA, $this->resolver->resolveByState(AssigneeResolver::MADDIE, 'VIC'));
    }

    public function test_resolve_by_state_returns_laura_for_sa(): void
    {
        $this->assertEquals(AssigneeResolver::LAURA, $this->resolver->resolveByState(AssigneeResolver::MADDIE, 'SA'));
    }

    // -- School enquiry assignee --

    public function test_school_enquiry_null_org_returns_laura(): void
    {
        $this->assertEquals(AssigneeResolver::LAURA, $this->resolver->resolveSchoolEnquiryAssignee(null, 'VIC'));
    }

    public function test_school_enquiry_null_org_returns_laura_even_for_nsw(): void
    {
        $this->assertEquals(AssigneeResolver::LAURA, $this->resolver->resolveSchoolEnquiryAssignee(null, 'NSW'));
    }

    public function test_school_enquiry_maddie_nsw_returns_brendan(): void
    {
        $this->assertEquals(AssigneeResolver::BRENDAN, $this->resolver->resolveSchoolEnquiryAssignee(AssigneeResolver::MADDIE, 'NSW'));
    }

    public function test_school_enquiry_spm_returns_spm(): void
    {
        $this->assertEquals('19x99', $this->resolver->resolveSchoolEnquiryAssignee('19x99', 'NSW'));
    }

    // -- School contact/org assignee --

    public function test_school_contact_delegates_to_resolve_by_state(): void
    {
        $this->assertEquals(
            $this->resolver->resolveByState(AssigneeResolver::MADDIE, 'NSW'),
            $this->resolver->resolveSchoolContactAssignee(AssigneeResolver::MADDIE, 'NSW')
        );
    }

    public function test_school_org_delegates_to_resolve_by_state(): void
    {
        $this->assertEquals(
            $this->resolver->resolveByState(AssigneeResolver::MADDIE, 'VIC'),
            $this->resolver->resolveSchoolOrgAssignee(AssigneeResolver::MADDIE, 'VIC')
        );
    }

    // -- isNewSchool --

    public function test_is_new_school_true_for_maddie(): void
    {
        $this->assertTrue($this->resolver->isNewSchool(AssigneeResolver::MADDIE));
    }

    public function test_is_new_school_true_for_laura(): void
    {
        $this->assertTrue($this->resolver->isNewSchool(AssigneeResolver::LAURA));
    }

    public function test_is_new_school_true_for_brendan(): void
    {
        $this->assertTrue($this->resolver->isNewSchool(AssigneeResolver::BRENDAN));
    }

    public function test_is_new_school_true_for_victor(): void
    {
        $this->assertTrue($this->resolver->isNewSchool(AssigneeResolver::VICTOR));
    }

    public function test_is_new_school_false_for_spm(): void
    {
        $this->assertFalse($this->resolver->isNewSchool('19x99'));
    }

    // -- Registration reply to --

    public function test_registration_reply_brendan_for_nsw(): void
    {
        $this->assertEquals(AssigneeResolver::BRENDAN, $this->resolver->resolveSchoolRegistrationReplyTo('NSW'));
    }

    public function test_registration_reply_laura_for_vic(): void
    {
        $this->assertEquals(AssigneeResolver::LAURA, $this->resolver->resolveSchoolRegistrationReplyTo('VIC'));
    }

    // -- Workplace --

    public function test_workplace_enquiry_always_laura(): void
    {
        $this->assertEquals(AssigneeResolver::LAURA, $this->resolver->resolveWorkplaceEnquiryAssignee());
    }

    public function test_workplace_contact_returns_org_when_not_maddie(): void
    {
        $this->assertEquals('19x99', $this->resolver->resolveWorkplaceContactAssignee('19x99'));
    }

    public function test_workplace_contact_returns_laura_for_maddie(): void
    {
        $this->assertEquals(AssigneeResolver::LAURA, $this->resolver->resolveWorkplaceContactAssignee(AssigneeResolver::MADDIE));
    }

    public function test_workplace_org_matches_contact(): void
    {
        $this->assertEquals(
            $this->resolver->resolveWorkplaceContactAssignee('19x99'),
            $this->resolver->resolveWorkplaceOrgAssignee('19x99')
        );
    }

    // -- Early Years --

    public function test_early_years_enquiry_always_brendan(): void
    {
        $this->assertEquals(AssigneeResolver::BRENDAN, $this->resolver->resolveEarlyYearsEnquiryAssignee());
    }

    public function test_early_years_contact_returns_org_when_not_maddie(): void
    {
        $this->assertEquals('19x99', $this->resolver->resolveEarlyYearsContactAssignee('19x99'));
    }

    public function test_early_years_contact_returns_brendan_for_maddie(): void
    {
        $this->assertEquals(AssigneeResolver::BRENDAN, $this->resolver->resolveEarlyYearsContactAssignee(AssigneeResolver::MADDIE));
    }

    // -- General --

    public function test_general_enquiry_returns_ashlee(): void
    {
        $this->assertEquals(AssigneeResolver::ASHLEE, $this->resolver->resolveGeneralEnquiryAssignee());
    }

    public function test_general_contact_returns_maddie(): void
    {
        $this->assertEquals(AssigneeResolver::MADDIE, $this->resolver->resolveGeneralContactAssignee());
    }

    public function test_general_org_returns_maddie(): void
    {
        $this->assertEquals(AssigneeResolver::MADDIE, $this->resolver->resolveGeneralOrgAssignee());
    }
}
