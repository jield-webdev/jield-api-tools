<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal;

use function array_keys;
use function in_array;
use function is_array;
use function is_object;
use function sprintf;
use function strtolower;
use function trigger_error;

use const E_USER_DEPRECATED;

class Entity implements Link\LinkCollectionAwareInterface
{
    use Link\LinkCollectionAwareTrait;

    /** @var object|array */
    protected $entity;

    /**
     * @param object|array $entity
     * @throws Exception\InvalidEntityException If entity is not an object or array.
     */
    public function __construct(object|array $entity, protected mixed $id = null)
    {
        if (! is_object(value: $entity) && ! is_array(value: $entity)) {
            throw new Exception\InvalidEntityException();
        }

        $this->entity = $entity;
    }

    /**
     * Retrieve properties
     *
     * @param  string $name
     * @return mixed
     *@throws Exception\InvalidArgumentException
     * @deprecated
     *
     */
    public function &__get(string $name)
    {
        trigger_error(
            message: sprintf(
                'Direct property access to %s::$%s is deprecated, use getters instead.',
                self::class,
                $name
            ),
            error_level: E_USER_DEPRECATED
        );
        $names = [
            'entity' => 'entity',
            'id'     => 'id',
        ];
        $name  = strtolower(string: $name);
        if (! in_array(needle: $name, haystack: array_keys(array: $names))) {
            throw new Exception\InvalidArgumentException(message: sprintf(
                'Invalid property name "%s"',
                $name
            ));
        }

        $prop = $names[$name];
        return $this->{$prop};
    }

    /**
     * @return mixed
     */
    public function getId(): mixed
    {
        return $this->id;
    }

    /**
     * TODO: Get by reference is that really necessary?
     *
     * @return object|array
     */
    public function &getEntity(): object|array
    {
        return $this->entity;
    }
}
