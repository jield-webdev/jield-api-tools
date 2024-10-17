<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal\Factory;


use Jield\ApiTools\Hal\View\HalJsonRenderer;
use Jield\ApiTools\Hal\View\HalJsonStrategy;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

class HalJsonStrategyFactory
{
    /**
     * @return HalJsonStrategy
     */
    public function __invoke(ContainerInterface $container)
    {
        $renderer = $container->get(HalJsonRenderer::class);
        Assert::isInstanceOf($renderer, HalJsonRenderer::class);

        return new HalJsonStrategy($renderer);
    }
}
