<?php

declare(strict_types=1);

namespace Jield\ApiTools\Rest\Factory;

use Jield\ApiTools\Hal\Collection;
use Jield\ApiTools\Rest\AbstractResourceListener;
use Jield\ApiTools\Rest\Resource;
use Jield\ApiTools\Rest\RestController;
use Laminas\EventManager\Event;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\AbstractFactoryInterface;
use Laminas\Stdlib\Parameters;
use Override;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_unique;
use function class_exists;
use function in_array;
use function is_array;
use function is_string;
use function method_exists;
use function sprintf;

class RestControllerFactory implements AbstractFactoryInterface
{
    /**
     * Cache of canCreateServiceWithName lookups
     */
    protected array $lookupCache = [];

    #[Override]
    public function canCreate(ContainerInterface $container, $requestedName): bool
    {
        if (array_key_exists(key: $requestedName, array: $this->lookupCache)) {
            return $this->lookupCache[$requestedName];
        }

        if (!$container->has('config') || !$container->has('EventManager')) {
            // Config and EventManager are required
            return false;
        }

        $config = $container->get('config');
        if (
            !isset($config['api-tools-rest'])
            || !is_array(value: $config['api-tools-rest'])
        ) {
            $this->lookupCache[$requestedName] = false;
            return false;
        }

        $config = $config['api-tools-rest'];

        if (
            !isset($config[$requestedName])
            || !isset($config[$requestedName]['listener'])
            || !isset($config[$requestedName]['route_name'])
        ) {
            // Configuration, and specifically the listener and route_name
            // keys, is required
            $this->lookupCache[$requestedName] = false;
            return false;
        }

        if (
            !$container->has($config[$requestedName]['listener'])
            && !class_exists(class: $config[$requestedName]['listener'])
        ) {
            // Service referenced by listener key is required
            $this->lookupCache[$requestedName] = false;
            throw new ServiceNotFoundException(message: sprintf(
                '%s requires that a valid "listener" service be specified for controller %s; no service found',
                __METHOD__,
                $requestedName
            ));
        }

        $this->lookupCache[$requestedName] = true;
        return true;
    }

