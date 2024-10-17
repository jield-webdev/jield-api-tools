<?php

declare(strict_types=1);

namespace Jield\ApiTools\ContentValidation;

use Laminas\Mvc\MvcEvent;

class Module
{
    /** @return void */
    public function onBootstrap(MvcEvent $e)
    {
        $app      = $e->getApplication();
        $events   = $app->getEventManager();
        $services = $app->getServiceManager();

        $services->get(ContentValidationListener::class)->attach($events);
    }
}
