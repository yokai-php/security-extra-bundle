<?php

namespace Yokai\SecurityExtraBundle\Tests\Voter;

use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Yokai\SecurityExtraBundle\Tests\Utils\SecurityUtils;
use Yokai\SecurityExtraBundle\Voter\CallableCollectionVoter;

/**
 * @author Yann Eugoné <eugone.yann@gmail.com>
 */
class CallableCollectionVoterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_vote_on_supported_attributes_and_subjects_only()
    {
        $token = SecurityUtils::createAnonymousToken();
        $voteYes = function () { return true; };

        // vote only for "publish_article" attribute and "string" subjects
        $voter = new CallableCollectionVoter(['publish_article'], ['string'], [$voteYes]);

        // abstained : unsupported attribute
        $this->assertVoterAbstained($voter, $token, 'some string var', ['delete_article']);

        // abstained : unsupported subject
        $this->assertVoterAbstained($voter, $token, ['some array var'], ['publish_article']);

        // granted
        $this->assertVoterGranted($voter, $token, 'some string var', ['publish_article']);


        // vote only for "delete_article" attribute and "\stdClass" subjects
        $voter = new CallableCollectionVoter(['delete_article'], ['stdClass'], [$voteYes]);

        // abstained : unsupported attribute
        $this->assertVoterAbstained($voter, $token, new \stdClass(), ['publish_article']);

        // abstained : unsupported subject
        $this->assertVoterAbstained($voter, $token, ['some array var'], ['delete_article']);

        // granted
        $this->assertVoterGranted($voter, $token, new \stdClass(), ['delete_article']);
    }

    /**
     * @test
     */
    public function it_vote_using_callables()
    {
        $noAnonymousToken = function (TokenInterface $token) {
            if ($token instanceof AnonymousToken) {
                return false;
            }

            return true;
        };
        $onlyForJohn = [
            new class {
                public function decide(UserInterface $user)
                {
                    if ($user->getUsername() !== 'john') {
                        return false;
                    }

                    return true;
                }
            },
            'decide'
        ];
        $onlyForSucceedSubject = 'isSucceed';
        $inaccessibleAttribute = new class {
            public function __invoke(string $attribute)
            {
                if ($attribute === 'action_you_cannot_perform') {
                    return false;
                }

                return true;
            }
        };

        $anonymousToken = SecurityUtils::createAnonymousToken();
        $authenticatedJohnToken = SecurityUtils::createAuthenticatedToken('john');
        $authenticatedJaneToken = SecurityUtils::createAuthenticatedToken('jane');
        $succeedSubject = new class {
            public function isSucceed()
            {
                return true;
            }
        };
        $failedSubject = new class {
            public function isSucceed()
            {
                return false;
            }
        };

        $voter = new CallableCollectionVoter(
            ['publish_article', 'action_you_cannot_perform'],
            [get_class($failedSubject), get_class($succeedSubject)],
            [$noAnonymousToken, $onlyForJohn, $onlyForSucceedSubject, $inaccessibleAttribute]
        );

        // denied : token is anonymous (see $noAnonymousToken)
        $this->assertVoterDenied($voter, $anonymousToken, $succeedSubject, ['publish_article']);

        // denied : user is jane (see $onlyForJohn)
        $this->assertVoterDenied($voter, $authenticatedJaneToken, $succeedSubject, ['publish_article']);

        // denied : subject is not succeed (see $onlyForSucceedSubject)
        $this->assertVoterDenied($voter, $authenticatedJohnToken, $failedSubject, ['publish_article']);

        // denied : attribute is not accessible (see $inaccessibleAttribute)
        $this->assertVoterDenied($voter, $authenticatedJohnToken, $succeedSubject, ['action_you_cannot_perform']);

        // granted
        $this->assertVoterGranted($voter, $authenticatedJohnToken, $succeedSubject, ['publish_article']);
    }

    /**
     * @test
     * @dataProvider invalidCallable
     * @expectedException \Yokai\SecurityExtraBundle\Exception\ExceptionInterface
     */
    public function it_fails_with_invalid_callable($invalidCallable)
    {
        $voter = new CallableCollectionVoter([], [], [$invalidCallable]);
        $voter->vote(SecurityUtils::createAnonymousToken(), ['dummy subject'], ['dummy_attribute']);
    }

    public function invalidCallable()
    {
        yield [ 'methodThatDoNotExistsOnSubject' ];
        yield [ new \stdClass() ];
        yield [ ['dummy array that is not callable'] ];
        yield [ 999 ];
        yield [ function (TokenInterface $token, UserInterface $user, array $subject, string $attribute, bool $somethingElse) {} ];
    }

    /**
     * @param VoterInterface $voter
     * @param TokenInterface $token
     * @param mixed          $subject
     * @param array          $attributes
     */
    private function assertVoterGranted(VoterInterface $voter, TokenInterface $token, $subject, array $attributes)
    {
        self::assertSame(VoterInterface::ACCESS_GRANTED, $voter->vote($token, $subject, $attributes));
    }

    /**
     * @param VoterInterface $voter
     * @param TokenInterface $token
     * @param mixed          $subject
     * @param array          $attributes
     */
    private function assertVoterAbstained(VoterInterface $voter, TokenInterface $token, $subject, array $attributes)
    {
        self::assertSame(VoterInterface::ACCESS_ABSTAIN, $voter->vote($token, $subject, $attributes));
    }

    /**
     * @param VoterInterface $voter
     * @param TokenInterface $token
     * @param mixed          $subject
     * @param array          $attributes
     */
    private function assertVoterDenied(VoterInterface $voter, TokenInterface $token, $subject, array $attributes)
    {
        self::assertSame(VoterInterface::ACCESS_DENIED, $voter->vote($token, $subject, $attributes));
    }
}
