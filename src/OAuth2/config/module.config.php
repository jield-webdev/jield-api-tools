<?php

declare(strict_types=1);

namespace Jield\ApiTools\OAuth2;

use Jield\ApiTools\ContentNegotiation\JsonModel;
use Laminas\View\Model\ViewModel;

return [
    'controllers'                   => [
        // Legacy Zend Framework aliases
        'factories' => [
            'Jield\ApiTools\OAuth2\Controller\Auth' => Factory\AuthControllerFactory::class,
        ],
    ],
    'service_manager'               => [
        'aliases'   => [
            'Jield\ApiTools\OAuth2\Provider\UserId' => Provider\UserId\AuthenticationService::class,
        ],
        'factories' => [
            Adapter\PdoAdapter::class                    => Factory\PdoAdapterFactory::class,
            Adapter\MongoAdapter::class                  => Factory\MongoAdapterFactory::class,
            Provider\UserId\AuthenticationService::class => Provider\UserId\AuthenticationServiceFactory::class,
            'Jield\ApiTools\OAuth2\Service\OAuth2Server' => Factory\OAuth2ServerFactory::class,
        ],
    ],
    'api-tools-oauth2'              => [
        /*
         * Config can include:
         * - 'storage' => 'name of storage service' - typically Jield\ApiTools\OAuth2\Adapter\PdoAdapter
         * - 'db' => [ // database configuration for the above PdoAdapter
         *       'dsn'      => 'PDO DSN',
         *       'username' => 'username',
         *       'password' => 'password'
         *   ]
         * - 'storage_settings' => [ // configuration to pass to the storage adapter
         *       // see https://github.com/bshaffer/oauth2-server-php/blob/develop/src/OAuth2/Storage/Pdo.php#L57-L66
         *   ]
         */
        'grant_types'                => [
            'client_credentials' => true,
            'authorization_code' => true,
            'password'           => true,
            'refresh_token'      => true,
            'jwt'                => true,
        ],
        /*
         * Error reporting style
         *
         * If true, client errors are returned using the
         * application/problem+json content type,
         * otherwise in the format described in the oauth2 specification
         * (default: true)
         */
        'api_problem_error_response' => true,
    ],
    'api-tools-content-negotiation' => [
        'controllers' => [
            'Jield\ApiTools\OAuth2\Controller\Auth' => [
                JsonModel::class => [
                    'application/json',
                    'application/*+json',
                ],
                ViewModel::class => [
                    'text/html',
                    'application/xhtml+xml',
                ],
            ],
        ],
    ],
];
