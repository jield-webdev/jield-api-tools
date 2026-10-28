<?php

declare(strict_types=1);

namespace Jield\ApiToolsTest\OAuth2;

use Jield\ApiTools\MvcAuth\Factory\DefaultAuthenticationListenerFactory;
use ErrorException;
use Jield\ApiTools\MvcAuth\Authentication\DefaultAuthenticationListener;
use Jield\ApiTools\MvcAuth\Authentication\OAuth2Adapter;
use Jield\ApiTools\MvcAuth\Identity\AuthenticatedIdentity;
use Jield\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Authentication\Adapter\Http;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Storage\NonPersistent;
use Laminas\Http\PhpEnvironment\Response;
use Laminas\Http\Request;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\RouteMatch;
use OAuth2\Encryption\Jwt;
use OAuth2\Server;
use OAuth2\Storage\Memory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class OAuth2AdapterTest extends TestCase
{
    public function testMalformedVerificationKeyReturnsUnauthorizedJson(): void
    {
        [$token] = $this->token();
        $adapter = $this->adapter('not a public key');
        [$request, $response, $event] = $this->request($token);

        set_error_handler(static function (int $severity, string $message): never {
            throw new ErrorException($message, 0, $severity);
        });
        try {
            $result = $adapter->authenticate($request, $response, $event);
        } finally {
            restore_error_handler();
        }

        self::assertSame($response, $result);
        self::assertSame(401, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaders()->get('Content-Type')->getFieldValue());
        self::assertContains('Bearer error="invalid_token"', (array)$response->getHeaders()->toArray()['WWW-Authenticate']);
        self::assertSame('invalid_token', json_decode($response->getContent(), true)['error']);
    }

    public function testWrongSignatureReturnsUnauthorized(): void
    {
        [$token] = $this->token();
        [, $otherPublicKey] = $this->token();
        $adapter = $this->adapter($otherPublicKey);
        [$request, $response, $event] = $this->request($token);

        $result = $adapter->authenticate($request, $response, $event);

        self::assertSame($response, $result);
        self::assertSame(401, $response->getStatusCode());
        self::assertStringContainsString('error="invalid_token"', implode(' ', (array)$response->getHeaders()->toArray()['WWW-Authenticate']));
    }

    public function testValidTokenStillAuthenticates(): void
    {
        [$token, $publicKey] = $this->token();
        $adapter = $this->adapter($publicKey);
        [$request, $response, $event] = $this->request($token);

        $identity = $adapter->authenticate($request, $response, $event);

        self::assertInstanceOf(AuthenticatedIdentity::class, $identity);
        self::assertSame(42, $identity->getAuthenticationIdentity()['user_id']);
    }

    public function testFactoryHandlesMappedApiTokenBeforeTheLegacyAdapter(): void
    {
        [$token] = $this->token();
        $server = $this->server('not a public key');
        $config = [
            'api-tools-oauth2' => ['storage' => 'storage'],
            'api-tools-mvc-auth' => ['authentication' => ['map' => ['Example\\V1' => 'jield_oauth2_pdo_adapter']]],
        ];
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturnCallback(static fn(string $name): mixed => match ($name) {
            'config' => $config,
            Http::class => false,
            'Jield\ApiTools\OAuth2\Service\OAuth2Server' => static fn() => $server,
        });
        $listener = (new DefaultAuthenticationListenerFactory())($container, DefaultAuthenticationListener::class);
        $listener->attach(new OAuth2Adapter($server, 'jield_oauth2_pdo_adapter'));
        [, $response, $event] = $this->request($token);
        $event->getMvcEvent()->setRouteMatch(new RouteMatch(['controller' => 'Example\\V1\\Rest\\Example']));

        set_error_handler(static function (int $severity, string $message): never {
            throw new ErrorException($message, 0, $severity);
        });
        try {
            $result = $listener($event);
        } finally {
            restore_error_handler();
        }

        self::assertSame($response, $result);
        self::assertSame(401, $response->getStatusCode());
    }

    private function adapter(string $publicKey): OAuth2Adapter
    {
        return new OAuth2Adapter($this->server($publicKey));
    }

    private function server(string $publicKey): Server
    {
        $storage = new Memory(['keys' => ['client' => [
            'public_key' => $publicKey,
            'encryption_algorithm' => 'RS256',
        ]]]);
        return new Server($storage, ['use_jwt_access_tokens' => true]);
    }

    private function token(): array
    {
        $key = @openssl_pkey_new(['private_key_bits' => 2048]);
        openssl_pkey_export($key, $privateKey);
        $publicKey = openssl_pkey_get_details($key)['key'];
        $token = new Jwt()->encode([
            'id' => 'test-token', 'aud' => 'client', 'sub' => 42, 'exp' => time() + 300,
        ], $privateKey, 'RS256');
        return [$token, $publicKey];
    }

    private function request(string $token): array
    {
        $request = new Request();
        $request->getHeaders()->addHeaderLine('Authorization', 'Bearer ' . $token);
        $response = new Response();
        $mvc = new MvcEvent();
        $mvc->setRequest($request);
        $mvc->setResponse($response);
        return [$request, $response, new MvcAuthEvent($mvc, new AuthenticationService(new NonPersistent()), null)];
    }
}
