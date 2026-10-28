<?php

declare(strict_types=1);

namespace Jield\ApiToolsTest\OAuth2;

use Jield\ApiTools\OAuth2\Factory\OAuth2ServerInstanceFactory;
use Jield\ApiTools\SettingsProvider;
use OAuth2\Request;
use OAuth2\Response;
use OAuth2\Storage\Memory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class RefreshTokenTest extends TestCase
{
    public function testRefreshTokenRotatesAndCannotBeReused(): void
    {
        $storage = new Memory(['client_credentials' => [
            'client' => ['client_secret' => 'secret', 'grant_types' => 'refresh_token'],
        ]]);
        $storage->setRefreshToken('old-refresh', 'client', 'user', time() + 300);
        $services = $this->createStub(ContainerInterface::class);
        $services->method('get')->willReturn($storage);
        $config = (new SettingsProvider())()['api-tools-oauth2'];
        $config['storage'] = 'memory';
        $config['grant_types'] = ['refresh_token' => true];
        $config['options']['use_jwt_access_tokens'] = false;
        $config['options']['use_openid_connect'] = false;
        $server = (new OAuth2ServerInstanceFactory($config, $services))();
        $request = new Request([], [
            'grant_type' => 'refresh_token', 'refresh_token' => 'old-refresh',
            'client_id' => 'client', 'client_secret' => 'secret',
        ], server: ['REQUEST_METHOD' => 'POST']);
        $response = new Response();
        $server->handleTokenRequest($request, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertNotEmpty($response->getParameter('access_token'));
        self::assertNotSame('old-refresh', $response->getParameter('refresh_token'));
        self::assertFalse($storage->getRefreshToken('old-refresh'));
        $response = new Response();
        $server->handleTokenRequest($request, $response);
        self::assertSame(400, $response->getStatusCode());
        self::assertSame('invalid_grant', $response->getParameter('error'));
    }
}
