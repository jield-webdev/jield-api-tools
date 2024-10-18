<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal;

use Closure;
use Jield\ApiTools\Hal\Exception;
use Jield\ApiTools\Hal\Extractor\EntityExtractor;
use Jield\ApiTools\Hal\Link\Link;
use Jield\ApiTools\Hal\Link\LinkCollection;
use Jield\ApiTools\Hal\Metadata\Metadata;
use Laminas\Paginator\Paginator;
use Traversable;

use function array_merge;
use function get_debug_type;
use function is_array;
use function is_callable;
use function is_string;
use function sprintf;
use function str_contains;

class ResourceFactory
{
    /** @var EntityHydratorManager */
    protected $entityHydratorManager;

    /** @var EntityExtractor */
    protected $entityExtractor;

    public function __construct(EntityHydratorManager $entityHydratorManager, EntityExtractor $entityExtractor)
    {
        $this->entityHydratorManager = $entityHydratorManager;
        $this->entityExtractor       = $entityExtractor;
    }

    /**
     * Create a entity and/or collection based on a metadata map
     *
     * @param Traversable|array<array-key, mixed>|Paginator<int, mixed> $object
     * @param bool $renderEmbeddedEntities
     * @throws Exception\RuntimeException
     */
    public function createEntityFromMetadata(array|Traversable|Paginator $object, Metadata $metadata, bool $renderEmbeddedEntities = true): Entity|Collection
    {
        if ($metadata->isCollection()) {
            return $this->createCollectionFromMetadata(object: $object, metadata: $metadata);
        }

        /** @psalm-var array<string,mixed> $data */
        $data = $this->entityExtractor->extract(object: $object);

        $entityIdentifierName = $metadata->getEntityIdentifierName();
        if ($entityIdentifierName && ! isset($data[$entityIdentifierName])) {
            throw new Exception\RuntimeException(message: sprintf(
                'Unable to determine entity identifier for object of type "%s"; no fields matching "%s"',
                get_debug_type(value: $object),
                $entityIdentifierName
            ));
        }

        /** @var string|null $id */
        $id = $entityIdentifierName !== '' && $entityIdentifierName !== '0' ? $data[$entityIdentifierName] : null;

        if (! $renderEmbeddedEntities) {
            $object = [];
        }

        $halEntity = new Entity(entity: $object, id: $id);

        $links = $halEntity->getLinks();
        $this->marshalMetadataLinks(metadata: $metadata, links: $links);

        $forceSelfLink = $metadata->getForceSelfLink();
        if ($forceSelfLink && ! $links->has(relation: 'self')) {
            $link = $this->marshalLinkFromMetadata(
                metadata: $metadata,
                object: $object,
                id: $id,
                routeIdentifierName: $metadata->getRouteIdentifierName()
            );
            $links->add(link: $link);
        }

        return $halEntity;
    }

    /**
     * @param Traversable|array|Paginator $object
     */
    public function createCollectionFromMetadata(Traversable|array|Paginator $object, Metadata $metadata): Collection
    {
        $halCollection = new Collection(collection: $object);
        $halCollection->setCollectionName(name: $metadata->getCollectionName());
        $halCollection->setCollectionRoute(route: $metadata->getRoute());
        $halCollection->setEntityRoute(route: $metadata->getEntityRoute());
        $halCollection->setRouteIdentifierName(identifier: $metadata->getRouteIdentifierName());
        $halCollection->setEntityIdentifierName(identifier: $metadata->getEntityIdentifierName());

        $links = $halCollection->getLinks();
        $this->marshalMetadataLinks(metadata: $metadata, links: $links);

        $forceSelfLink = $metadata->getForceSelfLink();
        if (
            $forceSelfLink && ! $links->has(relation: 'self')
            && ($metadata->hasUrl() || $metadata->hasRoute())
        ) {
            $link = $this->marshalLinkFromMetadata(metadata: $metadata, object: $object);
            $links->add(link: $link);
        }

        return $halCollection;
    }

    /**
     * Creates a link object, given metadata and a resource
     *
     * @param object|iterable<array-key|mixed, mixed>|Paginator<int, mixed> $object
     * @param string|null $id
     * @param string|null $routeIdentifierName
     * @param string $relation
     * @throws Exception\RuntimeException
     */
    public function marshalLinkFromMetadata(
        Metadata               $metadata,
        array|object|Paginator $object,
        string                 $id = null,
        string                 $routeIdentifierName = null,
        string                 $relation = 'self'
    ): Link
    {
        $link = new Link(relation: $relation);
        if ($metadata->hasUrl()) {
            $link->setUrl(href: $metadata->getUrl());
            return $link;
        }

        if (! $metadata->hasRoute()) {
            throw new Exception\RuntimeException(message: sprintf(
                'Unable to create a self link for resource of type "%s"; metadata does not contain a route or a href',
                get_debug_type(value: $object)
            ));
        }

        $params = $metadata->getRouteParams();

        // process any callbacks
        /** @var mixed $param */
        foreach ($params as $key => $param) {
            // bind to the object
            if ($param instanceof Closure) {
                /** @psalm-var object $object */
                $param = $param->bindTo($object);
            }

            // invoke callables with the object
            if (is_callable(value: $param)) {
                // @todo remove when minimum supported PHP version is bumped to 8.1 or greater
                $callback = is_array(value: $param) || (is_string(value: $param) && str_contains(haystack: $param, needle: '::'))
                    ? Closure::fromCallable(callback: $param)
                    : $param;
                /** @var mixed $value */
                $value        = $callback($object);
                $params[$key] = $value;
            }
        }

        if ($routeIdentifierName) {
            $params = array_merge($params, [$routeIdentifierName => $id]);
        }

        $link->setRoute(route: $metadata->getRoute(), params: $params, options: $metadata->getRouteOptions());
        return $link;
    }

    /**
     * Inject any links found in the metadata into the resource's link collection
     *
     */
    public function marshalMetadataLinks(Metadata $metadata, LinkCollection $links): void
    {
        foreach ($metadata->getLinks() as $linkData) {
            $link = Link::factory(spec: $linkData);
            $links->add(link: $link);
        }
    }
}
