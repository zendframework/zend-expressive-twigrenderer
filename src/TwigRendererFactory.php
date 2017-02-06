<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-twigrenderer for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-twigrenderer/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Twig;

use ArrayObject;
use Interop\Container\ContainerInterface;
use Twig_Environment as TwigEnvironment;

/**
 * Create and return a Twig template instance.
 */
class TwigRendererFactory
{
    /**
     * @param ContainerInterface $container
     * @return TwigRenderer
     * @throws Exception\InvalidConfigException for invalid config service values.
     */
    public function __invoke(ContainerInterface $container)
    {
        $config      = $container->has('config') ? $container->get('config') : [];
        $config      = $this->mergeConfig($config);
        $environment = $this->getEnvironment($container);

        return new TwigRenderer($environment, isset($config['extension']) ? $config['extension'] : 'html.twig');
    }

    /**
     * Merge expressive templating config with twig config.
     *
     * Pulls the `templates` and `twig` top-level keys from the configuration,
     * if present, and then returns the merged result, with those from the twig
     * array having precedence.
     *
     * @param array|ArrayObject $config
     * @return array
     * @throws Exception\InvalidConfigException if a non-array, non-ArrayObject
     *     $config is received.
     */
    private function mergeConfig($config)
    {
        $config = $config instanceof ArrayObject ? $config->getArrayCopy() : $config;

        if (! is_array($config)) {
            throw new Exception\InvalidConfigException(sprintf(
                'config service MUST be an array or ArrayObject; received %s',
                is_object($config) ? get_class($config) : gettype($config)
            ));
        }

        $expressiveConfig = isset($config['templates']) && is_array($config['templates'])
            ? $config['templates']
            : [];
        $twigConfig       = isset($config['twig']) && is_array($config['twig'])
            ? $config['twig']
            : [];

        return array_replace_recursive($expressiveConfig, $twigConfig);
    }

    /**
     * Retrieve and return the TwigEnvironment instance.
     *
     * If upgrading from a previous version of this package, developers will
     * not have registered the TwigEnvironment service yet; this method will
     * create it using the TwigEnvironmentFactory, but emit a deprecation
     * notice indicating the developer should update their configuration.
     *
     * If the service is registered, it is simply pulled and returned.
     *
     * @param ContainerInterface $container
     * @return TwigEnvironment
     */
    private function getEnvironment(ContainerInterface $container)
    {
        if ($container->has(TwigEnvironment::class)) {
            return $container->get(TwigEnvironment::class);
        }

        \trigger_error(sprintf(
            '%s now expects you to register the factory %s for the service %s; '
            . 'please update your dependency configuration.',
            __CLASS__,
            TwigEnvironmentFactory::class,
            TwigEnvironment::class
        ), \E_USER_DEPRECATED);

        $factory = new TwigEnvironmentFactory();
        return $factory($container);
    }
}
