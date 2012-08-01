<?php
namespace Millwright\ConfigurationBundle;

use Symfony\Component\HttpKernel\DependencyInjection\Extension as ExtensionBase;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Base rad extension
 */
abstract class Extension extends ExtensionBase
{
    protected $bundleRoot  = __DIR__;
    protected $isYml       = true;

    /**
     * Get configuration files array
     *
     * @return array
     */
    protected function getConfigParts()
    {
        return array(
            'services.yml',
        );
    }

    /**
     * Get configuration files root directory
     *
     * @return string
     */
    protected function getConfigRoot()
    {
        return dirname($this->bundleRoot) . '/Resources/config';
    }

    /**
     * Get file loader
     *
     * @param ContainerBuilder $container
     *
     * @return Loader\XmlFileLoader|Loader\YamlFileLoader
     */
    protected function getLoader(ContainerBuilder $container, $rootSuffix = null)
    {
        $root = $this->getConfigRoot();
        if ($rootSuffix) {
            $root .= DIRECTORY_SEPARATOR . $rootSuffix;
        }

        $locator = new FileLocator($root);

        return $this->isYml
            ? new Loader\YamlFileLoader($container, $locator)
            : new Loader\XmlFileLoader($container, $locator);
    }

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration(array(), $container);
        $config        = $configuration
            ? $this->processConfiguration($configuration, $configs)
            : array();

        $driver = isset($config['db_driver']) ? $config['db_driver'] : 'orm';

        $loader = $this->getLoader($container);

        $parts = $this->getConfigParts();

        foreach ($parts as $part) {
            $loader->load($part);
        }

        $loader = $this->getLoader($container, $driver);

        foreach ($parts as $part) {
            try {
                $loader->load($part);
            } catch (\InvalidArgumentException $e) {
            }
        }

        if ($config) {
            $this->copyParameters($config, $container);
        }
    }

    /**
     * Copy parameters from config to container
     *
     * @param array            $config app config
     * @param ContainerBuilder $container
     */
    protected function copyParameters(array $config, ContainerBuilder $container)
    {
        foreach ($config as $key => $value) {
            $key = $this->getAlias() . '.' . $key;
            $container->setParameter($key, $value);
        }
    }
}
