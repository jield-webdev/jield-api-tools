<?php

declare(strict_types=1);

namespace Jield\ApiTools\OAuth2\Factory;

use Jield\ApiTools\OAuth2\EntraJwksProvider;
use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final class EntraJwksProviderFactory
{
    public function __invoke(ContainerInterface $container): EntraJwksProvider
    {
        $config = $container->get('config')['api-tools-oauth2']['entra'] ?? [];

        return new EntraJwksProvider(
            new Client(),
            new FilesystemAdapter('entra_jwks', 3600, $config['cache_directory'] ?? null)
        );
    }
}
