<?php

declare(strict_types=1);

namespace Jield\ApiTools\Paginator;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Jield\ApiTools\Provider\ProviderInterface;
use Laminas\Paginator\Adapter\AdapterInterface;
use Override;
use function array_key_exists;

class DoctrineORMAdapter extends Paginator implements AdapterInterface
{
    public array $cache = [];

    private ProviderInterface $provider;

    public function setProvider(ProviderInterface $provider): DoctrineORMAdapter
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * @param int $offset
     * @param int $itemCountPerPage
     */
    #[Override]
    public function getItems($offset, $itemCountPerPage): array
    {
        if (
            array_key_exists($offset, $this->cache)
            && array_key_exists($itemCountPerPage, $this->cache[$offset])
        ) {
            return $this->cache[$offset][$itemCountPerPage];
        }

        $this->getQuery()->setFirstResult($offset);
        $this->getQuery()->setMaxResults($itemCountPerPage);

        if (!array_key_exists($offset, $this->cache)) {
            $this->cache[$offset] = [];
        }

        $results = [];
        foreach ($this->getQuery()->getResult() as $result) {
            $results[] = $this->provider->generateArray($result);
        }

        $this->cache[$offset][$itemCountPerPage] = $results;

        return $this->cache[$offset][$itemCountPerPage];
    }
}