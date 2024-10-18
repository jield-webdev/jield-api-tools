<?php

declare(strict_types=1);

namespace Jield\ApiTools\ApiProblem\Exception;

use Override;

class DomainException extends \DomainException implements
    ExceptionInterface,
    ProblemExceptionInterface
{
    /** @var string */
    protected $type;

    /** @var array */
    protected $details = [];

    /** @var string */
    protected $title;

    /**
     * @param array $details
     * @return self
     */
    public function setAdditionalDetails(array $details): static
    {
        $this->details = $details;
        return $this;
    }

    /**
     * @param string $uri
     * @return self
     */
    public function setType(string $uri): static
    {
        $this->type = (string) $uri;
        return $this;
    }

    /**
     * @param string $title
     * @return self
     */
    public function setTitle(string $title): static
    {
        $this->title = (string) $title;
        return $this;
    }

    /**
     * @return array
     */
    #[Override]
    public function getAdditionalDetails(): array
    {
        return $this->details;
    }

    /**
     * @return string
     */
    #[Override]
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    #[Override]
    public function getTitle(): string
    {
        return $this->title;
    }
}
