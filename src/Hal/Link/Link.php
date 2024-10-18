<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal\Link;

use Jield\ApiTools\ApiProblem\Exception\DomainException;
use Jield\ApiTools\Hal\Exception;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Uri\Exception as UriException;
use Laminas\Uri\UriFactory;
use Override;
use Psr\Link\LinkInterface;
use Traversable;

use function get_debug_type;
use function is_array;
use function is_string;
use function reset;
use function sprintf;

/**
 * Object describing a link relation
 */
class Link implements LinkInterface
{
    /** @var array<string,mixed> */
    protected $attributes = [];

    /** @var string[] */
    protected $rels;

    /** @var string */
    protected $route;

    /** @var array */
    protected $routeOptions = [];

    /** @var array<string,mixed> */
    protected $routeParams = [];

    /** @var string|null */
    protected $href;

    /**
     * Create a link relation
     *
     * @param string|array<array-key, string> $relation
     *@todo  filtering and/or validation of relation string
     */
    public function __construct(array|string $relation)
    {
        if (! is_array(value: $relation)) {
            $relation = [(string) $relation];
        }

        $this->rels = $relation;
    }

    /**
     * Factory for creating links
     * $spec['url'] is deprecated since 1.5.0; use $spec['href'] instead
     *
     * @psalm-param array{
     *     rel: string|array<array-key,string>,
     *     props?: array<array-key,mixed>,
     *     href?: string,
     *     route?: string|array{name:string,params:string|array<array-key,mixed>,options:string|array<array-key,mixed>},
     *     url?: string
     * } $spec
     * @throws Exception\InvalidArgumentException If missing a "rel" or invalid route specifications.
     */
    public static function factory(array $spec): Link
    {
        if (! isset($spec['rel'])) {
            throw new Exception\InvalidArgumentException(message: sprintf(
                '%s requires that the specification array contain a "rel" element; none found',
                __METHOD__
            ));
        }

        $link = new static(relation: $spec['rel']);
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        if (
            isset($spec['props'])
            && is_array(value: $spec['props'])
        ) {
            /** @var array<string, mixed> $props */
            $props = $spec['props'];
            $link->setProps(props: $props);
        }

        // deprecated since 1.5.0; use 'href' instead
        if (isset($spec['url'])) {
            $url = $spec['url'];
            $link->setUrl(href: $url);
            return $link;
        }

        /** @psalm-suppress RedundantConditionGivenDocblockType */
        if (isset($spec['href']) && is_string(value: $spec['href'])) {
            $link->href = $spec['href'];
            return $link;
        }

        if (isset($spec['route'])) {
            $routeInfo = $spec['route'];
            if (is_string(value: $routeInfo)) {
                $link->setRoute(route: $routeInfo);
                return $link;
            }

            /** @psalm-suppress DocblockTypeContradiction */
            if (! is_array(value: $routeInfo)) {
                throw new Exception\InvalidArgumentException(message: sprintf(
                    '%s requires that the specification array\'s "route" element be a string or array; received "%s"',
                    __METHOD__,
                    get_debug_type(value: $routeInfo)
                ));
            }

            if (! isset($routeInfo['name'])) {
                throw new Exception\InvalidArgumentException(message: sprintf(
                    '%s requires that the specification array\'s "route" array contain a "name" element; none found',
                    __METHOD__
                ));
            }

            $name    = $routeInfo['name'];
            $params  = isset($routeInfo['params']) && is_array(value: $routeInfo['params'])
                ? $routeInfo['params']
                : [];
            $options = isset($routeInfo['options']) && is_array(value: $routeInfo['options'])
                ? $routeInfo['options']
                : [];
            /** @psalm-suppress RedundantCastGivenDocblockType */
            $link->setRoute(route: (string) $name, params: $params, options: $options);
            return $link;
        }

        return $link;
    }

    /**
     * Set any additional, arbitrary properties to include in the link object
     *
     * "href" will be ignored.
     *
     * @param array<string, mixed> $props
     * @return self
     */
    public function setProps(array $props): static
    {
        if (isset($props['href'])) {
            unset($props['href']);
        }

        $this->attributes = $props;
        return $this;
    }

    /**
     * Set the route to use when generating the relation URI
     *
     * If any params or options are passed, those will be passed to route assembly.
     *
     * @param string $route
     * @param Traversable|array|null $params
     * @param Traversable|array|null $options
     * @return self
     * @throws DomainException
     */
    public function setRoute(string $route, Traversable|array $params = null, Traversable|array $options = null): static
    {
        if ($this->hasUrl()) {
            throw new DomainException(message: sprintf(
                '%s already has a URL set; cannot set route',
                self::class
            ));
        }

        $this->route = (string) $route;
        if ($params) {
            /** @psalm-var array<string,mixed> $params */
            $this->setRouteParams(params: $params);
        }

        if ($options) {
            $this->setRouteOptions(options: $options);
        }

        return $this;
    }

