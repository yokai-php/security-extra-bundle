<?php

namespace Yokai\SecurityExtraBundle\Tests\Functional\Fixtures\Repository;

use Yokai\SecurityExtraBundle\Tests\Functional\Fixtures\Entity\Group;
use Yokai\SecurityExtraBundle\Tests\Functional\Fixtures\Entity\User;

class UserGroupMembershipRepository
{
    public function canJoin(User $user, Group $group)
    {
        return $this->getMembership($user, $group) === null;
    }

    public function canLeave(User $user, Group $group)
    {
        return $this->getMembership($user, $group) !== null;
    }

    public function isMember(User $user, Group $group)
    {
        if (($membership = $this->getMembership($user, $group)) === null) {
            return false;
        }

        return true;
    }

    public function isAdministrator(User $user, Group $group)
    {
        if (($membership = $this->getMembership($user, $group)) === null) {
            return false;
        }

        return $membership->role === 'admin';
    }

    private function getMembership(User $user, Group $group)
    {
        foreach ($user->memberships as $membership) {
            if ($membership->group === $group) {
                return $membership;
            }
        }

        return null;
    }
}
