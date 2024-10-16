<?php

declare(strict_types=1);

namespace Jield\ApiTools\Rest\Factory;

use Jield\ApiTools\Rest\Listener\OptionsListener;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Psr\Container\ContainerInterface;

use function array_key_exists;
use function is_array;

class OptionsListenerFactory implements FactoryInterface
{
    /**
     * Create and return an OptionsListener instance.
     *
     * @param string $requestedName
     * @param null|array $options
     * @return OptionsListener
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        return new OptionsListener($this->getConfig($container));
    }

    /**
     * Retrieve api-tools-rest config from the container, if available.
     *
     * @return array
     */
    private function getConfig(ContainerInterface $container)
    {
        if (! $container->has('config')) {
            return [];
        }

        $config = $container->get('config');

        if (
            ! array_key_exists('api-tools-rest', $config)
            || ! is_array($config['api-tools-rest'])
        ) {
            return [];
        }

        return $config['api-tools-rest'];
    }
}
