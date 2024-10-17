<?php

declare(strict_types=1);

namespace Jield\ApiTools\MvcAuth\Factory;

use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Storage\NonPersistent;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;

class AuthenticationServiceFactory implements FactoryInterface
{
    /**
     * Create and return an AuthenticationService instance.
     *
     * @param string $requestedName
     * @param null|array $options
     * @return AuthenticationService
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new AuthenticationService($container->get(NonPersistent::class));
    }
}
