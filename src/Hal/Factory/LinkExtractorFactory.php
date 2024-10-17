<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal\Factory;


use Interop\Container\ContainerInterface;
use Jield\ApiTools\Hal\Extractor\LinkExtractor;
use Jield\ApiTools\Hal\Link\LinkUrlBuilder;
use Laminas\ServiceManager\ServiceLocatorInterface;

class LinkExtractorFactory
{
    /**
     * @param ContainerInterface|ServiceLocatorInterface $container
     * @return LinkExtractor
     */
    public function __invoke($container)
    {
        return new LinkExtractor($container->get(LinkUrlBuilder::class));
    }
}
