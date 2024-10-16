<?php

declare(strict_types=1);

namespace Jield\ApiTools;

use Admin\Service\UserService;
use Jield\ApiTools\MvcAuth\Identity\AuthenticatedIdentity;
use Jield\ApiTools\MvcAuth\MvcAuthEvent;
use Jield\ApiTools\ApiProblem\Listener\RenderErrorListener;
use Jield\ApiTools\Hal\View\HalJsonModel;
use Laminas\Authentication\AuthenticationService;
use Laminas\ConfigAggregator\ConfigAggregator;
use Laminas\EventManager\EventInterface;
use Laminas\EventManager\EventManager;
use Laminas\ModuleManager\Feature\BootstrapListenerInterface;
use Laminas\ModuleManager\Feature\ConfigProviderInterface;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceManager;
use Laminas\View\Model\JsonModel;

final class Module implements ConfigProviderInterface, BootstrapListenerInterface
{
    public function getConfig(): array
    {
        return (new ConfigAggregator(providers: [
            ConfigProvider::class,
            ListenerConfigProvider::class,
        ]))->getMergedConfig();
    }


    public function onBootstrap(EventInterface $e): void
    {
        $app = $e->getParam(name: 'application');

        /** @var ServiceManager $sm */
        $sm = $app->getServiceManager();
        /** @var EventManager $events */
        $events = $app->getEventManager();

        $events->attach(
            eventName: MvcAuthEvent::EVENT_AUTHENTICATION_POST,
            listener: $sm->get(name: MvcAuth\UnauthenticatedListener::class),
            priority: 100
        );
        $events->attach(
            eventName: MvcAuthEvent::EVENT_AUTHORIZATION_POST,
            listener: $sm->get(name: MvcAuth\UnauthorizedListener::class),
            priority: 100
        );
        $events->attach(eventName: MvcEvent::EVENT_RENDER, listener: [$this, 'onRender'], priority: 400);

        $events->attach(
            eventName: MvcEvent::EVENT_ROUTE,
            listener: function ($event) use ($sm): void {
                $identity = $event->getParam('Jield\ApiTools\MvcAuth\Identity');
                if ($identity instanceof AuthenticatedIdentity && $identity->getAuthenticationIdentity()['expires'] > time()) {
                    $userId                = $identity->getAuthenticationIdentity()['user_id'];
                    $authenticationService = $sm->get(name: AuthenticationService::class);
                    $userService           = $sm->get(name: UserService::class);
                    $authenticationService->getStorage()->write(contents: $userService->findUserById(id: $userId));
                }
            },
            priority: -998
        ); //This has to be called __before__ Jield/Authorize loads in the dynamic permissions, but after the moment the identity is set
    }

    public function onRender(MvcEvent $e)
    {
        $result = $e->getResult();
        if (
            ! $result instanceof HalJsonModel
            && ! $result instanceof JsonModel
        ) {
            return;
        }

        $app      = $e->getApplication();
        $services = $app->getServiceManager();
        $events   = $app->getEventManager();
        $services->get(RenderErrorListener::class)->attach($events);
    }
}
