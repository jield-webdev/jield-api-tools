<?php

declare(strict_types=1);

namespace Jield\ApiTools\ContentNegotiation\Factory;

use Psr\Container\ContainerInterface;
use Jield\ApiTools\ContentNegotiation\AcceptFilterListener;
use Jield\ApiTools\ContentNegotiation\ContentNegotiationOptions;

class AcceptFilterListenerFactory
{
    /**
     * @return AcceptFilterListener
     */
    public function __invoke(ContainerInterface $container)
    {
        $listener = new AcceptFilterListener();

        $options = $container->get(ContentNegotiationOptions::class);
        $listener->setConfig($options->getAcceptWhitelist());

        return $listener;
    }
}
