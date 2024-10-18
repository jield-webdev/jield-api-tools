<?php

declare(strict_types=1);

namespace Jield\ApiTools\ContentNegotiation\Factory;

use Psr\Container\ContainerInterface;
use Jield\ApiTools\ContentNegotiation\ContentNegotiationOptions;

use function is_array;

class ContentNegotiationOptionsFactory
{
    public function __invoke(ContainerInterface $container): ContentNegotiationOptions
    {
        return new ContentNegotiationOptions(options: $this->getConfig(container: $container));
    }

    /**
     * Attempt to retrieve the api-tools-content-negotiation configuration.
     *
     * - Consults the container's 'config' service, returning an empty array
     *   if not found.
     * - Validates that the api-tools-content-negotiation key exists, and evaluates
     *   to an array; if not,returns an empty array.
     *
     */
    private function getConfig(ContainerInterface $container): array
    {
        if (! $container->has('config')) {
            return [];
        }

        $config = $container->get('config');

        if (
            ! isset($config['api-tools-content-negotiation'])
            || ! is_array(value: $config['api-tools-content-negotiation'])
        ) {
            return [];
        }

        return $config['api-tools-content-negotiation'];
    }
}
