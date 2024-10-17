<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal\Factory;


use Jield\ApiTools\Hal\Link\LinkUrlBuilder;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Helper\ServerUrl;
use Laminas\View\Helper\Url;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

class LinkUrlBuilderFactory
{
    /**
     * @param ContainerInterface|ServiceLocatorInterface $container
     * @return LinkUrlBuilder
     */
    public function __invoke($container)
    {
        $halConfig = $container->get('Jield\ApiTools\Hal\HalConfig');

        $viewHelperManager = $container->get('ViewHelperManager');

        $serverUrlHelper = $viewHelperManager->get('ServerUrl');
        Assert::isInstanceOf($serverUrlHelper, ServerUrl::class);

        if (isset($halConfig['options']['use_proxy'])) {
            $serverUrlHelper->setUseProxy($halConfig['options']['use_proxy']);
        }

        $urlHelper = $viewHelperManager->get('Url');
        Assert::isInstanceOf($urlHelper, Url::class);

        return new LinkUrlBuilder($serverUrlHelper, $urlHelper);
    }
}
