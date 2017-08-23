<?php

namespace Yokai\SecurityExtraBundle\Tests\Functional\Fixtures\Entity;

class UserGroupMembership
{
    /**
     * @var User
     */
    public $user;

    /**
     * @var Group
     */
    public $group;

    /**
     * @var string
     */
    public $role;
}
