<?php

declare(strict_types=1);

namespace Jield\ApiTools\ContentValidation\InputFilter;

use Laminas\InputFilter\InputFilterInterface;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\Mvc\InjectApplicationEventInterface;

class InputFilterPlugin extends AbstractPlugin
{
    /** @return null|InputFilterInterface */
    public function __invoke(): ?InputFilterInterface
    {
        $controller = $this->getController();
        if (!$controller instanceof InjectApplicationEventInterface) {
            return null;
        }

        $event       = $controller->getEvent();
        $inputFilter = $event->getParam(name: __NAMESPACE__);

        if (!$inputFilter instanceof InputFilterInterface) {
            return null;
        }

        return $inputFilter;
    }
}
