<?php

declare(strict_types=1);

namespace Jield\ApiTools\MvcAuth\Authentication;

use Jield\ApiTools\MvcAuth\Identity;
use Jield\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\Router\RouteMatch;
use OAuth2\Server as OAuth2Server;
use function array_merge;
use function array_unique;
use function count;
use function rtrim;
use function strlen;
use function strpos;

class DefaultAuthenticationListener
{
    /**
     * Attached authentication adapters
     *
     * @var AdapterInterface[]
     */
    private $adapters = [];

    /**
     * Supported authentication types
     *
     * @var array
     */
    private $authenticationTypes = [];

    /**
     * Map of API/version to authentication type pairs
     *
     * @var array
     */
    private $authMap = [];

    /**
     * Attach an authentication adapter
     *
     * Adds the authentication adapter, and updates the list of supported
     * authentication types based on what the adapter provides.
     *
     * @return void
     */
    public function attach(AdapterInterface $adapter)
    {
        $this->adapters[]          = $adapter;
        $this->authenticationTypes = array_unique(array_merge($this->authenticationTypes, $adapter->provides()));
    }

    /**
     * Add custom authentication types.
     *
     * This method allows specifiying additional authentication types, outside
     * of adapters, that your application supports. The values provided are
     * merged with any types already discovered.
     *
     * @param array $types
     * @return void
     */
    public function addAuthenticationTypes(array $types)
    {
        $this->authenticationTypes = array_unique(
            array_merge(
                $this->authenticationTypes,
                $types
            )
        );
    }

    /**
     * Retrieve the supported authentication types
     *
     * @return array
     */
    public function getAuthenticationTypes()
    {
        return $this->authenticationTypes;
    }

    /**
     * Set the OAuth2 server
     *
     * This method is deprecated; create and attach an OAuth2Adapter instead.
     *
     * @return self
     * @deprecated
     *
     */
    public function setOauth2Server(OAuth2Server $oauth2Server)
    {
        $this->attach(new OAuth2Adapter($oauth2Server));
        return $this;
    }

    /**
     * Set the API/version to authentication type map.
     *
     * @param array $map
     * @return void
     */
    public function setAuthMap(array $map)
    {
        $this->authMap = $map;
    }

    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        $mvcEvent = $mvcAuthEvent->getMvcEvent();
        $request  = $mvcEvent->getRequest();
        $response = $mvcEvent->getResponse();

        if (
            !$request instanceof HttpRequest
            || $request->isOptions()
        ) {
            return;
        }

        $type = $this->getTypeFromMap($mvcEvent->getRouteMatch());
        if (false === $type && count($this->adapters) > 1) {
            // Ambiguous situation; no matching type in map, but multiple
            // authentication adapters; return a guest identity.
            $identity = new Identity\GuestIdentity();
            $mvcEvent->setParam('Jield\ApiTools\MvcAuth\Identity', $identity);
            return $identity;
        }

        $type = $type ?: $this->getTypeFromRequest($request);
        if (false === $type) {
            // No authentication type known; trigger any pre-flight actions,
            // and return a guest identity.
            $this->triggerAdapterPreAuth($request, $response);
            $identity = new Identity\GuestIdentity();
            $mvcEvent->setParam('Jield\ApiTools\MvcAuth\Identity', $identity);
            return $identity;
        }

        // Authenticate against first matching adapter
        $identity = $this->authenticate($type, $request, $response, $mvcAuthEvent);

        // If the adapter returns a response instance, return it directly.
        if ($identity instanceof HttpResponse) {
            return $identity;
        }

        // If no identity returned, create a guest identity
        if (!$identity instanceof Identity\IdentityInterface) {
            $identity = new Identity\GuestIdentity();
        }

        $mvcEvent->setParam('Jield\ApiTools\MvcAuth\Identity', $identity);
        return $identity;
    }

    /**
     * Match the controller to an authentication type, based on the API to
     * which the controller belongs.
     *
     * @param null|RouteMatch $routeMatch
     * @return string|false
     */
    private function getTypeFromMap($routeMatch = null)
    {
        if (null === $routeMatch) {
            return false;
        }

        $controller = $routeMatch->getParam('controller', false);
        if (false === $controller) {
            return false;
        }

        foreach ($this->authMap as $api => $type) {
            $api = rtrim($api, '\\') . '\\';
            if (strlen($api) > strlen($controller)) {
                continue;
            }

            if (0 === strpos($controller, $api)) {
                return $type;
            }
        }

        return false;
    }

    /**
     * Determine the authentication type based on request information
     *
     * @return false|string
     */
    private function getTypeFromRequest(HttpRequest $request)
    {
        foreach ($this->adapters as $adapter) {
            $type = $adapter->getTypeFromRequest($request);
            if (false !== $type) {
                return $type;
            }
        }
        return false;
    }

    /**
     * Trigger the preAuth routine of each adapter
     *
     * This method is triggered if no authentication type was discovered in the
     * request.
     */
    private function triggerAdapterPreAuth(HttpRequest $request, HttpResponse $response): void
    {
        foreach ($this->adapters as $adapter) {
            $adapter->preAuth($request, $response);
        }
    }

    /**
     * Invoke the adapter matching the given $type in order to peform authentication
     *
     * @param string $type
     * @return false|Identity\IdentityInterface
     */
    private function authenticate($type, HttpRequest $request, HttpResponse $response, MvcAuthEvent $mvcAuthEvent)
    {
        foreach ($this->adapters as $adapter) {
            if (!$adapter->matches($type)) {
                continue;
            }

            return $adapter->authenticate($request, $response, $mvcAuthEvent);
        }

        return false;
    }
}
