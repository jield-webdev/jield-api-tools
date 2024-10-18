<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal\Link;

trait LinkCollectionAwareTrait
{
    /** @var LinkCollection */
    protected $links;

    public function setLinks(LinkCollection $links): static
    {
        $this->links = $links;
        return $this;
    }

    /**
     * @return LinkCollection
     */
    public function getLinks(): LinkCollection
    {
        if (! $this->links instanceof LinkCollection) {
            $this->setLinks(links: new LinkCollection());
        }

        return $this->links;
    }
}
