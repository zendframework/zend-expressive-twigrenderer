<?php
/**
 * @see       https://github.com/zendframework/zend-expressive-twigrenderer for the canonical source repository
 * @copyright Copyright (c) 2015-2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-expressive-twigrenderer/blob/master/LICENSE.md New BSD License
 */

namespace Zend\Expressive\Twig\Exception;

use DomainException;
use Interop\Container\Exception\ContainerException;

class InvalidRuntimeLoaderException extends DomainException implements
    ContainerException,
    ExceptionInterface
{
}
