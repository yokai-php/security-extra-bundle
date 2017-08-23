<?php

namespace Yokai\SecurityExtraBundle\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Yokai\SecurityExtraBundle\Exception\LogicException;

/**
 * @author Yann Eugoné <eugone.yann@gmail.com>
 */
class CallableCollectionVoter extends Voter
{
    /**
     * Attribute list this is supporting.
     *
     * @var string[]
     */
    private $supportedAttributes;

    /**
     * Subject types list this is supporting.
     *
     * @var string[]
     */
    private $supportedSubjects;

    /**
     * Callable collection this must call.
     *
     * @var callable[]
     */
    private $callables;

    /**
     * @param string[]   $supportedAttributes Attribute list this is supporting
     * @param string[]   $supportedSubjects   Subject types list this is supporting
     * @param callable[] $callables           Callable collection this must call
     */
    public function __construct($supportedAttributes, $supportedSubjects, $callables)
    {
        $this->supportedAttributes = $supportedAttributes;
        $this->supportedSubjects = $supportedSubjects;
        $this->callables = $callables;
    }

    /**
     * @inheritDoc
     */
    protected function supports($attribute, $subject)
    {
        // if at least one supported attribute is configured
        // check if provided attribute is in that list
        if (count($this->supportedAttributes) > 0 && !in_array($attribute, $this->supportedAttributes, true)) {
            return false;
        }

        // if there is no subject
        // of if there is not at least one supported subject
        // this is supporting
        if ($subject === null || count($this->supportedSubjects) === 0) {
            return true;
        }

        // iterate over supported subjects
        foreach ($this->supportedSubjects as $supportedSubject) {
            // if supported subject is a class (or interface)
            // this supports if subject is an instance of
            if (class_exists($supportedSubject)) {
                if ($subject instanceof $supportedSubject) {
                    return true;
                }

                continue;
            }

            // if supported subject is not a class, it must be an internal type
            // this support if subject type is the same
            if (gettype($subject) === $supportedSubject) {
                return true;
            }
        }

        // supported attribute but unsupported subject

        return false;
    }

    /**
     * @inheritDoc
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        // iterate over configured callables
        foreach ($this->callables as $callable) {
            $callable = $this->normalizeCallable($callable, $subject);
            $parameters = $this->gatherParameters($callable, $attribute, $subject, $token);

            // if one callable returns falsy result
            // this deny access
            if (!(bool) call_user_func_array($callable, $parameters)) {
                return false;
            }
        }

        // no callable returns falsy result
        // this grand access

        return true;
    }

    /**
     * Normalizes a callable.
     * Will return a callable array. See http://php.net/manual/en/language.types.callable.php .
     *
     * @param string|object|array $callable The callable to normalize
     * @param mixed               $subject  The subject being voting on
     *
     * @return array The normalized callable
     * @throws \Exception
     */
    private function normalizeCallable($callable, $subject)
    {
        // callable is a string
        // it should be a method to call on subject
        if (is_string($callable)) {
            if (!is_object($subject) || !is_callable([$subject, $callable])) {
                throw new LogicException(
                    sprintf(
                        'Provided string callable "%s", but subject "%s" has no callable method with that name.',
                        (string) $callable,
                        is_object($subject) ? get_class($subject) : gettype($subject)
                    )
                );
            }

            return [$subject, $callable];
        }

        // callable is an object
        // it should have an __invoke method
        if (is_object($callable)) {
            if (!is_callable([$callable, '__invoke'])) {
                throw new LogicException(
                    sprintf(
                        'Provided object callable "%s", but it has no "__invoke" method.',
                        is_object($callable) ? get_class($callable) : gettype($callable)
                    )
                );
            }

            return [$callable, '__invoke'];
        }

        // callable is an array
        // it should be an array with [0] and [1]
        if (is_array($callable)) {
            if (!isset($callable[0]) || !isset($callable[1]) || !is_callable([$callable[0], $callable[1]])) {
                throw new LogicException('Provided array callable, but it is not callable.');
            }

            return [$callable[0], $callable[1]];
        }

        throw new LogicException(
            sprintf(
                'Unable to normalize callable "%". Please review your configuration.',
                is_object($callable) ? get_class($callable) : gettype($callable)
            )
        );
    }

    /**
     * Analyzes callable and determine the required parameters.
     *
     * @param array          $callable  The callable for which to gather parameters
     * @param string         $attribute The attribute being voting for
     * @param mixed          $subject   The subject being voting on
     * @param TokenInterface $token     The authentication being voting for
     *
     * @return array The parameters list
     * @throws \Exception
     */
    private function gatherParameters($callable, $attribute, $subject, TokenInterface $token)
    {
        if ($callable[0] instanceof \Closure) {
            // don't know why but, it seems that ['\Closure', '__invoke'] is not ok with \ReflectionMethod
            $reflection = new \ReflectionFunction($callable[0]);
        } else {
            $reflection = new \ReflectionMethod(get_class($callable[0]), $callable[1]);
        }

        $parameters = [];

        // iterating over all parameters for method
        foreach ($reflection->getParameters() as $parameter) {
            $parameterType = $parameter->getType();
            if (method_exists($parameterType, 'getName')) {
                $parameterType = $parameterType->getName();
            } else {
                $parameterType = (string) $parameterType; // PHP < 7.1 supports
            }
            $parameterName = $parameter->getName();
            $parameterPosition = $parameter->getPosition();
            switch (true) {
                // attribute is a bit tricky, cannot use any type to determine whether or not it is required
                // if the parameter name is "attribute" this assume it should be provided
                // adding subject to required parameters
                case $parameterName === 'attribute':
                    $parameters[$parameterPosition] = $attribute;
                    break;

                // parameter looks like the subject being voting on
                // adding subject to required parameters
                case is_a($subject, $parameterType) || gettype($subject) === $parameterType:
                    $parameters[$parameterPosition] = $subject;
                    break;

                // parameter looks like a security token
                // adding token to required parameters
                case is_a($token, $parameterType):
                    $parameters[$parameterPosition] = $token;
                    break;

                // parameter looks like a security user
                // adding user to required parameters
                case is_a($token->getUser(), $parameterType):
                    $parameters[$parameterPosition] = $token->getUser();
                    break;
            }
        }

        // this gathered parameters, but the callable needs something more
        // calling with these parameters will probably results to an error
        // so throwing an exception is the only thing to do
        if ($reflection->getNumberOfRequiredParameters() !== count($parameters)) {
            throw new LogicException(
                sprintf(
                    'The callable method "%s"->"%s"() needs parameters that cannot be provided.',
                    get_class($callable[0]),
                    $callable[1]
                )
            );
        }

        // return sorted parameters
        ksort($parameters);

        return $parameters;
    }
}
