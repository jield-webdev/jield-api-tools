<?php

declare(strict_types=1);

namespace Jield\ApiTools\ContentValidation;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Psr\Container\ContainerInterface;

class ContentValidationListenerFactory implements FactoryInterface
{
    /**
     * Create and return a ContentValidationListener instance.
     *
     * @param string $requestedName
     * @param array|null $options
     * @return ContentValidationListener
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        $config                  = $container->has('config') ? $container->get('config') : [];
        $contentValidationConfig = $config['api-tools-content-validation'] ?? [];
        $restServices            = $this->getRestServicesFromConfig($config);

        return new ContentValidationListener(
            $contentValidationConfig,
            $container->get('InputFilterManager'),
            $restServices
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
