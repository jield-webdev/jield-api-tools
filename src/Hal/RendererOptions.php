<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal;

use Laminas\Stdlib\AbstractOptions;

class RendererOptions extends AbstractOptions
{
    /** @var string */
    protected string $defaultHydrator;

    /** @var bool */
    protected bool $renderEmbeddedEntities = true;

    /** @var bool */
    protected bool $renderEmbeddedCollections = true;

    /** @var array */
    protected array $hydrators = [];

    /**
     * @param string $hydrator
     * @return void
     */
    public function setDefaultHydrator(string $hydrator): void
    {
        $this->defaultHydrator = $hydrator;
    }

    /**
     * @return string
     */
    public function getDefaultHydrator(): string
    {
        return $this->defaultHydrator;
    }

    /**
     * @param bool $flag
     * @return void
     */
    public function setRenderEmbeddedEntities(bool $flag): void
    {
        $this->renderEmbeddedEntities = (bool) $flag;
    }

    /**
     * @return bool
     */
    public function getRenderEmbeddedEntities(): bool
    {
        return $this->renderEmbeddedEntities;
    }

    /**
     * @param bool $flag
     * @return void
     */
    public function setRenderEmbeddedCollections(bool $flag): void
    {
        $this->renderEmbeddedCollections = (bool) $flag;
    }

    /**
     * @return bool
     */
    public function getRenderEmbeddedCollections(): bool
    {
        return $this->renderEmbeddedCollections;
    }

    /**
     * @param array $hydrators
     * @return void
     */
    public function setHydrators(array $hydrators): void
    {
        $this->hydrators = $hydrators;
    }

    /**
     * @return array
     */
    public function getHydrators(): array
    {
        return $this->hydrators;
    }
}
