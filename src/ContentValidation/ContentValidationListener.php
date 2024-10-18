<?php

declare(strict_types=1);

namespace Jield\ApiTools\ContentValidation;

use Jield\ApiTools\ApiProblem\ApiProblem;
use Jield\ApiTools\ApiProblem\ApiProblemResponse;
use Jield\ApiTools\ContentNegotiation\ParameterDataContainer;
use Laminas\EventManager\EventManager;
use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\EventManager\ListenerAggregateTrait;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response;
use Laminas\InputFilter\CollectionInputFilter;
use Laminas\InputFilter\Exception\InvalidArgumentException as InputFilterInvalidArgumentException;
use Laminas\InputFilter\InputFilterInterface;
use Laminas\InputFilter\UnknownInputsCapableInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\Stdlib\ArrayUtils;
use Override;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function count;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function preg_match;
use function sprintf;
use const ARRAY_FILTER_USE_BOTH;

class ContentValidationListener implements ListenerAggregateInterface, EventManagerAwareInterface
{
    use ListenerAggregateTrait;

    public const string EVENT_BEFORE_VALIDATE = 'contentvalidation.beforevalidate';

    protected array $config = [];

    protected ?EventManagerInterface $events = null;

    protected ?ServiceLocatorInterface $inputFilterManager = null;

    /**
     * Cache of input filter service names/instances
     */
    protected array $inputFilters = [];

    protected array $methodsWithoutBodies
        = [
            'GET',
            'HEAD',
            'OPTIONS',
        ];

    /**
     * Map of REST controllers => route identifier names
     *
     * Used to determine if we have a collection or an entity, for purposes of validation.
     */
    protected array $restControllers;

    public function __construct(
        array                    $config = [],
        ?ServiceLocatorInterface $inputFilterManager = null,
        array                    $restControllers = []
    )
    {
        $this->config             = $config;
        $this->inputFilterManager = $inputFilterManager;
        $this->restControllers    = $restControllers;

        if (isset($config['methods_without_bodies']) && is_array(value: $config['methods_without_bodies'])) {
            foreach ($config['methods_without_bodies'] as $method) {
                $this->addMethodWithoutBody(method: $method);
            }
        }
    }

    /**
     * Set event manager instance
     *
     * Sets the event manager identifiers to the current class, this class, and
     * the resource interface.
     *
     */
    #[Override]
    public function setEventManager(EventManagerInterface $eventManager): static
    {
        $eventManager->addIdentifiers(identifiers: [
            static::class,
            self::class,
            self::EVENT_BEFORE_VALIDATE,
        ]);
        $this->events = $eventManager;

        return $this;
    }

    /**
     * Retrieve event manager
     *
     * Lazy-instantiates an EM instance if none provided.
     *
     * @return EventManagerInterface
     */
    #[Override]
    public function getEventManager(): ?EventManagerInterface
    {
        if (null === $this->events) {
            $this->setEventManager(eventManager: new EventManager());
        }

        return $this->events;
    }

