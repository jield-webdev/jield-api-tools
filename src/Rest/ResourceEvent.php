<?php

declare(strict_types=1);

namespace Jield\ApiTools\Rest;

use ArrayAccess;
use InvalidArgumentException;
use Jield\ApiTools\MvcAuth\Identity\IdentityInterface;
use Laminas\EventManager\Event;
use Laminas\EventManager\Exception\InvalidArgumentException as EventManagerInvalidArgumentException;
use Laminas\InputFilter\InputFilterInterface;
use Laminas\Router\RouteMatch;
use Laminas\Stdlib\Parameters;
use Laminas\Stdlib\RequestInterface;
use Override;
use function gettype;
use function is_array;
use function is_object;
use function sprintf;

class ResourceEvent extends Event
{
    /** @var null|IdentityInterface */
    protected $identity;

    /** @var null|InputFilterInterface */
    protected $inputFilter;

    /** @var null|Parameters */
    protected $queryParams;

    /** @var null|RequestInterface */
    protected $request;

    /** @var null|RouteMatch */
    protected $routeMatch;

    /**
     * Overload setParams to inject request object, if passed via params
     *
     * @param array|ArrayAccess|object $params
     * @return self
     */
    #[Override]
    public function setParams($params): static
    {
        if (!is_array(value: $params) && !is_object(value: $params)) {
            throw new EventManagerInvalidArgumentException(message: sprintf(
                'Event parameters must be an array or object; received "%s"',
                gettype(value: $params)
            ));
        }

        if ((is_array(value: $params) || $params instanceof ArrayAccess) && isset($params['request'])) {
            $this->setRequest(request: $params['request']);
            unset($params['request']);
        }

        parent::setParams(params: $params);
        return $this;
    }

    public function setIdentity(?IdentityInterface $identity = null): static
    {
        $this->identity = $identity;
        return $this;
    }

    /**
     * @return null|IdentityInterface
     */
    public function getIdentity(): ?IdentityInterface
    {
        return $this->identity;
    }

    public function setInputFilter(?InputFilterInterface $inputFilter = null): static
    {
        $this->inputFilter = $inputFilter;
        return $this;
    }

    /**
     * @return null|InputFilterInterface
     */
    public function getInputFilter(): ?InputFilterInterface
    {
        return $this->inputFilter;
    }

    public function setQueryParams(?Parameters $params = null): static
    {
        $this->queryParams = $params;
        return $this;
    }

    /**
     * @return null|Parameters
     */
    public function getQueryParams(): ?Parameters
    {
        return $this->queryParams;
    }

    /**
     * Retrieve a single query parameter by name
     *
     * If not present, returns the $default value provided.
     *
     * @param string $name
     */
    public function getQueryParam(string $name, mixed $default = null): mixed
    {
        $params = $this->getQueryParams();
        if (!$params instanceof \Laminas\Stdlib\Parameters) {
            return $default;
        }

        return $params->get(name: $name, default: $default);
    }

    public function setRequest(?RequestInterface $request = null): static
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return null|RequestInterface
     */
    public function getRequest(): ?RequestInterface
    {
        return $this->request;
    }

    /**
     * @param RouteMatch|V2RouteMatch|null $matches
     * @return self
     */
    public function setRouteMatch(V2RouteMatch|RouteMatch $matches = null): static
    {
        if (null !== $matches && (!$matches instanceof RouteMatch && !$matches instanceof V2RouteMatch)) {
            throw new InvalidArgumentException(message: sprintf(
                '%s expects a null or %s or %s instances; received %s',
                __METHOD__,
                RouteMatch::class,
                V2RouteMatch::class,
                get_debug_type(value: $matches)
            ));
        }

        $this->routeMatch = $matches;
        return $this;
    }

    /**
     * @return null|RouteMatch|V2RouteMatch
     */
    public function getRouteMatch(): V2RouteMatch|RouteMatch|null
    {
        return $this->routeMatch;
    }

    /**
     * Retrieve a single route match parameter by name.
     *
     * If not present, returns the $default value provided.
     *
     * @param string $name
     */
    public function getRouteParam(string $name, mixed $default = null): mixed
    {
        $matches = $this->getRouteMatch();
        if (null === $matches) {
            return $default;
        }

        return $matches->getParam(name: $name, default: $default);
    }
}
