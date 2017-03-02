<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-twigrenderer for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-twigrenderer/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Twig;

use PHPUnit\Framework\TestCase;
use Twig_Environment;
use Twig_Loader_Array;
use Twig_LoaderInterface;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Twig\TwigExtension;

class TwigExtensionFunctionsRenderTest extends TestCase
{
    protected $templates;
    protected $twigLoader;
    protected $serverUrlHelper;
    protected $urlHelper;

    protected function setUp()
    {
        $this->twigLoader      = $this->prophesize(Twig_LoaderInterface::class);
        $this->serverUrlHelper = $this->prophesize(ServerUrlHelper::class);
        $this->urlHelper       = $this->prophesize(UrlHelper::class);

        $this->templates = [
            'template' => "{{ path('route') }}",
        ];
    }

    /**
     * @param string $assetsUrl
     * @param string $assetsVersion
     * @return Twig_Environment
     */
    protected function getTwigEnvironment($assetsUrl = '', $assetsVersion = '')
    {
        $loader = new Twig_Loader_Array($this->templates);

        $twig = new Twig_Environment($loader, ['debug' => true, 'cache' => false]);
        $twig->addExtension(new TwigExtension(
            $this->serverUrlHelper->reveal(),
            $this->urlHelper->reveal(),
            $assetsUrl,
            $assetsVersion
        ));

        return $twig;
    }

    public function testEnvironmentCreation()
    {
        $twig = $this->getTwigEnvironment();

        $this->assertInstanceOf(Twig_Environment::class, $twig);
    }

    /**
     * @dataProvider renderPathProvider
     *
     * @param string $template
     * @param string $route
     * @param array $routeParams
     * @param array $queryParams
     * @param null|string $fragment
     * @param array $options
     */
    public function testPathFunction(
        $template,
        $route,
        array $routeParams,
        array $queryParams,
        $fragment,
        array $options
    ) {
        $this->templates = [
            'template' => $template,
        ];

        $this->urlHelper->generate($route, $routeParams, $queryParams, $fragment, $options)->willReturn('PATH');
        $twig = $this->getTwigEnvironment();

        $this->assertSame('PATH', $twig->render('template'));
    }

    public function renderPathProvider()
    {
        return [
            'path'                => [
                "{{ path('route', {'foo': 'bar'}) }}",
                'route',
                ['foo' => 'bar'],
                [],
                null,
                [],
            ],
            'path-query'          => [
                "{{ path('path-query', {'id': '3'}, {'foo': 'bar'}) }}",
                'path-query',
                ['id' => 3],
                ['foo' => 'bar'],
                null,
                [],
            ],
            'path-query-fragment' => [
                "{{ path('path-query-fragment', {'foo': 'bar'}, {'qux': 'quux'}, 'corge') }}",
                'path-query-fragment',
                ['foo' => 'bar'],
                ['qux' => 'quux'],
                'corge',
                [],
            ],
            'path-reuse-result' => [
                "{{ path('path-query-fragment', {}, {}, null, {'reuse_result_params': true}) }}",
                'path-query-fragment',
                [],
                [],
                null,
                ['reuse_result_params' => true],
            ],
            'path-dont-reuse-result' => [
                "{{ path('path-query-fragment', {}, {}, null, {'reuse_result_params': false}) }}",
                'path-query-fragment',
                [],
                [],
                null,
                ['reuse_result_params' => false],
            ],
        ];
    }

    /**
     * @dataProvider renderUrlProvider
     *
     * @param string $template
     * @param string $route
     * @param array $routeParams
     * @param array $queryParams
     * @param null|string $fragment
     * @param array $options
     */
    public function testUrlFunction(
        $template,
        $route,
        array $routeParams,
        array $queryParams,
        $fragment,
        array $options
    ) {
        $this->templates = [
            'template' => $template,
        ];

        $this->urlHelper->generate($route, $routeParams, $queryParams, $fragment, $options)->willReturn('PATH');
        $this->serverUrlHelper->generate('PATH')->willReturn('HOST/PATH');
        $twig = $this->getTwigEnvironment();

        $this->assertSame('HOST/PATH', $twig->render('template'));
    }

    public function renderUrlProvider()
    {
        return [
            'path'                => [
                "{{ url('route', {'foo': 'bar'}) }}",
                'route',
                ['foo' => 'bar'],
                [],
                null,
                [],
            ],
            'path-query'          => [
                "{{ url('path-query', {'id': '3'}, {'foo': 'bar'}) }}",
                'path-query',
                ['id' => 3],
                ['foo' => 'bar'],
                null,
                [],
            ],
            'path-query-fragment' => [
                "{{ url('path-query-fragment', {'foo': 'bar'}, {'qux': 'quux'}, 'corge') }}",
                'path-query-fragment',
                ['foo' => 'bar'],
                ['qux' => 'quux'],
                'corge',
                [],
            ],
            'path-reuse-result' => [
                "{{ url('path-query-fragment', {}, {}, null, {'reuse_result_params': true}) }}",
                'path-query-fragment',
                [],
                [],
                null,
                ['reuse_result_params' => true],
            ],
            'path-dont-reuse-result' => [
                "{{ url('path-query-fragment', {}, {}, null, {'reuse_result_params': false}) }}",
                'path-query-fragment',
                [],
                [],
                null,
                ['reuse_result_params' => false],
            ],
        ];
    }

    public function testAbsoluteUrlFunction()
    {
        $this->templates = [
            'template' => "{{ absolute_url('path/to/something') }}",
        ];

        $this->serverUrlHelper->generate('path/to/something')->willReturn('HOST/PATH');
        $twig = $this->getTwigEnvironment();

        $this->assertSame('HOST/PATH', $twig->render('template'));
    }

    public function testAssetFunction()
    {
        $this->templates = [
            'template' => "{{ asset('path/to/asset/name.ext') }}",
        ];

        $twig = $this->getTwigEnvironment();

        $this->assertSame('path/to/asset/name.ext', $twig->render('template'));
    }

    public function testVersionedAssetFunction()
    {
        $this->templates = [
            'template' => "{{ asset('path/to/asset/name.ext', version=3) }}",
        ];

        $twig = $this->getTwigEnvironment();

        $this->assertSame('path/to/asset/name.ext?v=3', $twig->render('template'));
    }
}
