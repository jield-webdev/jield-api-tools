<?php

declare(strict_types=1);

namespace Jield\ApiToolsTest\OAuth2\Controller;

use Jield\ApiTools\OAuth2\Controller\TokenExchangeController;
use Jield\ApiTools\OAuth2\EntraException;
use Jield\ApiTools\OAuth2\TokenExchangeInterface;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use PHPUnit\Framework\TestCase;

final class TokenExchangeControllerTest extends TestCase
{
    public function testJsonExchangeReturnsUncacheableTokenResponse(): void
    {
        $exchange = $this->createMock(TokenExchangeInterface::class);
        $exchange->expects($this->once())->method('exchange')->with(['subject_token' => 'test-token'])
            ->willReturn(['access_token' => 'local-token']);
        $request = new Request();
        $request->setMethod('POST');
        $request->setContent('{"subject_token":"test-token"}');
        $request->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $response = $this->dispatch($exchange, $request);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('no-store', $response->getHeaders()->get('Cache-Control')->getFieldValue());
        self::assertSame(['access_token' => 'local-token'], json_decode($response->getContent(), true));
    }

    public function testFormExchangeReportsClientErrorWithoutReflectingRequest(): void
    {
        $exchange = $this->createMock(TokenExchangeInterface::class);
        $exchange->expects($this->once())->method('exchange')->with(['client_secret' => 'test-secret'])
            ->willThrowException(new EntraException('invalid_client', 'Invalid client', 401));
        $request = (new Request())->setMethod('POST');
        $request->getHeaders()->addHeaderLine('Content-Type', 'application/x-www-form-urlencoded');
        $request->getPost()->set('client_secret', 'test-secret');
        $response = $this->dispatch($exchange, $request);
        self::assertSame(401, $response->getStatusCode());
        self::assertStringNotContainsString('test-secret', $response->getContent());
    }

    public function testMalformedJsonAndUnsupportedMethodDoNotExchangeTokens(): void
    {
        $exchange = $this->createMock(TokenExchangeInterface::class);
        $exchange->expects($this->never())->method('exchange');
        $request = new Request();
        $request->setMethod('POST');
        $request->setContent('{invalid');
        $request->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        self::assertSame(400, $this->dispatch($exchange, $request)->getStatusCode());
        self::assertSame(405, $this->dispatch($exchange, (new Request())->setMethod('GET'))->getStatusCode());
    }

    private function dispatch(TokenExchangeInterface $exchange, Request $request): Response
    {
        $event = new MvcEvent();
        $event->setRequest($request);
        $event->setResponse(new Response());
        $event->setRouteMatch(new RouteMatch(['action' => 'token']));
        $controller = new TokenExchangeController($exchange);
        $controller->setEvent($event);
        return $controller->dispatch($request, new Response());
    }
}
