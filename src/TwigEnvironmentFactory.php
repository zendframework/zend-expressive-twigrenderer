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
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Extension\CoreExtension;
use Twig\Extension\DebugExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\FilesystemLoader;
use Twig\NodeVisitor\OptimizerNodeVisitor;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
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
 *     'auto_reload' => true, // recompile the template whenever the source code changes
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
     * @param ContainerInterface $container
     *
     * @return Environment
     * @throws LoaderError
     */
    public function __invoke(ContainerInterface $container) : Environment
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

        // Create the engine instance
        $loader      = new FilesystemLoader();
        $environment = new Environment($loader, [
            'cache'            => $config['cache_dir'] ?? false,
            'debug'            => $config['debug'] ?? $debug,
            'strict_variables' => $config['strict_variables'] ?? $debug,
            'auto_reload'      => $config['auto_reload'] ?? $debug,
            'optimizations'    => $config['optimizations'] ?? OptimizerNodeVisitor::OPTIMIZE_ALL,
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
            $environment->getExtension(CoreExtension::class)->setTimezone($timezone);
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
            $environment->addExtension(new DebugExtension());
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
            $namespace = $namespace ?: FilesystemLoader::MAIN_NAMESPACE;
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
     * @param Environment        $environment
     * @param ContainerInterface $container
     * @param array              $extensions
     */
    private function injectExtensions(
        Environment $environment,
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
     * If the extension is not an ExtensionInterface, raises an exception.
     *
     * @param string|ExtensionInterface $extension
     *
     * @param ContainerInterface        $container
     *
     * @return ExtensionInterface
     */
    private function loadExtension($extension, ContainerInterface $container): ExtensionInterface
    {
        // Load the extension from the container if present
        if (is_string($extension) && $container->has($extension)) {
            $extension = $container->get($extension);
        }

        if (! $extension instanceof ExtensionInterface) {
            throw new Exception\InvalidExtensionException(sprintf(
                'Twig extension must be an instance of %s; "%s" given,',
                ExtensionInterface::class,
                is_object($extension) ? get_class($extension) : gettype($extension)
            ));
        }

        return $extension;
    }

    /**
     * Inject Runtime Loaders into the TwigEnvironment instance.
     *
     * @param Environment        $environment
     * @param ContainerInterface $container
     * @param array              $runtimes
     */
    private function injectRuntimeLoaders(
        Environment $environment,
        ContainerInterface $container,
        array $runtimes
    ) : void {
        foreach ($runtimes as $runtimeLoader) {
            $runtimeLoader = $this->loadRuntimeLoader($runtimeLoader, $container);
            $environment->addRuntimeLoader($runtimeLoader);
        }
    }

    /**
     * @param string|RuntimeLoaderInterface $runtimeLoader
     *
     * @param ContainerInterface            $container
     *
     * @return RuntimeLoaderInterface
     */
    private function loadRuntimeLoader($runtimeLoader, ContainerInterface $container): RuntimeLoaderInterface
    {
        // Load the runtime loader from the container
        if (is_string($runtimeLoader) && $container->has($runtimeLoader)) {
            $runtimeLoader = $container->get($runtimeLoader);
        }

        if (! $runtimeLoader instanceof RuntimeLoaderInterface) {
            throw new Exception\InvalidRuntimeLoaderException(sprintf(
                'Twig runtime loader must be an instance of %s; "%s" given,',
                RuntimeLoaderInterface::class,
                is_object($runtimeLoader) ? get_class($runtimeLoader) : gettype($runtimeLoader)
            ));
        }

        return $runtimeLoader;
    }
}
