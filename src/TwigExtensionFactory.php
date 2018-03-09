<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-twigrenderer for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-twigrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Twig;

use ArrayObject;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Twig\Exception\InvalidConfigException;

class TwigExtensionFactory
{
    public function __invoke(ContainerInterface $container) : TwigExtension
    {
        if (! $container->has(ServerUrlHelper::class)) {
            throw new InvalidConfigException(sprintf(
                'Missing required `%s` dependency.',
                ServerUrlHelper::class
            ));
        }

        if (! $container->has(UrlHelper::class)) {
            throw new InvalidConfigException(sprintf(
                'Missing required `%s` dependency.',
                UrlHelper::class
            ));
        }

        $config = $container->has('config') ? $container->get('config') : [];
        $config = $this->mergeConfig($config);

        return new TwigExtension(
            $container->get(ServerUrlHelper::class),
            $container->get(UrlHelper::class),
            $config['assets_url'] ?? '',
            $config['assets_version'] ?? '',
            $config['globals'] ?? []
        );
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
    private function mergeConfig($config) : array
    {
        $config = $config instanceof ArrayObject ? $config->getArrayCopy() : $config;

        if (! is_array($config)) {
            throw new Exception\InvalidConfigException(sprintf(
                'Config service MUST be an array or ArrayObject; received %s',
                is_object($config) ? get_class($config) : gettype($config)
            ));
        }

        $expressiveConfig = (isset($config['templates']) && is_array($config['templates']))
            ? $config['templates']
            : [];
        $twigConfig       = (isset($config['twig']) && is_array($config['twig']))
            ? $config['twig']
            : [];

        return array_replace_recursive($expressiveConfig, $twigConfig);
    }
}
