<?php

namespace Yokai\SecurityExtraBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * @author Yann Eugoné <eugone.yann@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder()
    {
        $tree = new TreeBuilder();
        $root = $tree->root('yokai_security_extra');

        $root
            ->children()
                ->append($this->getPermissionsNode())
            ->end()
        ;

        return $tree;
    }

    /**
     * @return ArrayNodeDefinition
     */
    private function getPermissionsNode()
    {
        $isString = function ($value) {
            return is_string($value);
        };
        $isNotCollection = function ($value) {
            if (!is_array($value)) {
                return true;
            }

            return isset($value[0]) && is_string($value[0]) && isset($value[1]) && is_string($value[1]);
        };
        $toArray = function ($value) {
            return [$value];
        };

        ($node = $this->root('permissions'))
            ->prototype('array')
                ->children()
                    ->arrayNode('attributes')
                        ->info('Matching attribute(s). Empty means all attributes.')
                        ->prototype('scalar')->end()
                        ->beforeNormalization()
                            ->ifTrue($isString)->then($toArray)
                        ->end()
                    ->end()
                    ->arrayNode('subjects')
                        ->info('Matching subject(s) types. Can be either classes, interfaces or types. Empty means all subjects.')
                        ->prototype('scalar')->end()
                        ->beforeNormalization()
                            ->ifTrue($isString)->then($toArray)
                        ->end()
                    ->end()
                    ->arrayNode('roles')
                        ->info('Required role(s) for these attributes & subjects.')
                        ->prototype('scalar')->end()
                        ->beforeNormalization()
                            ->ifTrue($isString)->then($toArray)
                        ->end()
                    ->end()
                    ->arrayNode('callables')
                        ->info('Callables that will verify access.')
                        ->prototype('variable')->end()
                        ->beforeNormalization()
                            ->ifTrue($isNotCollection)->then($toArray)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $node;
    }

    /**
     * @param string $name
     *
     * @return ArrayNodeDefinition
     */
    private function root($name)
    {
        return (new TreeBuilder())->root($name);
    }
}
