<?php

declare(strict_types=1);

namespace Jield\ApiTools\MvcAuth\Authentication;

use Jield\ApiTools\MvcAuth\MvcAuthEvent;
use Laminas\Http\Response as HttpResponse;

class DefaultAuthenticationPostListener
{
    /**
     * Determine if we have an authentication failure, and, if so, return a 401 response
     *
     * @return null|HttpResponse
     */
    public function __invoke(MvcAuthEvent $mvcAuthEvent)
    {
        if (! $mvcAuthEvent->hasAuthenticationResult()) {
            return;
        }

        $authResult = $mvcAuthEvent->getAuthenticationResult();
        if ($authResult->isValid()) {
            return;
        }

        $mvcEvent = $mvcAuthEvent->getMvcEvent();
        $response = $mvcEvent->getResponse();
        if (! $response instanceof HttpResponse) {
            return $response;
        }

        $response->setStatusCode(401);
        $response->setReasonPhrase('Unauthorized');
        return $response;
    }
}
