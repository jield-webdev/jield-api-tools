<?php

namespace Jield\ApiTools;

use Jield\ApiTools\ContentNegotiation\JsonModel;
use Jield\ApiTools\Hal\View\HalJsonModel;
use Laminas\View\Model\ViewModel;

final class SettingsProvider
{
    public function __invoke(): array
    {
        return [
            'api-tools-oauth2'              => [
                'db'              => [
                    'dsn'      => 'insert here the DSN for DB connection', // for example "mysql:dbname=oauth2_db;host=localhost"
                    'username' => 'insert here the DB username',
                    'password' => 'insert here the DB password',
                ],
                'storage'         => 'Laminas\ApiTools\OAuth2\Adapter\PdoAdapter', // service name for the OAuth2 storage adapter

                /**
                 * These special OAuth2Server options are parsed outside the options array
                 */
                'allow_implicit'  => false, // default (set to true when you need to support browser-based or mobile apps)
                'access_lifetime' => 3600, // default (set a value in seconds for access tokens lifetime)
                'enforce_state'   => true,  // default

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
                'grant_types'     => [
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
                    'use_jwt_access_tokens'             => false,
                    'store_encrypted_token_string'      => true,
                    'use_openid_connect'                => false,
                    'id_lifetime'                       => 3600,
                    'www_realm'                         => 'Service',
                    'token_param_name'                  => 'access_token',
                    'token_bearer_header_name'          => 'Bearer',
                    'require_exact_redirect_uri'        => true,
                    'allow_credentials_in_request_body' => true,
                    'allow_public_clients'              => true,
                    'always_issue_new_refresh_token'    => false,
                    'unset_refresh_token_after_use'     => true,
                ],
            ],
            'api-tools-mvc-auth'            => [
                'authentication' => [
                    /**
                     *
                     * Starting in 1.1, we have an "adapters" key, which is a key/value
                     * pair of adapter name -> adapter configuration information. Each
                     * adapter should name the Jield\ApiTools\MvcAuth\Authentication\AdapterInterface
                     * type in the 'adapter' key.
                     *
                     * For HttpAdapter cases, specify an 'options' key with the options
                     * to use to create the Laminas\Authentication\Adapter\Http instance.
                     *
                     * Starting in 1.2, you can specify a resolver implementing the
                     * Laminas\Authentication\Adapter\Http\ResolverInterface that is passed
                     * into the Laminas\Authentication\Adapter\Http as either basic or digest
                     * resolver. This allows you to implement your own method of authentication
                     * instead of having to rely on the two default methods (ApacheResolver
                     * for basic authentication and FileResolver for digest authentication,
                     * both based on files).
                     *
                     * When you want to use this feature, use the "basic_resolver_factory"
                     * key to get your custom resolver instance from the Laminas service manager.
                     * If this key is set and pointing to a valid entry in the service manager,
                     * the entry "htpasswd" is ignored (unless you use it in your custom
                     * factory to build the resolver).
                     *
                     * Using the "digest_resolver_factory" ignores the "htdigest" key in
                     * the same way.
                     *
                     * For OAuth2Adapter instances, specify a 'storage' key, with options
                     * to use for matching the adapter and creating an OAuth2 storage
                     * instance. The array MUST contain a `route' key, with the route
                     * at which the specific adapter will match authentication requests.
                     * To specify the storage instance, you may use one of two approaches:
                     *
                     * - Specify a "storage" subkey pointing to a named service or an array
                     *   of named services to use.
                     * - Specify an "adapter" subkey with the value "pdo" or "mongo", and
                     *   include additional subkeys for configuring a Jield\ApiTools\OAuth2\Adapter\PdoAdapter
                     *   or Jield\ApiTools\OAuth2\Adapter\MongoAdapter, accordingly. See the api-tools-oauth2
                     *   documentation for details.
                     *
                     * This looks like the following for the HTTP basic/digest and OAuth2
                     * adapters:
                     * 'adapters' => [
                     * // HTTP adapter
                     * 'api' => [
                     * 'adapter' => 'Jield\ApiTools\MvcAuth\Authentication\HttpAdapter',
                     * 'options' => [
                     * 'accept_schemes' => ['basic', 'digest'],
                     * 'realm' => 'api',
                     * 'digest_domains' => 'https://example.com',
                     * 'nonce_timeout' => 3600,
                     * 'htpasswd' => 'data/htpasswd',
                     * 'htdigest' => 'data/htdigest',
                     * // If this is set, the htpasswd key is ignored:
                     * 'basic_resolver_factory' => 'ServiceManagerKeyToAsk',
                     * // If this is set, the htdigest key is ignored:
                     * 'digest_resolver_factory' => 'ServiceManagerKeyToAsk',
                     * ],
                     * ],
                     * // OAuth2 adapter, using an "adapter" type of "pdo"
                     * 'user' => [
                     * 'adapter' => 'Jield\ApiTools\MvcAuth\Authentication\OAuth2Adapter',
                     * 'storage' => [
                     * 'adapter' => 'pdo',
                     * 'route' => '/user',
                     * 'dsn' => 'mysql:host=localhost;dbname=oauth2',
                     * 'username' => 'username',
                     * 'password' => 'password',
                     * 'options' => [
                     * 1002 => 'SET NAMES utf8', // PDO::MYSQL_ATTR_INIT_COMMAND
                     * ],
                     * ],
                     * ],
                     * // OAuth2 adapter, using an "adapter" type of "mongo"
                     * 'client' => [
                     * 'adapter' => 'Jield\ApiTools\MvcAuth\Authentication\OAuth2Adapter',
                     * 'storage' => [
                     * 'adapter' => 'mongo',
                     * 'route' => '/client',
                     * 'locator_name' => 'SomeServiceName', // If provided, pulls the given service
                     * 'dsn' => 'mongodb://localhost',
                     * 'database' => 'oauth2',
                     * 'options' => [
                     * 'username' => 'username',
                     * 'password' => 'password',
                     * 'connectTimeoutMS' => 500,
                     * ],
                     * ],
                     * ],
                     * // OAuth2 adapter, using a named "storage" service
                     * 'named-storage' => [
                     * 'adapter' => 'Jield\ApiTools\MvcAuth\Authentication\OAuth2Adapter',
                     * 'storage' => [
                     * 'storage' => 'Name\Of\An\OAuth2\Storage\Service',
                     * 'route' => '/named-storage',
                     * ],
                     * ],
                     * ],
                     *
                     * Next, we also have a "map", which maps an API module (with
                     * optional version) to a given authentication type (one of basic,
                     * digest, or oauth2):
                     * 'map' => [
                     * 'ApiModuleName' => 'oauth2',
                     * 'OtherApi\V2' => 'basic',
                     * 'AnotherApi\V1' => 'digest',
                     * ],
                     *
                     * We also allow you to specify custom authentication types that you
                     * support via listeners; by adding them to the configuration, you
                     * ensure that they will be available for mapping modules to
                     * authentication types in the Admin.
                     * 'types' => [
                     * 'token',
                     * 'key',
                     * 'etc',
                     * ]
                     */
                ],
                'authorization'  => [
                    'deny_by_default' => false,
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
