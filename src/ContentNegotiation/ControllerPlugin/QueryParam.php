<?php

declare(strict_types=1);

namespace Jield\ApiTools\ContentNegotiation\ControllerPlugin;

use Jield\ApiTools\ContentNegotiation\ParameterDataContainer;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class QueryParam extends AbstractPlugin
{
    /**
     * Grabs a param from route match by default.
     *
     * @param string|null $param
     * @param mixed|null $default
     * @return mixed
     */
    public function __invoke(string $param = null, mixed $default = null): mixed
    {
        $controller = $this->getController();
        if ($controller instanceof AbstractController) {
            $parameterData = $controller->getEvent()->getParam(name: 'LaminasContentNegotiationParameterData');
            if ($parameterData instanceof ParameterDataContainer) {
                return $parameterData->getQueryParam(name: $param, default: $default);
            }
        }

        return $this->getController()->getRequest()->getQuery($param, $default);
    }
}
