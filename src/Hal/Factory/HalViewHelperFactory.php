<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal\Factory;


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
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;
use function sprintf;

class HalViewHelperFactory
{
    /**
     * @param ContainerInterface|ServiceLocatorInterface $container
     * @return Plugin\Hal
     */
    public function __invoke(ContainerInterface $container)
    {
        $container = $container instanceof AbstractPluginManager
            ? $container->getServiceLocator()
            : $container;

        $rendererOptions = $container->get(RendererOptions::class);
        Assert::isInstanceOf($rendererOptions, RendererOptions::class);
        $metadataMap = $container->get(MetadataMap::class);
        Assert::isInstanceOf($metadataMap, MetadataMap::class);

        $hydrators = $metadataMap->getHydratorManager();
        Assert::isInstanceOf($hydrators, HydratorPluginManager::class);

        $helper = new Plugin\Hal($hydrators);

        if ($container->has('EventManager')) {
            $eventManager = $container->get('EventManager');
            Assert::isInstanceOf($eventManager, EventManagerInterface::class);
            $helper->setEventManager($eventManager);
        }

        $helper->setMetadataMap($metadataMap);
        $linkUrlBuilder = $container->get(Link\LinkUrlBuilder::class);
        Assert::isInstanceOf($linkUrlBuilder, Link\LinkUrlBuilder::class);
        $helper->setLinkUrlBuilder($linkUrlBuilder);

        $linkCollectionExtractor = $container->get(LinkCollectionExtractor::class);
        Assert::isInstanceOf($linkCollectionExtractor, LinkCollectionExtractor::class);
        $helper->setLinkCollectionExtractor($linkCollectionExtractor);

        $defaultHydrator = $rendererOptions->getDefaultHydrator();
        if ($defaultHydrator) {
            if (!$hydrators->has($defaultHydrator)) {
                throw new Exception\DomainException(sprintf(
                    'Cannot locate default hydrator by name "%s" via the HydratorManager',
                    $defaultHydrator
                ));
            }

            $hydrator = $hydrators->get($defaultHydrator);
            Assert::isInstanceOf($hydrator, HydratorInterface::class);
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
}
