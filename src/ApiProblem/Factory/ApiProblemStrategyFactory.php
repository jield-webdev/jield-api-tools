<?php

declare(strict_types=1);

namespace Jield\ApiTools\ApiProblem\Factory;

use Interop\Container\ContainerInterface;
use Jield\ApiTools\ApiProblem\View\ApiProblemRenderer;
use Jield\ApiTools\ApiProblem\View\ApiProblemStrategy;

class ApiProblemStrategyFactory
{
    /**
     * @return ApiProblemStrategy
     */
    public function __invoke(ContainerInterface $container)
    {
        return new ApiProblemStrategy($container->get(ApiProblemRenderer::class));
    }
}
