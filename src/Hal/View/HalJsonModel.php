<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal\View;

use Jield\ApiTools\Hal\Collection;
use Jield\ApiTools\Hal\Entity;
use Laminas\View\Model\JsonModel;

use function sprintf;
use function trigger_error;

use const E_USER_DEPRECATED;

/**
 * Simple extension to facilitate the specialized JsonStrategy and JsonRenderer
 * in this Module.
 */
class HalJsonModel extends JsonModel
{
    /** @var bool */
    protected $terminate = true;

    /**
     * Does the payload represent a HAL collection?
     *
     * @return bool
     */
    public function isCollection()
    {
        /** @var mixed $payload */
        $payload = $this->getPayload();
        return $payload instanceof Collection;
    }

    /**
     * Does the payload represent a HAL item?
     *
     * Deprecated; please use isEntity().
     *
     * @deprecated
     *
     * @return bool
     */
    public function isResource()
    {
        trigger_error(sprintf('%s is deprecated; please use %s::isEntity', __METHOD__, self::class), E_USER_DEPRECATED);
        return self::isEntity();
    }

    /**
     * Does the payload represent a HAL entity?
     *
     * @return bool
     */
    public function isEntity()
    {
        /** @var mixed $payload */
        $payload = $this->getPayload();
        return $payload instanceof Entity;
    }

    /**
     * Set the payload for the response
     *
     * This is the value to represent in the response.
     *
     * @param  mixed $payload
     * @return self
     */
    public function setPayload($payload)
    {
        $this->setVariable('payload', $payload);
        return $this;
    }

    /**
     * Retrieve the payload for the response
     *
     * @return mixed
     */
    public function getPayload()
    {
        return $this->getVariable('payload');
    }

    /**
     * Override setTerminal()
     *
     * Does nothing; does not allow re-setting "terminate" terminate.
     *
     * @param  bool $terminate
     * @return self
     */
    public function setTerminal($terminate = true)
    {
        return $this;
    }
}
