<?php

declare(strict_types=1);

namespace Jield\ApiTools\MvcAuth\Identity;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Mvc\InjectApplicationEventInterface;

class IdentityPlugin extends AbstractPlugin
{
    /** @return GuestIdentity|IdentityInterface */
    public function __invoke(): GuestIdentity|IdentityInterface
    {
        $controller = $this->getController();
        if (!$controller instanceof InjectApplicationEventInterface) {
            return new GuestIdentity();
        }

        $event    = $controller->getEvent();
        $identity = $event->getParam(name: __NAMESPACE__);

        if (!$identity instanceof IdentityInterface) {
            return new GuestIdentity();
        }

        return $identity;
    }
}
