<?php

declare(strict_types=1);

namespace Jield\ApiTools\MvcAuth\Identity;

use Laminas\Permissions\Rbac\Role;
use Override;

class AuthenticatedIdentity extends Role implements IdentityInterface
{
    /** @param mixed $identity */
    public function __construct(protected $identity)
    {
    }

    /** @return null|string */
    public function getRoleId(): ?string
    {
        return $this->name;
    }

    /** @return mixed */
    #[Override]
    public function getAuthenticationIdentity(): mixed
    {
        return $this->identity;
    }

    /**
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
