<?php

/*
 * This file is part of the Klipper package.
 *
 * (c) François Pluchino <francois.pluchino@klipper.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Klipper\Bundle\TranslationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * This is the class that validates and merges configuration from your config files.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class Configuration implements ConfigurationInterface
{
    private bool $debug;

    public function __construct(bool $debug)
    {
        $this->debug = $debug;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('klipper_translation');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
            ->append($this->getExceptionNode())
            ->end()
        ;

        return $treeBuilder;
    }

    protected function getExceptionNode(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder('exception');

        /** @var ArrayNodeDefinition $node */
        $node = $treeBuilder->getRootNode();
        $node
            ->fixXmlConfig('code', 'codes')
            ->fixXmlConfig('message', 'messages')
            ->addDefaultsIfNotSet()
            ->children()
            ->arrayNode('codes')
            ->useAttributeAsKey('name')
            ->beforeNormalization()
            ->ifArray()
            ->then(static function (array $items): array {
                foreach ($items as &$item) {
                    if (\is_int($item)) {
                        continue;
                    }

                    if (!\defined('Symfony\Component\HttpFoundation\Response::'.$item)) {
                        throw new InvalidConfigurationException(
                            'Invalid HTTP code in fos_rest.exception.codes, see Symfony\Component\HttpFoundation\Response for all valid codes.'
                        );
                    }

                    $item = \constant('Symfony\Component\HttpFoundation\Response::'.$item);
                }

                return $items;
            })
            ->end()
            ->prototype('integer')->end()
            ->validate()
            ->ifArray()
            ->then(function (array $items): array {
                foreach ($items as $class => $code) {
                    $this->testExceptionExists($class);
                }

                return $items;
            })
            ->end()
            ->end()
            ->arrayNode('messages')
            ->useAttributeAsKey('name')
            ->prototype('scalar')->end()
            ->validate()
            ->ifArray()
            ->then(function (array $items): array {
                foreach ($items as $class => $nomatter) {
                    $this->testExceptionExists($class);
                }

                return $items;
            })
            ->end()
            ->end()
            ->booleanNode('debug')
            ->defaultValue($this->debug)
            ->end()
            ->end()
        ;

        return $node;
    }

    /**
     * Checks if an exception is loadable.
     *
     * @param string $exception Class to test
     *
     * @throws InvalidConfigurationException if the class was not found
     */
    private function testExceptionExists(string $exception): void
    {
        if (class_exists($exception)
            && !is_subclass_of($exception, \Exception::class)
            && !is_a($exception, \Exception::class, true)
        ) {
            throw new InvalidConfigurationException("KlipperTranslationBundle exception mapper: Could not load class '{$exception}' or the class does not extend from '\\Exception'. Most probably this is a configuration problem.");
        }
    }
}
