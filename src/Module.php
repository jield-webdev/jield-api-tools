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
use Jield\ApiTools\Rpc\OptionsListener;
use Laminas\Authentication\AuthenticationService;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\EventManager\EventInterface;
use Laminas\EventManager\EventManager;
use Laminas\Http\Request as HttpRequest;
use Laminas\ModuleManager\Feature\BootstrapListenerInterface;
use Laminas\ModuleManager\Feature\ConfigProviderInterface;
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
            SettingsProvider::class,
        ]))->getMergedConfig();
    }

    public function onBootstrap(EventInterface|MvcEvent $e): void
    {
        $app = $e->getParam(name: 'application');

        /** @var ServiceManager $serviceManager */
        $serviceManager = $app->getServiceManager();
        /** @var EventManager $eventManager */
        $eventManager = $app->getEventManager();

        //ApiProblem
        $serviceManager->get(ApiProblemListener::class)->attach($eventManager);
        $eventManager->attach(eventName: MvcEvent::EVENT_RENDER, listener: [$this, 'onRenderApiProblem'], priority: 100);
        $eventManager->attach(eventName: MvcEvent::EVENT_RENDER, listener: [$this, 'onRenderHal'], priority: 100);

        $sendResponseListener = $serviceManager->get('SendResponseListener');
        $sendResponseListener->getEventManager()->attach(
            SendResponseEvent::EVENT_SEND_RESPONSE,
            $serviceManager->get(SendApiProblemResponseListener::class),
            -500
        );

        $eventManager->attach(eventName: MvcEvent::EVENT_ROUTE, listener: $serviceManager->get(ContentTypeListener::class), priority: -625);

        $serviceManager->get(AcceptFilterListener::class)->attach($eventManager);
        $serviceManager->get(ContentTypeFilterListener::class)->attach($eventManager);

        $contentNegotiationOptions = $serviceManager->get(ContentNegotiationOptions::class);
        if ($contentNegotiationOptions->getXHttpMethodOverrideEnabled()) {
            $serviceManager->get(HttpMethodOverrideListener::class)->attach($eventManager);
        }

        $sharedEventManager = $eventManager->getSharedManager();
        $sharedEventManager->attach(
            DispatchableInterface::class,
            MvcEvent::EVENT_DISPATCH,
            listener: $serviceManager->get(AcceptListener::class),
            priority: -10
        );

        $eventManager->attach(
            eventName: MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            listener: $serviceManager->get(name: MvcAuth\UnauthenticatedListener::class),
            priority: 100
        );
        $eventManager->attach(
            eventName: MvcAuthEvent::EVENT_AUTHORIZATION_POST,
            listener: $serviceManager->get(name: MvcAuth\UnauthorizedListener::class),
            priority: 100
        );
        $eventManager->attach(eventName: MvcEvent::EVENT_RENDER, listener: [$this, 'onRenderMain'], priority: 400);

        $serviceManager->get(ContentValidationListener::class)->attach($eventManager);

        if ($e->getRequest() instanceof HttpRequest) {
            $authentication = $serviceManager->get('authentication');
            $mvcAuthEvent   = new MvcAuthEvent(
                $e,
                $authentication,
                $serviceManager->get('authorization')
            );

            new MvcRouteListener($mvcAuthEvent, $eventManager, $authentication);

            $eventManager->attach(
                MvcAuthEvent::EVENT_AUTHENTICATION,
                $serviceManager->get(DefaultAuthenticationListener::class)
            );
            $eventManager->attach(
                MvcAuthEvent::EVENT_AUTHENTICATION_POST,
                $serviceManager->get(DefaultAuthenticationPostListener::class)
            );
            $eventManager->attach(
                MvcAuthEvent::EVENT_AUTHORIZATION,
                $serviceManager->get(DefaultResourceResolverListener::class),
                1000
            );
            $eventManager->attach(
                MvcAuthEvent::EVENT_AUTHORIZATION,
                $serviceManager->get(DefaultAuthorizationListener::class)
            );
            $eventManager->attach(
                MvcAuthEvent::EVENT_AUTHORIZATION_POST,
                $serviceManager->get(DefaultAuthorizationPostListener::class)
            );
        }

        $serviceManager->get(\Jield\ApiTools\Rest\Listener\OptionsListener::class)->attach($eventManager);
        $serviceManager->get(\Jield\ApiTools\Rest\Listener\RestParametersListener::class)->attachShared($sharedEventManager);

        // Attach OptionsListener
        $optionsListener = $serviceManager->get(OptionsListener::class);
        $optionsListener->attach($app->getEventManager());

        // Setup json strategy
        $strategy = $serviceManager->get('ViewJsonStrategy');
        $view     = $serviceManager->get('ViewManager')->getView();
        $strategy->attach($view->getEventManager(), 100);

        $eventManager->attach(
            eventName: MvcEvent::EVENT_ROUTE,
            listener: function ($event) use ($serviceManager): void {
                $identity = $event->getParam(\Jield\ApiTools\MvcAuth\Identity\AuthenticatedIdentity::class);
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

    public function onRenderMain(MvcEvent $e): void
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

    public function onRenderApiProblem(MvcEvent $e): void
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

    public function onRenderHal(MvcEvent $e): void
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
}
