<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal\Factory;


use Jield\ApiTools\ApiProblem\View\ApiProblemRenderer;
use Jield\ApiTools\Hal\View\HalJsonRenderer;
use Laminas\View\HelperPluginManager;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

class HalJsonRendererFactory
{
    /**
     * @return HalJsonRenderer
     */
    public function __invoke(ContainerInterface $container)
    {
        $helpers = $container->get('ViewHelperManager');
        Assert::isInstanceOf($helpers, HelperPluginManager::class);
        $apiProblemRenderer = $container->get(ApiProblemRenderer::class);
        Assert::isInstanceOf($apiProblemRenderer, ApiProblemRenderer::class);

        $renderer = new HalJsonRenderer($apiProblemRenderer);
        $renderer->setHelperPluginManager($helpers);

        return $renderer;
    }
}
