<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal\Link;

use Countable;
use Jield\ApiTools\ApiProblem\ApiProblem;
use Jield\ApiTools\Hal\Collection;
use Laminas\Paginator\Paginator;
use Laminas\Stdlib\ArrayUtils;
use Traversable;

use function count;
use function is_array;

class PaginationInjector implements PaginationInjectorInterface
{
    /**
     * @inheritDoc
     */
    public function injectPaginationLinks(Collection $halCollection)
    {
        $collection = $halCollection->getCollection();
        if (! $collection instanceof Paginator) {
            return false;
        }

        $this->configureCollection($halCollection);

        $pageCount = count($collection);
        if ($pageCount === 0) {
            return true;
        }

        $page = $halCollection->getPage();

        if ($page < 1 || $page > $pageCount) {
            return new ApiProblem(409, 'Invalid page provided');
        }

        $this->injectLinks($halCollection);

        return true;
    }

    private function configureCollection(Collection $halCollection): void
    {
        /** @var Paginator $collection */
        $collection = $halCollection->getCollection();
        $page       = $halCollection->getPage();
        $pageSize   = $halCollection->getPageSize();

        $collection->setItemCountPerPage($pageSize);
        $collection->setCurrentPageNumber($page);
    }

    private function injectLinks(Collection $halCollection): void
    {
        $this->injectSelfLink($halCollection);
        $this->injectFirstLink($halCollection);
        $this->injectLastLink($halCollection);
        $this->injectPrevLink($halCollection);
        $this->injectNextLink($halCollection);
    }

    private function injectSelfLink(Collection $halCollection): void
    {
        $page = $halCollection->getPage();
        $link = $this->createPaginationLink('self', $halCollection, $page);
        $halCollection->getLinks()->add($link, true);
    }

    private function injectFirstLink(Collection $halCollection): void
    {
        $link = $this->createPaginationLink('first', $halCollection);
        $halCollection->getLinks()->add($link);
    }

    private function injectLastLink(Collection $halCollection): void
    {
        $page = $this->countCollection($halCollection->getCollection());
        $link = $this->createPaginationLink('last', $halCollection, $page);
        $halCollection->getLinks()->add($link);
    }

    private function injectPrevLink(Collection $halCollection): void
    {
        $page = $halCollection->getPage();
        $prev = $page > 1 ? $page - 1 : false;

        if ($prev) {
            $link = $this->createPaginationLink('prev', $halCollection, $prev);
            $halCollection->getLinks()->add($link);
        }
    }

    private function injectNextLink(Collection $halCollection): void
    {
        $page      = $halCollection->getPage();
        $pageCount = $this->countCollection($halCollection->getCollection());
        $next      = $page < $pageCount ? $page + 1 : false;

        if ($next) {
            $link = $this->createPaginationLink('next', $halCollection, $next);
            $halCollection->getLinks()->add($link);
        }
    }

    /**
     * @param string $relation
     * @param int $page
     * @return Link
     */
    private function createPaginationLink($relation, Collection $halCollection, $page = null)
    {
        $options = ArrayUtils::merge(
            $halCollection->getCollectionRouteOptions(),
            ['query' => ['page' => $page]]
        );

        return Link::factory([
            'rel'   => $relation,
            'route' => [
                'name'    => $halCollection->getCollectionRoute(),
                'params'  => $halCollection->getCollectionRouteParams(),
                'options' => $options,
            ],
        ]);
    }

    /** @param array<mixed>|Traversable|Paginator $collection */
    private function countCollection(mixed $collection): int
    {
        return match (true) {
            $collection instanceof Countable => $collection->count(),
            is_array($collection) => count($collection),
            default => 1,
        };
    }
}
