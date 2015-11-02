<?php

namespace ZendTest\Expressive\Twig\TestAsset\Extension;

class FooTwigExtension extends \Twig_Extension
{
    public function getName()
    {
        return 'foo-twig-extension';
    }
}
