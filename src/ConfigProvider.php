<?php

namespace Jield\ApiTools;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;

final class ConfigProvider
{
    public function getMergedConfig(): array
    {
        return [
            ConfigAbstractFactory::class => $this->getConfigAbstractFactory(),
            'service_manager'            => $this->getServiceMangerConfig(),
            'controller_plugins'         => $this->getControllerPluginConfig(),
        ];
    }

    public function getControllerPluginConfig(): array
    {
        return [
        ];
    }

    public function getServiceMangerConfig(): array
    {
        return [
            'factories' => [
            ],
        ];
    }


    public function getConfigAbstractFactory(): array
    {
        return [

        ];
    }
}
