<?php

namespace Yokai\SecurityExtraBundle\Tests\Functional;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Yokai\SecurityExtraBundle\Tests\Functional\Fixtures\Entity\Article;
use Yokai\SecurityExtraBundle\Tests\Functional\Fixtures\Entity\Group;
use Yokai\SecurityExtraBundle\Tests\Functional\Fixtures\Entity\User;
use Yokai\SecurityExtraBundle\Tests\Functional\Fixtures\Entity\UserGroupMembership;
use Yokai\SecurityExtraBundle\Tests\Utils\SecurityUtils;

class ApplicationTest extends KernelTestCase
{
    /**
     * @test
     */
    public function it_loads_configuration_and_vote_on_articles()
    {
        self::bootKernel(['test_case' => 'Article']);

        $john = $this->createUser('john', ['ROLE_ARTICLE_READ', 'ROLE_ARTICLE_WRITE']);
        $jane = $this->createUser('jane', ['ROLE_ARTICLE_READ']);
        $jack = $this->createUser('jack', ['ROLE_USER']);

        $publishedArticleByJohn = $this->createArticle('published', $john);
        $draftArticleByJohn = $this->createArticle('draft', $john);

        // authenticate $john and do assertions against articles
        $this->authenticateUser($john);

        $this->assertGranted('browse');
        $this->assertGranted('create');

        $this->assertGranted('details', $publishedArticleByJohn);
        $this->assertGranted('update', $publishedArticleByJohn);
        $this->assertNotGranted('publish', $publishedArticleByJohn);
        $this->assertGranted('delete', $publishedArticleByJohn);

        $this->assertGranted('details', $draftArticleByJohn);
        $this->assertGranted('update', $draftArticleByJohn);
        $this->assertGranted('publish', $draftArticleByJohn);
        $this->assertGranted('delete', $draftArticleByJohn);

        // authenticate $jane and do assertions against articles
        $this->authenticateUser($jane);

        $this->assertGranted('browse');
        $this->assertNotGranted('create');

        $this->assertGranted('details', $publishedArticleByJohn);
        $this->assertNotGranted('update', $publishedArticleByJohn);
        $this->assertNotGranted('publish', $publishedArticleByJohn);
        $this->assertNotGranted('delete', $publishedArticleByJohn);

        $this->assertGranted('details', $draftArticleByJohn);
        $this->assertNotGranted('update', $draftArticleByJohn);
        $this->assertNotGranted('publish', $draftArticleByJohn);
        $this->assertNotGranted('delete', $draftArticleByJohn);

        // authenticate $jack and do assertions against articles
        $this->authenticateUser($jack);

        $this->assertNotGranted('browse');
        $this->assertNotGranted('create');

        $this->assertNotGranted('details', $publishedArticleByJohn);
        $this->assertNotGranted('update', $publishedArticleByJohn);
        $this->assertNotGranted('publish', $publishedArticleByJohn);
        $this->assertNotGranted('delete', $publishedArticleByJohn);

        $this->assertNotGranted('details', $draftArticleByJohn);
        $this->assertNotGranted('update', $draftArticleByJohn);
        $this->assertNotGranted('publish', $draftArticleByJohn);
        $this->assertNotGranted('delete', $draftArticleByJohn);
    }
    /**
     * @test
     */
    public function it_loads_configuration_and_vote_on_groups()
    {
        self::bootKernel(['test_case' => 'Group']);

        $php = $this->createGroup('PHP');
        $symfony = $this->createGroup('Symfony');
        $laravel = $this->createGroup('Laravel');

        $john = $this->createUser('john');
        $john->memberships[] = $this->createMembership($john, $php, 'admin');
        $john->memberships[] = $this->createMembership($john, $symfony, 'admin');
        $john->memberships[] = $this->createMembership($john, $laravel, 'member');

        $jane = $this->createUser('jane');
        $jane->memberships[] = $this->createMembership($jane, $php, 'member');
        $jane->memberships[] = $this->createMembership($jane, $laravel, 'admin');

        $jack = $this->createUser('jack');

        // authenticate $jack and do assertions against groups
        $this->authenticateUser($john);

        $this->assertNotGranted('join', $php);
        $this->assertGranted('leave', $php);
        $this->assertGranted('send_message', $php);
        $this->assertGranted('close', $php);

        $this->assertNotGranted('join', $symfony);
        $this->assertGranted('leave', $symfony);
        $this->assertGranted('send_message', $symfony);
        $this->assertGranted('close', $symfony);

        $this->assertNotGranted('join', $laravel);
        $this->assertGranted('leave', $laravel);
        $this->assertGranted('send_message', $laravel);
        $this->assertNotGranted('close', $laravel);

        // authenticate $jack and do assertions against groups
        $this->authenticateUser($jane);

        $this->assertNotGranted('join', $php);
        $this->assertGranted('leave', $php);
        $this->assertGranted('send_message', $php);
        $this->assertNotGranted('close', $php);

        $this->assertGranted('join', $symfony);
        $this->assertNotGranted('leave', $symfony);
        $this->assertNotGranted('send_message', $symfony);
        $this->assertNotGranted('close', $symfony);

        $this->assertNotGranted('join', $laravel);
        $this->assertGranted('leave', $laravel);
        $this->assertGranted('send_message', $laravel);
        $this->assertGranted('close', $laravel);

        // authenticate $jack and do assertions against groups
        $this->authenticateUser($jack);

        $this->assertGranted('join', $php);
        $this->assertNotGranted('leave', $php);
        $this->assertNotGranted('send_message', $php);
        $this->assertNotGranted('close', $php);

        $this->assertGranted('join', $symfony);
        $this->assertNotGranted('leave', $symfony);
        $this->assertNotGranted('send_message', $symfony);
        $this->assertNotGranted('close', $symfony);

        $this->assertGranted('join', $laravel);
        $this->assertNotGranted('leave', $laravel);
        $this->assertNotGranted('send_message', $laravel);
        $this->assertNotGranted('close', $laravel);
    }

    private function createArticle(string $status, User $author)
    {
        $article = new Article();
        $article->title = ucfirst($status) . ' article by ' . $author->name;
        $article->author = $author;
        $article->status = $status;

        return $article;
    }

    private function createUser(string $name, array $roles = ['ROLE_USER'])
    {
        $user = new User();
        $user->name = $name;
        $user->roles = $roles;

        return $user;
    }

    private function createGroup(string $name)
    {
        $group = new Group();
        $group->name = $name;

        return $group;
    }

    private function createMembership(User $user, Group $group, string $role)
    {
        $membership = new UserGroupMembership();
        $membership->user = $user;
        $membership->group = $group;
        $membership->role = $role;

        return $membership;
    }

    private function authenticateUser(UserInterface $user)
    {
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = self::$kernel->getContainer()->get('security.token_storage');
        $tokenStorage->setToken(SecurityUtils::createAuthenticatedToken($user));
    }

    private function assertGranted($attributes, $object = null)
    {
        /** @var AuthorizationCheckerInterface $authorizationChecker */
        $authorizationChecker = self::$kernel->getContainer()->get('security.authorization_checker');

        self::assertTrue($authorizationChecker->isGranted($attributes, $object));
    }

    private function assertNotGranted($attributes, $object = null)
    {
        /** @var AuthorizationCheckerInterface $authorizationChecker */
        $authorizationChecker = self::$kernel->getContainer()->get('security.authorization_checker');

        self::assertFalse($authorizationChecker->isGranted($attributes, $object));
    }
}