    /**
     * @param int $priority
     * @see   ListenerAggregateInterface
     *
     */
    #[Override]
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        // trigger after authentication/authorization and content negotiation
        $this->listeners[] = $events->attach(eventName: MvcEvent::EVENT_ROUTE, listener: $this->onRoute(...), priority: -650);
    }

    /**
     * Attempt to validate the incoming request
     *
     * If an input filter is associated with the matched controller service,
     * attempt to validate the incoming request, and inject the event with the
     * input filter, as the "Jield\ApiTools\ContentValidation\InputFilter" parameter.
     *
     * Uses the ContentNegotiation ParameterDataContainer to retrieve parameters
     * to validate, and returns an ApiProblemResponse when validation fails.
     *
     * Also returns an ApiProblemResponse in cases of:
     *
     * - Invalid input filter service name
     * - Missing ParameterDataContainer (i.e., ContentNegotiation is not registered)
     *
     */
    public function onRoute(MvcEvent $e): ?ApiProblemResponse
    {
        $request = $e->getRequest();
        if (!$request instanceof HttpRequest) {
            return null;
        }

        $routeMatches = $e->getRouteMatch();
        if (!$routeMatches instanceof RouteMatch) {
            return null;
        }

        $controllerService = $routeMatches->getParam(name: 'controller', default: false);
        if (!$controllerService) {
            return null;
        }

        $method        = $request->getMethod();
        $dataContainer = $e->getParam(name: 'LaminasContentNegotiationParameterData', default: false);
        if (!$dataContainer instanceof ParameterDataContainer) {
            return new ApiProblemResponse(
                apiProblem: new ApiProblem(
                    status: 500,
                    detail: 'Laminas\\ApiTools\\ContentNegotiation module is not initialized; cannot validate request'
                )
            );
        }

        $data         = in_array(needle: $method, haystack: $this->methodsWithoutBodies)
            ? $dataContainer->getQueryParams()
            : $dataContainer->getBodyParams();
        $receivedData = in_array(needle: $method, haystack: $this->methodsWithoutBodies)
            ? $dataContainer->getQueryParams()
            : $dataContainer->getBodyParams();

        if (null === $data || '' === $data) {
            $data = [];
        }

        $isCollection = $this->isCollection(serviceName: $controllerService, data: $data, matches: $routeMatches, request: $request);

        $inputFilterService = $this->getInputFilterService(controllerService: $controllerService, method: $method, isCollection: $isCollection);

        if (!$inputFilterService) {
            return null;
        }

        if (!$this->hasInputFilter(inputFilterService: $inputFilterService)) {
            return new ApiProblemResponse(
                apiProblem: new ApiProblem(
                    status: 500,
                    detail: sprintf('Listed input filter "%s" does not exist; cannot validate request', $inputFilterService)
                )
            );
        }

        $files = $request->getFiles();
        if (!$isCollection && 0 < count(value: $files)) {
            // File uploads are not validated for collections; impossible to
            // match file fields to discrete sets
            $data = ArrayUtils::merge(a: $data, b: $files->toArray(), preserveNumericKeys: true);
        }

        $inputFilter = $this->getInputFilter(inputFilterService: $inputFilterService);

        if (
            $isCollection && !in_array(needle: $method, haystack: $this->methodsWithoutBodies)
            && !$inputFilter instanceof CollectionInputFilter
        ) {
            $collectionInputFilter = new CollectionInputFilter();
            $collectionInputFilter->setInputFilter(inputFilter: $inputFilter);
            $inputFilter = $collectionInputFilter;
        }

        $e->setParam(name: 'Jield\ApiTools\ContentValidation\InputFilter', value: $inputFilter);
        $e->setParam(name: 'Jield\ApiTools\ContentValidation\ParameterData', value: $data);

        $currentEventName = $e->getName();
        $e->setName(name: self::EVENT_BEFORE_VALIDATE);

        $events  = $this->getEventManager();
        $results = $events->triggerEventUntil(callback: fn($result) => $result instanceof ApiProblem
            || $result instanceof ApiProblemResponse, event: $e);
        $e->setName(name: $currentEventName);

        $last = $results->last();

        if ($last instanceof ApiProblem) {
            $last = new ApiProblemResponse(apiProblem: $last);
        }

        if ($last instanceof ApiProblemResponse) {
            return $last;
        }

        $data = ArrayUtils::merge(a: $data, b: $e->getParam(name: 'Jield\ApiTools\ContentValidation\ParameterData'), preserveNumericKeys: true);

        $inputFilter->setData(data: $data);

        $status = $request->isPatch()
            ? $this->validatePatch(inputFilter: $inputFilter, data: $data, isCollection: $isCollection)
            : $inputFilter->isValid();

        if ($status instanceof ApiProblemResponse) {
            return $status;
        }

        // Invalid? Return a 422 response.
        if (false === $status) {
            return new ApiProblemResponse(
                apiProblem: new ApiProblem(status: 422, detail: 'Failed Validation', type: null, title: null, additional: [
                    'validation_messages' => $inputFilter->getMessages(),
                ])
            );
        }

        // Should we use the raw data vs. the filtered data?
        // - If no `use_raw_data` flag is present, always use the raw data, as
        //   that was the default experience starting in 1.0.
        // - If the flag is present AND is boolean true, that is also
        //   an indicator that the raw data should be present.
        $useRawData = $this->useRawData(controllerService: $controllerService);
        if (!$useRawData) {
            $data = $inputFilter->getValues();
        }

        // Should we remove empty data from received data?
        // - If no `remove_empty_data` flag is present, do nothing - use data as is
        // - If `remove_empty_data` flag is present AND is boolean true, then remove
        //   empty data from current data array
        // - Does not remove empty data if keys matched received data
        $removeEmptyData = $this->shouldRemoveEmptyData(controllerService: $controllerService);
        if ($removeEmptyData) {
            $data = $this->removeEmptyData(data: $data, compareTo: $receivedData);
        }

        // If we don't have an instance of UnknownInputsCapableInterface, or no
        // unknown data is in the input filter, at this point we can just
        // set the current data into the data container.
        if (
            !$inputFilter instanceof UnknownInputsCapableInterface
            || !$inputFilter->hasUnknown()
        ) {
            $dataContainer->setBodyParams(bodyParams: $data);
            return null;
        }

        $unknown = $inputFilter->getUnknown();

        if ($this->allowsOnlyFieldsInFilter(controllerService: $controllerService)) {
            if ($inputFilter instanceof CollectionInputFilter) {
                $unknownFields = [];
                foreach ($unknown as $key => $fields) {
                    $unknownFields[] = '[' . $key . ': ' . implode(separator: ', ', array: array_keys(array: $fields)) . ']';
                }

                $fields = implode(separator: ', ', array: $unknownFields);
            } else {
                $fields = implode(separator: ', ', array: array_keys(array: $unknown));
            }

            $detail  = sprintf('Unrecognized fields: %s', $fields);
            $problem = new ApiProblem(status: Response::STATUS_CODE_422, detail: $detail);

            return new ApiProblemResponse(apiProblem: $problem);
        }

        // The raw data already contains unknown inputs, so no need to merge
        // them with the data.
        if ($useRawData) {
            $dataContainer->setBodyParams(bodyParams: $data);
            return null;
        }

        // When not using raw data, we merge the unknown data with the
        // validated data to get the full set of input.
        $dataContainer->setBodyParams(bodyParams: array_merge($data, $unknown));
        return null;
    }

    /**
     * Add HTTP Method without body content
     *
     * @param string $method
     * @return void
     */
    public function addMethodWithoutBody(string $method): void
    {
        $this->methodsWithoutBodies[] = $method;
    }

    /**
     * @param string $controllerService
     * @return boolean
     */
    protected function allowsOnlyFieldsInFilter(string $controllerService): bool
    {
        if (isset($this->config[$controllerService]['allows_only_fields_in_filter'])) {
            return true === $this->config[$controllerService]['allows_only_fields_in_filter'];
        }

        return false;
    }

    /**
     * @param string $controllerService
     * @return bool
     */
    protected function useRawData(string $controllerService): bool
    {
        return !isset($this->config[$controllerService]['use_raw_data'])
            || (isset($this->config[$controllerService]['use_raw_data'])
                && $this->config[$controllerService]['use_raw_data'] === true);
    }

    /**
     * @param string $controllerService
     * @return bool
     */
    protected function shouldRemoveEmptyData(string $controllerService): bool
    {
        return isset($this->config[$controllerService]['remove_empty_data'])
            && $this->config[$controllerService]['remove_empty_data'] === true;
    }

    /**
     * @param array $data Data to filter null values from
     * @param array $compareTo Original data, send along to preserve
     *     keys/values in $data which are intentional
     * @return array
     */
    protected function removeEmptyData(array $data, array $compareTo = []): array
    {
        /**
         * Callback for array_filter() to remove null values (array_filter() removes 'false' values)
         *
         * @param mixed $value
         * @param int|string|null $key
         */
        $removeNull = function (mixed $value, null|int|string $key = null) use ($compareTo): bool {
            // If comparison array is empty, do a straight comparison
            if ($compareTo === []) {
                return null !== $value;
            }

            // If key exists in comparison array, the 'null' value is on purpose, leave as is
            if (array_key_exists(key: $key, array: $compareTo)) {
                return true;
            }

            return null !== $value;
        };

        $data = array_filter(array: $data, callback: $removeNull, mode: ARRAY_FILTER_USE_BOTH);

        if ($data === []) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (
                !is_array(value: $value)
                && (!empty($value) || is_bool(value: $value) && !in_array(needle: $key, haystack: $compareTo))
            ) {
                continue;
            }

            if (!is_array(value: $value)) {
                unset($data[$key]);
                continue;
            }

            if (array_filter(array: $value, callback: $removeNull, mode: ARRAY_FILTER_USE_BOTH) === []) {
                unset($data[$key]);
                continue;
            }

            $tmpValue = array_key_exists(key: $key, array: $compareTo) && is_array(value: $compareTo[$key])
                ? $this->removeEmptyData(data: $value, compareTo: $compareTo[$key])
                : $this->removeEmptyData(data: $value);

            // Additional check to ensure it's not an empty recursive result
            if (array_filter(array: $tmpValue, callback: $removeNull, mode: ARRAY_FILTER_USE_BOTH) === []) {
                unset($data[$key]);
                continue;
            }

            $data[$key] = $tmpValue;
        }

        return $data;
    }

    /**
     * Retrieve the input filter service name
     *
     * Test first to see if we have a method-specific input filter, and
     * secondarily for a general one.
     *
     * If neither are present, return boolean false.
     *
     * @param string $controllerService
     * @param string $method
     * @param bool $isCollection
     * @return string|false
     */
    protected function getInputFilterService(string $controllerService, string $method, bool $isCollection): false|string
    {
        if ($isCollection && isset($this->config[$controllerService][$method . '_COLLECTION'])) {
            return $this->config[$controllerService][$method . '_COLLECTION'];
        }

        if (isset($this->config[$controllerService][$method])) {
            return $this->config[$controllerService][$method];
        }

        if ($method === 'DELETE' || in_array(needle: $method, haystack: $this->methodsWithoutBodies)) {
            return false;
        }

        return $this->config[$controllerService]['input_filter'] ?? false;
    }

    /**
     * Determine if we have an input filter matching the service name
     *
     * @param string $inputFilterService
     * @return bool
     */
    protected function hasInputFilter(string $inputFilterService): bool
    {
        if (array_key_exists(key: $inputFilterService, array: $this->inputFilters)) {
            return true;
        }

        if (
            null === $this->inputFilterManager
            || !$this->inputFilterManager->has($inputFilterService)
        ) {
            return false;
        }

        $inputFilter = $this->inputFilterManager->get($inputFilterService);
        if (!$inputFilter instanceof InputFilterInterface) {
            return false;
        }

        $this->inputFilters[$inputFilterService] = $inputFilter;
        return true;
    }

    /**
     * Retrieve the named input filter service
     *
     * @param string $inputFilterService
     * @return InputFilterInterface
     */
    protected function getInputFilter(string $inputFilterService): InputFilterInterface
    {
        return $this->inputFilters[$inputFilterService];
    }

    /**
     * Does the request represent a collection?
     *
     * @param string $serviceName
     * @param array $data
     * @param RouteMatch $matches
     */
    protected function isCollection(string $serviceName, array $data, RouteMatch $matches, HttpRequest $request): bool
    {
        if (!array_key_exists(key: $serviceName, array: $this->restControllers)) {
            return false;
        }

        if ($request->isPost() && ($data === [] || ArrayUtils::isHashTable(value: $data))) {
            return false;
        }

        $identifierName = $this->restControllers[$serviceName];
        if ($matches->getParam(name: $identifierName) !== null) {
            return false;
        }

        return null === $request->getQuery(name: $identifierName, default: null);
    }

    /**
     * Validate a PATCH request
     *
     * @param object|array $data
     * @param bool $isCollection
     */
    protected function validatePatch(InputFilterInterface $inputFilter, object|array $data, bool $isCollection): bool|ApiProblemResponse
    {
        if ($isCollection) {
            $validationGroup = $data;
            foreach ($validationGroup as &$subData) {
                $subData = array_keys(array: $subData);
            }
        } else {
            $validationGroup = array_keys(array: $data);
        }

        try {
            $inputFilter->setValidationGroup(name: $validationGroup);
            return $inputFilter->isValid();
        } catch (InputFilterInvalidArgumentException $inputFilterInvalidArgumentException) {
            $pattern = '/expects a list of valid input names; "(?P<field>[^"]*)" was not found/';
            $matched = preg_match(pattern: $pattern, subject: $inputFilterInvalidArgumentException->getMessage(), matches: $matches);
            if ($matched === 0) {
                return new ApiProblemResponse(
                    apiProblem: new ApiProblem(status: 400, detail: $inputFilterInvalidArgumentException)
                );
            }

            return new ApiProblemResponse(
                apiProblem: new ApiProblem(status: 400, detail: 'Unrecognized field "' . $matches['field'] . '"')
            );
        }
    }
}
