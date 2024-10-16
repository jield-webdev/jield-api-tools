<?php

declare(strict_types=1);

namespace Jield\ApiTools\MvcAuth\Factory;

use Jield\ApiTools\MvcAuth\Authorization\DefaultResourceResolverListener;
use Laminas\Http\Request;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Psr\Container\ContainerInterface;

/**
 * Factory for creating the DefaultResourceResolverListener from configuration.
 */
class DefaultResourceResolverListenerFactory implements FactoryInterface
{
    /** @var array<Request::METHOD_*, bool> */
    protected $httpMethods = [
        Request::METHOD_DELETE => true,
        Request::METHOD_GET    => true,
        Request::METHOD_PATCH  => true,
        Request::METHOD_POST   => true,
        Request::METHOD_PUT    => true,
    ];

    /**
     * Create and return a DefaultResourceResolverListener instance.
     *
     * @param string             $requestedName
     * @param null|array         $options
     * @return DefaultResourceResolverListener
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config = $container->has('config') ? $container->get('config') : [];

        return new DefaultResourceResolverListener(
            $this->getRestServicesFromConfig($config)
        );
    }

    /**
     * Generate the list of REST services for the listener
     *
     * Looks for api-tools-rest configuration, and creates a list of controller
     * service / identifier name pairs to pass to the listener.
     *
     * @param array $config
     * @return array
     */
    protected function getRestServicesFromConfig(array $config)
    {
        $restServices = [];
        if (! isset($config['api-tools-rest'])) {
            return $restServices;
        }

        foreach ($config['api-tools-rest'] as $controllerService => $restConfig) {
            if (! isset($restConfig['route_identifier_name'])) {
                continue;
            }
            $restServices[$controllerService] = $restConfig['route_identifier_name'];
        }

        return $restServices;
    }
}
