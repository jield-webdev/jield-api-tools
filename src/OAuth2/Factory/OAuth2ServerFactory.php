<?php

declare(strict_types=1);

namespace Jield\ApiTools\OAuth2\Factory;

use Laminas\ServiceManager\ServiceLocatorInterface;
use Psr\Container\ContainerInterface;

class OAuth2ServerFactory
{
    /**
     * @return OAuth2ServerInstanceFactory
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get('config');
        $config = $config['api-tools-oauth2'] ?? [];
        return new OAuth2ServerInstanceFactory($config, $container);
    }

    /**
     * Provided for backwards compatibility; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return OAuth2ServerInstanceFactory
     */
    public function createService($container)
    {
        return $this($container);
    }
}
