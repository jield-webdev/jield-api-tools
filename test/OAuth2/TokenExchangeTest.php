<?php

declare(strict_types=1);

namespace Jield\ApiToolsTest\OAuth2;

use Jield\ApiTools\OAuth2\EntraException;
use Jield\ApiTools\OAuth2\TokenExchange;
use OAuth2\Encryption\Jwt;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TokenExchangeTest extends TestCase
{
    public function testIssuesSignedTokenForAnotherApplicationAndBoundsItsLifetime(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        openssl_pkey_export($key, $private);
        $public = openssl_pkey_get_details($key)['key'];
        $exchange = new TokenExchange(['access_lifetime' => 7200, 'options' => ['issuer' => 'another-app']]);
        $result = $exchange->issueToken('client', 'external-user', ['Records.Read'], $private, time() + 120, ['custom' => true, 'exp' => time() + 99999])->toArray();
        $claims = new Jwt()->decode($result['access_token'], $public, ['RS256']);
        self::assertSame('another-app', $claims['iss']);
        self::assertSame('external-user', $claims['sub']);
        self::assertSame('Records.Read', $claims['scope']);
        self::assertTrue($claims['custom']);
        self::assertLessThanOrEqual(120, $result['expires_in']);
        self::assertSame($result['expires_in'], $claims['exp'] - $claims['iat']);
        self::assertSame($claims['id'], $claims['jti']);
        self::assertSame(TokenExchange::ACCESS_TOKEN, $result['issued_token_type']);
        self::assertArrayNotHasKey('refresh_token', $result);
    }

    public function testExpiredSubjectCannotIssueAToken(): void
    {
        $this->expectException(EntraException::class);
        $this->expectExceptionMessage('expired');
        new TokenExchange()->issueToken('client', 'user', ['Read'], 'private-key', time() - 1);
    }

    #[DataProvider('invalidRequests')]
    public function testRejectsUnsupportedOrMalformedRequests(array $changes): void
    {
        $this->expectException(EntraException::class);
        new TokenExchange()->validateRequest(array_replace([
            'grant_type' => TokenExchange::GRANT_TYPE,
            'subject_token_type' => TokenExchange::ACCESS_TOKEN,
            'client_id' => 'client', 'client_secret' => 'secret', 'subject_token' => 'token',
        ], $changes));
    }

    public static function invalidRequests(): array
    {
        return [
            [['grant_type' => 'password']], [['subject_token_type' => 'id_token']],
            [['requested_token_type' => 'refresh_token']], [['actor_token' => 'actor']],
            [['resource' => 'other']], [['audience' => 'other']],
            [['client_id' => []]], [['client_secret' => '']], [['subject_token' => null]],
        ];
    }
}
