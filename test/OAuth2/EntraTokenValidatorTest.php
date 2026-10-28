<?php

declare(strict_types=1);

namespace Jield\ApiToolsTest\OAuth2;

use Jield\ApiTools\OAuth2\Factory\EntraTokenValidatorFactory;
use Jield\ApiTools\OAuth2\EntraResourceInterface;
use Jield\ApiTools\OAuth2\DevelopmentEntraTokenValidator;
use Jield\ApiTools\OAuth2\EntraException;
use Jield\ApiTools\OAuth2\EntraJwksProvider;
use Jield\ApiTools\OAuth2\EntraTokenValidator;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class EntraTokenValidatorTest extends TestCase
{
    private const string TENANT = '11111111-1111-1111-1111-111111111111';
    private const string API = '22222222-2222-2222-2222-222222222222';
    private const string CALLER = '33333333-3333-3333-3333-333333333333';
    private static string $privateKey = '';
    private static string $publicKey;

    public static function setUpBeforeClass(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        openssl_pkey_export($key, self::$privateKey);
        self::$publicKey = openssl_pkey_get_details($key)['key'];
    }

    public function testAcceptsSignedDelegatedTokenForConfiguredApiAndCaller(): void
    {
        $claims = $this->claims();
        self::assertSame($claims, $this->validator()->validate($this->token($claims), $this->service()));
    }

    public function testAcceptsSignedApplicationTokenWithRequiredRole(): void
    {
        $claims = $this->applicationClaims();
        self::assertSame($claims, $this->validator()->validate($this->token($claims), $this->service()));
    }

    #[DataProvider('invalidApplicationClaims')]
    public function testRejectsUntrustedApplicationTokens(array $changes): void
    {
        $this->expectException(EntraException::class);
        $this->validator()->validate($this->token(array_replace($this->applicationClaims(), $changes)), $this->service());
    }

    public static function invalidApplicationClaims(): array
    {
        return [
            'wrong tenant' => [['tid' => self::CALLER]],
            'wrong issuer' => [['iss' => 'https://example.test']],
            'wrong audience' => [['aud' => self::CALLER]],
            'v1 token' => [['ver' => '1.0']],
            'unauthorized caller' => [['azp' => self::API]],
            'missing caller' => [['azp' => null]],
            'missing principal' => [['oid' => null]],
            'unidentified role token' => [['idtyp' => null]],
            'user role token' => [['idtyp' => 'user']],
            'delegated token with app marker' => [['scp' => 'Records.Read.All']],
            'null delegated scope' => [['scp' => null]],
            'missing roles' => [['roles' => null]],
            'wrong role' => [['roles' => ['Records.Read.Own']]],
            'string roles' => [['roles' => 'Records.Read.All']],
            'non-string role' => [['roles' => ['Records.Read.All', ['nested']]]],
            'object-shaped roles' => [['roles' => ['role' => 'Records.Read.All']]],
            'expired token' => [['exp' => 1]],
            'future token' => [['nbf' => 4102444800]],
        ];
    }

    #[DataProvider('invalidClaims')]
    public function testRejectsUntrustedClaims(array $changes): void
    {
        $this->expectException(EntraException::class);
        $this->validator()->validate($this->token(array_replace($this->claims(), $changes)), $this->service());
    }

    public static function invalidClaims(): array
    {
        return [
            'other tenant' => [['tid' => self::CALLER]],
            'other issuer' => [['iss' => 'https://example.test']],
            'graph audience' => [['aud' => '00000003-0000-0000-c000-000000000000']],
            'app ID URI instead of v2 audience' => [['aud' => 'api://' . self::API]],
            'v1 token' => [['ver' => '1.0']],
            'unknown caller' => [['azp' => self::API]],
            'missing caller' => [['azp' => null]],
            'missing object ID' => [['oid' => null]],
            'app only' => [['scp' => null, 'roles' => ['Records.Read.Own'], 'idtyp' => 'app']],
            'app type with scopes' => [['idtyp' => 'app']],
            'ID token' => [['scp' => null]],
            'empty scopes' => [['scp' => '']],
            'expired' => [['exp' => 1]],
            'missing expiry' => [['exp' => null]],
            'future validity' => [['nbf' => 4102444800]],
            'future issued at' => [['iat' => 4102444800]],
            'missing nbf' => [['nbf' => null]],
        ];
    }

    public function testRejectsForgedSignature(): void
    {
        $token = $this->token($this->claims());
        $parts = explode('.', $token);
        $claims = $this->claims();
        $claims['oid'] = self::CALLER;
        $parts[1] = JWT::urlsafeB64Encode(json_encode($claims, JSON_THROW_ON_ERROR));
        $this->expectException(EntraException::class);
        $this->validator()->validate(implode('.', $parts), $this->service());
    }

    public function testRejectsForgedApplicationRole(): void
    {
        $claims = array_replace($this->applicationClaims(), ['roles' => ['Other.Role']]);
        $parts = explode('.', $this->token($claims));
        $parts[1] = JWT::urlsafeB64Encode(json_encode($this->applicationClaims(), JSON_THROW_ON_ERROR));
        $this->expectException(EntraException::class);
        $this->validator()->validate(implode('.', $parts), $this->service());
    }

    public function testRejectsSymmetricAlgorithmBeforeLookingUpKeys(): void
    {
        $keys = $this->createMock(EntraJwksProvider::class);
        $keys->expects($this->never())->method('getKey');
        $this->expectException(EntraException::class);
        (new EntraTokenValidator($keys, ['Records.Read.All']))->validate(JWT::encode($this->claims(), str_repeat('x', 32), 'HS256', 'test'), $this->service());
    }

    public function testRejectsDisabledServiceAndMalformedTenantBeforeNetworkAccess(): void
    {
        $service = $this->service('../common');
        $keys = $this->createMock(EntraJwksProvider::class);
        $keys->expects($this->never())->method('getKey');
        $this->expectException(EntraException::class);
        (new EntraTokenValidator($keys, ['Records.Read.All']))->validate($this->token($this->claims()), $service);
    }

    public function testProductionKeepsTheStandardClaimError(): void
    {
        try {
            $this->validator()->validate($this->token(array_replace($this->claims(), ['aud' => self::CALLER])), $this->service());
            self::fail('Expected the wrong audience to be rejected');
        } catch (EntraException $exception) {
            self::assertSame('invalid_grant', $exception->error);
            self::assertSame('Entra token claims do not match the configured API, tenant or caller', $exception->getMessage());
        }
    }

    #[DataProvider('developmentClaimErrors')]
    public function testDevelopmentIdentifiesTheInvalidClaim(array $changes, string $detail): void
    {
        try {
            $this->developmentValidator()->validate($this->token(array_replace($this->claims(), $changes)), $this->service());
            self::fail('Expected the invalid claim to be rejected');
        } catch (EntraException $exception) {
            self::assertSame('invalid_grant', $exception->error);
            self::assertStringContainsString($detail, $exception->getMessage());
        }
    }

    public static function developmentClaimErrors(): array
    {
        return [
            'audience' => [['aud' => self::CALLER], 'Claim aud must be "' . self::API . '"; got "' . self::CALLER . '"'],
            'caller' => [['azp' => self::API], 'Claim azp "' . self::API . '" is not in the configured caller client IDs'],
            'missing scope' => [['scp' => null], 'got scp null and idtyp <missing>'],
            'missing validity' => [['nbf' => null], 'Claim nbf must be an integer'],
        ];
    }

    public function testDevelopmentExplainsApplicationRoleFailure(): void
    {
        $claims = array_replace($this->applicationClaims(), ['roles' => ['Other.Role']]);
        $this->expectExceptionMessage('Claim roles must contain Records.Read.All; got ["Other.Role"]');
        $this->developmentValidator()->validate($this->token($claims), $this->service());
    }

    public function testDevelopmentReportsJwtSignatureFailure(): void
    {
        $parts = explode('.', $this->token($this->claims()));
        $parts[1] = JWT::urlsafeB64Encode(json_encode(array_replace($this->claims(), ['oid' => self::CALLER]), JSON_THROW_ON_ERROR));

        $this->expectExceptionMessage('JWT verification failed (Firebase\\JWT\\SignatureInvalidException): Signature verification failed');
        $this->developmentValidator()->validate(implode('.', $parts), $this->service());
    }

    public function testDevelopmentExplainsInvalidHeaderAndConfiguration(): void
    {
        $service = $this->service('../common');
        try {
            $this->developmentValidator()->validate($this->token($this->claims()), $service);
            self::fail('Expected the malformed tenant ID to be rejected');
        } catch (EntraException $exception) {
            self::assertStringContainsString('The configured tenant ID must be a GUID; got "../common"', $exception->getMessage());
        }

        $this->expectExceptionMessage('Header alg must be RS256; got "HS256"');
        $this->developmentValidator()->validate(JWT::encode($this->claims(), str_repeat('x', 32), 'HS256', 'test'), $this->service());
    }

    public function testDevelopmentExplainsSigningKeyLookupWhilePreservingItsStatus(): void
    {
        $keys = $this->createStub(EntraJwksProvider::class);
        $keys->method('getKey')->willThrowException(new EntraException('temporarily_unavailable', 'Entra signing keys are unavailable', 503));

        try {
            (new DevelopmentEntraTokenValidator($keys, ['Records.Read.All']))->validate($this->token($this->claims()), $this->service());
            self::fail('Expected signing key lookup to fail');
        } catch (EntraException $exception) {
            self::assertSame('temporarily_unavailable', $exception->error);
            self::assertSame(503, $exception->status);
            self::assertSame('Entra signing keys are unavailable: signing key lookup failed for kid "test-key"', $exception->getMessage());
        }
    }

    public function testDevelopmentExplainsMalformedJwtStructure(): void
    {
        $this->expectExceptionMessage('JWT must contain exactly three dot-separated segments; got 2');
        $this->developmentValidator()->validate('header.payload', $this->service());
    }

    public function testFactoryUsesExplicitDiagnosticConfiguration(): void
    {
        $keys = $this->createStub(EntraJwksProvider::class);
        foreach ([true, false] as $diagnostics) {
            $container = $this->createStub(ContainerInterface::class);
            $container->method('get')->willReturnCallback(static fn(string $name): mixed => match ($name) {
                EntraJwksProvider::class => $keys,
                'config' => ['api-tools-oauth2' => ['entra' => ['diagnostics' => $diagnostics, 'application_roles' => ['Records.Read.All']]]],
            });
            self::assertSame(
                $diagnostics ? DevelopmentEntraTokenValidator::class : EntraTokenValidator::class,
                (new EntraTokenValidatorFactory())($container)::class
            );
        }
    }

    private function claims(): array
    {
        return [
            'ver' => '2.0', 'tid' => self::TENANT, 'iss' => 'https://login.microsoftonline.com/' . self::TENANT . '/v2.0',
            'aud' => self::API, 'azp' => self::CALLER, 'oid' => '44444444-4444-4444-4444-444444444444',
            'scp' => 'Records.Read.Own', 'iat' => time() - 10, 'nbf' => time() - 10, 'exp' => time() + 600,
        ];
    }

    private function service(string $tenantId = self::TENANT): EntraResourceInterface
    {
        $service = $this->createStub(EntraResourceInterface::class);
        $service->method('serviceIsMicrosoft')->willReturn(true);
        $service->method('getTenantId')->willReturn($tenantId);
        $service->method('getClientId')->willReturn(self::API);
        $service->method('isEntraTokenExchangeEnabled')->willReturn(true);
        $service->method('getEntraAllowedClientIds')->willReturn(self::CALLER);
        return $service;
    }

    private function applicationClaims(): array
    {
        $claims = $this->claims();
        unset($claims['scp']);
        return $claims + ['idtyp' => 'app', 'roles' => ['Records.Read.All']];
    }

    private function validator(): EntraTokenValidator
    {
        $keys = $this->createStub(EntraJwksProvider::class);
        $keys->method('getKey')->willReturn(new Key(self::$publicKey, 'RS256'));
        return new EntraTokenValidator($keys, ['Records.Read.All']);
    }

    private function developmentValidator(): DevelopmentEntraTokenValidator
    {
        $keys = $this->createStub(EntraJwksProvider::class);
        $keys->method('getKey')->willReturn(new Key(self::$publicKey, 'RS256'));
        return new DevelopmentEntraTokenValidator($keys, ['Records.Read.All']);
    }

    private function token(array $claims): string
    {
        return JWT::encode($claims, self::$privateKey, 'RS256', 'test-key');
    }
}
