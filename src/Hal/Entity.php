<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal;

use Laminas\Paginator\Paginator;

class Entity implements Link\LinkCollectionAwareInterface
{
    use Link\LinkCollectionAwareTrait;

    protected array|Paginator $entity = [];

    /**
     * @throws Exception\InvalidEntityException If entity is not an object or array.
     */
    public function __construct(Paginator|array $entity, protected mixed $id = null)
    {
        $this->entity = $entity;
    }

    public function getId(): mixed
    {
        return $this->id;
    }

    public function getEntity(): object|array
    {
        return $this->entity;
    }
}
