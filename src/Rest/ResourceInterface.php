<?php

declare(strict_types=1);

namespace Jield\ApiTools\Rest;

use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\Paginator\Paginator;
use Laminas\Stdlib\Parameters;

/**
 * Interface describing operations for a given resource.
 */
interface ResourceInterface extends EventManagerAwareInterface
{
    /**
     * Set the event parameters
     */
    public function setEventParams(array $params): ResourceInterface;

    /**
     * Get the event parameters
     */
    public function getEventParams(): array;

    /**
     * @param string $name
     */
    public function setEventParam(string $name, mixed $value): mixed;

    public function getEventParam(mixed $name, mixed $default = null): mixed;

    /**
     * Create a record in the resource
     */
    public function create(Parameters $data): mixed;

    /**
     * Update (replace) an existing record
     */
    public function update(int|string $id, object|array $data): mixed;

    /**
     * Update (replace) an existing collection of records
     */
    public function replaceList(array $data): mixed;

    /**
     * Partial update of an existing record
     */
    public function patch(int|string $id, object|array $data): mixed;

    /**
     * Delete an existing record
     */
    public function delete(int|string $id): mixed;

    /**
     * Delete an existing collection of records
     */
    public function deleteList(array $data = null): mixed;

    /**
     * Fetch an existing record
     */
    public function fetch(int|string $id): mixed;

    /**
     * Fetch a collection of records
     */
    public function fetchAll(): Paginator;
}
