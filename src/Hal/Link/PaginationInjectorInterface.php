<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal\Link;

use Jield\ApiTools\ApiProblem\ApiProblem;
use Jield\ApiTools\Hal\Collection;

interface PaginationInjectorInterface
{
    /**
     * Generate HAL links for a paginated collection
     *
     * @return bool|ApiProblem
     */
    public function injectPaginationLinks(Collection $halCollection);
}
