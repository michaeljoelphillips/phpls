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
                            ->arrayNode('php')
                                ->canBeDisabled()
                            ->end()
                            ->arrayNode('phpcs')
                                ->canBeEnabled()
                                ->children()
                                    ->enumNode('severity')
                                        ->values(['error', 'warning'])
                                        ->defaultValue('error')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('phpstan')
                                ->canBeEnabled()
                                ->children()
                                    ->enumNode('severity')
                                        ->values(['error', 'warning'])
                                        ->defaultValue('error')
                                    ->end()
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
