<?php

namespace Yokai\SecurityExtraBundle\Tests\Functional\Fixtures\Entity;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $roles = ['ROLE_USER'];

    /**
     * @var UserGroupMembership[]
     */
    public $memberships = [];

    public function getRoles()
    {
        return $this->roles;
    }

    public function getPassword()
    {
        return 'password';
    }

    public function getSalt()
    {
        return null;
    }

    public function getUsername()
    {
        return $this->name;
    }

    public function eraseCredentials()
    {
    }
}
