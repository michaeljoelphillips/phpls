<?php

declare(strict_types=1);

namespace LanguageServer\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ServerConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder('config');

        $builder
            ->getRootNode()
                ->children()
                    ->arrayNode('ctags')
                        ->children()
                            ->arrayNode('completion')
                                ->children()
                                    ->integerNode('keyword_length')
                                        ->defaultValue(3)
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('diagnostics')
                        ->canBeDisabled()
                        ->children()
                            ->arrayNode('ignore')
                                ->scalarPrototype()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('log')
                        ->canBeEnabled()
                        ->children()
                            ->scalarNode('path')
                            ->end()
                            ->enumNode('level')
                                ->values(['info', 'debug'])
                                ->defaultValue('info')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $builder;
    }
}
