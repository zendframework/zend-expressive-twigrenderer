<?php

namespace ZendTest\Expressive\Twig;

use Interop\Container\ContainerInterface;
use DateTimeZone;
use PHPUnit_Framework_TestCase as TestCase;
use Twig_Environment as TwigEnvironment;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Twig\Exception\InvalidConfigException;
use Zend\Expressive\Twig\Exception\InvalidExtensionException;
use Zend\Expressive\Twig\TwigEnvironmentFactory;
use Zend\Expressive\Twig\TwigExtension;

class TwigEnvironmentFactoryTest extends TestCase
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function testCallingFactoryWithNoConfigReturnsTwigEnvironmentInstance()
    {
        $this->container->has('config')->willReturn(false);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);
        $factory     = new TwigEnvironmentFactory();
        $environment = $factory($this->container->reveal());

        $this->assertInstanceOf(TwigEnvironment::class, $environment);

        return $environment;
    }

    public function testUsesDebugConfiguration()
    {
        $config = ['debug' => true];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);
        $factory     = new TwigEnvironmentFactory();
        $environment = $factory($this->container->reveal());

        $this->assertTrue($environment->isDebug());
        $this->assertFalse($environment->getCache());
        $this->assertTrue($environment->isStrictVariables());
        $this->assertTrue($environment->isAutoReload());
    }

    /**
     * @depends testCallingFactoryWithNoConfigReturnsTwigEnvironmentInstance
     */
    public function testDebugDisabledSetsUpEnvironmentForProduction(TwigEnvironment $environment)
    {
        $this->assertFalse($environment->isDebug());
        $this->assertFalse($environment->isStrictVariables());
        $this->assertFalse($environment->isAutoReload());
    }

    public function testCanSpecifyCacheDirectoryViaConfiguration()
    {
        $config = ['templates' => ['cache_dir' => __DIR__]];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);
        $factory     = new TwigEnvironmentFactory();
        $environment = $factory($this->container->reveal());

        $this->assertEquals($config['templates']['cache_dir'], $environment->getCache());
    }

    public function testAddsTwigExtensionIfRouterIsInContainer()
    {
        $serverUrlHelper = $this->prophesize(ServerUrlHelper::class)->reveal();
        $urlHelper       = $this->prophesize(UrlHelper::class)->reveal();
        $this->container->has('config')->willReturn(false);
        $this->container->has(ServerUrlHelper::class)->willReturn(true);
        $this->container->get(ServerUrlHelper::class)->willReturn($serverUrlHelper);
        $this->container->has(UrlHelper::class)->willReturn(true);
        $this->container->get(UrlHelper::class)->willReturn($urlHelper);
        $factory     = new TwigEnvironmentFactory();
        $environment = $factory($this->container->reveal());

        $this->assertTrue($environment->hasExtension('zend-expressive'));
    }

    public function testUsesAssetsConfigurationWhenAddingTwigExtension()
    {
        $config          = [
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
        $factory     = new TwigEnvironmentFactory();
        $environment = $factory($this->container->reveal());
        $extension   = $environment->getExtension('zend-expressive');

        $this->assertInstanceOf(TwigExtension::class, $extension);
        $this->assertAttributeEquals($config['templates']['assets_url'], 'assetsUrl', $extension);
        $this->assertAttributeEquals($config['templates']['assets_version'], 'assetsVersion', $extension);
        $this->assertAttributeSame($serverUrlHelper, 'serverUrlHelper', $extension);
        $this->assertAttributeSame($urlHelper, 'urlHelper', $extension);
    }

    public function invalidExtensions()
    {
        return [
            'null'                  => [null],
            'true'                  => [true],
            'false'                 => [false],
            'zero'                  => [0],
            'int'                   => [1],
            'zero-float'            => [0.0],
            'float'                 => [1.1],
            'non-service-string'    => ['not-an-extension'],
            'array'                 => [['not-an-extension']],
            'non-extensions-object' => [(object) ['extension' => 'not-an-extension']],
        ];
    }

    /**
     * @dataProvider invalidExtensions
     */
    public function testRaisesExceptionForInvalidExtensions($extension)
    {
        $config = [
            'templates' => [
            ],
            'twig'      => [
                'extensions' => [$extension],
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);

        if (is_string($extension)) {
            $this->container->has($extension)->willReturn(false);
        }

        $factory = new TwigEnvironmentFactory();

        $this->setExpectedException(InvalidExtensionException::class);
        $factory($this->container->reveal());
    }

    public function testConfiguresGlobals()
    {
        $config          = [
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
        $factory     = new TwigEnvironmentFactory();
        $environment = $factory($this->container->reveal());
        $extension   = $environment->getExtension('zend-expressive');

        $this->assertInstanceOf(TwigExtension::class, $extension);
        $this->assertAttributeEquals($config['twig']['globals'], 'globals', $extension);
        $this->assertAttributeSame($serverUrlHelper, 'serverUrlHelper', $extension);
        $this->assertAttributeSame($urlHelper, 'urlHelper', $extension);
    }

    public function invalidConfiguration()
    {
        // @codingStandardsIgnoreStart
        //                        [Config value,                        Type ]
        return [
            'true'             => [true, 'boolean'],
            'false'            => [false, 'boolean'],
            'zero'             => [0, 'integer'],
            'int'              => [1, 'integer'],
            'zero-float'       => [0.0, 'double'],
            'float'            => [1.1, 'double'],
            'string'           => ['not-configuration', 'string'],
            'non-array-object' => [(object) ['not' => 'configuration'], 'stdClass'],
        ];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider invalidConfiguration
     */
    public function testRaisesExceptionForInvalidConfigService($config, $contains)
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $factory = new TwigEnvironmentFactory();

        $this->setExpectedException(InvalidConfigException::class, $contains);
        $factory($this->container->reveal());
    }

    public function testUsesTimezoneConfiguration()
    {
        $tz = DateTimeZone::listIdentifiers()[0];
        $config = [
            'twig' => [
                'timezone' => $tz
            ]
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);
        $factory = new TwigEnvironmentFactory();
        $environment = $factory($this->container->reveal());
        $fetchedTz = $environment->getExtension('core')->getTimezone();
        $this->assertEquals(new DateTimeZone($tz), $fetchedTz);
    }

    public function testRaisesExceptionForInvalidTimezone()
    {
        $tz = 'Luna/Copernicus_Crater';
        $config = [
            'twig' => [
                'timezone' => $tz
            ]
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);
        $factory = new TwigEnvironmentFactory();
        $this->setExpectedException(InvalidConfigException::class);
        $factory($this->container->reveal());
    }
}
