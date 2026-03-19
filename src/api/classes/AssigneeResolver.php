<?php

class AssigneeResolver
{
    public const MADDIE = '19x1';
    public const LAURA = '19x8';
    public const VICTOR = '19x33';
    public const ASHLEE = '19x29';
    public const BRENDAN = '19x57';
    public const HELENOR = '19x24';

    public const BRENDAN_STATES = ['NSW', 'QLD'];

    private const NEW_SCHOOL_ASSIGNEES = [
        self::MADDIE, self::LAURA, self::VICTOR,
        self::HELENOR, self::BRENDAN,
    ];

    // -- School --

    public function resolveByState(string $orgAssignee, string $state): string
    {
        if ($orgAssignee != self::MADDIE) {
            return $orgAssignee;
        }

        if (in_array($state, self::BRENDAN_STATES)) {
            return self::BRENDAN;
        }
        return self::LAURA;
    }

    public function resolveSchoolEnquiryAssignee(?string $orgAssignee, string $state): string
    {
        if (is_null($orgAssignee)) {
            return self::LAURA;
        }
        return $this->resolveByState($orgAssignee, $state);
    }

    public function resolveSchoolContactAssignee(string $orgAssignee, string $state): string
    {
        return $this->resolveByState($orgAssignee, $state);
    }

    public function resolveSchoolOrgAssignee(string $orgAssignee, string $state): string
    {
        return $this->resolveByState($orgAssignee, $state);
    }

    public function isNewSchool(?string $orgAssignee): bool
    {
        return in_array($orgAssignee, self::NEW_SCHOOL_ASSIGNEES);
    }

    public function resolveSchoolRegistrationReplyTo(string $state): string
    {
        if (in_array($state, self::BRENDAN_STATES)) {
            return self::BRENDAN;
        }
        return self::LAURA;
    }

    // -- Workplace --

    public function resolveWorkplaceEnquiryAssignee(): string
    {
        return self::LAURA;
    }

    public function resolveWorkplaceContactAssignee(string $orgAssignee): string
    {
        if ($orgAssignee != self::MADDIE) {
            return $orgAssignee;
        }
        return self::LAURA;
    }

    public function resolveWorkplaceOrgAssignee(string $orgAssignee): string
    {
        return $this->resolveWorkplaceContactAssignee($orgAssignee);
    }

    // -- Early Years --

    public function resolveEarlyYearsEnquiryAssignee(): string
    {
        return self::BRENDAN;
    }

    public function resolveEarlyYearsContactAssignee(string $orgAssignee): string
    {
        if ($orgAssignee != self::MADDIE) {
            return $orgAssignee;
        }
        return self::BRENDAN;
    }

    public function resolveEarlyYearsOrgAssignee(string $orgAssignee): string
    {
        return $this->resolveEarlyYearsContactAssignee($orgAssignee);
    }

    // -- General --

    public function resolveGeneralEnquiryAssignee(): string
    {
        return self::ASHLEE;
    }

    public function resolveGeneralContactAssignee(): string
    {
        return self::MADDIE;
    }

    public function resolveGeneralOrgAssignee(): string
    {
        return self::MADDIE;
    }
}
