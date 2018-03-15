<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-twigrenderer for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-twigrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Twig;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Twig\Exception\InvalidConfigException;

use function sprintf;

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
        $config = TwigRendererFactory::mergeConfig($config);

        return new TwigExtension(
            $container->get(ServerUrlHelper::class),
            $container->get(UrlHelper::class),
            $config['assets_url'] ?? '',
            $config['assets_version'] ?? '',
            $config['globals'] ?? []
        );
    }
}
