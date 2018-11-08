<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-twigrenderer for the canonical source repository
 * @copyright Copyright (c) 2017-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-twigrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Twig;

use Twig_Environment;
use Twig\Environment;
use Zend\Expressive\Template\TemplateRendererInterface;

class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'templates'    => $this->getTemplates(),
        ];
    }

    public function getDependencies() : array
    {
        return [
            'aliases'   => [
                TemplateRendererInterface::class => TwigRenderer::class,
                Twig_Environment::class => Environment::class
            ],
            'factories' => [
                Environment::class => TwigEnvironmentFactory::class,
                TwigExtension::class    => TwigExtensionFactory::class,
                TwigRenderer::class     => TwigRendererFactory::class,
            ],
        ];
    }

    public function getTemplates() : array
    {
        return [
            'extension' => 'html.twig',
            'paths'     => [],
        ];
    }
}
