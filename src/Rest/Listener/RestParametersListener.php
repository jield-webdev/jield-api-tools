<?php

declare(strict_types=1);

namespace Jield\ApiTools\Rest\Listener;

use Jield\ApiTools\Rest\RestController;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\EventManager\ListenerAggregateTrait;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Mvc\MvcEvent;

use Override;
use function method_exists;

class RestParametersListener implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    protected $sharedListeners = [];

    /** @param int $priority */
    #[Override]
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(eventName: MvcEvent::EVENT_DISPATCH, listener: $this->onDispatch(...), priority: 100);
    }

    public function attachShared(SharedEventManagerInterface $events): void
    {
        $listener = $events->attach(
            RestController::class,
            MvcEvent::EVENT_DISPATCH,
            listener: $this->onDispatch(...),
            priority: 100
        );

        if (! $listener) {
            $listener = $this->onDispatch(...);
        }

        $this->sharedListeners[] = $listener;
    }

    public function detachShared(SharedEventManagerInterface $events): void
    {
        $eventManagerVersion = method_exists(object_or_class: $events, method: 'getEvents') ? 2 : 3;
        foreach ($this->sharedListeners as $index => $listener) {
            switch ($eventManagerVersion) {
                case 2:
                    if ($events->detach(listener: RestController::class, identifier: $listener)) {
                        unset($this->sharedListeners[$index]);
                    }

                    break;
                case 3:
                    if ($events->detach(listener: $listener, identifier: RestController::class, eventName: MvcEvent::EVENT_DISPATCH)) {
                        unset($this->sharedListeners[$index]);
                    }

                    break;
            }
        }
    }

    /**
     * Listen to the dispatch event
     */
    public function onDispatch(MvcEvent $e): void
    {
        $controller = $e->getTarget();
        if (! $controller instanceof RestController) {
            return;
        }

        $request  = $e->getRequest();
        $query    = $request->getQuery();
        $matches  = $e->getRouteMatch();
        $resource = $controller->getResource();
        $resource->setQueryParams(params: $query);
        $resource->setRouteMatch(matches: $matches);
    }
}
