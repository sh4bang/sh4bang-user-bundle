<?php

namespace Sh4bang\UserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sh4bang_user');

        $rootNode
            ->children()
                ->scalarNode('user_class')->isRequired()->cannotBeEmpty()->end()

                ->integerNode('token_security_ttl')
                    ->defaultValue('86400')
                ->end()
                ->integerNode('token_reopen_ttl')
                    ->defaultValue('86400')
                ->end()
                ->integerNode('token_confirmation_ttl')
                    ->defaultValue('31536000')
                ->end()

                ->arrayNode('route')
                    ->isRequired()
                    ->children()
                        ->scalarNode('terms')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('login')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('confirm_account')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('reopen_account')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('reset_password')->isRequired()->cannotBeEmpty()->end()
                    ->end()
                ->end()

                ->arrayNode('email')
                    ->isRequired()
                    ->children()
                        ->scalarNode('from_address')->isRequired()->cannotBeEmpty()->end()
                        ->scalarNode('from_name')->isRequired()->cannotBeEmpty()->end()
                        ->arrayNode('template')
                            ->isRequired()
                            ->children()
                                ->scalarNode('confirmation')->isRequired()->cannotBeEmpty()->end()
                                ->scalarNode('change_password')->isRequired()->cannotBeEmpty()->end()
                                ->scalarNode('reopen_account')->isRequired()->cannotBeEmpty()->end()
                                ->scalarNode('resetting')->isRequired()->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
