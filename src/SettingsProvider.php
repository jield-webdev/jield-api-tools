<?php

namespace Jield\ApiTools;

use Jield\ApiTools\ContentNegotiation\JsonModel;
use Jield\ApiTools\Hal\View\HalJsonModel;
use Jield\ApiTools\MvcAuth\Authentication\OAuth2Adapter;
use Laminas\View\Model\ViewModel;

final class SettingsProvider
{
    public function __invoke(): array
    {
        return [
            'router'                        => [
                'routes' => [
                    'oauth' => [
                        'options' => [
                            'spec'  => '%oauth%',
                            'regex' => '(?P<oauth>(/oauth))',
                        ],
                        'type'    => 'regex',
                    ],
                ],
            ],
            'api-tools-oauth2'              => [
                'storage'                    => \Jield\ApiTools\OAuth2\Adapter\PdoAdapter::class,

                /**
                 * These special OAuth2Server options are parsed outside the options array
                 */
                'allow_implicit'             => true, // default (set to true when you need to support browser-based or mobile apps)
                'access_lifetime'            => 3600, // default (set a value in seconds for access tokens lifetime)
                'enforce_state'              => true,  // default

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
                'api_problem_error_response' => true,
                /**
                 * These are all OAuth2Server options with their default values
                 */
                'options'                    => [
                    'use_jwt_access_tokens'             => true,
                    'store_encrypted_token_string'      => true,
                    'use_openid_connect'                => true,
                    'jwt_extra_payload_callable'        => null,
                    'auth_code_lifetime'                => 3600,
                    'id_lifetime'                       => 3600,
                    'www_realm'                         => 'Service',
                    'token_param_name'                  => 'access_token',
                    'token_bearer_header_name'          => 'Bearer',
                    'require_exact_redirect_uri'        => true,
                    'allow_credentials_in_request_body' => true,
                    'allow_public_clients'              => true,
                    'always_issue_new_refresh_token'    => true,
                    'unset_refresh_token_after_use'     => true,
                ],
            ],
            'api-tools-mvc-auth'            => [
                'authentication' => [
                    'map'      => [
                        'Api\\V1' => 'jield_oauth2_pdo_adapter',
                    ],
                    'adapters' => [
                        'jield_oauth2_pdo_adapter' => [
                            'adapter' => OAuth2Adapter::class,
                            'storage' => [
                                'storage' => \Jield\ApiTools\OAuth2\Adapter\PdoAdapter::class
                            ]
                        ],
                    ],
                ],
            ],
            'api-tools-content-negotiation' => [
                // This is an array of controller service names pointing to one of:
                // - named selectors (see below)
                // - an array of specific selectors, in the same format as for the
                //   selectors key
                'controllers'                    => [
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

                // This is an array of named selectors. Each selector consists of a
                // view model type pointing to the Accept mediatypes that will trigger
                // selection of that view model; see the documentation on the
                // AcceptableViewModelSelector plugin for details on the format:
                // http://docs.laminas.dev/laminas-mvc/plugins/#acceptableviewmodelselector-plugin
                'selectors'                      => [
                    'Json'    => [
                        \Jield\ApiTools\ContentNegotiation\JsonModel::class => [
                            'application/json',
                            'application/*+json',
                        ],
                    ],
                    'HalJson' => [
                        HalJsonModel::class => [
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
    }
}
