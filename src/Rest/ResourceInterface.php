<?php

declare(strict_types=1);

namespace Jield\ApiTools\Rest;

use Laminas\EventManager\EventManagerAwareInterface;
use Laminas\Paginator\Paginator;

/**
 * Interface describing operations for a given resource.
 */
interface ResourceInterface extends EventManagerAwareInterface
{
    /**
     * Set the event parameters
     *
     * @param array $params
     * @return self
     */
    public function setEventParams(array $params): ResourceInterface;

    /**
     * Get the event parameters
     *
     * @return array
     */
    public function getEventParams(): array;

    /**
     * @param string $name
     */
    public function setEventParam(string $name, mixed $value): mixed;

    public function getEventParam(mixed $name, mixed $default = null): mixed;

    /**
     * Create a record in the resource
     *
     * @param object|array $data
     * @return array|object
     */
    public function create(object|array $data): object|array;

    /**
     * Update (replace) an existing record
     *
     * @param int|string $id
     * @param object|array $data
     * @return array|object
     */
    public function update(int|string $id, object|array $data): object|array;

    /**
     * Update (replace) an existing collection of records
     *
     * @param array $data
     * @return array|object
     */
    public function replaceList(array $data): object|array;

    /**
     * Partial update of an existing record
     *
     * @param int|string $id
     * @param object|array $data
     * @return array|object
     */
    public function patch(int|string $id, object|array $data): object|array;

    /**
     * Delete an existing record
     *
     * @param int|string $id
     * @return bool
     */
    public function delete(int|string $id): bool;

    /**
     * Delete an existing collection of records
     *
     * @param array|null $data
     * @return bool
     */
    public function deleteList(array $data = null): bool;

    /**
     * Fetch an existing record
     *
     * @param int|string $id
     * @return false|array|object
     */
    public function fetch(int|string $id): object|false|array;

    /**
     * Fetch a collection of records
     *
     * @return Paginator
     */
    public function fetchAll(): Paginator;
}
