# jield-api-tools

Own implementation of API endpoints. This repository is based on the Laminas API tools but includes shared functionality
to easily create API endpoints

Development of version 2 takes place on `2.x`. The first prerelease is `v2.0.0-beta.1`; the existing 1.x line remains
separate. Version 2 requires PHP 8.4 or 8.5.

Install the first beta explicitly:

```shell
composer require 'jield-webdev/jield-api-tools:2.0.0-beta.1'
```

Some upstream dependencies still restrict PHP to 8.4. On PHP 8.5, append `--ignore-platform-req=php+` to the
Composer command to ignore only PHP upper bounds while retaining minimum versions and extension requirements.

See [RELEASING.md](RELEASING.md) for the branch policy, checks, and commands to publish beta and stable versions.

For testing

```shell
docker compose run --rm php vendor/bin/phpstan --memory-limit=2G
```

### Configure application listener discovery

The package does not assume a vendor location or an application namespace. Add a configured provider to your
application's config aggregator:

```php
new \Jield\ApiTools\ListenerConfigProvider([
    'MyApp\\Api\\Rest' => __DIR__ . '/Rest',
]),
```

Each key is an autoloadable namespace prefix and each value is its listener directory. Multiple namespaces/directories
are supported. The application owns these paths, so Composer symlinks and Docker mounts work without package changes.

### Reuse Entra authentication

The package registers `EntraJwksProvider`, `EntraTokenValidator`, `EntraOnBehalfOfClient`, and `TokenExchange` as
services. Implement `EntraResourceInterface` and `EntraClientInterface` on your configuration object or an adapter; the
package has no dependency on application entities or user services.

Configure `api-tools-oauth2.entra.application_roles` with the roles your app accepts for application tokens. An empty
list rejects application tokens. `diagnostics` defaults to false; enable detailed validation errors explicitly for
development. `cache_directory` optionally selects the JWKS cache location. Token lifetime and issuer use the existing
`api-tools-oauth2.access_lifetime` and `api-tools-oauth2.options.issuer` settings.

Extend `AbstractEntraBearerAuthenticationListener` to select supported routes and map a validated token to an identity.
Its `createIdentity` hook receives unverified claims only to select trusted local configuration; it must call the
validator before creating an identity. The package handles bearer parsing, rejection responses, event identity
registration and nonpersistent authentication storage. The application attaches its listener to
`MvcAuthEvent::EVENT_AUTHENTICATION` at its chosen priority.

Implement `TokenExchangeInterface` for application client/user authorization and persistence, then inject it into
`OAuth2\Controller\TokenExchangeController`. Use `TokenExchange::validateRequest()` and `issueToken()` for RFC request
checks, signed access tokens and source expiry limits. Exchanges do not issue refresh tokens. Normal OAuth authorization
and refresh grants remain in the package server factories; refresh rotation uses the existing OAuth options.

Run package tests with `vendor/bin/phpunit`. When developing against an application's installed dependencies:

```shell
API_TOOLS_TEST_AUTOLOAD=../solodb/vendor/autoload.php ../solodb/vendor/bin/phpunit -c phpunit.xml
```
