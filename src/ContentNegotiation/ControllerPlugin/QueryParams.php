<?php

declare(strict_types=1);

namespace Jield\ApiTools\ContentNegotiation\ControllerPlugin;

use Jield\ApiTools\ContentNegotiation\ParameterDataContainer;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class QueryParams extends AbstractPlugin
{
    /**
     * @return array
     * @throws RuntimeException If controller does not implement InjectApplicationEventInterface.
     */
    public function __invoke(): array
    {
        $controller = $this->getController();
        if ($controller instanceof AbstractController) {
            $parameterData = $controller->getEvent()->getParam(name: 'LaminasContentNegotiationParameterData');
            if ($parameterData instanceof ParameterDataContainer) {
                return $parameterData->getQueryParams();
            }
        }

        return $this->getController()->getRequest()->getQuery()->toArray();
    }
}
