<?php

declare(strict_types=1);

namespace ApiV2\Domain\Schools;

use ApiV2\Config\UserIds;

/**
 * Pure business rules for resolving CRM assignees for Schools.
 */
class AssigneeRules
{
    /**
     * Resolve the assignee for a school enquiry.
     *
     * - If org has no assignee, default to LAURA.
     * - If org assignee is not MADDIE, keep it.
     * - If org assignee IS MADDIE, route NSW/QLD to BRENDAN, rest to LAURA.
     */
    public static function resolveEnquiryAssignee(?string $orgAssigneeId, ?string $state): string
    {
        if (is_null($orgAssigneeId)) {
            return UserIds::LAURA;
        }

        if ($orgAssigneeId !== UserIds::MADDIE) {
            return $orgAssigneeId;
        }

        return self::routeByState($state, UserIds::LAURA);
    }

    /**
     * Resolve the assignee for a school contact.
     */
    public static function resolveContactAssignee(?string $orgAssigneeId, ?string $state): string
    {
        if ($orgAssigneeId !== null && $orgAssigneeId !== UserIds::MADDIE) {
            return $orgAssigneeId;
        }

        return self::routeByState($state, UserIds::LAURA);
    }

    /**
     * Resolve the assignee for a school organisation.
     */
    public static function resolveOrgAssignee(?string $orgAssigneeId, ?string $state): string
    {
        if ($orgAssigneeId !== null && $orgAssigneeId !== UserIds::MADDIE) {
            return $orgAssigneeId;
        }

        return self::routeByState($state, UserIds::LAURA);
    }

    /**
     * Determine if a school is "new" (not assigned to a dedicated SPM).
     *
     * New schools have their org assigned to one of the generic/internal staff,
     * not a dedicated School Partnership Manager.
     */
    public static function isNewSchool(?string $orgAssigneeId): bool
    {
        $nonSpmUsers = [
            UserIds::MADDIE,
            UserIds::LAURA,
            UserIds::VICTOR,
            UserIds::HELENOR,
            UserIds::BRENDAN,
        ];

        return in_array($orgAssigneeId, $nonSpmUsers, true);
    }

    /**
     * Resolve the reply-to staff member for registration confirmation emails.
     */
    public static function resolveRegistrationReplyTo(?string $state): string
    {
        return self::routeByState($state, UserIds::LAURA);
    }

    /**
     * NSW and QLD route to BRENDAN; all others to the given default.
     */
    private static function routeByState(?string $state, string $default): string
    {
        if ($state === 'NSW' || $state === 'QLD') {
            return UserIds::BRENDAN;
        }

        return $default;
    }
}