    /**
     * Create named controller instance
     */
    #[Override]
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): RestController
    {
        $config = $container->get('config');
        $config = $config['api-tools-rest'][$requestedName];

        /** @var AbstractResourceListener $listener */
        $listener = $container->has($config['listener']) ? $container->get($config['listener']) : new $config['listener']();

        Assert::isInstanceOf(value: $listener, class: AbstractResourceListener::class);

        $resourceIdentifiers = [$listener::class];
        if (isset($config['resource_identifiers'])) {
            if (!is_array(value: $config['resource_identifiers'])) {
                $config['resource_identifiers'] = (array)$config['resource_identifiers'];
            }

            $resourceIdentifiers = array_merge($resourceIdentifiers, $config['resource_identifiers']);
        }

        $events = $container->get('EventManager');
        $events->setIdentifiers($resourceIdentifiers);

        $listener->attach(events: $events);

        $resource = new Resource();
        $resource->setEventManager($events);

        $identifier = $requestedName;
        if (isset($config['identifier'])) {
            $identifier = $config['identifier'];
        }

        $controllerClass = $config['controller_class'] ?? RestController::class;
        $controller      = new $controllerClass($identifier);

        if (!$controller instanceof RestController) {
            throw new ServiceNotCreatedException(message: sprintf(
                '"%s" must be an implementation of Jield\ApiTools\Rest\RestController',
                $controllerClass
            ));
        }

        $controller->setEventManager(events: $container->get('EventManager'));
        $controller->setResource(resource: $resource);
        $this->setControllerOptions(config: $config, controller: $controller);

        if (isset($config['entity_class'])) {
            $listener->setEntityClass(className: $config['entity_class']);
        }

        if (isset($config['collection_class'])) {
            $listener->setCollectionClass(className: $config['collection_class']);
        }

        return $controller;
    }

    /**
     * Loop through configuration to discover and set controller options.
     *
     */
    protected function setControllerOptions(array $config, RestController $controller): void
    {
        foreach ($config as $option => $value) {
            switch ($option) {
                case 'collection_http_methods':
                    $controller->setCollectionHttpMethods(methods: $value);
                    break;

                case 'collection_name':
                    $controller->setCollectionName(name: $value);
                    break;

                case 'collection_query_whitelist':
                    if (is_string(value: $value)) {
                        $value = (array)$value;
                    }

                    if (!is_array(value: $value)) {
                        break;
                    }

                    // Create a listener that checks the query string against
                    // the whitelisted query parameters in order to seed the
                    // collection route options.
                    $whitelist = $value;
                    $controller->getEventManager()->attach(eventName: 'getList.pre', listener: function (Event $e) use ($whitelist) {
                        $controller = $e->getTarget();
                        $resource   = $controller->getResource();
                        if (!$resource instanceof Resource) {
                            // ResourceInterface does not define setQueryParams, so we need
                            // specifically a Resource instance
                            return;
                        }

                        $request = $controller->getRequest();
                        if (!method_exists(object_or_class: $request, method: 'getQuery')) {
                            return;
                        }

                        $query  = $request->getQuery();
                        $params = new Parameters(values: []);

                        // If a query Input Filter exists, merge its keys with the query whitelist
                        if ($resource->getInputFilter() instanceof \Laminas\InputFilter\InputFilterInterface) {
                            $whitelist = array_unique(array: array_merge(
                                $whitelist,
                                array_keys(array: $resource->getInputFilter()->getInputs())
                            ));
                        }

                        foreach ($query as $key => $value) {
                            if (!in_array(needle: $key, haystack: $whitelist)) {
                                continue;
                            }

                            $params->set(name: $key, value: $value);
                        }

                        $resource->setQueryParams(params: $params);
                    });

                    $controller->getEventManager()->attach(eventName: 'getList.post', listener: function (Event $e) {
                        $controller = $e->getTarget();
                        $resource   = $controller->getResource();
                        if (!$resource instanceof Resource) {
                            // ResourceInterface does not define setQueryParams, so we need
                            // specifically a Resource instance
                            return;
                        }

                        $collection = $e->getParam(name: 'collection');
                        if (!$collection instanceof Collection) {
                            return;
                        }

                        $params = $resource->getQueryParams()->getArrayCopy();

                        // Set collection route options with the captured query whitelist, to
                        // ensure paginated links are generated correctly
                        $collection->setCollectionRouteOptions(options: [
                            'query' => $params,
                        ]);

                        // If no self link defined, set the options in the collection and return
                        $links = $collection->getLinks();
                        if (!$links->has(relation: 'self')) {
                            return;
                        }

                        // If self link is defined, but is not route-based, return
                        $self = $links->get(relation: 'self');
                        if (!$self->hasRoute()) {
                            return;
                        }

                        // Otherwise, merge the query string parameters with
                        // the self link's route options
                        $self    = $links->get(relation: 'self');
                        $options = $self->getRouteOptions();
                        $self->setRouteOptions(options: array_merge($options, [
                            'query' => $params,
                        ]));
                    });
                    break;

                case 'entity_http_methods':
                    $controller->setEntityHttpMethods(methods: $value);
                    break;

                /**
                 * The identifierName is a property of the ancestor
                 * and is described by Laminas API Tools as route_identifier_name
                 */
                case 'route_identifier_name':
                    $controller->setIdentifierName(name: $value);
                    break;

                case 'min_page_size':
                    $controller->setMinPageSize(count: $value);
                    break;

                case 'page_size':
                    $controller->setPageSize(count: $value);
                    break;

                case 'max_page_size':
                    $controller->setMaxPageSize(count: $value);
                    break;

                case 'page_size_param':
                    $controller->setPageSizeParam(param: $value);
                    break;

                case 'route_name':
                    $controller->setRoute(route: $value);
                    break;
            }
        }
    }
}
