<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal\Factory;


use Psr\Container\ContainerInterface;
use Jield\ApiTools\Hal\Metadata;
use Laminas\Hydrator\HydratorPluginManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use function is_array;

class MetadataMapFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): Metadata\MetadataMap
    {
        $config = $container->get('Jield\ApiTools\Hal\HalConfig');

        $hydrators = $container->has('HydratorManager')
            ? $container->get('HydratorManager')
            : new HydratorPluginManager(configInstanceOrParentLocator: $container);

        $map = isset($config['metadata_map']) && is_array(value: $config['metadata_map'])
            ? $config['metadata_map']
            : [];

        return new Metadata\MetadataMap(map: $map, hydrators: $hydrators);
    }
}
