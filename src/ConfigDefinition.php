<?php

declare(strict_types=1);

namespace Keboola\SlackWriter;

use Keboola\Component\Config\BaseConfigDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class ConfigDefinition extends BaseConfigDefinition
{
    protected function getParametersDefinition(): ArrayNodeDefinition
    {
        $parametersNode = parent::getParametersDefinition();
        // @formatter:off
        /** @noinspection NullPointerExceptionInspection */
        $parametersNode
            ->children()
                ->scalarNode('#token')->cannotBeEmpty()->end()
                ->scalarNode('channel')->cannotBeEmpty()->end()
                ->booleanNode('structured')->defaultFalse()->end()
            ->end()
        ;
        // @formatter:on
        return $parametersNode;
    }
}
