<?php

declare(strict_types=1);

namespace Jield\ApiTools\MvcAuth;

use Jield\ApiTools\MvcAuth\Identity\IdentityInterface;
use Laminas\Authentication\Result;
use Laminas\EventManager\Event;
use Laminas\Mvc\MvcEvent;

class MvcAuthEvent extends Event
{
    public const string EVENT_AUTHENTICATION      = 'authentication';

    public const string EVENT_AUTHENTICATION_POST = 'authentication.post';

    public const string EVENT_AUTHORIZATION       = 'authorization';

    public const string EVENT_AUTHORIZATION_POST  = 'authorization.post';

    /** @var MvcEvent */
    protected MvcEvent $mvcEvent;

    /** @var Result */
    protected Result $authenticationResult;

    /**
     * Whether or not authorization has completed/succeeded
     *
     * @var bool
     */
    protected bool $authorized = false;

    /**
     * The resource used for authorization queries
     *
     * @var mixed
     */
    protected mixed $resource;

    /**
     * @param mixed    $authentication
     * @param mixed    $authorization
     */
    public function __construct(MvcEvent $mvcEvent, protected $authentication, protected $authorization)
    {
        $this->mvcEvent       = $mvcEvent;
    }

    /**
     * @return mixed
     */
    public function getAuthenticationService(): mixed
    {
        return $this->authentication;
    }

    /**
     * @return bool
     */
    public function hasAuthenticationResult(): bool
    {
        return $this->authenticationResult !== null;
    }

    public function setAuthenticationResult(Result $result): static
    {
        $this->authenticationResult = $result;
        return $this;
    }

    /**
     * @return null|Result
     */
    public function getAuthenticationResult(): ?Result
    {
        return $this->authenticationResult;
    }

    /**
     * @return mixed
     */
    public function getAuthorizationService(): mixed
    {
        return $this->authorization;
    }

    /**
     * @return MvcEvent
     */
    public function getMvcEvent(): MvcEvent
    {
        return $this->mvcEvent;
    }

    /**
     * @return mixed|null
     */
    public function getIdentity(): mixed
    {
        return $this->authentication->getIdentity();
    }

    /**
     * @return $this
     */
    public function setIdentity(IdentityInterface $identity): static
    {
        $this->authentication->getStorage()->write($identity);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getResource(): mixed
    {
        return $this->resource;
    }

    public function setResource(mixed $resource): static
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return $this->authorized;
    }

    /**
     * @param bool $flag
     * @return self
     */
    public function setIsAuthorized(bool $flag): static
    {
        $this->authorized = (bool) $flag;
        return $this;
    }
}
