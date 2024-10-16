<?php

declare(strict_types=1);

namespace Jield\ApiTools\Rpc\Factory;

use Jield\ApiTools\Rpc\OptionsListener;
use Psr\Container\ContainerInterface;

class OptionsListenerFactory
{
    /**
     * @return OptionsListener
     */
    public function __invoke(ContainerInterface $container)
    {
        return new OptionsListener($this->getConfig($container));
    }

    /**
     * Attempt to marshal configuration from the "config" service.
     *
     * @return array
     */
    private function getConfig(ContainerInterface $container)
    {
        if (! $container->has('config')) {
            return [];
        }

        $config = $container->get('config');
        if (! isset($config['api-tools-rpc'])) {
            return [];
        }

        return $config['api-tools-rpc'];
    }
}