    /**
     * Set route assembly options
     *
     * @param  mixed|array|Traversable $options
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public function setRouteOptions(mixed $options): static
    {
        if ($options instanceof Traversable) {
            $options = ArrayUtils::iteratorToArray(iterator: $options);
        }

        if (! is_array(value: $options)) {
            throw new Exception\InvalidArgumentException(message: sprintf(
                '%s expects an array or Traversable; received "%s"',
                __METHOD__,
                get_debug_type(value: $options)
            ));
        }

        $this->routeOptions = $options;
        return $this;
    }

    /**
     * Set route assembly parameters/substitutions
     *
     * @param Traversable|array<string, mixed> $params
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public function setRouteParams(array|Traversable $params): static
    {
        if (! is_array(value: $params) && ! $params instanceof Traversable) {
            throw new Exception\InvalidArgumentException(message: sprintf(
                '%s expects an array or Traversable; received "%s"',
                __METHOD__,
                get_debug_type(value: $params)
            ));
        }

        if ($params instanceof Traversable) {
            $params = ArrayUtils::iteratorToArray(iterator: $params);
        }

        /** @psalm-var array<string, mixed> $params */
        $this->routeParams = $params;
        return $this;
    }

    /**
     * Set an explicit URL for the link relation
     *
     * @param string $href
     * @return self
     * @throws DomainException
     * @throws Exception\InvalidArgumentException
     */
    public function setUrl(string $href): static
    {
        if ($this->hasRoute()) {
            throw new DomainException(message: sprintf(
                '%s already has a route set; cannot set URL',
                self::class
            ));
        }

        try {
            $uri = UriFactory::factory(uriString: $href);
        } catch (UriException\ExceptionInterface $exception) {
            throw new Exception\InvalidArgumentException(message: sprintf(
                'Received invalid URL: %s',
                $exception->getMessage()
            ), code: (int) $exception->getCode(), previous: $exception);
        }

        if (! $uri->isValid()) {
            throw new Exception\InvalidArgumentException(
                message: 'Received invalid URL'
            );
        }

        $this->href = $href;
        return $this;
    }

    /**
     * Get additional properties to include in Link representation
     *
     * @deprecated 1.4.3 Use getAttributes() instead
     *
     * @return array
     */
    public function getProps(): array
    {
        return $this->getAttributes();
    }

    /**
     * Retrieve the link relation
     *
     * @deprecated 1.4.3 Use getRels() and update your code to handle an array of strings
     *
     * @return string
     */
    public function getRelation(): string
    {
        $rels = $this->getRels();

        return (string) reset(array: $rels);
    }

    /**
     * Return the route to be used to generate the link URL, if any
     *
     * @return null|string
     */
    public function getRoute(): ?string
    {
        return $this->route;
    }

    /**
     * Retrieve route assembly options, if any
     *
     * @return array
     */
    public function getRouteOptions(): array
    {
        return $this->routeOptions;
    }

    /**
     * Retrieve route assembly parameters/substitutions, if any
     *
     * @return array<string,mixed>
     */
    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    /**
     * Retrieve the link URL, if set
     *
     * @deprecated 1.4.3 Use getHref() instead
     *
     * @return null|string
     */
    public function getUrl(): ?string
    {
        return $this->getHref();
    }

    /**
     * Is the link relation complete -- do we have either a URL or a route set?
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return ! empty($this->href) || ! empty($this->route);
    }

    /**
     * Does the link have a route set?
     *
     * @return bool
     */
    public function hasRoute(): bool
    {
        return ! empty($this->route);
    }

    /**
     * Does the link have a URL set?
     *
     * @deprecated since 1.5.0; no empty URLs will be allowed in the future.
     *
     * @return bool
     */
    public function hasUrl(): bool
    {
        return ! empty($this->href);
    }

    /**
     * Returns the target of the link.
     *
     * The target link must be one of:
     * - An absolute URI, as defined by RFC 5988.
     * - A relative URI, as defined by RFC 5988. The base of the relative link
     *   is assumed to be known based on context by the client.
     * - A URI template as defined by RFC 6570.
     *
     * If a URI template is returned, isTemplated() MUST return True.
     */
    #[Override]
    public function getHref(): string
    {
        return (string) $this->href;
    }

    /**
     * Returns whether or not this is a templated link.
     *
     * @return bool True if this link object is templated, False otherwise.
     *     Currently, templated links are not yet supported, so this will
     *     always return false.
     */
    #[Override]
    public function isTemplated(): bool
    {
        return false; // api-tools-hal doesn't support this currently
    }

    /**
     * Returns the relationship type(s) of the link.
     *
     * This method returns 0 or more relationship types for a link, expressed
     * as an array of strings.
     *
     * @return string[]
     */
    #[Override]
    public function getRels(): array
    {
        return $this->rels;
    }

    /**
     * Returns a list of attributes that describe the target URI.
     *
     * @return array<string,mixed>
     *    A key-value list of attributes, where the key is a string and the value
     *    is either a PHP primitive or an array of PHP strings. If no values are
     *    found an empty array MUST be returned.
     */
    #[Override]
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
