<?php

declare(strict_types=1);

namespace Jield\ApiToolsTest\Fixture\Listeners;

use Jield\ApiTools\Listener\AbstractRoutedListener;

final class ExampleListener extends AbstractRoutedListener
{
    protected static string $route = '/example';

    public function fetchAll(\Laminas\Stdlib\Parameters $params): array
    {
        return [];
    }
}
