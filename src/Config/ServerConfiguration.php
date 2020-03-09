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
            ->arrayNode('log')
            ->children()
            ->booleanNode('enabled')
            ->defaultFalse()
            ->end()
            ->scalarNode('path')
            ->end()
            ->enumNode('level')
            ->values(['info', 'debug'])
            ->end()
            ->end()
            ->end();

        return $builder;
    }
}
