<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal\Factory;


use Interop\Container\ContainerInterface;
use Jield\ApiTools\Hal\Extractor\LinkCollectionExtractor;
use Jield\ApiTools\Hal\Extractor\LinkExtractor;
use Laminas\ServiceManager\ServiceLocatorInterface;

class LinkCollectionExtractorFactory
{
    /**
     * @param ContainerInterface|ServiceLocatorInterface $container
     * @return LinkCollectionExtractor
     */
    public function __invoke($container)
    {
        return new LinkCollectionExtractor($container->get(LinkExtractor::class));
    }
}
