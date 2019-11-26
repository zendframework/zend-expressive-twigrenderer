<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-twigrenderer for the canonical source repository
 * @copyright Copyright (c) 2015-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-twigrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Twig;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ProphecyInterface;
use Psr\Container\ContainerInterface;
use ReflectionProperty;
use Twig\Environment;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Template\TemplatePath;
use Zend\Expressive\Twig\Exception\InvalidConfigException;
use Zend\Expressive\Twig\TwigEnvironmentFactory;
use Zend\Expressive\Twig\TwigExtension;
use Zend\Expressive\Twig\TwigRenderer;
use Zend\Expressive\Twig\TwigRendererFactory;

use function restore_error_handler;
use function set_error_handler;
use function sprintf;

use const E_USER_DEPRECATED;

class TwigRendererFactoryTest extends TestCase
{
    /**
     * @var ContainerInterface|ProphecyInterface
     */
    private $container;

    /**
     * @var callable
     */
    private $errorHandler;

    protected function setUp() : void
    {
        $this->restoreErrorHandler();
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    protected function tearDown() : void
    {
        $this->restoreErrorHandler();
    }

    public function restoreErrorHandler()
    {
        if ($this->errorHandler) {
            restore_error_handler();
            $this->errorHandler = null;
        }
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
            1     => __DIR__ . '/TestAsset/one',
            'bar' => [
                __DIR__ . '/TestAsset/baz',
                __DIR__ . '/TestAsset/bat',
            ],
            0     => [
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
        $this->container->has(TwigExtension::class)->willReturn(false);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);
        $environment = new TwigEnvironmentFactory();
        $this->container->has(Environment::class)->willReturn(true);
        $this->container->get(Environment::class)->willReturn(
            $environment($this->container->reveal())
        );

        $factory = new TwigRendererFactory();
        $twig    = $factory($this->container->reveal());
        $this->assertInstanceOf(TwigRenderer::class, $twig);

        return $twig;
    }

    /**
     * @depends testCallingFactoryWithNoConfigReturnsTwigInstance
     *
     * @param TwigRenderer $twig
     */
    public function testUnconfiguredTwigInstanceContainsNoPaths(TwigRenderer $twig)
    {
        $paths = $twig->getPaths();
        $this->assertIsArray($paths);
        $this->assertEmpty($paths);
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
        $this->container->has(TwigExtension::class)->willReturn(false);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);
        $environment = new TwigEnvironmentFactory();
        $this->container->has(Environment::class)->willReturn(true);
        $this->container->get(Environment::class)->willReturn(
            $environment($this->container->reveal())
        );
        $factory = new TwigRendererFactory();
        $twig    = $factory($this->container->reveal());

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
        $this->container->has(TwigExtension::class)->willReturn(false);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);
        $environment = new TwigEnvironmentFactory();
        $this->container->has(Environment::class)->willReturn(true);
        $this->container->get(Environment::class)->willReturn(
            $environment($this->container->reveal())
        );
        $factory = new TwigRendererFactory();
        $twig    = $factory($this->container->reveal());

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

    public function testCallingFactoryWithoutTwigEnvironmentServiceEmitsDeprecationNotice()
    {
        $this->container->has('config')->willReturn(false);
        $this->container->has(TwigExtension::class)->willReturn(false);
        $this->container->has(ServerUrlHelper::class)->willReturn(false);
        $this->container->has(UrlHelper::class)->willReturn(false);
        $this->container->has(Environment::class)->willReturn(false);

        $factory = new TwigRendererFactory();

        $this->errorHandler = set_error_handler(function ($errno, $errstr) {
            $this->assertStringContainsString(Environment::class, $errstr);
            return true;
        }, E_USER_DEPRECATED);

        $twig = $factory($this->container->reveal());
        $this->assertInstanceOf(TwigRenderer::class, $twig);
    }

    public function testMergeConfigRaisesExceptionForInvalidConfig()
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Config service MUST be an array or ArrayObject; received string');

        TwigRendererFactory::mergeConfig('foo');
    }

    public function testMergesConfigCorrectly()
    {
        $config = [
            'templates' => [
                'extension' => 'file extension used by templates; defaults to html.twig',
                'paths' => [],
            ],
            'twig' => [
                'cache_dir' => 'path to cached templates',
                'assets_url' => 'base URL for assets',
                'assets_version' => 'base version for assets',
                'extensions' => [],
                'runtime_loaders' => [],
                'globals' => ['ga_tracking' => 'UA-XXXXX-X'],
                'timezone' => 'default timezone identifier, e.g.: America/New_York',
            ],
        ];

        $mergedConfig = TwigRendererFactory::mergeConfig($config);

        $this->assertArrayHasKey('extension', $mergedConfig);
        $this->assertArrayHasKey('paths', $mergedConfig);
        $this->assertArrayHasKey('cache_dir', $mergedConfig);
        $this->assertArrayHasKey('assets_version', $mergedConfig);
        $this->assertArrayHasKey('runtime_loaders', $mergedConfig);
        $this->assertArrayHasKey('globals', $mergedConfig);
        $this->assertArrayHasKey('timezone', $mergedConfig);
    }
}
