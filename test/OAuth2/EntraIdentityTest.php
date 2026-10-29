<?php

declare(strict_types=1);

namespace Jield\ApiToolsTest\OAuth2;

use Jield\ApiTools\OAuth2\EntraIdentity;
use PHPUnit\Framework\TestCase;

final class EntraIdentityTest extends TestCase
{
    public function testDelegatedPermissionsComeFromScopesAndDoNotIncludeRoles(): void
    {
        $claims = ['scp' => "Example.Read\nExample.Write Example.Read", 'roles' => ['Example.Read.All']];
        self::assertFalse(EntraIdentity::isApplication($claims));
        self::assertSame(['Example.Read', 'Example.Write'], EntraIdentity::scopes($claims, ['Example.Read.All']));
    }

    public function testApplicationPermissionsRequireAnExplicitApplicationAllowList(): void
    {
        $claims = ['idtyp' => 'app', 'roles' => ['Example.Read.All', 'Other.Read.All']];
        self::assertTrue(EntraIdentity::isApplication($claims));
        self::assertSame([], EntraIdentity::scopes($claims));
        self::assertSame(['Other.Read.All'], EntraIdentity::scopes($claims, ['Unrelated.Read', 'Other.Read.All']));
        self::assertSame([], EntraIdentity::scopes($claims, ['other.read.all']));
    }

    public function testMissingPermissionsDoNotGrantAccess(): void
    {
        self::assertSame([], EntraIdentity::scopes([]));
        self::assertSame([], EntraIdentity::scopes(['idtyp' => 'app'], ['Example.Read.All']));
    }

    public function testApplicationSubjectsAreStableAndIncludeTheTenantAndObject(): void
    {
        self::assertSame('spn:tenant:object', EntraIdentity::applicationSubject('tenant', 'object'));
        self::assertNotSame(
            EntraIdentity::applicationSubject('tenant-a', 'object'),
            EntraIdentity::applicationSubject('tenant-b', 'object')
        );
    }
}
