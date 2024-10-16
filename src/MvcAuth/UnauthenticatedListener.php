<?php

declare(strict_types=1);

namespace Jield\ApiTools\MvcAuth;

use Jield\ApiTools\ApiProblem\ApiProblem;
use Jield\ApiTools\ApiProblem\ApiProblemResponse;

class UnauthenticatedListener
{
    /**
     * Determine if we have an authentication failure, and, if so, return a 401 response
     *
     * @return null|ApiProblemResponse
     */
    public function __invoke(MvcAuthEvent $mvcAuthEvent): ?ApiProblemResponse
    {
        if (!$mvcAuthEvent->hasAuthenticationResult()) {
            return null;
        }

        $authResult = $mvcAuthEvent->getAuthenticationResult();
        if ($authResult->isValid()) {
            return null;
        }

        $mvcEvent = $mvcAuthEvent->getMvcEvent();
        $response = new ApiProblemResponse(new ApiProblem(401, 'Unauthorized'));
        $mvcEvent->setResponse($response);
        return $response;
    }
}
