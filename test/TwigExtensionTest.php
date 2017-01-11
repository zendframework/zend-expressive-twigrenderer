<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @see       https://github.com/zendframework/zend-expressive for the canonical source repository
 * @copyright Copyright (c) 2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Expressive\Twig;

use PHPUnit_Framework_TestCase as TestCase;
use Twig_SimpleFunction as SimpleFunction;
use Zend\Expressive\Helper\ServerUrlHelper;
use Zend\Expressive\Helper\UrlHelper;
use Zend\Expressive\Twig\TwigExtension;

class TwigExtensionTest extends TestCase
{
    public function setUp()
    {
        $this->serverUrlHelper = $this->prophesize(ServerUrlHelper::class);
        $this->urlHelper = $this->prophesize(UrlHelper::class);
    }

    public function createExtension($assetsUrl, $assetsVersion)
    {
        return new TwigExtension(
            $this->serverUrlHelper->reveal(),
            $this->urlHelper->reveal(),
            $assetsUrl,
            $assetsVersion
        );
    }

    public function findFunction($name, array $functions)
    {
        foreach ($functions as $function) {
            $this->assertInstanceOf(SimpleFunction::class, $function);
            if ($function->getName() === $name) {
                return $function;
            }
        }

        return false;
    }

    public function assertFunctionExists($name, array $functions, $message = null)
    {
        $message  = $message ?: sprintf('Failed to identify function by name %s', $name);
        $function = $this->findFunction($name, $functions);
        $this->assertInstanceOf(SimpleFunction::class, $function, $message);
    }

    public function testRegistersTwigFunctions()
    {
        $extension = $this->createExtension('', '');
        $functions = $extension->getFunctions();
        $this->assertFunctionExists('path', $functions);
        $this->assertFunctionExists('url', $functions);
        $this->assertFunctionExists('absolute_url', $functions);
        $this->assertFunctionExists('asset', $functions);
    }

    public function testMapsTwigFunctionsToExpectedMethods()
    {
        $extension = $this->createExtension('', '');
        $functions = $extension->getFunctions();
        $this->assertSame(
            [$extension, 'renderUri'],
            $this->findFunction('path', $functions)->getCallable(),
            'Received different path function than expected'
        );
        $this->assertSame(
            [$extension, 'renderUrl'],
            $this->findFunction('url', $functions)->getCallable(),
            'Received different url function than expected'
        );
        $this->assertSame(
            [$extension, 'renderUrlFromPath'],
            $this->findFunction('absolute_url', $functions)->getCallable(),
            'Received different path function than expected'
        );
        $this->assertSame(
            [$extension, 'renderAssetUrl'],
            $this->findFunction('asset', $functions)->getCallable(),
            'Received different asset function than expected'
        );
    }

    public function testRenderUriDelegatesToComposedUrlHelper()
    {
        $this->urlHelper->generate('foo', ['id' => 1])->willReturn('URL');
        $extension = $this->createExtension('', '');
        $this->assertSame('URL', $extension->renderUri('foo', ['id' => 1]));
    }

    public function testRenderUrlDelegatesToComposedUrlHelperAndServerUrlHelper()
    {
        $this->urlHelper->generate('foo', ['id' => 1])->willReturn('PATH');
        $this->serverUrlHelper->generate('PATH')->willReturn('HOST/PATH');
        $extension = $this->createExtension('', '');
        $this->assertSame('HOST/PATH', $extension->renderUrl('foo', ['id' => 1]));
    }

    public function testRenderUrlFromPathDelegatesToComposedServerUrlHelper()
    {
        $this->serverUrlHelper->generate('PATH')->willReturn('HOST/PATH');
        $extension = $this->createExtension('', '');
        $this->assertSame('HOST/PATH', $extension->renderUrlFromPath('PATH'));
    }

    public function testRenderAssetUrlUsesComposedAssetUrlAndVersionToGenerateUrl()
    {
        $extension = $this->createExtension('https://images.example.com/', 'XYZ');
        $this->assertSame('https://images.example.com/foo.png?v=XYZ', $extension->renderAssetUrl('foo.png'));
    }

    public function testRenderAssetUrlUsesProvidedVersionToGenerateUrl()
    {
        $extension = $this->createExtension('https://images.example.com/', 'XYZ');
        $this->assertSame(
            'https://images.example.com/foo.png?v=ABC',
            $extension->renderAssetUrl('foo.png', 'ABC')
        );
    }

    public function emptyAssetVersions()
    {
        return [
            'null'         => [null],
            'empty-string' => [''],
        ];
    }

    /**
     * @dataProvider emptyAssetVersions
     */
    public function testRenderAssetUrlWithoutProvidedVersion($emptyValue)
    {
        $extension = $this->createExtension('https://images.example.com/', $emptyValue);
        $this->assertSame(
            'https://images.example.com/foo.png',
            $extension->renderAssetUrl('foo.png')
        );
    }

    public function zeroAssetVersions()
    {
        return [
            'zero'        => [0],
            'zero-string' => ['0'],
        ];
    }

    /**
     * @dataProvider zeroAssetVersions
     */
    public function testRendersZeroVersionAssetUrl($zeroValue)
    {
        $extension = $this->createExtension('https://images.example.com/', $zeroValue);
        $this->assertSame(
            'https://images.example.com/foo.png?v=0',
            $extension->renderAssetUrl('foo.png')
        );
    }
}
