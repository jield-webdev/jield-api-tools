<?php

declare(strict_types=1);

namespace Jield\ApiTools\MvcAuth\Factory;

use Laminas\Authentication\Adapter\Http\FileResolver;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Override;
use Psr\Container\ContainerInterface;

class FileResolverFactory implements FactoryInterface
{
    /**
     * Create and return a FileResolver instance, if configured.
     *
     * @param string             $requestedName
     */
    #[Override]
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): false|FileResolver
    {
        if (! $container->has('config')) {
            return false;
        }

        $config = $container->get('config');

        if (! isset($config['api-tools-mvc-auth']['authentication']['http']['htdigest'])) {
            return false;
        }

        $htdigest = $config['api-tools-mvc-auth']['authentication']['http']['htdigest'];

        return new FileResolver(path: $htdigest);
    }
}
