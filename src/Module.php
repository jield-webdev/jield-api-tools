<?php

declare(strict_types=1);

namespace Jield\ApiTools;

use Laminas\ModuleManager\Feature\ConfigProviderInterface;

final class Module implements ConfigProviderInterface
{
    public function getConfig(): array
    {
        return (new ConfigProvider())->getMergedConfig();
    }
}
