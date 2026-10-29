<?php

declare(strict_types=1);

namespace Jield\ApiToolsTest\OAuth2;

use Jield\ApiTools\Listener\AbstractRoutedListener;
use Jield\ApiTools\MvcAuth\Identity\AuthenticatedIdentity;
use Jield\ApiTools\OAuth2\AbstractListenerScopeGuard;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ListenerScopeGuardTest extends TestCase
{
    #[DataProvider('policies')]
    public function testScopeAndMethodBoundaries(string $listener, string $method, ?string $scope, bool $application, ?int $status): void
    {
        $event = new MvcEvent();
        $event->setRequest((new Request())->setMethod($method));
        $event->setResponse(new Response());
        $event->setRouteMatch(new RouteMatch(['controller' => $listener]));
        if ($scope !== null) {
            $event->setParam(AuthenticatedIdentity::class, new AuthenticatedIdentity([
                'id' => 'token', 'expires' => time() + 100, 'scope' => $scope,
                'principal_type' => $application ? 'application' : 'user',
            ]));
        }
        $guard = new class extends AbstractListenerScopeGuard {
            protected function validateIdentity(MvcEvent $event, array $identity): ?Response
            {
                $event->setParam('validated', true);
                return null;
            }
        };
        $response = $guard($event);
        self::assertSame($status, $response?->getStatusCode());
        self::assertSame($status === null && $scope !== null && $method !== 'OPTIONS', $event->getParam('validated', false));
    }

    public static function policies(): array
    {
        return [
            [ScopedListener::class, 'GET', 'Example.Read', false, null],
            [ScopedListener::class, 'HEAD', 'Example.Read', false, null],
            [ScopedListener::class, 'GET', 'Example.Read.All', false, null],
            [ScopedListener::class, 'PATCH', 'Example.Write', false, null],
            [ScopedListener::class, 'PATCH', 'Example.Read', false, 403],
            [ScopedListener::class, 'GET', 'example.read', false, 403],
            [ScopedListener::class, 'GET', 'Example.*', false, 403],
            [ScopedListener::class, 'POST', 'Example.Write', false, 403],
            [ScopedListener::class, 'GET', '', false, 403],
            [ScopedListener::class, 'GET', null, false, 401],
            [ScopedListener::class, 'GET', 'Example.Read', true, 403],
            [ScopedListener::class, 'GET', 'Example.Read.All', true, null],
            [ScopedListener::class, 'PATCH', 'Example.Write Example.Read.All', true, 403],
            [UnscopedListener::class, 'GET', 'Example.Read', false, 403],
            [UnscopedListener::class, 'GET', null, false, null],
            ['browser-controller', 'GET', 'Example.Read', false, 403],
            ['browser-controller', 'GET', null, false, null],
            [ScopedListener::class, 'OPTIONS', null, false, null],
        ];
    }

    public function testExpiredTokenIsRejectedBeforeApplicationValidation(): void
    {
        $event = new MvcEvent();
        $event->setRequest((new Request())->setMethod('GET'));
        $event->setResponse(new Response());
        $event->setRouteMatch(new RouteMatch(['controller' => ScopedListener::class]));
        $event->setParam(AuthenticatedIdentity::class, new AuthenticatedIdentity([
            'id' => 'token', 'expires' => time() - 1, 'scope' => 'Example.Read',
        ]));
        $guard = new class extends AbstractListenerScopeGuard {
            protected function validateIdentity(MvcEvent $event, array $identity): ?Response
            {
                TestCase::fail('Expired tokens must not reach application validation');
            }
        };
        $response = $guard($event);
        self::assertSame(401, $response->getStatusCode());
        self::assertSame('no-store', $response->getHeaders()->get('Cache-Control')->getFieldValue());
        self::assertSame('invalid_token', json_decode($response->getContent(), true)['error']);
    }

    public function testExplicitHeadPolicyOverridesGetAndOtherVerbsStayDenied(): void
    {
        $listener = new class extends AbstractRoutedListener {
            protected static array $scopes = ['GET' => ['Read'], 'HEAD' => []];
        };
        self::assertSame([], $listener::getScopesForMethod('HEAD'));
        self::assertSame([], $listener::getScopesForMethod('DELETE'));
        self::assertSame(['Read'], $listener::getScopesForMethod('get'));
    }
}

final class ScopedListener extends AbstractRoutedListener
{
    protected static array $scopes = ['GET' => ['Example.Read', 'Example.Read.All'], 'PATCH' => ['Example.Write']];
    protected static array $applicationScopes = ['Example.Read.All'];
    protected static bool $requireAccessToken = true;
}

final class UnscopedListener extends AbstractRoutedListener
{
}
