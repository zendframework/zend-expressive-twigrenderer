<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-twigrenderer for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-twigrenderer/blob/master/LICENSE.md New BSD License
 */

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
     * @var string
     */
    private $assetsUrl;

    /**
     * @var string
     */
    private $assetsVersion;

    /**
     * @var array
     */
    private $globals;

    /**
     * @param ServerUrlHelper $serverUrlHelper
     * @param UrlHelper       $urlHelper
     * @param string          $assetsUrl
     * @param string          $assetsVersion
     * @param array           $globals
     */
    public function __construct(
        ServerUrlHelper $serverUrlHelper,
        UrlHelper $urlHelper,
        $assetsUrl,
        $assetsVersion,
        array $globals = []
    ) {
        $this->serverUrlHelper = $serverUrlHelper;
        $this->urlHelper       = $urlHelper;
        $this->assetsUrl       = $assetsUrl;
        $this->assetsVersion   = $assetsVersion;
        $this->globals         = $globals;
    }

    /**
     * @return array
     */
    public function getGlobals()
    {
        return $this->globals;
    }

    /**
     * @return Twig_SimpleFunction[]
     */
    public function getFunctions()
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
     * @param null|string $route
     * @param array       $routeParams
     * @param array       $queryParams
     * @param null|string $fragmentIdentifier
     * @param array       $options      Can have the following keys:
     *                                  - reuse_result_params (bool): indicates if the current
     *                                  RouteResult parameters will be used, defaults to true
     * @return string
     */
    public function renderUri(
        $route = null,
        $routeParams = [],
        $queryParams = [],
        $fragmentIdentifier = null,
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
     * @param null|string $route
     * @param array       $routeParams
     * @param array       $queryParams
     * @param null|string $fragmentIdentifier
     * @param array       $options      Can have the following keys:
     *                                  - reuse_result_params (bool): indicates if the current
     *                                  RouteResult parameters will be used, defaults to true
     * @return string
     */
    public function renderUrl(
        $route = null,
        $routeParams = [],
        $queryParams = [],
        $fragmentIdentifier = null,
        array $options = []
    ) {
        return $this->serverUrlHelper->generate(
            $this->urlHelper->generate($route, $routeParams, $queryParams, $fragmentIdentifier, $options)
        );
    }

    /**
     * Render absolute url from a path
     *
     * Usage: {{ absolute_url('path/to/something') }}
     * Generates: http://example.com/path/to/something
     *
     * @param null|string $path
     * @return string
     */
    public function renderUrlFromPath($path = null)
    {
        return $this->serverUrlHelper->generate($path);
    }

    /**
     * Render asset url, optionally versioned
     *
     * Usage: {{ asset('path/to/asset/name.ext', version=3) }}
     * Generates: path/to/asset/name.ext?v=3
     *
     * @param string $path
     * @param null|string $version
     * @return string
     */
    public function renderAssetUrl($path, $version = null)
    {
        $assetsVersion = $version !== null && $version !== '' ? $version : $this->assetsVersion;

        // One more time, in case $this->assetsVersion was null or an empty string
        $assetsVersion = $assetsVersion !== null && $assetsVersion !== '' ? '?v=' . $assetsVersion : '';

        return $this->assetsUrl . $path . $assetsVersion;
    }
}
