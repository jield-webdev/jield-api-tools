<?php

declare(strict_types=1);

namespace Jield\ApiTools\ContentNegotiation;

use Jield\ApiTools\ApiProblem\ApiProblem;
use Jield\ApiTools\ApiProblem\ApiProblemResponse;
use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\Request as HttpRequest;
use Laminas\Mvc\MvcEvent;

use Override;
use function array_key_exists;
use function in_array;
use function sprintf;

class HttpMethodOverrideListener extends AbstractListenerAggregate
{
    /** @var array */
    protected $httpMethodOverride = [];

    /**
     * @param array $httpMethodOverride
     */
    public function __construct(array $httpMethodOverride)
    {
        $this->httpMethodOverride = $httpMethodOverride;
    }

    /**
     * Priority is set very high (should be executed before all other listeners that rely on the request method value).
     * TODO: Check priority value, maybe value should be even higher??
     *
     * @param int                   $priority
     */
    #[Override]
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(eventName: MvcEvent::EVENT_ROUTE, listener: $this->onRoute(...), priority: -40);
    }

    /**
     * Checks for X-HTTP-Method-Override header and sets header inside request object.
     *
     */
    public function onRoute(MvcEvent $event): ?ApiProblemResponse
    {
        $request = $event->getRequest();

        if (! $request instanceof HttpRequest) {
            return null;
        }

        if (! $request->getHeaders()->has(name: 'X-HTTP-Method-Override')) {
            return null;
        }

        $method = $request->getMethod();

        if (! array_key_exists(key: $method, array: $this->httpMethodOverride)) {
            return new ApiProblemResponse(apiProblem: new ApiProblem(
                status: 400,
                detail: sprintf('Overriding %s method with X-HTTP-Method-Override header is not allowed', $method)
            ));
        }

        $header         = $request->getHeader(name: 'X-HTTP-Method-Override');
        $overrideMethod = $header->getFieldValue();
        $allowedMethods = $this->httpMethodOverride[$method];

        if (! in_array(needle: $overrideMethod, haystack: $allowedMethods)) {
            return new ApiProblemResponse(apiProblem: new ApiProblem(
                status: 400,
                detail: sprintf('Illegal override method %s in X-HTTP-Method-Override header', $overrideMethod)
            ));
        }

        $request->setMethod(method: $overrideMethod);
        return null;
    }
}
