<?php

declare(strict_types=1);

namespace Jield\ApiTools\ContentNegotiation\ControllerPlugin;

use Jield\ApiTools\ContentNegotiation\ParameterDataContainer;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Mvc\Exception\RuntimeException;
use Laminas\Mvc\InjectApplicationEventInterface;

class RouteParam extends AbstractPlugin
{
    public function __invoke(?string $param = null, mixed $default = null): mixed
    {
        $controller = $this->getController();

        if (!$controller instanceof InjectApplicationEventInterface) {
            throw new RuntimeException(
                message: 'Controllers must implement Laminas\Mvc\InjectApplicationEventInterface to use this plugin.'
            );
        }

        if ($controller instanceof AbstractController) {
            $parameterData = $controller->getEvent()->getParam(name: 'LaminasContentNegotiationParameterData');
            if ($parameterData instanceof ParameterDataContainer) {
                return $parameterData->getRouteParam(name: $param, default: $default);
            }
        }

        return $controller->getEvent()->getRouteMatch()->getParam(name: $param, default: $default);
    }
}
