<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-twigrenderer for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-twigrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Zend\Expressive\Twig;

use Twig_Extension;
use Twig_SimpleFunction;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;

/**
 * Twig extension for rendering URLs and assets URLs from Expressive.
 *
 * @author Geert Eltink (https://xtreamwayz.com)
 */
class TwigExtension extends Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    /**
     * @var ServerUrlHelper
     */
    private $serverUrlHelper;

    /**
     * @var UrlHelper
     */
    private $urlHelper;

    /**
     * @var null|string
     */
    private $assetsUrl;

    /**
     * @var null|string|int
     */
    private $assetsVersion;

    /**
     * @var array
     */
    private $globals;

    public function __construct(
        ServerUrlHelper $serverUrlHelper,
        UrlHelper $urlHelper,
        ?string $assetsUrl,
        $assetsVersion,
        array $globals = []
    ) {
        $this->serverUrlHelper = $serverUrlHelper;
        $this->urlHelper       = $urlHelper;
        $this->assetsUrl       = $assetsUrl;
        $this->assetsVersion   = $assetsVersion;
        $this->globals         = $globals;
    }

    public function getGlobals() : array
    {
        return $this->globals;
    }

    /**
     * @return Twig_SimpleFunction[]
     */
    public function getFunctions() : array
    {
        return [
            new Twig_SimpleFunction('absolute_url', [$this, 'renderUrlFromPath']),
            new Twig_SimpleFunction('asset', [$this, 'renderAssetUrl']),
            new Twig_SimpleFunction('path', [$this, 'renderUri']),
            new Twig_SimpleFunction('url', [$this, 'renderUrl']),
        ];
    }

    /**
     * Render relative uri for a given named route
     *
     * Usage: {{ path('article_show', {'id': '3'}) }}
     * Generates: /article/3
     *
     * Usage: {{ path('article_show', {'id': '3'}, {'foo': 'bar'}, 'fragment') }}
     * Generates: /article/3?foo=bar#fragment
     *
     * @param array $options Can have the following keys:
     *     - reuse_result_params (bool): indicates if the current
     *       RouteResult parameters will be used, defaults to true
     */
    public function renderUri(
        ?string $route = null,
        array $routeParams = [],
        array $queryParams = [],
        ?string $fragmentIdentifier = null,
        array $options = []
    ) {
        return $this->urlHelper->generate($route, $routeParams, $queryParams, $fragmentIdentifier, $options);
    }

    /**
     * Render absolute url for a given named route
     *
     * Usage: {{ url('article_show', {'slug': 'article.slug'}) }}
     * Generates: http://example.com/article/article.slug
     *
     * Usage: {{ url('article_show', {'id': '3'}, {'foo': 'bar'}, 'fragment') }}
     * Generates: http://example.com/article/3?foo=bar#fragment
     *
     * @param array $options Can have the following keys:
     *     - reuse_result_params (bool): indicates if the current
     *       RouteResult parameters will be used, defaults to true
     */
    public function renderUrl(
        ?string $route = null,
        array $routeParams = [],
        array $queryParams = [],
        ?string $fragmentIdentifier = null,
        array $options = []
    ) {
        return $this->serverUrlHelper->generate(
            $this->renderUri($route, $routeParams, $queryParams, $fragmentIdentifier, $options)
        );
    }

    /**
     * Render absolute url from a path
     *
     * Usage: {{ absolute_url('path/to/something') }}
     * Generates: http://example.com/path/to/something
     */
    public function renderUrlFromPath(string $path = null) : string
    {
        return $this->serverUrlHelper->generate($path);
    }

    /**
     * Render asset url, optionally versioned
     *
     * Usage: {{ asset('path/to/asset/name.ext', version=3) }}
     * Generates: path/to/asset/name.ext?v=3
     */
    public function renderAssetUrl(string $path, string $version = null) : string
    {
        $assetsVersion = $version !== null && $version !== '' ? $version : $this->assetsVersion;

        // One more time, in case $this->assetsVersion was null or an empty string
        $assetsVersion = $assetsVersion !== null && $assetsVersion !== '' ? '?v=' . $assetsVersion : '';

        return $this->assetsUrl . $path . $assetsVersion;
    }
}
