<?php

declare(strict_types=1);

namespace Jield\ApiTools\ContentNegotiation;

use Jield\ApiTools\ApiProblem\ApiProblem;
use Jield\ApiTools\ApiProblem\ApiProblemResponse;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ArrayUtils;

use Override;
use function method_exists;

class ContentTypeFilterListener extends AbstractListenerAggregate
{
    /**
     * Whitelist configuration
     *
     * @var array
     */
    protected $config = [];

    /**
     * @param int                    $priority
     */
    #[Override]
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(eventName: MvcEvent::EVENT_ROUTE, listener: $this->onRoute(...), priority: -625);
    }

    /**
     * Set whitelist configuration
     *
     * @param  array $config
     * @return self
     */
    public function setConfig(array $config): static
    {
        $this->config = ArrayUtils::merge(a: $this->config, b: $config);
        return $this;
    }

    /**
     * Test if the content-type received is allowable.
     *
     */
    public function onRoute(MvcEvent $e): ?ApiProblemResponse
    {
        if (empty($this->config)) {
            return null;
        }

        $controllerName = $e->getRouteMatch()->getParam(name: 'controller');
        if (! isset($this->config[$controllerName])) {
            return null;
        }

        // Only worry about content types on HTTP methods that submit content
        // via the request body.
        $request = $e->getRequest();
        if (! method_exists(object_or_class: $request, method: 'getHeaders')) {
            // Not an HTTP request; nothing to do
            return null;
        }

        $requestBody = (string) $request->getContent();

        if ($requestBody === '' || $requestBody === '0') {
            return null;
        }

        $headers = $request->getHeaders();
        if (! $headers->has('content-type')) {
            return new ApiProblemResponse(
                apiProblem: new ApiProblem(status: 415, detail: 'Invalid content-type specified')
            );
        }

        $contentTypeHeader = $headers->get('content-type');

        $matched = $contentTypeHeader->match($this->config[$controllerName]);

        if (false === $matched) {
            return new ApiProblemResponse(
                apiProblem: new ApiProblem(status: 415, detail: 'Invalid content-type specified')
            );
        }

        return null;
    }
}
