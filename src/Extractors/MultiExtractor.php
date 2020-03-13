<?php

namespace Garbetjie\JsonApiResources\Extractors;

use Garbetjie\JsonApiResources\ExtractorInterface;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use function collect;

/**
 * An extractor that consists of other extractors.
 *
 * Used when you need to combine the output of other extractors (such as the PluckExtractor and the ClosureExtractor)
 * into the same inclusion list.
 *
 */
class MultiExtractor implements ExtractorInterface
{
    /**
     * @var ExtractorInterface[]
     */
    private $extractors = [];

    /**
     * @param ExtractorInterface[] $extractors
     */
    public function __construct(array $extractors)
    {
        $this->extractors = $extractors;
    }

    /**
     * @param mixed $resource
     * @return Collection
     */
    public function __invoke($resource)
    {
        $all = collect();

        foreach ($this->extractors as $extractor) {
            $all = $all->concat($extractor->__invoke($resource));
        }

        return $all;
    }

}
