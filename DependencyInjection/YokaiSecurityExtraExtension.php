<?php

namespace Yokai\SecurityExtraBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Yokai\SecurityExtraBundle\Callback\HasRoles;
use Yokai\SecurityExtraBundle\Voter\CallableCollectionVoter;

/**
 * @author Yann Eugoné <eugone.yann@gmail.com>
 */
class YokaiSecurityExtraExtension extends Extension
{
    /**
     * @inheritdoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        foreach ($config['permissions'] as $permission) {
            $callables = $this->buildCallables($container, $permission['roles'], $permission['callables']);

            $voterDefinition = (new Definition(CallableCollectionVoter::class))
                ->setArguments([$permission['attributes'], $permission['subjects'], $callables])
                ->addTag('security.voter')
                ->setPublic(false)
            ;

            $container->setDefinition($this->uniqueVoterId(), $voterDefinition);
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $roles
     * @param array            $callables
     *
     * @return array
     */
    private function buildCallables(ContainerBuilder $container, array $roles, array $callables)
    {
        $return = [];

        if (count($roles) > 0) {
            $callbackDefinition = (new Definition(HasRoles::class))
                ->setArguments([new Reference('security.role_hierarchy'), $roles])
                ->setPublic(false)
            ;

            $container->setDefinition($callbackId = $this->uniqueCallbackId(), $callbackDefinition);

            $return[] = new Reference($callbackId);
        }

        foreach ($callables as $callable) {
            if (is_array($callable) && isset($callable[0])) {
                $callable[0] = $this->replaceWithReference($callable[0]);
            }

            if (is_string($callable)) {
                $callable = $this->replaceWithReference($callable);
            }

            $return[] = $callable;
        }

        return $return;
    }

    /**
     * @return string
     */
    private function uniqueVoterId()
    {
        return uniqid('app.security.voter.configurable.', true);
    }

    /**
     * @return string
     */
    private function uniqueCallbackId()
    {
        return uniqid('app.security.voter.callback.', true);
    }

    /**
     * @param string $string
     *
     * @return string|Reference
     */
    private function replaceWithReference(string $string)
    {
        if (strpos($string, '@') !== 0) {
            return $string;
        }

        return new Reference(substr($string, 1));
    }
}
