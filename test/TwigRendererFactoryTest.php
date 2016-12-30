<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       https://github.com/zendframework/zend-expressive for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Twig;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionProperty;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplatePath;
use Zend\Expressive\Twig\Exception\InvalidConfigException;
use Zend\Expressive\Twig\Exception\InvalidExtensionException;
use Zend\Expressive\Twig\Exception\InvalidRuntimeLoaderException;
use Zend\Expressive\Twig\TwigExtension;
use Zend\Expressive\Twig\TwigRenderer;
use Zend\Expressive\Twig\TwigRendererFactory;
use ZendTest\Expressive\Twig\TestAsset\Extension\FooTwigExtension;
use ZendTest\Expressive\Twig\TestAsset\Extension\BarTwigExtension;

class TwigRendererFactoryTest extends TestCase
{
    /**
     * @var ContainerInterface
    */
    private $container;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function fetchTwigEnvironment(TwigRenderer $twig)
    {
        $r = new ReflectionProperty($twig, 'template');
        $r->setAccessible(true);
        return $r->getValue($twig);
    }

    public function getConfigurationPaths()
    {
        return [
            'foo' => __DIR__ . '/TestAsset/bar',
            1 => __DIR__ . '/TestAsset/one',
            'bar' => [
                __DIR__ . '/TestAsset/baz',
                __DIR__ . '/TestAsset/bat',
            ],
            0 => [
                __DIR__ . '/TestAsset/two',
                __DIR__ . '/TestAsset/three',
            ],
        ];
    }

    public function assertPathsHasNamespace($namespace, array $paths, $message = null)
    {
        $message = $message ?: sprintf('Paths do not contain namespace %s', $namespace ?: 'null');

        $found = false;
        foreach ($paths as $path) {
            $this->assertInstanceOf(TemplatePath::class, $path, 'Non-TemplatePath found in paths list');
            if ($path->getNamespace() === $namespace) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, $message);
    }

    public function assertPathNamespaceCount($expected, $namespace, array $paths, $message = null)
    {
        $message = $message ?: sprintf('Did not find %d paths with namespace %s', $expected, $namespace ?: 'null');

        $count = 0;
        foreach ($paths as $path) {
            $this->assertInstanceOf(TemplatePath::class, $path, 'Non-TemplatePath found in paths list');
            if ($path->getNamespace() === $namespace) {
                $count += 1;
            }
        }
        $this->assertSame($expected, $count, $message);
    }

    public function assertPathNamespaceContains($expected, $namespace, array $paths, $message = null)
    {
        $message = $message ?: sprintf('Did not find path %s in namespace %s', $expected, $namespace ?: null);

        $found = [];
        foreach ($paths as $path) {
            $this->assertInstanceOf(TemplatePath::class, $path, 'Non-TemplatePath found in paths list');
            if ($path->getNamespace() === $namespace) {
                $found[] = $path->getPath();
            }
        }
        $this->assertContains($expected, $found, $message);
    }

