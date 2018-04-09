<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-twigrenderer for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-twigrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Twig;

use ArrayObject;
use DateTimeZone;
use Psr\Container\ContainerInterface;
use Twig_Environment as TwigEnvironment;
use Twig_Extension_Core as TwigExtensionCore;
use Twig_Extension_Debug as TwigExtensionDebug;
use Twig_ExtensionInterface as TwigExtensionInterface;
use Twig_Loader_Filesystem as TwigLoader;
use Twig_NodeVisitor_Optimizer as TwigOptimizer;
use Twig_RuntimeLoaderInterface as TwigRuntimeLoaderInterface;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;

use function get_class;
use function gettype;
use function is_array;
use function is_numeric;
use function is_object;
use function is_string;
use function sprintf;

/**
 * Create and return a Twig Environment instance.
 *
 * Optionally uses the service 'config', which should return an array. This
 * factory consumes the following structure:
 *
 * <code>
 * 'debug' => boolean,
 * 'templates' => [
 *     'extension' => 'file extension used by templates; defaults to html.twig',
 *     'paths' => [
 *         // namespace / path pairs
 *         //
 *         // Numeric namespaces imply the default/main namespace. Paths may be
 *         // strings or arrays of string paths to associate with the namespace.
 *     ],
 * ],
 * 'twig' => [
 *     'cache_dir' => 'path to cached templates',
 *     'assets_url' => 'base URL for assets',
 *     'assets_version' => 'base version for assets',
 *     'extensions' => [
 *         // extension service names or instances
 *     ],
 *     'runtime_loaders' => [
 *         // runtime loaders names or instances
 *     ],
 *     'globals' => [
 *         // Global variables passed to twig templates
 *         'ga_tracking' => 'UA-XXXXX-X'
 *     ],
 *     'timezone' => 'default timezone identifier, e.g.: America/New_York',
 *     'optimizations' => -1, // -1: Enable all (default), 0: disable optimizations
 *     'autoescape' => 'html', // Auto-escaping strategy [html|js|css|url|false]
 * ],
 * </code>
 *
 * Note: the various keys in the `twig` configuration key can occur in either
 * that location, or under `templates` (which was the behavior prior to 0.3.0);
 * the two arrays are merged by the factory.
 */
class TwigEnvironmentFactory
{
    /**
     * @throws Exception\InvalidConfigException for invalid config service values.
     */
    public function __invoke(ContainerInterface $container) : TwigEnvironment
    {
        $config = $container->has('config') ? $container->get('config') : [];

        if (! is_array($config) && ! $config instanceof ArrayObject) {
            throw new Exception\InvalidConfigException(sprintf(
                '"config" service must be an array or ArrayObject for the %s to be able to consume it; received %s',
                __CLASS__,
                (is_object($config) ? get_class($config) : gettype($config))
            ));
        }

        $debug    = (bool) ($config['debug'] ?? false);
        $config   = TwigRendererFactory::mergeConfig($config);
        $cacheDir = $config['cache_dir'] ?? false;

        // Create the engine instance
        $loader      = new TwigLoader();
        $environment = new TwigEnvironment($loader, [
            'cache'            => $debug ? false : $cacheDir,
            'debug'            => $debug,
            'strict_variables' => $debug,
            'auto_reload'      => $debug,
            'optimizations'    => $config['optimizations'] ?? TwigOptimizer::OPTIMIZE_ALL,
            'autoescape'       => $config['autoescape'] ?? 'html',
        ]);

        if (isset($config['timezone'])) {
            $timezone = $config['timezone'];
            if (! is_string($timezone)) {
                throw new Exception\InvalidConfigException('"timezone" configuration value must be a string');
            }
            try {
                $timezone = new DateTimeZone($timezone);
            } catch (\Exception $e) {
                throw new Exception\InvalidConfigException(sprintf('Unknown or invalid timezone: "%s"', $timezone));
            }
            $environment->getExtension(TwigExtensionCore::class)->setTimezone($timezone);
        }

        // Add expressive twig extension if requirements are met
        if ($container->has(TwigExtension::class)
            && $container->has(ServerUrlHelper::class)
            && $container->has(UrlHelper::class)
        ) {
            $environment->addExtension($container->get(TwigExtension::class));
        }

        // Add debug extension
        if ($debug) {
            $environment->addExtension(new TwigExtensionDebug());
        }

        // Add user defined extensions
        $extensions = isset($config['extensions']) && is_array($config['extensions'])
            ? $config['extensions']
            : [];
        $this->injectExtensions($environment, $container, $extensions);

        // Add user defined runtime loaders
        $runtimeLoaders = isset($config['runtime_loaders']) && is_array($config['runtime_loaders'])
            ? $config['runtime_loaders']
            : [];
        $this->injectRuntimeLoaders($environment, $container, $runtimeLoaders);

        // Add template paths
        $allPaths = isset($config['paths']) && is_array($config['paths']) ? $config['paths'] : [];
        foreach ($allPaths as $namespace => $paths) {
            $namespace = is_numeric($namespace) ? null : $namespace;
            $namespace = $namespace ?: TwigLoader::MAIN_NAMESPACE;
            foreach ((array) $paths as $path) {
                $loader->addPath($path, $namespace);
            }
        }

        // Inject environment
        return $environment;
    }

