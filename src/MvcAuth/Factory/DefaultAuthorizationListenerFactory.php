<?php

declare(strict_types=1);

namespace Jield\ApiTools\MvcAuth\Factory;

use Jield\ApiTools\MvcAuth\Authorization\AuthorizationInterface;
use Jield\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use function sprintf;

/**
 * Factory for creating the DefaultAuthorizationListener from configuration.
 */
class DefaultAuthorizationListenerFactory implements FactoryInterface
{
    /**
     * Create and return the default authorization listener.
     *
     * @param string $requestedName
     * @param null|array $options
     * @return DefaultAuthorizationListener
     * @throws ServiceNotCreatedException If the AuthorizationInterface service is missing.
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        if (!$container->has(AuthorizationInterface::class)) {
            throw new ServiceNotCreatedException(sprintf(
                'Cannot create %s service; no %s service available!',
                DefaultAuthorizationListener::class,
                AuthorizationInterface::class
            ));
        }

        $authorization = $container->get(AuthorizationInterface::class);

        return new DefaultAuthorizationListener($authorization);
    }

}
