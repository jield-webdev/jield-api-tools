<?php

declare(strict_types=1);

use Jield\ApiTools\ContentNegotiation\JsonModel;

return [
    'api-tools-content-negotiation' => [
        // This is an array of controller service names pointing to one of:
        // - named selectors (see below)
        // - an array of specific selectors, in the same format as for the
        //   selectors key
        'controllers'                    => [],

        // This is an array of named selectors. Each selector consists of a
        // view model type pointing to the Accept mediatypes that will trigger
        // selection of that view model; see the documentation on the
        // AcceptableViewModelSelector plugin for details on the format:
        // http://docs.laminas.dev/laminas-mvc/plugins/#acceptableviewmodelselector-plugin
        'selectors'                      => [
            'Json' => [
                JsonModel::class => [
                    'application/json',
                    'application/*+json',
                ],
            ],
        ],

        // Array of controller service name => allowed accept header pairs.
        // The allowed content type may be a string, or an array of strings.
        'accept_whitelist'               => [],

        // Array of controller service name => allowed content type pairs.
        // The allowed content type may be a string, or an array of strings.
        'content_type_whitelist'         => [],

        // Enable x-http method override feature
        // When set to 'true' the  http method in the request will be overridden
        // by the method inside the 'X-HTTP-Method-Override' header (if present)
        'x_http_method_override_enabled' => false,

        // Map incoming HTTP request methods to acceptable X-HTTP-Method-Override
        // values; when matched, the override value will be used for the incoming
        // request.
        'http_override_methods'          => [
            // Example:
            // The following allows the X-HTTP-Method-Override header to override
            // a GET request using one of the values in the supplied array:
            // 'GET' => ['HEAD', 'POST', 'PUT', 'DELETE', 'PATCH']
        ],
    ],
];