    /**
     * Inject extensions into the TwigEnvironment instance.
     *
     * @throws Exception\InvalidExtensionException if any extension provided or
     *     retrieved does not implement TwigExtensionInterface.
     */
    private function injectExtensions(
        TwigEnvironment $environment,
        ContainerInterface $container,
        array $extensions
    ) : void {
        foreach ($extensions as $extension) {
            $extension = $this->loadExtension($extension, $container);

            if (! $environment->hasExtension(get_class($extension))) {
                $environment->addExtension($extension);
            }
        }
    }

    /**
     * Load an extension.
     *
     * If the extension is a string service name, retrieves it from the container.
     *
     * If the extension is not a TwigExtensionInterface, raises an exception.
     *
     * @param string|TwigExtensionInterface $extension
     * @throws Exception\InvalidExtensionException if the extension provided or
     *     retrieved does not implement TwigExtensionInterface.
     */
    private function loadExtension($extension, ContainerInterface $container) : TwigExtensionInterface
    {
        // Load the extension from the container if present
        if (is_string($extension) && $container->has($extension)) {
            $extension = $container->get($extension);
        }

        if (! $extension instanceof TwigExtensionInterface) {
            throw new Exception\InvalidExtensionException(sprintf(
                'Twig extension must be an instance of Twig_ExtensionInterface; "%s" given,',
                is_object($extension) ? get_class($extension) : gettype($extension)
            ));
        }

        return $extension;
    }

    /**
     * Inject Runtime Loaders into the TwigEnvironment instance.
     *
     * @throws Exception\InvalidRuntimeLoaderException if a given runtime loader
     *     or the service it represents is not a TwigRuntimeLoaderInterface instance.
     */
    private function injectRuntimeLoaders(
        TwigEnvironment $environment,
        ContainerInterface $container,
        array $runtimes
    ) : void {
        foreach ($runtimes as $runtimeLoader) {
            $runtimeLoader = $this->loadRuntimeLoader($runtimeLoader, $container);
            $environment->addRuntimeLoader($runtimeLoader);
        }
    }

    /**
     * @param string|TwigRuntimeLoaderInterface $runtimeLoader
     * @throws Exception\InvalidRuntimeLoaderException if a given $runtimeLoader
     *     or the service it represents is not a TwigRuntimeLoaderInterface instance.
     */
    private function loadRuntimeLoader($runtimeLoader, ContainerInterface $container) : TwigRuntimeLoaderInterface
    {
        // Load the runtime loader from the container
        if (is_string($runtimeLoader) && $container->has($runtimeLoader)) {
            $runtimeLoader = $container->get($runtimeLoader);
        }

        if (! $runtimeLoader instanceof TwigRuntimeLoaderInterface) {
            throw new Exception\InvalidRuntimeLoaderException(sprintf(
                'Twig runtime loader must be an instance of %s; "%s" given,',
                TwigRuntimeLoaderInterface::class,
                is_object($runtimeLoader) ? get_class($runtimeLoader) : gettype($runtimeLoader)
            ));
        }

        return $runtimeLoader;
    }
}
