<?php

declare(strict_types=1);

namespace Jield\ApiTools\Paginator;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Jield\ApiTools\Provider\ProviderInterface;
use Laminas\Cache\Storage\Adapter\AbstractAdapter;
use Laminas\Paginator\Adapter\AdapterInterface;
use Override;

class DoctrineCachedORMAdapter extends Paginator implements AdapterInterface
{
    public function __construct(
        QueryBuilder              $query,
        private ProviderInterface $provider,
        private AbstractAdapter   $cache,
        private string            $cacheKey
    )
    {
        parent::__construct($query, true);
    }


    /**
     * @param int $offset
     * @param int $itemCountPerPage
     */
    #[Override]
    public function getItems($offset, $itemCountPerPage): array
    {
        //Append the offset and itemCountPerPage to the cache key
        $cacheKey = $this->cacheKey . $offset . $itemCountPerPage;

        if (!$this->cache->hasItem($cacheKey)) {
            $this->getQuery()->setFirstResult($offset);
            $this->getQuery()->setMaxResults($itemCountPerPage);

            $results = [];
            foreach ($this->getQuery()->getResult() as $result) {
                $results[] = $this->provider->generateArray($result);
            }

            $this->cache->setItem($cacheKey, $results);
        }

        return $this->cache->getItem($cacheKey);
    }
}