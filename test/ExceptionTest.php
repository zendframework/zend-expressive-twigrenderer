<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-twigrenderer for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-twigrenderer/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace ZendTest\Expressive\Twig;

use Generator;
use PHPUnit\Framework\TestCase;
use Zend\Expressive\Template\Exception\ExceptionInterface as TemplateExceptionInterface;
use Zend\Expressive\Twig\Exception\ExceptionInterface;

use function basename;
use function glob;
use function is_a;
use function strrpos;
use function substr;

class ExceptionTest extends TestCase
{
    public function testExceptionInterfaceExtendsTemplateExceptionInterface() : void
    {
        $this->assertTrue(is_a(ExceptionInterface::class, TemplateExceptionInterface::class, true));
    }

    public function exception() : Generator
    {
        $namespace = substr(ExceptionInterface::class, 0, strrpos(ExceptionInterface::class, '\\') + 1);

        $exceptions = glob(__DIR__ . '/../src/Exception/*.php');
        foreach ($exceptions as $exception) {
            $class = substr(basename($exception), 0, -4);

            yield $class => [$namespace . $class];
        }
    }

    /**
     * @dataProvider exception
     */
    public function testExceptionIsInstanceOfExceptionInterface(string $exception) : void
    {
        $this->assertContains('Exception', $exception);
        $this->assertTrue(is_a($exception, ExceptionInterface::class, true));
    }
}
