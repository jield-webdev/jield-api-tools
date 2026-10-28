<?php

declare(strict_types=1);

namespace Jield\ApiToolsTest\Rest\Listener;

use Jield\ApiTools\Rest\Listener\RestParametersListener;
use Jield\ApiTools\Rest\RestController;
use Laminas\EventManager\SharedEventManager;
use Laminas\Mvc\MvcEvent;
use PHPUnit\Framework\TestCase;

class RestParametersListenerTest extends TestCase
{
    public function testSharedListenerCanBeDetached(): void
    {
        $events = new SharedEventManager();
        $listener = new RestParametersListener();

        $listener->attachShared($events);
        self::assertCount(1, $events->getListeners([RestController::class], MvcEvent::EVENT_DISPATCH)[100]);

        $listener->detachShared($events);
        self::assertSame([], $events->getListeners([RestController::class], MvcEvent::EVENT_DISPATCH));
    }
}
