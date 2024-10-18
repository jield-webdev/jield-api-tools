<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal;

use Exception;
use Jield\ApiTools\Hal\Exception\InvalidArgumentException;
use Jield\ApiTools\Hal\Exception\InvalidCollectionException;
use Laminas\Paginator\Paginator;
use Laminas\Stdlib\ArrayUtils;
use Traversable;
use function get_debug_type;
use function is_array;
use function sprintf;
use function trigger_error;
use const E_USER_DEPRECATED;

/**
 * Model a collection for use with HAL payloads
 */
class Collection implements Link\LinkCollectionAwareInterface
{
    use Link\LinkCollectionAwareTrait;

    /**
     * Additional attributes to render with the collection
     *
     * @var array
     */
    protected $attributes = [];

    /** @var Paginator|Traversable|array<array-key, mixed> */
    protected $collection;

    /**
     * Name of collection (used to identify it in the "_embedded" object)
     *
     * @var string
     */
    protected $collectionName = 'items';

    /** @var string */
    protected $collectionRoute;

    /** @var array */
    protected $collectionRouteOptions = [];

    /** @var array */
    protected $collectionRouteParams = [];

    /**
     * Name of the field representing the identifier
     *
     * @var string
     */
    protected $entityIdentifierName = 'id';

    /**
     * Name of the route parameter identifier for individual entities of the collection
     *
     * @var string
     */
    protected $routeIdentifierName = 'id';

    /**
     * Current page
     *
     * @var int
     */
    protected $page = 1;

    /**
     * Number of entities per page
     *
     * @var int
     */
    protected $pageSize = 30;

    /** @var Link\LinkCollection */
    protected $entityLinks;

    /** @var string */
    protected $entityRoute;

    /** @var array */
    protected $entityRouteOptions = [];

    /** @var array */
    protected $entityRouteParams = [];

    /**
     * @param Traversable|array<array-key, mixed>|Paginator $collection
     * @param string|null $entityRoute
     * @param Traversable|array|null $entityRouteParams
     * @param Traversable|array|null $entityRouteOptions
     * @throws InvalidCollectionException
     */
    public function __construct(array|Traversable|Paginator $collection, string $entityRoute = null, Traversable|array $entityRouteParams = null, Traversable|array $entityRouteOptions = null)
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (!is_array(value: $collection) && !$collection instanceof Traversable) {
            throw new InvalidCollectionException(message: sprintf(
                '%s expects an array or Traversable; received "%s"',
                __METHOD__,
                get_debug_type(value: $collection)
            ));
        }

        /** @psalm-suppress PossiblyInvalidPropertyAssignmentValue */
        $this->collection = $collection;

        if (null !== $entityRoute) {
            $this->setEntityRoute(route: $entityRoute);
        }

        if (null !== $entityRouteParams) {
            $this->setEntityRouteParams(params: $entityRouteParams);
        }

