<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-twigrenderer for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-twigrenderer/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Twig;

use LogicException;
use Twig_Environment as TwigEnvironment;
use Twig_Loader_Filesystem as TwigFilesystem;
use Zend\Expressive\Template\ArrayParametersTrait;
use Zend\Expressive\Template\DefaultParamsTrait;
use Zend\Expressive\Template\TemplatePath;
use Zend\Expressive\Template\TemplateRendererInterface;

/**
 * Template implementation bridging twig/twig
 */
class TwigRenderer implements TemplateRendererInterface
{
    use ArrayParametersTrait;
    use DefaultParamsTrait;

    /**
     * @var string
     */
    private $suffix;

    /**
     * @var TwigFilesystem
     */
    protected $twigLoader;

    /**
     * @var TwigEnvironment
     */
    protected $template;

    /**
     * @param TwigEnvironment $template
     * @param string          $suffix
     */
    public function __construct(TwigEnvironment $template = null, $suffix = 'html')
    {
        if (null === $template) {
            $template = $this->createTemplate($this->getDefaultLoader());
        }

        try {
            $loader = $template->getLoader();
        } catch (LogicException $e) {
            $loader = $this->getDefaultLoader();
            $template->setLoader($loader);
        }

        $this->template   = $template;
        $this->twigLoader = $loader;
        $this->suffix     = is_string($suffix) ? $suffix : 'html';
    }

    /**
     * Create a default Twig environment
     *
     * @param TwigFilesystem $loader
     * @return TwigEnvironment
     */
    private function createTemplate(TwigFilesystem $loader)
    {
        return new TwigEnvironment($loader);
    }

    /**
     * Get the default loader for template
     *
     * @return TwigFilesystem
     */
    private function getDefaultLoader()
    {
        return new TwigFilesystem();
    }

    /**
     * Render
     *
     * @param string $name
     * @param array|object $params
     * @return string
     * @throws \Zend\Expressive\Template\Exception\InvalidArgumentException for non-array, non-object parameters.
     */
    public function render($name, $params = [])
    {
        // Merge parameters based on requested template name
        $params = $this->mergeParams($name, $this->normalizeParams($params));

        $name   = $this->normalizeTemplate($name);

        // Merge parameters based on normalized template name
        $params = $this->mergeParams($name, $params);

        return $this->template->render($name, $params);
    }

    /**
     * Add a path for template
     *
     * @param string $path
     * @param null|string $namespace
     * @return void
     */
    public function addPath($path, $namespace = null)
    {
        $namespace = $namespace ?: TwigFilesystem::MAIN_NAMESPACE;
        $this->twigLoader->addPath($path, $namespace);
    }

    /**
     * Get the template directories
     *
     * @return TemplatePath[]
     */
    public function getPaths()
    {
        $paths = [];
        foreach ($this->twigLoader->getNamespaces() as $namespace) {
            $name = ($namespace !== TwigFilesystem::MAIN_NAMESPACE) ? $namespace : null;

            foreach ($this->twigLoader->getPaths($namespace) as $path) {
                $paths[] = new TemplatePath($path, $name);
            }
        }
        return $paths;
    }

    /**
     * Normalize namespaced template.
     *
     * Normalizes templates in the format "namespace::template" to
     * "@namespace/template".
     *
     * @param string $template
     * @return string
     */
    public function normalizeTemplate($template)
    {
        $template = preg_replace('#^([^:]+)::(.*)$#', '@$1/$2', $template);
        if (! preg_match('#\.[a-z]+$#i', $template)) {
            return sprintf('%s.%s', $template, $this->suffix);
        }

        return $template;
    }
}
