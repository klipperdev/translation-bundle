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

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @author François Pluchino <francois.pluchino@klipper.dev>
 */
class KlipperTranslationExtension extends Extension
{
    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration($container->getParameter('kernel.debug'));
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('exception.xml');

        $this->configExceptionMessage($container, $config['exception']);
    }

    /**
     * Configure the exception message.
     *
     * @param ContainerBuilder $container The container builder
     * @param array            $config    The config of exception
     */
    protected function configExceptionMessage(ContainerBuilder $container, array $config): void
    {
        $container->getDefinition('klipper_translation.exception_message_manager')
            ->replaceArgument(1, $config['messages'])
            ->replaceArgument(2, $config['codes'])
        ;
    }
}
