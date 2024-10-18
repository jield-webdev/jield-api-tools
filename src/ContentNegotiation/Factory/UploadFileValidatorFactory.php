<?php

declare(strict_types=1);

namespace Jield\ApiTools\ContentNegotiation\Factory;

use Override;
use Psr\Container\ContainerInterface;
use Jield\ApiTools\ContentNegotiation\Validator\UploadFile;
use Laminas\ServiceManager\AbstractPluginManager;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceLocatorInterface;

use function method_exists;

class UploadFileValidatorFactory implements FactoryInterface
{
    /**
     * Required for v2 compatibility.
     *
     * @var null|array
     */
    private $options;

    /**
     * @param string $requestedName,
     * @param array<string, mixed>|null $options
     */
    #[Override]
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): UploadFile
    {
        if (
            $container instanceof AbstractPluginManager
            && ! method_exists(object_or_class: $container, method: 'configure')
        ) {
            $container = $container->getServiceLocator() ?: $container;
        }

        $validator = new UploadFile(options: $options);
        if ($container->has('Request')) {
            $validator->setRequest(request: $container->get('Request'));
        }

        return $validator;
    }

    /**
     * Create and return an UploadFile validator (v2 compatibility)
     *
     * @param string|null $name
     * @param string|null $requestedName
     */
    public function createService(ServiceLocatorInterface $container, string $name = null, string $requestedName = null): UploadFile
    {
        $requestedName = $requestedName ?: UploadFile::class;

        if ($container instanceof AbstractPluginManager) {
            $container = $container->getServiceLocator() ?: $container;
        }

        return $this(container: $container, requestedName: $requestedName, options: $this->options);
    }

    /**
     * Allow injecting options at build time; required for v2 compatibility.
     *
     * @param array $options
     * @return void
     */
    public function setCreationOptions(array $options): void
    {
        $this->options = $options;
    }
}
