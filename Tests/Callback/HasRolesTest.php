<?php

namespace Yokai\SecurityExtraBundle\Tests\Callback;

use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;
use Symfony\Component\Security\Core\Authorization\Voter\RoleVoter;
use Yokai\SecurityExtraBundle\Callback\HasRoles;
use Yokai\SecurityExtraBundle\Tests\Utils\SecurityUtils;

/**
 * @author Yann Eugoné <eugone.yann@gmail.com>
 */
class HasRolesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_uses_decision_manager_to_check_if_token_has_role()
    {
        // assertions strongly depends on strategy configured to AccessDecisionManager
        $decisionManager = new AccessDecisionManager([new RoleVoter()], AccessDecisionManager::STRATEGY_UNANIMOUS);

        $anonymousToken = SecurityUtils::createAnonymousToken();
        $userToken = SecurityUtils::createAuthenticatedToken('john', ['ROLE_USER']);
        $userAdminToken = SecurityUtils::createAuthenticatedToken('john', ['ROLE_USER', 'ROLE_ADMIN']);
        $superadminToken = SecurityUtils::createAuthenticatedToken('john', ['ROLE_ADMIN', 'ROLE_SUPERADMIN']);

        $isUser = new HasRoles($decisionManager, ['ROLE_USER']);
        self::assertFalse($isUser($anonymousToken));
        self::assertTrue($isUser($userToken));
        self::assertTrue($isUser($userAdminToken));
        self::assertFalse($isUser($superadminToken));

        $isAdmin = new HasRoles($decisionManager, ['ROLE_ADMIN']);
        self::assertFalse($isAdmin($anonymousToken));
        self::assertFalse($isAdmin($userToken));
        self::assertTrue($isAdmin($userAdminToken));
        self::assertTrue($isAdmin($superadminToken));

        $isUserAdmin = new HasRoles($decisionManager, ['ROLE_USER', 'ROLE_ADMIN']);
        self::assertFalse($isUserAdmin($anonymousToken));
        self::assertFalse($isUserAdmin($userToken));
        self::assertTrue($isUserAdmin($userAdminToken));
        self::assertFalse($isUserAdmin($superadminToken));

        $isUserSuperadmin = new HasRoles($decisionManager, ['ROLE_USER', 'ROLE_SUPERADMIN']);
        self::assertFalse($isUserSuperadmin($anonymousToken));
        self::assertFalse($isUserSuperadmin($userToken));
        self::assertFalse($isUserSuperadmin($userAdminToken));
        self::assertFalse($isUserSuperadmin($superadminToken));
    }
}
