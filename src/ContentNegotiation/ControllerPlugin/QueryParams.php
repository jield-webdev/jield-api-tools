<?php

declare(strict_types=1);

namespace Jield\ApiTools\ContentNegotiation\ControllerPlugin;

use Jield\ApiTools\ContentNegotiation\ParameterDataContainer;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class QueryParams extends AbstractPlugin
{
    public function __invoke(): array
    {
        $controller = $this->getController();
        if ($controller instanceof AbstractController) {
            $parameterData = $controller->getEvent()->getParam(name: 'LaminasContentNegotiationParameterData');
            if ($parameterData instanceof ParameterDataContainer) {
                return $parameterData->getQueryParams();
            }
        }

        if (!method_exists(object_or_class: $controller, method: 'getRequest')) {
            return [];
        }

        $request = $controller->getRequest();
        if (!method_exists(object_or_class: $request, method: 'getQuery')) {
            return [];
        }

        return $request->getQuery()->toArray();
    }
}
