<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-twigrenderer for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-twigrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Twig;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\LoaderInterface;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Twig\TwigExtension;

class TwigExtensionFunctionsRenderTest extends TestCase
{
    protected $templates;
    protected $twigLoader;
    protected $serverUrlHelper;
    protected $urlHelper;

    protected function setUp() : void
    {
        $this->twigLoader      = $this->prophesize(LoaderInterface::class);
        $this->serverUrlHelper = $this->prophesize(ServerUrlHelper::class);
        $this->urlHelper       = $this->prophesize(UrlHelper::class);

        $this->templates = [
            'template' => "{{ path('route') }}",
        ];
    }

    /**
     * @param string $assetsUrl
     * @param string $assetsVersion
     * @return Environment
     */
    protected function getTwigEnvironment($assetsUrl = '', $assetsVersion = '')
    {
        $loader = new ArrayLoader($this->templates);

        $twig = new Environment($loader, ['debug' => true, 'cache' => false]);
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

        $this->assertInstanceOf(Environment::class, $twig);
    }

    /**
     * @dataProvider renderPathProvider
     */
    public function testPathFunction(
        string $template,
        string $route,
        array $routeParams,
        array $queryParams,
        ?string $fragment,
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
     */
    public function testUrlFunction(
        string $template,
        string $route,
        array $routeParams,
        array $queryParams,
        ?string $fragment,
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
