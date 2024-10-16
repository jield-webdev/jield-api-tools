<?php

declare(strict_types=1);

namespace Jield\ApiTools\ContentNegotiation\Factory;

use Interop\Container\ContainerInterface;
use Jield\ApiTools\ContentNegotiation\ContentNegotiationOptions;
use Jield\ApiTools\ContentNegotiation\ContentTypeFilterListener;

class ContentTypeFilterListenerFactory
{
    /**
     * @return ContentTypeFilterListener
     */
    public function __invoke(ContainerInterface $container)
    {
        $listener = new ContentTypeFilterListener();

        $options = $container->get(ContentNegotiationOptions::class);
        $listener->setConfig($options->getContentTypeWhitelist());

        return $listener;
    }
}
