<?php

declare(strict_types=1);

namespace Jield\ApiTools\Rest;

use Jield\ApiTools\ApiProblem\ApiProblem;
use Jield\ApiTools\MvcAuth\Identity\IdentityInterface;
use Laminas\EventManager\EventManagerInterface;
use Laminas\EventManager\ListenerAggregateInterface;
use Laminas\EventManager\ListenerAggregateTrait;
use Laminas\InputFilter\InputFilterInterface;
use Laminas\Stdlib\Parameters;

use Override;
use function sprintf;

abstract class AbstractResourceListener implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    /** @var ResourceEvent */
    protected $event;

    /**
     * The entity_class config for the calling controller api-tools-rest config
     *
     * @var string
     */
    protected $entityClass;

    /**
     * The collection_class config for the calling controller api-tools-rest config
     *
     * @var string
     */
    protected $collectionClass;

    /**
     * Current identity, if discovered in the resource event.
     *
     * @var IdentityInterface
     */
    protected $identity;

    /**
     * Input filter, if discovered in the resource event.
     *
     * @var InputFilterInterface
     */
    protected $inputFilter;

    /**
     * Set the entity_class for the controller config calling this resource
     *
     * @param string $className
     * @return $this
     */
    public function setEntityClass(string $className): static
    {
        $this->entityClass = $className;
        return $this;
    }

    /** @return string */
    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    /**
     * @param string $className
     * @return $this
     */
    public function setCollectionClass(string $className): static
    {
        $this->collectionClass = $className;
        return $this;
    }

    /** @return string */
    public function getCollectionClass(): string
    {
        return $this->collectionClass;
    }

    /**
     * Retrieve the current resource event, if any
     *
     * @return ResourceEvent
     */
    public function getEvent(): ResourceEvent
    {
        return $this->event;
    }

    /**
     * Retrieve the identity, if any
     *
     * Proxies to the resource event to find the identity, if not already
     * composed, and composes it.
     *
     * @return null|IdentityInterface
     */
    public function getIdentity(): ?IdentityInterface
    {
        if ($this->identity) {
            return $this->identity;
        }

        $event = $this->getEvent();

        $this->identity = $event->getIdentity();
        return $this->identity;
    }

    /**
     * Retrieve the input filter, if any
     *
     * Proxies to the resource event to find the input filter, if not already
     * composed, and composes it.
     *
     * @return null|InputFilterInterface
     */
    public function getInputFilter(): ?InputFilterInterface
    {
        if ($this->inputFilter) {
            return $this->inputFilter;
        }

        $event = $this->getEvent();

        $this->inputFilter = $event->getInputFilter();
        return $this->inputFilter;
    }

    /**
     * Attach listeners for all Resource events
     *
     * @param int $priority
     */
    #[Override]
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(eventName: 'create', listener: $this->dispatch(...));
        $this->listeners[] = $events->attach(eventName: 'delete', listener: $this->dispatch(...));
        $this->listeners[] = $events->attach(eventName: 'deleteList', listener: $this->dispatch(...));
        $this->listeners[] = $events->attach(eventName: 'fetch', listener: $this->dispatch(...));
        $this->listeners[] = $events->attach(eventName: 'fetchAll', listener: $this->dispatch(...));
        $this->listeners[] = $events->attach(eventName: 'patch', listener: $this->dispatch(...));
        $this->listeners[] = $events->attach(eventName: 'patchList', listener: $this->dispatch(...));
        $this->listeners[] = $events->attach(eventName: 'replaceList', listener: $this->dispatch(...));
        $this->listeners[] = $events->attach(eventName: 'update', listener: $this->dispatch(...));
    }

    /**
     * Dispatch an incoming event to the appropriate method
     *
     * Marshals arguments from the event parameters.
     *
     */
    public function dispatch(ResourceEvent $event): mixed
    {
        $this->event = $event;
        switch ($event->getName()) {
            case 'create':
                $data = $event->getParam(name: 'data', default: []);
                return $this->create(data: $data);
            case 'delete':
                $id = $event->getParam(name: 'id', default: null);
                return $this->delete(id: $id);
            case 'deleteList':
                $data = $event->getParam(name: 'data', default: []);
                return $this->deleteList(data: $data);
            case 'fetch':
                $id = $event->getParam(name: 'id', default: null);
                return $this->fetch(id: $id);
            case 'fetchAll':
                $queryParams = $event->getQueryParams() ?: [];
                return $this->fetchAll(params: $queryParams);
            case 'patch':
                $id   = $event->getParam(name: 'id', default: null);
                $data = $event->getParam(name: 'data', default: []);
                return $this->patch(id: $id, data: $data);
            case 'patchList':
                $data = $event->getParam(name: 'data', default: []);
                return $this->patchList(data: $data);
            case 'replaceList':
                $data = $event->getParam(name: 'data', default: []);
                return $this->replaceList(data: $data);
            case 'update':
                $id   = $event->getParam(name: 'id', default: null);
                $data = $event->getParam(name: 'data', default: []);
                return $this->update(id: $id, data: $data);
            default:
                throw new Exception\RuntimeException(
                    message: sprintf(
                        '%s has not been setup to handle the event "%s"',
                        __METHOD__,
                        $event->getName()
                    )
                );
        }
    }

    /**
     * Create a resource
     *
     * @return ApiProblem|mixed
     */
    public function create(mixed $data): mixed
    {
        return new ApiProblem(status: 405, detail: 'The POST method has not been defined');
    }

    /**
     * Delete a resource
     *
     * @return ApiProblem|mixed
     */
    public function delete(mixed $id): mixed
    {
        return new ApiProblem(status: 405, detail: 'The DELETE method has not been defined for individual resources');
    }

    /**
     * Delete a collection, or members of a collection
     *
     * @return ApiProblem|mixed
     */
    public function deleteList(mixed $data): mixed
    {
        return new ApiProblem(status: 405, detail: 'The DELETE method has not been defined for collections');
    }

    /**
     * Fetch a resource
     *
     * @return ApiProblem|mixed
     */
    public function fetch(mixed $id): mixed
    {
        return new ApiProblem(status: 405, detail: 'The GET method has not been defined for individual resources');
    }

    /**
     * Fetch all or a subset of resources
     *
     * @param array|Parameters $params
     * @return ApiProblem|mixed
     */
    public function fetchAll(array|Parameters $params = []): mixed
    {
        return new ApiProblem(status: 405, detail: 'The GET method has not been defined for collections');
    }

    /**
     * Patch (partial in-place update) a resource
     *
     * @return ApiProblem|mixed
     */
    public function patch(mixed $id, mixed $data): mixed
    {
        return new ApiProblem(status: 405, detail: 'The PATCH method has not been defined for individual resources');
    }

    /**
     * Patch (partial in-place update) a collection or members of a collection
     *
     * @return ApiProblem|mixed
     */
    public function patchList(mixed $data): mixed
    {
        return new ApiProblem(status: 405, detail: 'The PATCH method has not been defined for collections');
    }

    /**
     * Replace a collection or members of a collection
     *
     * @return ApiProblem|mixed
     */
    public function replaceList(mixed $data): mixed
    {
        return new ApiProblem(status: 405, detail: 'The PUT method has not been defined for collections');
    }

    /**
     * Update a resource
     *
     * @return ApiProblem|mixed
     */
    public function update(mixed $id, mixed $data): mixed
    {
        return new ApiProblem(status: 405, detail: 'The PUT method has not been defined for individual resources');
    }
}
