<?php

namespace Yokai\SecurityExtraBundle\Tests\Functional\Fixtures\Entity;

class Article
{
    /**
     * @var string
     */
    public $title;

    /**
     * @var User
     */
    public $author;

    /**
     * @var string
     */
    public $status;

    /**
     * @param User $user
     *
     * @return bool
     */
    public function isOwnedBy(User $user)
    {
        return $this->author === $user;
    }
}
