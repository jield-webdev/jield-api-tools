<?php

declare(strict_types=1);

namespace Jield\ApiTools\Hal\Extractor;

use Jield\ApiTools\Hal\Link\Link;

interface LinkExtractorInterface
{
    /**
     * Extract a structured link array from a Link instance.
     *
     */
    public function extract(Link $link): array;
}
