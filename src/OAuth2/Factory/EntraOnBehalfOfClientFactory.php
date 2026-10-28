<?php

declare(strict_types=1);

namespace Jield\ApiTools\OAuth2\Factory;

use Jield\ApiTools\OAuth2\EntraOnBehalfOfClient;
use GuzzleHttp\Client;
use Psr\Container\ContainerInterface;

final class EntraOnBehalfOfClientFactory
{
    public function __invoke(ContainerInterface $container): EntraOnBehalfOfClient
    {
        return new EntraOnBehalfOfClient(new Client());
    }
}
