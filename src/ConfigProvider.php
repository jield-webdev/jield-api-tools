<?php

namespace Jield\ApiTools;

use Jield\ApiTools\ContentValidation\ContentValidationListener;
use Jield\ApiTools\ContentValidation\ContentValidationListenerFactory;
use Jield\ApiTools\Hal\Metadata\MetadataMap;
use Jield\ApiTools\Hal\RendererOptions;
use Jield\ApiTools\Hal\View\HalJsonRenderer;
use Jield\ApiTools\Hal\View\HalJsonStrategy;
use Laminas\Authentication\Storage\NonPersistent;
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
            'controllers'                => $this->getControllerPlugin(),
            'filters'                    => $this->getFiltersConfig(),
            'validators'                 => $this->getValidatorsConfig(),
            'input_filters'              => $this->getInputFiltersConfig(),
            'view_helpers'               => $this->getViewHelpersConfig(),
        ];
    }

    public function getControllerPluginConfig(): array
    {
        return [
            'aliases'   => [
                'routeParam'     => \Jield\ApiTools\ContentNegotiation\ControllerPlugin\RouteParam::class,
                'queryParam'     => \Jield\ApiTools\ContentNegotiation\ControllerPlugin\QueryParam::class,
                'bodyParam'      => \Jield\ApiTools\ContentNegotiation\ControllerPlugin\BodyParam::class,
                'routeParams'    => \Jield\ApiTools\ContentNegotiation\ControllerPlugin\RouteParams::class,
                'queryParams'    => \Jield\ApiTools\ContentNegotiation\ControllerPlugin\QueryParams::class,
                'bodyParams'     => \Jield\ApiTools\ContentNegotiation\ControllerPlugin\BodyParams::class,
                'getInputFilter' => \Jield\ApiTools\ContentValidation\InputFilter\InputFilterPlugin::class,
                'Hal'            => \Jield\ApiTools\Hal\Factory\HalControllerPluginFactory::class,
                'getIdentity'    => \Jield\ApiTools\MvcAuth\Identity\IdentityPlugin::class,
            ],
            'factories' => [
                \Jield\ApiTools\ContentNegotiation\ControllerPlugin\RouteParam::class  => InvokableFactory::class,
                \Jield\ApiTools\ContentNegotiation\ControllerPlugin\QueryParam::class  => InvokableFactory::class,
                \Jield\ApiTools\ContentNegotiation\ControllerPlugin\BodyParam::class   => InvokableFactory::class,
                \Jield\ApiTools\ContentNegotiation\ControllerPlugin\RouteParams::class => InvokableFactory::class,
                \Jield\ApiTools\ContentNegotiation\ControllerPlugin\QueryParams::class => InvokableFactory::class,
                \Jield\ApiTools\ContentNegotiation\ControllerPlugin\BodyParams::class  => InvokableFactory::class,
                \Jield\ApiTools\ContentValidation\InputFilter\InputFilterPlugin::class => InvokableFactory::class,
                \Jield\ApiTools\MvcAuth\Identity\IdentityPlugin::class                 => InvokableFactory::class,
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

    public function getInputFiltersConfig(): array
    {
        return [
            'abstract_factories' => [
                \Laminas\InputFilter\InputFilterAbstractServiceFactory::class,
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
            'aliases'    => [
                'authentication'                                                    => 'Jield\ApiTools\MvcAuth\Authentication',
                'authorization'                                                     => \Jield\ApiTools\MvcAuth\Authorization\AuthorizationInterface::class,
                \Jield\ApiTools\MvcAuth\Authorization\AuthorizationInterface::class => \Jield\ApiTools\MvcAuth\Authorization\AclAuthorization::class,
                'Jield\ApiTools\OAuth2\Provider\UserId'                             => \Jield\ApiTools\OAuth2\Provider\UserId\AuthenticationService::class,

            ],
            'delegators' => [
                \Jield\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener::class => [
                    \Jield\ApiTools\MvcAuth\Factory\AuthenticationAdapterDelegatorFactory::class,
                ],
            ],
            'invokables' => [
                \Jield\ApiTools\Rest\Listener\RestParametersListener::class => \Jield\ApiTools\Rest\Listener\RestParametersListener::class,
            ],
            'factories'  => [
                \Jield\ApiTools\Rest\Listener\OptionsListener::class => \Jield\ApiTools\Rest\Factory\OptionsListenerFactory::class,
                \Jield\ApiTools\Rpc\OptionsListener::class  => \Jield\ApiTools\Rpc\Factory\OptionsListenerFactory::class,

                \Jield\ApiTools\OAuth2\Adapter\PdoAdapter::class                    => \Jield\ApiTools\OAuth2\Factory\PdoAdapterFactory::class,
                \Jield\ApiTools\OAuth2\Provider\UserId\AuthenticationService::class => \Jield\ApiTools\OAuth2\Provider\UserId\AuthenticationServiceFactory::class,
                //                'Jield\ApiTools\OAuth2\Service\OAuth2Server'                        => \Jield\ApiTools\OAuth2\Factory\OAuth2ServerFactory::class,

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

                ContentValidationListener::class => ContentValidationListenerFactory::class,

                \Jield\ApiTools\Hal\Extractor\LinkExtractor::class           => \Jield\ApiTools\Hal\Factory\LinkExtractorFactory::class,
                \Jield\ApiTools\Hal\Extractor\LinkCollectionExtractor::class => \Jield\ApiTools\Hal\Factory\LinkCollectionExtractorFactory::class,
                HalJsonRenderer::class                                       => \Jield\ApiTools\Hal\Factory\HalJsonRendererFactory::class,
                HalJsonStrategy::class                                       => \Jield\ApiTools\Hal\Factory\HalJsonStrategyFactory::class,
                \Jield\ApiTools\Hal\Link\LinkUrlBuilder::class               => \Jield\ApiTools\Hal\Factory\LinkUrlBuilderFactory::class,
                MetadataMap::class                                           => \Jield\ApiTools\Hal\Factory\MetadataMapFactory::class,
                RendererOptions::class                                       => \Jield\ApiTools\Hal\Factory\RendererOptionsFactory::class,

                'Jield\ApiTools\MvcAuth\Authentication'                                         => \Jield\ApiTools\MvcAuth\Factory\AuthenticationServiceFactory::class,
                'Jield\ApiTools\MvcAuth\ApacheResolver'                                         => \Jield\ApiTools\MvcAuth\Factory\ApacheResolverFactory::class,
                'Jield\ApiTools\MvcAuth\FileResolver'                                           => \Jield\ApiTools\MvcAuth\Factory\FileResolverFactory::class,
                \Jield\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener::class     => \Jield\ApiTools\MvcAuth\Factory\DefaultAuthenticationListenerFactory::class,
                \Laminas\Authentication\Adapter\Http::class                                     => \Jield\ApiTools\MvcAuth\Factory\DefaultAuthHttpAdapterFactory::class,
                \Jield\ApiTools\MvcAuth\Authorization\AclAuthorization::class                   => \Jield\ApiTools\MvcAuth\Factory\AclAuthorizationFactory::class,
                \Jield\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener::class       => \Jield\ApiTools\MvcAuth\Factory\DefaultAuthorizationListenerFactory::class,
                \Jield\ApiTools\MvcAuth\Authorization\DefaultResourceResolverListener::class    => \Jield\ApiTools\MvcAuth\Factory\DefaultResourceResolverListenerFactory::class,
                'Jield\ApiTools\OAuth2\Service\OAuth2Server'                                    => \Jield\ApiTools\MvcAuth\Factory\NamedOAuth2ServerFactory::class,
                NonPersistent::class                                                            => InvokableFactory::class,
                \Jield\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener::class => InvokableFactory::class,
                \Jield\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener::class   => InvokableFactory::class,
            ],
        ];
    }


    public function getConfigAbstractFactory(): array
    {
        return [

        ];
    }

    private function getViewHelpersConfig(): array
    {
        return [
            'factories' => [
                'Hal' => \Jield\ApiTools\Hal\Factory\HalViewHelperFactory::class,
            ],
        ];
    }

    private function getControllerPlugin(): array
    {
        return [
            'controllers' => [
                'factories'          => [
                    'Jield\ApiTools\OAuth2\Controller\Auth' => \Jield\ApiTools\OAuth2\Factory\AuthControllerFactory::class,
                ],
                'abstract_factories' => [
                    \Jield\ApiTools\Rest\Factory\RestControllerFactory::class,
                    \Jield\ApiTools\Rpc\Factory\RpcControllerFactory::class,
                ],
            ],
        ];
    }
}
