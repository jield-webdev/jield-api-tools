<?php

namespace Jield\ApiTools;

use Laminas\ServiceManager\AbstractFactory\ConfigAbstractFactory;
use Laminas\ServiceManager\Factory\InvokableFactory;

final class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            ConfigAbstractFactory::class => $this->getConfigAbstractFactory(),
            'service_manager'            => $this->getServiceMangerConfig(),
            'controller_plugins'         => $this->getControllerPluginConfig(),
            'filters'                    => $this->getFiltersConfig(),
            'validators'                 => $this->getValidatorsConfig(),
        ];
    }

    public function getControllerPluginConfig(): array
    {
        return [
            'aliases'   => [
                'routeParam'  => \Jield\ApiTools\ContentNegotiation\ControllerPlugin\RouteParam::class,
                'queryParam'  => \Jield\ApiTools\ContentNegotiation\ControllerPlugin\QueryParam::class,
                'bodyParam'   => \Jield\ApiTools\ContentNegotiation\ControllerPlugin\BodyParam::class,
                'routeParams' => \Jield\ApiTools\ContentNegotiation\ControllerPlugin\RouteParams::class,
                'queryParams' => \Jield\ApiTools\ContentNegotiation\ControllerPlugin\QueryParams::class,
                'bodyParams'  => \Jield\ApiTools\ContentNegotiation\ControllerPlugin\BodyParams::class,
            ],
            'factories' => [
                \Jield\ApiTools\ContentNegotiation\ControllerPlugin\RouteParam::class  => InvokableFactory::class,
                \Jield\ApiTools\ContentNegotiation\ControllerPlugin\QueryParam::class  => InvokableFactory::class,
                \Jield\ApiTools\ContentNegotiation\ControllerPlugin\BodyParam::class   => InvokableFactory::class,
                \Jield\ApiTools\ContentNegotiation\ControllerPlugin\RouteParams::class => InvokableFactory::class,
                \Jield\ApiTools\ContentNegotiation\ControllerPlugin\QueryParams::class => InvokableFactory::class,
                \Jield\ApiTools\ContentNegotiation\ControllerPlugin\BodyParams::class  => InvokableFactory::class,
            ],
        ];
    }

    public function getFiltersConfig(): array
    {
        return [
            'factories' => [
                // Overwrite RenameUpload filter's factory
                \Laminas\Filter\File\RenameUpload::class => \Jield\ApiTools\ContentNegotiation\Factory\RenameUploadFilterFactory::class,
            ],
        ];
    }

    public function getValidatorsConfig(): array
    {
        return [
            'factories' => [
                // Overwrite UploadFile validator's factory
                \Laminas\Validator\File\UploadFile::class => \Jield\ApiTools\ContentNegotiation\Factory\UploadFileValidatorFactory::class,
            ],
        ];
    }

    public function getServiceMangerConfig(): array
    {
        return [
            'factories' => [
                'factories' => [
                    MvcAuth\UnauthenticatedListener::class => InvokableFactory::class,
                    MvcAuth\UnauthorizedListener::class    => InvokableFactory::class,

                    \Jield\ApiTools\ApiProblem\Listener\ApiProblemListener::class             => \Jield\ApiTools\ApiProblem\Factory\ApiProblemListenerFactory::class,
                    \Jield\ApiTools\ApiProblem\Listener\RenderErrorListener::class            => \Jield\ApiTools\ApiProblem\Factory\RenderErrorListenerFactory::class,
                    \Jield\ApiTools\ApiProblem\Listener\SendApiProblemResponseListener::class => \Jield\ApiTools\ApiProblem\Factory\SendApiProblemResponseListenerFactory::class,
                    \Jield\ApiTools\ApiProblem\View\ApiProblemRenderer::class                 => \Jield\ApiTools\ApiProblem\Factory\ApiProblemRendererFactory::class,
                    \Jield\ApiTools\ApiProblem\View\ApiProblemStrategy::class                 => \Jield\ApiTools\ApiProblem\Factory\ApiProblemStrategyFactory::class,

                    \Jield\ApiTools\ContentNegotiation\ContentTypeListener::class        => \Laminas\ServiceManager\Factory\InvokableFactory::class,
                    \Jield\ApiTools\ContentNegotiation\AcceptListener::class             => \Jield\ApiTools\ContentNegotiation\Factory\AcceptListenerFactory::class,
                    \Jield\ApiTools\ContentNegotiation\AcceptFilterListener::class       => \Jield\ApiTools\ContentNegotiation\Factory\AcceptFilterListenerFactory::class,
                    \Jield\ApiTools\ContentNegotiation\ContentTypeFilterListener::class  => \Jield\ApiTools\ContentNegotiation\Factory\ContentTypeFilterListenerFactory::class,
                    \Jield\ApiTools\ContentNegotiation\ContentNegotiationOptions::class  => \Jield\ApiTools\ContentNegotiation\Factory\ContentNegotiationOptionsFactory::class,
                    \Jield\ApiTools\ContentNegotiation\HttpMethodOverrideListener::class => \Jield\ApiTools\ContentNegotiation\Factory\HttpMethodOverrideListenerFactory::class,
                ],
            ],
        ];
    }


    public function getConfigAbstractFactory(): array
    {
        return [

        ];
    }
}
