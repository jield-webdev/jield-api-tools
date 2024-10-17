<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal\Factory;


use Psr\Container\ContainerInterface;
use Jield\ApiTools\Hal\Exception;
use Jield\ApiTools\Hal\Extractor\LinkCollectionExtractor;
use Jield\ApiTools\Hal\Link;
use Jield\ApiTools\Hal\Metadata\MetadataMap;
use Jield\ApiTools\Hal\Plugin;
use Jield\ApiTools\Hal\RendererOptions;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Hydrator\HydratorInterface;
use Laminas\Hydrator\HydratorPluginManager;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\ServiceLocatorInterface;

use function assert;
use function sprintf;

class HalViewHelperFactory
{
    /**
     * @param  ContainerInterface|ServiceLocatorInterface $container
     * @return Plugin\Hal
     */
    public function __invoke(ContainerInterface $container)
    {
        $container = $container instanceof AbstractPluginManager
            ? $container->getServiceLocator()
            : $container;

        $rendererOptions = $container->get(RendererOptions::class);
        assert($rendererOptions instanceof RendererOptions);
        $metadataMap = $container->get('Jield\ApiTools\Hal\MetadataMap');
        assert($metadataMap instanceof MetadataMap);

        $hydrators = $metadataMap->getHydratorManager();
        assert($hydrators instanceof HydratorPluginManager);

        $helper = new Plugin\Hal($hydrators);

        if ($container->has('EventManager')) {
            $eventManager = $container->get('EventManager');
            assert($eventManager instanceof EventManagerInterface);
            $helper->setEventManager($eventManager);
        }

        $helper->setMetadataMap($metadataMap);
        $linkUrlBuilder = $container->get(Link\LinkUrlBuilder::class);
        assert($linkUrlBuilder instanceof Link\LinkUrlBuilder);
        $helper->setLinkUrlBuilder($linkUrlBuilder);

        $linkCollectionExtractor = $container->get(LinkCollectionExtractor::class);
        assert($linkCollectionExtractor instanceof LinkCollectionExtractor);
        $helper->setLinkCollectionExtractor($linkCollectionExtractor);

        $defaultHydrator = $rendererOptions->getDefaultHydrator();
        if ($defaultHydrator) {
            if (! $hydrators->has($defaultHydrator)) {
                throw new Exception\DomainException(sprintf(
                    'Cannot locate default hydrator by name "%s" via the HydratorManager',
                    $defaultHydrator
                ));
            }

            $hydrator = $hydrators->get($defaultHydrator);
            assert($hydrator instanceof HydratorInterface);
            $helper->setDefaultHydrator($hydrator);
        }

        $helper->setRenderEmbeddedEntities($rendererOptions->getRenderEmbeddedEntities());
        $helper->setRenderCollections($rendererOptions->getRenderEmbeddedCollections());

        $hydratorMap = $rendererOptions->getHydrators();
        foreach ($hydratorMap as $class => $hydratorServiceName) {
            $helper->addHydrator($class, $hydratorServiceName);
        }

        return $helper;
    }

    /**
     * Proxies to __invoke() to provide backwards compatibility.
     *
     * @deprecated since 1.4.0; use __invoke instead.
     *
     * @param  ServiceLocatorInterface $container
     * @return Plugin\Hal
     */
    public function createService($container)
    {
        return $this($container);
    }
}
