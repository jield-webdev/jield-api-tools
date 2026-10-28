<?php

declare(strict_types=1);

namespace Jield\ApiToolsTest\OAuth2;

use Jield\ApiTools\OAuth2\EntraException;
use Jield\ApiTools\OAuth2\EntraJwksProvider;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class EntraJwksProviderTest extends TestCase
{
    private const string TENANT = '11111111-1111-1111-1111-111111111111';

    public function testCachesMicrosoftKeysAndRefreshesOnRollover(): void
    {
        $cache = new ArrayAdapter();
        $item = $cache->getItem('entra_jwks_' . self::TENANT);
        $cache->save($item->set(['keys' => [$this->key('old')], 'fetchedAt' => time() - 61]));
        $handler = new MockHandler([new Response(200, [], json_encode(['keys' => [$this->key('new')]], JSON_THROW_ON_ERROR))]);
        $provider = new EntraJwksProvider(new Client(['handler' => HandlerStack::create($handler)]), $cache);
        self::assertSame('RS256', $provider->getKey(self::TENANT, 'new')->getAlgorithm());
        self::assertSame('RS256', $provider->getKey(self::TENANT, 'new')->getAlgorithm());
        self::assertCount(0, $handler);
        self::assertSame('https://login.microsoftonline.com/' . self::TENANT . '/discovery/v2.0/keys', (string)$handler->getLastRequest()->getUri());
    }

    public function testUnknownKeyDoesNotRepeatedlyFetchJwks(): void
    {
        $cache = new ArrayAdapter();
        $cache->save($cache->getItem('entra_jwks_' . self::TENANT)->set(['keys' => [], 'fetchedAt' => time()]));
        $handler = new MockHandler([]);
        $this->expectException(EntraException::class);
        (new EntraJwksProvider(new Client(['handler' => HandlerStack::create($handler)]), $cache))->getKey(self::TENANT, 'unknown');
    }

    public function testRejectsKeyFromOtherIssuer(): void
    {
        $key = $this->key('test');
        $key['issuer'] = 'https://example.test';
        $handler = new MockHandler([new Response(200, [], json_encode(['keys' => [$key]], JSON_THROW_ON_ERROR))]);
        $this->expectException(EntraException::class);
        (new EntraJwksProvider(new Client(['handler' => HandlerStack::create($handler)]), new ArrayAdapter()))->getKey(self::TENANT, 'test');
    }

    public function testFailsClosedWhenMicrosoftIsUnavailable(): void
    {
        $handler = new MockHandler([new Response(503)]);
        try {
            (new EntraJwksProvider(new Client(['handler' => HandlerStack::create($handler)]), new ArrayAdapter()))->getKey(self::TENANT, 'test');
            self::fail('Unavailable keys must reject the request');
        } catch (EntraException $exception) {
            self::assertSame(503, $exception->status);
            self::assertSame('temporarily_unavailable', $exception->error);
        }
    }

    private function key(string $kid): array
    {
        $details = openssl_pkey_get_details(openssl_pkey_new(['private_key_bits' => 2048]));
        return [
            'kid' => $kid, 'kty' => 'RSA', 'use' => 'sig',
            'n' => JWT::urlsafeB64Encode($details['rsa']['n']),
            'e' => JWT::urlsafeB64Encode($details['rsa']['e']),
            'issuer' => 'https://login.microsoftonline.com/{tenantid}/v2.0',
        ];
    }
}
