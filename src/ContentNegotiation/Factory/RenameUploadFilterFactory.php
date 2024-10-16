<?php

declare(strict_types=1);

namespace Jield\ApiTools\ContentNegotiation\Factory;

use Interop\Container\ContainerInterface;
use Jield\ApiTools\ContentNegotiation\Filter\RenameUpload;
use Laminas\ServiceManager\Factory\FactoryInterface;

class RenameUploadFilterFactory implements FactoryInterface
{
    /**
     * @param string $requestedName ,
     * @param null|array $options
     * @return RenameUpload
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): RenameUpload
    {
        $filter = new RenameUpload($options);

        if ($container->has('Request')) {
            $filter->setRequest($container->get('Request'));
        }

        return $filter;
    }
}
