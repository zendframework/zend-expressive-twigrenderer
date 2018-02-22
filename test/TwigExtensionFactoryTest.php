<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-twigrenderer for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-twigrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Twig;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ProphecyInterface;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Twig\TwigExtension;
use Zend\Expressive\Twig\TwigExtensionFactory;

class TwigExtensionFactoryTest extends TestCase
{
    /**
     * @var ContainerInterface|ProphecyInterface
     */
    private $container;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function testUsesAssetsConfigurationWhenAddingTwigExtension()
    {
        $config = [
            'templates' => [
                'assets_url'     => 'http://assets.example.com/',
                'assets_version' => 'XYZ',
            ],
        ];

        $serverUrlHelper = $this->prophesize(ServerUrlHelper::class)->reveal();
        $urlHelper       = $this->prophesize(UrlHelper::class)->reveal();
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(ServerUrlHelper::class)->willReturn(true);
        $this->container->get(ServerUrlHelper::class)->willReturn($serverUrlHelper);
        $this->container->has(UrlHelper::class)->willReturn(true);
        $this->container->get(UrlHelper::class)->willReturn($urlHelper);
        $factory   = new TwigExtensionFactory();
        $extension = $factory($this->container->reveal());

        $this->assertInstanceOf(TwigExtension::class, $extension);
        $this->assertAttributeEquals($config['templates']['assets_url'], 'assetsUrl', $extension);
        $this->assertAttributeEquals($config['templates']['assets_version'], 'assetsVersion', $extension);
        $this->assertAttributeSame($serverUrlHelper, 'serverUrlHelper', $extension);
        $this->assertAttributeSame($urlHelper, 'urlHelper', $extension);
    }

    public function testConfiguresGlobals()
    {
        $config = [
            'twig' => [
                'globals' => [
                    'ga_tracking' => 'UA-XXXXX-X',
                    'foo'         => 'bar',
                ],
            ],
        ];

        $serverUrlHelper = $this->prophesize(ServerUrlHelper::class)->reveal();
        $urlHelper       = $this->prophesize(UrlHelper::class)->reveal();
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(ServerUrlHelper::class)->willReturn(true);
        $this->container->get(ServerUrlHelper::class)->willReturn($serverUrlHelper);
        $this->container->has(UrlHelper::class)->willReturn(true);
        $this->container->get(UrlHelper::class)->willReturn($urlHelper);
        $factory   = new TwigExtensionFactory();
        $extension = $factory($this->container->reveal());

        $this->assertInstanceOf(TwigExtension::class, $extension);
        $this->assertAttributeEquals($config['twig']['globals'], 'globals', $extension);
        $this->assertAttributeSame($serverUrlHelper, 'serverUrlHelper', $extension);
        $this->assertAttributeSame($urlHelper, 'urlHelper', $extension);
    }
}
