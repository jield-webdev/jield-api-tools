<?php

declare(strict_types=1);

namespace Jield\ApiTools;

use Admin\Service\UserService;
use Jield\ApiTools\ApiProblem\Listener\ApiProblemListener;
use Jield\ApiTools\ApiProblem\Listener\RenderErrorListener;
use Jield\ApiTools\ApiProblem\Listener\SendApiProblemResponseListener;
use Jield\ApiTools\ApiProblem\View\ApiProblemStrategy;
use Jield\ApiTools\ContentNegotiation\AcceptFilterListener;
use Jield\ApiTools\ContentNegotiation\AcceptListener;
use Jield\ApiTools\ContentNegotiation\ContentNegotiationOptions;
use Jield\ApiTools\ContentNegotiation\ContentTypeFilterListener;
use Jield\ApiTools\ContentNegotiation\ContentTypeListener;
use Jield\ApiTools\ContentNegotiation\HttpMethodOverrideListener;
use Jield\ApiTools\ContentValidation\ContentValidationListener;
use Jield\ApiTools\Hal\View\HalJsonModel;
use Jield\ApiTools\Hal\View\HalJsonStrategy;
use Jield\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener;
use Jield\ApiTools\MvcAuth\Authentication\DefaultAuthenticationPostListener;
use Jield\ApiTools\MvcAuth\Authorization\DefaultAuthorizationListener;
use Jield\ApiTools\MvcAuth\Authorization\DefaultAuthorizationPostListener;
use Jield\ApiTools\MvcAuth\Authorization\DefaultResourceResolverListener;
use Jield\ApiTools\MvcAuth\Identity\AuthenticatedIdentity;
use Jield\ApiTools\MvcAuth\MvcAuthEvent;
use Jield\ApiTools\MvcAuth\MvcRouteListener;
use Jield\ApiTools\OAuth2\Factory\OAuth2ServerFactory;
use Jield\ApiTools\Rpc\OptionsListener;
use Laminas\Authentication\AuthenticationService;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\EventManager\EventInterface;
use Laminas\EventManager\EventManager;
use Laminas\Http\Request as HttpRequest;
use Laminas\ModuleManager\Feature\BootstrapListenerInterface;
use Laminas\ModuleManager\Feature\ConfigProviderInterface;
use Laminas\ModuleManager\ModuleEvent;
use Laminas\ModuleManager\ModuleManager;
use Laminas\Mvc\ApplicationInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\Mvc\ResponseSender\SendResponseEvent;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\DispatchableInterface;
use Laminas\View\Model\JsonModel;
use Laminas\View\View;

final class Module implements ConfigProviderInterface, BootstrapListenerInterface
{
    public function getConfig(): array
    {
        return (new ConfigAggregator(providers: [
            ConfigProvider::class,
            ListenerConfigProvider::class,
        ]))->getMergedConfig();
    }

    public function init(ModuleManager $moduleManager): void
    {
        $events = $moduleManager->getEventManager();
        $events->attach(ModuleEvent::EVENT_MERGE_CONFIG, [$this, 'onMergeConfig']);
    }

    public function onMergeConfig(ModuleEvent $e)
    {
        $configListener = $e->getConfigListener();
        $config         = $configListener->getMergedConfig(false);
        $service        = 'Jield\ApiTools\OAuth2\Service\OAuth2Server';
        $default        = OAuth2ServerFactory::class;

        if (
            !isset($config['service_manager']['factories'][$service])
            || $config['service_manager']['factories'][$service] !== $default
        ) {
            return;
        }

        $config['service_manager']['factories'][$service] = __NAMESPACE__ . '\Factory\NamedOAuth2ServerFactory';
        $configListener->setMergedConfig($config);
    }


