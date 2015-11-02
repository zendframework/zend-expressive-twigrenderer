<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       https://github.com/zendframework/zend-expressive for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Twig;

use Interop\Container\ContainerInterface;
use Twig_Environment as TwigEnvironment;
use Twig_Extension_Debug as TwigExtensionDebug;
use Twig_ExtensionInterface;
use Twig_Loader_Filesystem as TwigLoader;
use Zend\Expressive\Router\RouterInterface;

/**
 * Create and return a Twig template instance.
 *
 * Optionally uses the service 'config', which should return an array. This
 * factory consumes the following structure:
 *
 * <code>
 * 'debug' => boolean,
 * 'templates' => [
 *     'cache_dir' => 'path to cached templates',
 *     'assets_url' => 'base URL for assets',
 *     'assets_version' => 'base version for assets',
 *     'extension' => 'file extension used by templates; defaults to html.twig',
 *     'paths' => [
 *         // namespace / path pairs
 *         //
 *         // Numeric namespaces imply the default/main namespace. Paths may be
 *         // strings or arrays of string paths to associate with the namespace.
 *     ],
 * ]
 * </code>
 */
class TwigRendererFactory
{
    /**
     * @param ContainerInterface $container
     * @return TwigRenderer
     */
    public function __invoke(ContainerInterface $container)
    {
        $config   = $container->has('config') ? $container->get('config') : [];
        $debug    = array_key_exists('debug', $config) ? (bool) $config['debug'] : false;
        $config   = isset($config['templates']) ? $config['templates'] : [];
        $cacheDir = isset($config['cache_dir']) ? $config['cache_dir'] : false;

        // Create the engine instance
        $loader      = new TwigLoader();
        $environment = new TwigEnvironment($loader, [
            'cache'            => $debug ? false : $cacheDir,
            'debug'            => $debug,
            'strict_variables' => $debug,
            'auto_reload'      => $debug
        ]);

        // Add extensions
        if ($container->has(RouterInterface::class)) {
            $environment->addExtension(new TwigExtension(
                $container->get(RouterInterface::class),
                isset($config['assets_url']) ? $config['assets_url'] : '',
                isset($config['assets_version']) ? $config['assets_version'] : ''
            ));
        }

        if ($debug) {
            $environment->addExtension(new TwigExtensionDebug());
        }

        // Add user defined extensions
        $extensions = isset($config['helpers']['twig']) && is_array($config['helpers']['twig'])
            ? $config['helpers']['twig'] : [];

        if (!empty($extensions)) {
            $this->injectHelpers($environment, $container, $extensions);
        }

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
     * Inject helpers into the TwigEnvironment instance.
     *
     * @param TwigEnvironment $environment
     * @param ContainerInterface $container
     * @param array $extensions
     */
    private function injectHelpers(TwigEnvironment $environment, ContainerInterface $container, array $extensions)
    {
        foreach ($extensions as $extension) {
            // Load the extension from the container
            if (is_string($extension) && $container->has($extension)) {
                $extension = $container->get($extension);
            }

            if (!$extension instanceof Twig_ExtensionInterface) {
                throw new \Exception(sprintf(
                    'Twig extension must be an instance of Twig_ExtensionInterface "%s" given,',
                    is_object($extension) ? get_class($extension) : gettype($extension)
                ));
            }

            if ($environment->hasExtension($extension->getName())) {
                continue;
            }

            $environment->addExtension($extension);
        }
    }
}
