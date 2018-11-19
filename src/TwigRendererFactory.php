<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-twigrenderer for the canonical source repository
 * @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-twigrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Twig;

use ArrayObject;
use Psr\Container\ContainerInterface;
use Twig\Error\LoaderError;
use Twig\Environment;

use function array_replace_recursive;
use function get_class;
use function gettype;
use function is_array;
use function is_object;
use function sprintf;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * Create and return a Twig template instance.
 */
class TwigRendererFactory
{
    /**
     * @throws LoaderError
     * @throws Exception\InvalidConfigException if a non-array, non-ArrayObject
     *     $config is received.
     */
    public function __invoke(ContainerInterface $container) : TwigRenderer
    {
        $config      = $container->has('config') ? $container->get('config') : [];
        $config      = self::mergeConfig($config);
        $environment = $this->getEnvironment($container);

        return new TwigRenderer($environment, $config['extension'] ?? 'html.twig');
    }

    /**
     * Merge expressive templating config with twig config.
     *
     * Pulls the `templates` and `twig` top-level keys from the configuration,
     * if present, and then returns the merged result, with those from the twig
     * array having precedence.
     *
     * @param array|ArrayObject $config
     * @throws Exception\InvalidConfigException if a non-array, non-ArrayObject
     *     $config is received.
     */
    public static function mergeConfig($config) : array
    {
        $config = $config instanceof ArrayObject ? $config->getArrayCopy() : $config;

        if (! is_array($config)) {
            throw new Exception\InvalidConfigException(sprintf(
                'Config service MUST be an array or ArrayObject; received %s',
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
     * @throws LoaderError
     */
    private function getEnvironment(ContainerInterface $container) : Environment
    {
        if ($container->has(Environment::class)) {
            return $container->get(Environment::class);
        }

        trigger_error(sprintf(
            '%s now expects you to register the factory %s for the service %s; '
            . 'please update your dependency configuration.',
            __CLASS__,
            TwigEnvironmentFactory::class,
            Environment::class
        ), E_USER_DEPRECATED);

        $factory = new TwigEnvironmentFactory();
        return $factory($container);
    }
}
