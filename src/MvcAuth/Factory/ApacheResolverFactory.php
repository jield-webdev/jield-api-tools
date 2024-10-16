<?php

declare(strict_types=1);

namespace Jield\ApiTools\MvcAuth\Factory;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Psr\Container\ContainerInterface;

class ApacheResolverFactory implements FactoryInterface
{
    /**
     * Create and return an ApacheResolver instance.
     *
     * If appropriate configuration is not found, returns boolean false.
     *
     * @param string $requestedName
     * @param null|array $options
     * @return false|ApacheResolver
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        if (false === $container->has('config')) {
            return false;
        }

        $config = $container->get('config');

        if (! isset($config['api-tools-mvc-auth']['authentication']['http']['htpasswd'])) {
            return false;
        }

        $htpasswd = $config['api-tools-mvc-auth']['authentication']['http']['htpasswd'];

        return new ApacheResolver($htpasswd);
    }

    /**
     * Create and return an ApacheResolve instance (v2).
     *
     * Exists for backwards compatibility only; proxies to __invoke().
     *
     * @return false|ApacheResolver
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, ApacheResolver::class);
    }
}
