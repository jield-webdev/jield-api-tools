<?php

declare(strict_types=1);

namespace Jield\ApiTools\OAuth2;

use Firebase\JWT\JWT;
use Jield\ApiTools\MvcAuth\Identity\AuthenticatedIdentity;
use Jield\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Authentication\Storage\NonPersistent;
use Laminas\Http\Request;
use Laminas\Http\Response;
use Throwable;

abstract class AbstractEntraBearerAuthenticationListener
{
    abstract protected function supports(MvcAuthEvent $event): bool;

    /** Unverified claims may only select local configuration; validate the token before returning an identity. */
    abstract protected function createIdentity(string $token, array $unverified, Response $response): AuthenticatedIdentity|Response;

    public function __invoke(MvcAuthEvent $event): AuthenticatedIdentity|Response|null
    {
        $mvc     = $event->getMvcEvent();
        $request = $mvc->getRequest();
        $response = $mvc->getResponse();
        if (!$request instanceof Request || !$response instanceof Response || !$this->supports($event)) {
            return null;
        }

        $header = $request->getHeaders()->get('Authorization');
        if (!$header || !preg_match('/^Bearer (\S+)$/iD', $header->getFieldValue(), $matches)) {
            return null;
        }

        $token = $matches[1];
        $parts = explode('.', $token);
        if (count($parts) !== 3 || strlen($token) > 32768) {
            return null;
        }
        try {
            $unverified = json_decode(JWT::urlsafeB64Decode($parts[1]), true, 16, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }
        if (!is_array($unverified) || !is_string($unverified['iss'] ?? null)
            || !str_starts_with($unverified['iss'], 'https://login.microsoftonline.com/')) {
            return null;
        }

        $tenantId = $unverified['tid'] ?? null;
        $audience = $unverified['aud'] ?? null;
        if (!is_string($tenantId) || !is_string($audience)
            || preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $tenantId) !== 1
            || preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $audience) !== 1) {
            return $this->deny($response);
        }

        try {
            $identity = $this->createIdentity($token, $unverified, $response);
        } catch (EntraException $exception) {
            return $this->deny($response, $exception->status === 503 ? 503 : 401);
        }
        if ($identity instanceof Response) {
            return $identity;
        }

        // A bearer token must never populate the browser session.
        $event->getAuthenticationService()->setStorage(new NonPersistent());
        $mvc->setParam(AuthenticatedIdentity::class, $identity);
        $mvc->setParam(static::class, true);
        return $identity;
    }

    protected function deny(Response $response, int $status = 401): Response
    {
        $response->setStatusCode($status);
        $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
        $response->getHeaders()->addHeaderLine('Cache-Control', 'no-store');
        $response->getHeaders()->addHeaderLine('WWW-Authenticate', 'Bearer error="' . ($status === 403 ? 'insufficient_scope' : 'invalid_token') . '"');
        return $response->setContent(json_encode([
            'error'             => $status === 403 ? 'insufficient_scope' : 'invalid_token',
            'error_description' => $status === 503 ? 'Entra signing keys are unavailable' : 'Entra token is not authorized for this API',
        ], JSON_THROW_ON_ERROR));
    }
}