    public function onBootstrap(EventInterface|MvcEvent $e): void
    {
        $app = $e->getParam(name: 'application');

        /** @var ServiceManager $serviceManager */
        $serviceManager = $app->getServiceManager();
        /** @var EventManager $events */
        $events = $app->getEventManager();

        //ApiProblem
        $serviceManager->get(ApiProblemListener::class)->attach($serviceManager);
        $events->attach(eventName: MvcEvent::EVENT_RENDER, listener: [$this, 'onRenderApiProblem'], priority: 100);
        $events->attach(eventName: MvcEvent::EVENT_RENDER, listener: [$this, 'onRenderHal'], priority: 100);

        $sendResponseListener = $serviceManager->get('SendResponseListener');
        $sendResponseListener->getEventManager()->attach(
            SendResponseEvent::EVENT_SEND_RESPONSE,
            $serviceManager->get(SendApiProblemResponseListener::class),
            -500
        );

        $events->attach(eventName: MvcEvent::EVENT_ROUTE, listener: $serviceManager->get(ContentTypeListener::class), priority: -625);

        $serviceManager->get(AcceptFilterListener::class)->attach($events);
        $serviceManager->get(ContentTypeFilterListener::class)->attach($events);

        $contentNegotiationOptions = $serviceManager->get(ContentNegotiationOptions::class);
        if ($contentNegotiationOptions->getXHttpMethodOverrideEnabled()) {
            $serviceManager->get(HttpMethodOverrideListener::class)->attach($events);
        }

        $sharedEventManager = $events->getSharedManager();
        $sharedEventManager->attach(
            DispatchableInterface::class,
            MvcEvent::EVENT_DISPATCH,
            listener: $serviceManager->get(AcceptListener::class),
            priority: -10
        );

        $events->attach(
            eventName: MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            listener: $serviceManager->get(name: MvcAuth\UnauthenticatedListener::class),
            priority: 100
        );
        $events->attach(
            eventName: MvcAuthEvent::EVENT_AUTHORIZATION_POST,
            listener: $serviceManager->get(name: MvcAuth\UnauthorizedListener::class),
            priority: 100
        );
        $events->attach(eventName: MvcEvent::EVENT_RENDER, listener: [$this, 'onRenderMain'], priority: 400);

        $serviceManager->get(ContentValidationListener::class)->attach($events);

        if ($e->getRequest() instanceof HttpRequest) {
            $authentication = $serviceManager->get('authentication');
            $mvcAuthEvent   = new MvcAuthEvent(
                $e,
                $authentication,
                $serviceManager->get('authorization')
            );

            new MvcRouteListener($mvcAuthEvent, $events, $authentication);

            $events->attach(
                MvcAuthEvent::EVENT_AUTHENTICATION,
                $serviceManager->get(DefaultAuthenticationListener::class)
            );
            $events->attach(
                MvcAuthEvent::EVENT_AUTHENTICATION_POST,
                $serviceManager->get(DefaultAuthenticationPostListener::class)
            );
            $events->attach(
                MvcAuthEvent::EVENT_AUTHORIZATION,
                $serviceManager->get(DefaultResourceResolverListener::class),
                1000
            );
            $events->attach(
                MvcAuthEvent::EVENT_AUTHORIZATION,
                $serviceManager->get(DefaultAuthorizationListener::class)
            );
            $events->attach(
                MvcAuthEvent::EVENT_AUTHORIZATION_POST,
                $serviceManager->get(DefaultAuthorizationPostListener::class)
            );

            $events->attach(
                MvcAuthEvent::EVENT_AUTHENTICATION_POST,
                [$this, 'onAuthenticationPost'],
                -1
            );
        }

        $serviceManager->get('Jield\ApiTools\Rest\OptionsListener')->attach($events);
        $serviceManager->get('Jield\ApiTools\Rest\RestParametersListener')->attachShared($sharedEventManager);

        // Attach OptionsListener
        $optionsListener = $serviceManager->get(OptionsListener::class);
        $optionsListener->attach($app->getEventManager());

        // Setup json strategy
        $strategy = $serviceManager->get('ViewJsonStrategy');
        $view     = $serviceManager->get('ViewManager')->getView();
        $strategy->attach($view->getEventManager(), 100);

        $events->attach(
            eventName: MvcEvent::EVENT_ROUTE,
            listener: function ($event) use ($serviceManager): void {
                $identity = $event->getParam('Jield\ApiTools\MvcAuth\Identity');
                if ($identity instanceof AuthenticatedIdentity && $identity->getAuthenticationIdentity()['expires'] > time()) {
                    $userId                = $identity->getAuthenticationIdentity()['user_id'];
                    $authenticationService = $serviceManager->get(name: AuthenticationService::class);
                    $userService           = $serviceManager->get(name: UserService::class);
                    $authenticationService->getStorage()->write(contents: $userService->findUserById(id: $userId));
                }
            },
            priority: -998
        ); //This has to be called __before__ Jield/Authorize loads in the dynamic permissions, but after the moment the identity is set
    }

    private function onRenderMain(MvcEvent $e): void
    {
        $result = $e->getResult();
        if (!$result instanceof JsonModel) {
            return;
        }

        $app      = $e->getApplication();
        $services = $app->getServiceManager();
        $events   = $app->getEventManager();
        $services->get(RenderErrorListener::class)->attach($events);
    }

    private function onRenderApiProblem(MvcEvent $e): void
    {
        $app      = $e->getApplication();
        $services = $app->getServiceManager();

        if ($services->has('View')) {
            $view   = $services->get('View');
            $events = $view->getEventManager();

            // register at high priority, to "beat" normal json strategy registered
            // via view manager, as well as HAL strategy.
            $services->get(ApiProblemStrategy::class)->attach($events, 400);
        }
    }

    private function onRenderHal(MvcEvent $e): void
    {
        $result = $e->getResult();
        if (!$result instanceof HalJsonModel) {
            return;
        }

        /** @var ApplicationInterface $application */
        $application = $e->getTarget();
        $services    = $application->getServiceManager();
        /** @var View $view */
        $view   = $services->get('View');
        $events = $view->getEventManager();

        // register at high priority, to "beat" normal json strategy registered
        // via view manager
        /** @var HalJsonStrategy $halStrategy */
        $halStrategy = $services->get(HalJsonStrategy::class);
        $halStrategy->attach(events: $events, priority: 200);
    }

    private function onAuthenticationPost(MvcAuthEvent $e): void
    {
        if ($this->container->has('api-identity')) {
            return;
        }

        $this->container->setService('api-identity', $e->getIdentity());
    }
}
