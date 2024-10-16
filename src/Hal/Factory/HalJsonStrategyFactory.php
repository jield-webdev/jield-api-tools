<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal\Factory;

// phpcs:ignore WebimpressCodingStandard.PHP.CorrectClassNameCase.Invalid
use Interop\Container\ContainerInterface;
use Jield\ApiTools\Hal\View\HalJsonRenderer;
use Jield\ApiTools\Hal\View\HalJsonStrategy;

use function assert;

class HalJsonStrategyFactory
{
    /**
     * @return HalJsonStrategy
     */
    public function __invoke(ContainerInterface $container)
    {
        $renderer = $container->get('Jield\ApiTools\Hal\JsonRenderer');
        assert($renderer instanceof HalJsonRenderer);

        return new HalJsonStrategy($renderer);
    }
}
