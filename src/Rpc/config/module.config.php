<?php

declare(strict_types=1);

namespace Jield\ApiTools\Rpc;

return [
    'controllers'     => [
        'abstract_factories' => [
            Factory\RpcControllerFactory::class,
        ],
    ],
    'service_manager' => [
        'factories' => [
            OptionsListener::class => Factory\OptionsListenerFactory::class,
        ],
    ],
];
