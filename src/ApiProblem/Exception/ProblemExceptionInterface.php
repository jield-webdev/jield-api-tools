<?php

declare(strict_types=1);

namespace Jield\ApiTools\ApiProblem\Exception;

use Traversable;

/**
 * Interface for exceptions that can provide additional API Problem details.
 */
interface ProblemExceptionInterface
{
    /**
     * @return null|array|Traversable
     */
    public function getAdditionalDetails(): Traversable|array|null;

    /**
     * @return string
     */
    public function getType(): string;

    /**
     * @return string
     */
    public function getTitle(): string;
}
