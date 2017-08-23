<?php

namespace Yokai\SecurityExtraBundle\Callback;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;

/**
 * @author Yann Eugoné <eugone.yann@gmail.com>
 */
class HasRoles
{
    /**
     * The role hierarchy.
     * @var RoleHierarchyInterface
     */
    private $roleHierarchy;

    /**
     * The roles that must be accessible.
     * @var string[]
     */
    private $roles;

    /**
     * @param RoleHierarchyInterface $roleHierarchy The role hierarchy
     * @param string[]               $roles         The roles that must be accessible
     */
    public function __construct(RoleHierarchyInterface $roleHierarchy, $roles)
    {
        $this->roleHierarchy = $roleHierarchy;
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
        // extract and normalize roles from hierarchy
        $roles = array_map(
            function (Role $role) {
                return $role->getRole();
            },
            $this->roleHierarchy->getReachableRoles($token->getRoles())
        );

        // iterating over all configured roles
        // if a single role is missing this will return false
        foreach ($this->roles as $role) {
            if (!in_array($role, $roles, true)) {
                return false;
            }
        }

        // all configured roles are accessible to the security token
        // return true

        return true;
    }
}