    public function testCallingFactoryWithNoConfigReturnsTwigInstance()
    {
        $this->container->has('config')->willReturn(false);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);
        $factory = new TwigRendererFactory();
        $twig = $factory($this->container->reveal());
        $this->assertInstanceOf(TwigRenderer::class, $twig);
        return $twig;
    }

    /**
     * @depends testCallingFactoryWithNoConfigReturnsTwigInstance
     */
    public function testUnconfiguredTwigInstanceContainsNoPaths(TwigRenderer $twig)
    {
        $paths = $twig->getPaths();
        $this->assertInternalType('array', $paths);
        $this->assertEmpty($paths);
    }

    public function testUsesDebugConfigurationToPrepareEnvironment()
    {
        $config = ['debug' => true];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);
        $factory = new TwigRendererFactory();
        $twig = $factory($this->container->reveal());

        $environment = $this->fetchTwigEnvironment($twig);

        $this->assertTrue($environment->isDebug());
        $this->assertFalse($environment->getCache());
        $this->assertTrue($environment->isStrictVariables());
        $this->assertTrue($environment->isAutoReload());
    }

    /**
     * @depends testCallingFactoryWithNoConfigReturnsTwigInstance
     */
    public function testDebugDisabledSetsUpEnvironmentForProduction(TwigRenderer $twig)
    {
        $environment = $this->fetchTwigEnvironment($twig);

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
        $factory = new TwigRendererFactory();
        $twig = $factory($this->container->reveal());

        $environment = $this->fetchTwigEnvironment($twig);
        $this->assertEquals($config['templates']['cache_dir'], $environment->getCache());
    }

    public function testAddsTwigExtensionIfRouterIsInContainer()
    {
        $serverUrlHelper = $this->prophesize(ServerUrlHelper::class)->reveal();
        $urlHelper = $this->prophesize(UrlHelper::class)->reveal();
        $this->container->has('config')->willReturn(false);
        $this->container->has(ServerUrlHelper::class)->willReturn(true);
        $this->container->get(ServerUrlHelper::class)->willReturn($serverUrlHelper);
        $this->container->has(UrlHelper::class)->willReturn(true);
        $this->container->get(UrlHelper::class)->willReturn($urlHelper);

        $factory = new TwigRendererFactory();
        $twig = $factory($this->container->reveal());

        $environment = $this->fetchTwigEnvironment($twig);
        $this->assertTrue($environment->hasExtension('zend-expressive'));
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
        $urlHelper = $this->prophesize(UrlHelper::class)->reveal();
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(ServerUrlHelper::class)->willReturn(true);
        $this->container->get(ServerUrlHelper::class)->willReturn($serverUrlHelper);
        $this->container->has(UrlHelper::class)->willReturn(true);
        $this->container->get(UrlHelper::class)->willReturn($urlHelper);
        $factory = new TwigRendererFactory();
        $twig = $factory($this->container->reveal());

        $environment = $this->fetchTwigEnvironment($twig);
        $extension = $environment->getExtension('zend-expressive');
        $this->assertInstanceOf(TwigExtension::class, $extension);
        $this->assertAttributeEquals($config['templates']['assets_url'], 'assetsUrl', $extension);
        $this->assertAttributeEquals($config['templates']['assets_version'], 'assetsVersion', $extension);
        $this->assertAttributeSame($serverUrlHelper, 'serverUrlHelper', $extension);
        $this->assertAttributeSame($urlHelper, 'urlHelper', $extension);
    }

    public function testConfiguresTemplateSuffix()
    {
        $config = [
            'templates' => [
                'extension' => 'tpl',
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);
        $factory = new TwigRendererFactory();
        $twig = $factory($this->container->reveal());

        $this->assertAttributeSame($config['templates']['extension'], 'suffix', $twig);
    }

    public function testUsesGlobalsConfigurationWhenAddingTwigExtension()
    {
        $config = [
            'templates' => [
                'paths' => $this->getConfigurationPaths(),
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);
        $factory = new TwigRendererFactory();
        $twig = $factory($this->container->reveal());

        $paths = $twig->getPaths();
        $this->assertPathsHasNamespace('foo', $paths);
        $this->assertPathsHasNamespace('bar', $paths);
        $this->assertPathsHasNamespace(null, $paths);

        $this->assertPathNamespaceCount(1, 'foo', $paths);
        $this->assertPathNamespaceCount(2, 'bar', $paths);
        $this->assertPathNamespaceCount(3, null, $paths);

        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/bar', 'foo', $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/baz', 'bar', $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/bat', 'bar', $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/one', null, $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/two', null, $paths);
        $this->assertPathNamespaceContains(__DIR__ . '/TestAsset/three', null, $paths);
    }

    public function testInjectsCustomExtensionsIntoTwigEnvironment()
    {
        $config = [
            'templates' => [
            ],
            'twig' => [
                'extensions' => [
                    new FooTwigExtension(),
                    BarTwigExtension::class,
                ],
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);
        $this->container->has(BarTwigExtension::class)->willReturn(true);
        $this->container->get(BarTwigExtension::class)->willReturn(new BarTwigExtension());
        $factory = new TwigRendererFactory();
        $view = $factory($this->container->reveal());
        $this->assertInstanceOf(TwigRenderer::class, $view);
        $environment = $this->fetchTwigEnvironment($view);
        $this->assertTrue($environment->hasExtension('foo-twig-extension'));
        $this->assertInstanceOf(FooTwigExtension::class, $environment->getExtension('foo-twig-extension'));
        $this->assertTrue($environment->hasExtension('bar-twig-extension'));
        $this->assertInstanceOf(BarTwigExtension::class, $environment->getExtension('bar-twig-extension'));
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
            'twig' => [
                'extensions' => [ $extension ],
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);

        if (is_string($extension)) {
            $this->container->has($extension)->willReturn(false);
        }

        $factory = new TwigRendererFactory();

        $this->setExpectedException(InvalidExtensionException::class);
        $factory($this->container->reveal());
    }

    public function testConfiguresGlobals()
    {
        $config = [
            'twig' => [
                'globals' => [
                    'ga_tracking' => 'UA-XXXXX-X',
                    'foo' => 'bar',
                ],
            ],
        ];
        $serverUrlHelper = $this->prophesize(ServerUrlHelper::class)->reveal();
        $urlHelper = $this->prophesize(UrlHelper::class)->reveal();
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(ServerUrlHelper::class)->willReturn(true);
        $this->container->get(ServerUrlHelper::class)->willReturn($serverUrlHelper);
        $this->container->has(UrlHelper::class)->willReturn(true);
        $this->container->get(UrlHelper::class)->willReturn($urlHelper);
        $factory = new TwigRendererFactory();
        $twig = $factory($this->container->reveal());

        $environment = $this->fetchTwigEnvironment($twig);
        $extension = $environment->getExtension('zend-expressive');
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
            'true'             => [true,                                'boolean'],
            'false'            => [false,                               'boolean'],
            'zero'             => [0,                                   'integer'],
            'int'              => [1,                                   'integer'],
            'zero-float'       => [0.0,                                 'double'],
            'float'            => [1.1,                                 'double'],
            'string'           => ['not-configuration',                 'string'],
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

        $factory = new TwigRendererFactory();
        $this->setExpectedException(InvalidConfigException::class, $contains);
        $factory($this->container->reveal());
    }

    public function invalidRuntimeLoaders()
    {
        return [
            'null'                  => [null],
            'true'                  => [true],
            'false'                 => [false],
            'zero'                  => [0],
            'int'                   => [1],
            'zero-float'            => [0.0],
            'float'                 => [1.1],
            'non-service-string'    => ['not-an-runtime-loader'],
            'array'                 => [['not-an-runtime-loader']],
            'non-extensions-object' => [(object) ['extension' => 'not-an-runtime-loader']],
        ];
    }

    /**
     * @dataProvider invalidRuntimeLoaders
     */
    public function testRaisesExceptionForInvalidRuntimeLoaders($runtimeLoader)
    {
        $config = [
            'templates' => [
            ],
            'twig' => [
                'runtime_loaders' => [ $runtimeLoader ],
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);

        if (is_string($runtimeLoader)) {
            $this->container->has($runtimeLoader)->willReturn(false);
        }

        $factory = new TwigRendererFactory();

        $this->setExpectedException(InvalidRuntimeLoaderException::class);
        $factory($this->container->reveal());
    }

    public function testInjectsCustomRuntimeLoadersIntoTwigEnvironment()
    {
        $fooRuntime = self::prophesize(\Twig_RuntimeLoaderInterface::class);
        $fooRuntime->load('Test\Runtime\FooRuntime')->willReturn('foo-runtime');
        $fooRuntime->load('Test\Runtime\BarRuntime')->willReturn(null);

        $barRuntime = self::prophesize(\Twig_RuntimeLoaderInterface::class);
        $barRuntime->load('Test\Runtime\BarRuntime')->willReturn('bar-runtime');
        $barRuntime->load('Test\Runtime\FooRuntime')->willReturn(null);

        $config = [
            'templates' => [
            ],
            'twig' => [
                'runtime_loaders' => [
                    $fooRuntime->reveal(),
                    'Test\Runtime\BarRuntimeLoader',
                ],
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);
        $this->container->has('Test\Runtime\BarRuntimeLoader')->willReturn(true);
        $this->container->get('Test\Runtime\BarRuntimeLoader')->willReturn($barRuntime->reveal());
        $factory = new TwigRendererFactory();
        $view = $factory($this->container->reveal());
        $this->assertInstanceOf(TwigRenderer::class, $view);
        $environment = $this->fetchTwigEnvironment($view);
        $this->assertEquals('bar-runtime', $environment->getRuntime('Test\Runtime\BarRuntime'));
        $this->assertEquals('foo-runtime', $environment->getRuntime('Test\Runtime\FooRuntime'));
    }
}
