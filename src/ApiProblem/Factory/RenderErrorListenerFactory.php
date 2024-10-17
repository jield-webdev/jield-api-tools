<?php

declare(strict_types=1);

namespace Jield\ApiTools\ApiProblem\Factory;

use Psr\Container\ContainerInterface;
use Jield\ApiTools\ApiProblem\Listener\RenderErrorListener;

class RenderErrorListenerFactory
{
    /**
     * @return RenderErrorListener
     */
    public function __invoke(ContainerInterface $container)
    {
        $config            = $container->get('config');
        $displayExceptions = false;

        if (
            isset($config['view_manager'])
            && isset($config['view_manager']['display_exceptions'])
        ) {
            $displayExceptions = (bool) $config['view_manager']['display_exceptions'];
        }

        $listener = new RenderErrorListener();
        $listener->setDisplayExceptions($displayExceptions);

        return $listener;
    }
}
