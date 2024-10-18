<?php

declare(strict_types=1);

namespace Jield\ApiTools\ContentNegotiation\Factory;

use Psr\Container\ContainerInterface;
use Jield\ApiTools\ContentNegotiation\Request as HttpRequest;
use Laminas\Console\Console;
use Laminas\Console\Request as ConsoleRequest;

use function class_exists;

use const PHP_SAPI;

/**
 * @deprecated Since 1.6.0. This factory is no longer used within the module.
 */
class RequestFactory
{
    public function __invoke(ContainerInterface $container): HttpRequest|ConsoleRequest
    {
        // If console tooling is present, use that to determine whether or not
        // we are in a console environment. This approach allows overriding the
        // environment for purposes of testing HTTP requests from the CLI.
        if (class_exists(class: Console::class)) {
            return Console::isConsole() ? new ConsoleRequest() : new HttpRequest();
        }

        // If console tooling is not present, we use the PHP_SAPI value to decide.
        return PHP_SAPI === 'cli' ? new ConsoleRequest() : new HttpRequest();
    }
}
