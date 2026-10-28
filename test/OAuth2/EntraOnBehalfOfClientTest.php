<?php

declare(strict_types=1);

namespace Jield\ApiToolsTest\OAuth2;

use Jield\ApiTools\OAuth2\EntraClientInterface;
use Jield\ApiTools\OAuth2\EntraOnBehalfOfClient;
use Jield\ApiTools\OAuth2\EntraException;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class EntraOnBehalfOfClientTest extends TestCase
{
    public function testUsesDatabaseCredentialsAndDelegatedAssertion(): void
    {
        $handler = new MockHandler([new Response(200, [], '{"access_token":"token-b"}')]);
        $client = new EntraOnBehalfOfClient(new Client(['handler' => HandlerStack::create($handler)]));
        self::assertSame('token-b', $client->acquireToken($this->service(), 'token-a', 'api://resource/Records.Read.Own'));
        parse_str((string)$handler->getLastRequest()->getBody(), $form);
        self::assertSame('middle-tier', $form['client_id']);
        self::assertSame('test-secret', $form['client_secret']);
        self::assertSame('token-a', $form['assertion']);
        self::assertSame('on_behalf_of', $form['requested_token_use']);
        self::assertSame('urn:ietf:params:oauth:grant-type:jwt-bearer', $form['grant_type']);
        self::assertSame('api://resource/Records.Read.Own', $form['scope']);
        self::assertSame('https://login.microsoftonline.com/11111111-1111-1111-1111-111111111111/oauth2/v2.0/token', (string)$handler->getLastRequest()->getUri());
    }

    public function testDoesNotExposeProviderErrorsOrCredentials(): void
    {
        $handler = new MockHandler([new Response(400, [], '{"error":"invalid_grant","error_description":"test-secret token-a"}')]);
        try {
            (new EntraOnBehalfOfClient(new Client(['handler' => HandlerStack::create($handler)])))
                ->acquireToken($this->service(), 'token-a', 'api://resource/Records.Read.Own');
            self::fail('Expected refusal');
        } catch (EntraException $exception) {
            self::assertStringNotContainsString('test-secret', $exception->getMessage());
            self::assertStringNotContainsString('token-a', $exception->getMessage());
        }
    }

    private function service(): EntraClientInterface
    {
        $service = $this->createStub(EntraClientInterface::class);
        $service->method('serviceIsMicrosoft')->willReturn(true);
        $service->method('getTenantId')->willReturn('11111111-1111-1111-1111-111111111111');
        $service->method('getClientId')->willReturn('middle-tier');
        $service->method('getClientSecret')->willReturn('test-secret');
        $service->method('getAccessTokenUrl')->willReturn('https://login.microsoftonline.com/11111111-1111-1111-1111-111111111111/oauth2/v2.0/token');
        return $service;
    }
}
