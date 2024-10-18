<?php

declare(strict_types=1);

namespace Jield\ApiTools\ContentNegotiation;

class ParameterDataContainer
{
    /** @var array */
    protected $routeParams = [];

    /** @var array */
    protected $queryParams = [];

    /** @var array */
    protected $bodyParams = [];

    /**
     * @return array
     */
    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    /**
     * @param  array $routeParams
     * @return self
     */
    public function setRouteParams(array $routeParams): static
    {
        $this->routeParams = $routeParams;
        return $this;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasRouteParam(string $name): bool
    {
        return isset($this->routeParams[$name]);
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    public function getRouteParam(string $name, mixed $default = null): mixed
    {
        if (isset($this->routeParams[$name])) {
            return $this->routeParams[$name];
        }

        return $default;
    }

    /**
     * @param string $name
     */
    public function setRouteParam(string $name, mixed $value): static
    {
        $this->routeParams[$name] = $value;
        return $this;
    }

    /**
     * @param  array $queryParams
     * @return self
     */
    public function setQueryParams(array $queryParams): static
    {
        $this->queryParams = $queryParams;
        return $this;
    }

    /**
     * @return array
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasQueryParam(string $name): bool
    {
        return isset($this->queryParams[$name]);
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    public function getQueryParam(string $name, mixed $default = null): mixed
    {
        if (isset($this->queryParams[$name])) {
            return $this->queryParams[$name];
        }

        return $default;
    }

    /**
     * @param string $name
     */
    public function setQueryParam(string $name, mixed $value): static
    {
        $this->queryParams[$name] = $value;
        return $this;
    }

    /**
     * @param  array $bodyParams
     * @return self
     */
    public function setBodyParams(array $bodyParams): static
    {
        $this->bodyParams = $bodyParams;
        return $this;
    }

    /**
     * @return array
     */
    public function getBodyParams(): array
    {
        return $this->bodyParams;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasBodyParam(string $name): bool
    {
        return isset($this->bodyParams[$name]);
    }

    /**
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     */
    public function getBodyParam(string $name, mixed $default = null): mixed
    {
        if (isset($this->bodyParams[$name])) {
            return $this->bodyParams[$name];
        }

        return $default;
    }

    /**
     * @param string $name
     */
    public function setBodyParam(string $name, mixed $value): static
    {
        $this->bodyParams[$name] = $value;
        return $this;
    }
}
