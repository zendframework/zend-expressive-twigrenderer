<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       https://github.com/zendframework/zend-expressive for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Twig;

use ArrayObject;
use Interop\Container\ContainerInterface;
use Twig_Environment as TwigEnvironment;
use Twig_Extension_Debug as TwigExtensionDebug;
use Twig_ExtensionInterface;
use Twig_Loader_Filesystem as TwigLoader;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;

/**
 * Create and return a Twig template instance.
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
 *     'globals' => [
 *         // Global variables passed to twig templates
 *         'ga_tracking' => 'UA-XXXXX-X'
 *     ],
 * ],
 * </code>
 *
 * Note: the various keys in the `twig` configuration key can occur in either
 * that location, or under `templates` (which was the behavior prior to 0.3.0);
 * the two arrays are merged by the factory.
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
        $config   = $container->has('config') ? $container->get('config') : [];

        if (! is_array($config) && ! $config instanceof ArrayObject) {
            throw new Exception\InvalidConfigException(sprintf(
                '"config" service must be an array or ArrayObject for the %s to be able to consume it; received %s',
                __CLASS__,
                (is_object($config) ? get_class($config) : gettype($config))
            ));
        }

        $debug    = array_key_exists('debug', $config) ? (bool) $config['debug'] : false;
        $config   = $this->mergeConfig($config);
        $cacheDir = isset($config['cache_dir']) ? $config['cache_dir'] : false;

        // Create the engine instance
        $loader      = new TwigLoader();
        $environment = new TwigEnvironment($loader, [
            'cache'            => $debug ? false : $cacheDir,
            'debug'            => $debug,
            'strict_variables' => $debug,
            'auto_reload'      => $debug
        ]);

        // Add expressive twig extension
        if ($container->has(ServerUrlHelper::class) && $container->has(UrlHelper::class)) {
            $environment->addExtension(new TwigExtension(
                $container->get(ServerUrlHelper::class),
                $container->get(UrlHelper::class),
                isset($config['assets_url']) ? $config['assets_url'] : '',
                isset($config['assets_version']) ? $config['assets_version'] : '',
                isset($config['globals']) ? $config['globals'] : []
            ));
        }

        // Add debug extension
        if ($debug) {
            $environment->addExtension(new TwigExtensionDebug());
        }

        // Add user defined extensions
        $extensions = (isset($config['extensions']) && is_array($config['extensions']))
            ? $config['extensions']
            : [];
        $this->injectExtensions($environment, $container, $extensions);

        // Inject environment
        $twig = new TwigRenderer($environment, isset($config['extension']) ? $config['extension'] : 'html.twig');

        // Add template paths
        $allPaths = isset($config['paths']) && is_array($config['paths']) ? $config['paths'] : [];
        foreach ($allPaths as $namespace => $paths) {
            $namespace = is_numeric($namespace) ? null : $namespace;
            foreach ((array) $paths as $path) {
                $twig->addPath($path, $namespace);
            }
        }

        return $twig;
    }

    /**
     * Inject extensions into the TwigEnvironment instance.
     *
     * @param TwigEnvironment $environment
     * @param ContainerInterface $container
     * @param array $extensions
     * @throws Exception\InvalidExtensionException
     */
    private function injectExtensions(TwigEnvironment $environment, ContainerInterface $container, array $extensions)
    {
        foreach ($extensions as $extension) {
            // Load the extension from the container
            if (is_string($extension) && $container->has($extension)) {
                $extension = $container->get($extension);
            }

            if (! $extension instanceof Twig_ExtensionInterface) {
                throw new Exception\InvalidExtensionException(sprintf(
                    'Twig extension must be an instance of Twig_ExtensionInterface; "%s" given,',
                    is_object($extension) ? get_class($extension) : gettype($extension)
                ));
            }

            if ($environment->hasExtension($extension->getName())) {
                continue;
            }

            $environment->addExtension($extension);
        }
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

        $expressiveConfig = (isset($config['templates']) && is_array($config['templates']))
            ? $config['templates']
            : [];
        $twigConfig = (isset($config['twig']) && is_array($config['twig']))
            ? $config['twig']
            : [];

        return array_replace_recursive($expressiveConfig, $twigConfig);
    }
}
