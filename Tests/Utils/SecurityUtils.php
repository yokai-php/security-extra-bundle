<?php

namespace Yokai\SecurityExtraBundle\Tests\Utils;

use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @author Yann Eugoné <eugone.yann@gmail.com>
 */
class SecurityUtils
{
    const SECRET = 'ThisIsNotSecret';
    const PROVIDER_KEY = 'main';

    /**
     * @param string|UserInterface $user
     * @param array                $roles
     *
     * @return UsernamePasswordToken
     */
    public static function createAuthenticatedToken($user, array $roles = [])
    {
        if ($user instanceof UserInterface && empty($roles)) {
            $roles = $user->getRoles();
        }
        if (is_string($user)) {
            $user = new User($user, $user, $roles);
        }

        return new UsernamePasswordToken($user, $user->getUsername(), self::PROVIDER_KEY, $roles);
    }

    /**
     * @return AnonymousToken
     */
    public static function createAnonymousToken()
    {
        return new AnonymousToken(self::SECRET, 'anon.', []);
    }
}