        if (null !== $entityRouteOptions) {
            $this->setEntityRouteOptions(options: $entityRouteOptions);
        }
    }

    /**
     * Proxy to properties to allow read access
     *
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function __get(string $name)
    {
        throw new Exception(message: 'Direct query of values is deprecated.  Use getters.');
    }

    /**
     * Set additional attributes to render as part of the collection
     *
     * @param array $attributes
     * @return self
     */
    public function setAttributes(array $attributes): static
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * Set the collection name (for use within the _embedded object)
     *
     * @param string $name
     * @return self
     */
    public function setCollectionName(string $name): static
    {
        $this->collectionName = (string)$name;
        return $this;
    }

    /**
     * Set the collection route; used for generating pagination links
     *
     * @param string $route
     * @return self
     */
    public function setCollectionRoute(string $route): static
    {
        $this->collectionRoute = (string)$route;
        return $this;
    }

    /**
     * Set options to use with the collection route; used for generating pagination links
     *
     * @param Traversable|array $options
     * @return self
     * @throws InvalidArgumentException
     */
    public function setCollectionRouteOptions(Traversable|array $options): static
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray(iterator: $options);
        }

        if (!is_array(value: $options)) {
            throw new InvalidArgumentException(message: sprintf(
                '%s expects an array or Traversable; received "%s"',
                __METHOD__,
                get_debug_type(value: $options)
            ));
        }

        $this->collectionRouteOptions = $options;
        return $this;
    }

    /**
     * Set parameters/substitutions to use with the collection route; used for generating pagination links
     *
     * @param Traversable|array $params
     * @return self
     * @throws InvalidArgumentException
     */
    public function setCollectionRouteParams(Traversable|array $params): static
    {
        if ($params instanceof Traversable) {
            $params = ArrayUtils::iteratorToArray(iterator: $params);
        }

        if (!is_array(value: $params)) {
            throw new InvalidArgumentException(message: sprintf(
                '%s expects an array or Traversable; received "%s"',
                __METHOD__,
                get_debug_type(value: $params)
            ));
        }

        $this->collectionRouteParams = $params;
        return $this;
    }

    /**
     * Set the route identifier name
     *
     * @param string $identifier
     * @return self
     */
    public function setRouteIdentifierName(string $identifier): static
    {
        $this->routeIdentifierName = $identifier;
        return $this;
    }

    /**
     * Set the entity identifier name
     *
     * @param string $identifier
     * @return self
     */
    public function setEntityIdentifierName(string $identifier): static
    {
        $this->entityIdentifierName = $identifier;
        return $this;
    }

    /**
     * Set current page
     *
     * @param int $page
     * @return self
     * @throws InvalidArgumentException For non-positive and/or non-integer values.
     */
    public function setPage(int $page): static
    {

        $page = (int)$page;
        if ($page < 1) {
            throw new InvalidArgumentException(message: sprintf(
                'Page must be a positive integer; received "%s"',
                $page
            ));
        }

        $this->page = $page;
        return $this;
    }

    /**
     * Set page size
     *
     * @param int $size
     * @return self
     * @throws InvalidArgumentException For non-positive and/or non-integer values.
     */
    public function setPageSize(int $size): static
    {
        $size = (int)$size;
        if ($size < 1 && $size !== -1) {
            throw new InvalidArgumentException(message: sprintf(
                'size must be a positive integer or -1 (to disable pagination); received "%s"',
                $size
            ));
        }

        $this->pageSize = $size;
        return $this;
    }

    /**
     * Set default set of links to use for entities
     *
     */
    public function setEntityLinks(Link\LinkCollection $links): static
    {
        $this->entityLinks = $links;
        return $this;
    }

    /**
     * Set default set of links to use for entities
     *
     * Deprecated; please use setEntityLinks().
     *
     * @deprecated
     *
     */
    public function setResourceLinks(Link\LinkCollection $links): static
    {
        trigger_error(message: sprintf(
            '%s is deprecated; please use %s::setEntityLinks',
            __METHOD__,
            self::class
        ), error_level: E_USER_DEPRECATED);
        return $this->setEntityLinks(links: $links);
    }

    /**
     * Set the entity route
     *
     * @param string $route
     * @return self
     */
    public function setEntityRoute(string $route): static
    {
        $this->entityRoute = (string)$route;
        return $this;
    }

    /**
     * Set the entity route
     *
     * Deprecated; please use setEntityRoute().
     *
     * @param string $route
     * @return self
     * @deprecated
     *
     */
    public function setResourceRoute(string $route): static
    {
        trigger_error(message: sprintf(
            '%s is deprecated; please use %s::setEntityRoute',
            __METHOD__,
            self::class
        ), error_level: E_USER_DEPRECATED);
        return $this->setEntityRoute(route: $route);
    }

    /**
     * Set options to use with the entity route
     *
     * @param Traversable|array $options
     * @return self
     * @throws InvalidArgumentException
     */
    public function setEntityRouteOptions(Traversable|array $options): static
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray(iterator: $options);
        }

        if (!is_array(value: $options)) {
            throw new InvalidArgumentException(message: sprintf(
                '%s expects an array or Traversable; received "%s"',
                __METHOD__,
                get_debug_type(value: $options)
            ));
        }

        $this->entityRouteOptions = $options;
        return $this;
    }

    /**
     * Set options to use with the entity route
     *
     * Deprecated; please use setEntityRouteOptions().
     *
     * @param Traversable|array $options
     * @return self
     * @throws InvalidArgumentException
     * @deprecated
     *
     */
    public function setResourceRouteOptions(Traversable|array $options): static
    {
        trigger_error(message: sprintf(
            '%s is deprecated; please use %s::setEntityRouteOptions',
            __METHOD__,
            self::class
        ), error_level: E_USER_DEPRECATED);
        return $this->setEntityRouteOptions(options: $options);
    }

    /**
     * Set parameters/substitutions to use with the entity route
     *
     * @param Traversable|array $params
     * @return self
     * @throws InvalidArgumentException
     */
    public function setEntityRouteParams(Traversable|array $params): static
    {
        if ($params instanceof Traversable) {
            $params = ArrayUtils::iteratorToArray(iterator: $params);
        }

        if (!is_array(value: $params)) {
            throw new InvalidArgumentException(message: sprintf(
                '%s expects an array or Traversable; received "%s"',
                __METHOD__,
                get_debug_type(value: $params)
            ));
        }

        $this->entityRouteParams = $params;
        return $this;
    }

    /**
     * Set parameters/substitutions to use with the entity route
     *
     * Deprecated; please use setEntityRouteParams().
     *
     * @param Traversable|array $params
     * @return self
     * @throws InvalidArgumentException
     * @deprecated
     *
     */
    public function setResourceRouteParams(Traversable|array $params): static
    {
        trigger_error(message: sprintf(
            '%s is deprecated; please use %s::setEntityRouteParams',
            __METHOD__,
            self::class
        ), error_level: E_USER_DEPRECATED);
        return $this->setEntityRouteParams(params: $params);
    }

    /**
     * Retrieve default entity links, if any
     *
     * @return null|Link\LinkCollection
     */
    public function getEntityLinks(): ?Link\LinkCollection
    {
        return $this->entityLinks;
    }

    /**
     * Retrieve default entity links, if any
     *
     * Deprecated; please use getEntityLinks().
     *
     * @return null|Link\LinkCollection
     * @deprecated
     *
     */
    public function getResourceLinks(): ?Link\LinkCollection
    {
        trigger_error(message: sprintf(
            '%s is deprecated; please use %s::getEntityLinks',
            __METHOD__,
            self::class
        ), error_level: E_USER_DEPRECATED);
        return $this->getEntityLinks();
    }

    /**
     * Attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Collection
     *
     * @return array|Traversable|Paginator
     */
    public function getCollection(): array|Traversable|Paginator
    {
        return $this->collection;
    }

    /**
     * Collection Name
     *
     * @return string
     */
    public function getCollectionName(): string
    {
        return $this->collectionName;
    }

    /**
     * Collection Route
     *
     * @return string
     */
    public function getCollectionRoute(): string
    {
        return $this->collectionRoute;
    }

    /**
     * Collection Route Options
     *
     * @return array
     */
    public function getCollectionRouteOptions(): array
    {
        return $this->collectionRouteOptions;
    }

    /**
     * Collection Route Params
     *
     * @return array
     */
    public function getCollectionRouteParams(): array
    {
        return $this->collectionRouteParams;
    }

    /**
     * Route Identifier Name
     *
     * @return string
     */
    public function getRouteIdentifierName(): string
    {
        return $this->routeIdentifierName;
    }

    /**
     * Entity Identifier Name
     *
     * @return string
     */
    public function getEntityIdentifierName(): string
    {
        return $this->entityIdentifierName;
    }

    /**
     * Entity Route
     *
     * @return string
     */
    public function getEntityRoute(): string
    {
        return $this->entityRoute;
    }

    /**
     * Entity Route
     *
     * Deprecated; please use getEntityRoute().
     *
     * @return string
     * @deprecated
     *
     */
    public function getResourceRoute(): string
    {
        trigger_error(message: sprintf(
            '%s is deprecated; please use %s::getEntityRoute',
            __METHOD__,
            self::class
        ), error_level: E_USER_DEPRECATED);
        return $this->getEntityRoute();
    }

    /**
     * Entity Route Options
     *
     * @return array
     */
    public function getEntityRouteOptions(): array
    {
        return $this->entityRouteOptions;
    }

    /**
     * Entity Route Options
     *
     * Deprecated; please use getEntityRouteOptions().
     *
     * @return array
     * @deprecated
     *
     */
    public function getResourceRouteOptions(): array
    {
        trigger_error(message: sprintf(
            '%s is deprecated; please use %s::getEntityRouteOptions',
            __METHOD__,
            self::class
        ), error_level: E_USER_DEPRECATED);
        return $this->getEntityRouteOptions();
    }

    /**
     * Entity Route Params
     *
     * @return array
     */
    public function getEntityRouteParams(): array
    {
        return $this->entityRouteParams;
    }

    /**
     * Entity Route Params
     *
     * Deprecated; please use getEntityRouteParams().
     *
     * @return array
     * @deprecated
     *
     */
    public function getResourceRouteParams(): array
    {
        trigger_error(message: sprintf(
            '%s is deprecated; please use %s::getEntityRouteParams',
            __METHOD__,
            self::class
        ), error_level: E_USER_DEPRECATED);
        return $this->getEntityRouteParams();
    }

    /**
     * Page
     *
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * Page Size
     *
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }
}
