<?php

namespace Yokai\SecurityExtraBundle\Callback;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

/**
 * @author Yann Eugoné <eugone.yann@gmail.com>
 */
class HasRoles
{
    /**
     * Symfony's Security decision manager
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * The roles that must be accessible.
     * @var string[]
     */
    private $roles;

    /**
     * @param AccessDecisionManagerInterface $decisionManager The role hierarchy
     * @param string[]               $roles         The roles that must be accessible
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager, $roles)
    {
        $this->decisionManager = $decisionManager;
        $this->roles = $roles;
    }

    /**
     * Check if the provided token has access to every configured roles.
     *
     * @param TokenInterface $token The security token
     *
     * @return bool Whether or not token has all configured roles
     */
    public function __invoke(TokenInterface $token)
    {
        return $this->decisionManager->decide($token, $this->roles);
    }
}
