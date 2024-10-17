<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal;

use Jield\ApiTools\Hal\View\HalJsonModel;

return [
    'api-tools-hal'                 => [
        'options' => [
            // Needed for generate valid _link url when you use a proxy
            'use_proxy' => false,
        ],
    ],
    // Creates a "HalJson" selector for laminas-api-tools/api-tools-content-negotiation
    'api-tools-content-negotiation' => [
        'selectors' => [
            'HalJson' => [
                HalJsonModel::class => [
                    'application/json',
                    'application/*+json',
                ],
            ],
        ],
    ],
];
