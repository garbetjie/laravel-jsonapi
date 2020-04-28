<?php

namespace Garbetjie\Laravel\JsonApi\Extractors;

use Garbetjie\Laravel\JsonApi\IncludeExtractorInterface;
use Illuminate\Support\Collection;
use function collect;
use function Garbetjie\Laravel\JsonApi\to_collection;
use function get_class;

/**
 * An extractor that combines other extractors and merges the results of all of them together.
 *
 * Used when you need to combine the output of other extractors (such as the PluckExtractor and the ClosureExtractor)
 * into the same inclusion list.
 *
 */
class CombinationIncludeExtractor implements IncludeExtractorInterface
{
    /**
     * @var IncludeExtractorInterface[]
     */
    private $extractors = [];

    /**
     * @param IncludeExtractorInterface[] $extractors
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
            $all = $all->concat(to_collection($extractor->__invoke($resource)));
        }

        return $all;
    }

}
