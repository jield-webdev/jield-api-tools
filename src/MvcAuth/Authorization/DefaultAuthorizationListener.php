<?php

declare(strict_types=1);

namespace Jield\ApiTools\MvcAuth\Authorization;

use Jield\ApiTools\MvcAuth\Identity\IdentityInterface;
use Jield\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\Router\RouteMatch as V2RouteMatch;
use Laminas\Router\RouteMatch;

class DefaultAuthorizationListener
{
    /** @var AuthorizationInterface */
    protected $authorization;

    public function __construct(AuthorizationInterface $authorization)
    {
        $this->authorization = $authorization;
    }

    /**
     * Attempt to authorize the discovered identity based on the ACLs present
     *
     * @return bool
     */
    public function __invoke(MvcAuthEvent $mvcAuthEvent): ?bool
    {
        if ($mvcAuthEvent->isAuthorized()) {
            return null;
        }

        $mvcEvent = $mvcAuthEvent->getMvcEvent();

        $request = $mvcEvent->getRequest();
        if (! $request instanceof Request) {
            return null;
        }

        $response = $mvcEvent->getResponse();
        if (! $response instanceof Response) {
            return null;
        }

        $routeMatch = $mvcEvent->getRouteMatch();
        if (!$routeMatch instanceof RouteMatch && !$routeMatch instanceof V2RouteMatch) {
            return null;
        }

        $identity = $mvcAuthEvent->getIdentity();
        if (! $identity instanceof IdentityInterface) {
            return null;
        }

        $resource = $mvcAuthEvent->getResource();
        $identity = $mvcAuthEvent->getIdentity();
        return $this->authorization->isAuthorized(identity: $identity, resource: $resource, privilege: $request->getMethod());
    }
}
