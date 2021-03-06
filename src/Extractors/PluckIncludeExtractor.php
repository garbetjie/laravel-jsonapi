<?php

namespace Garbetjie\Laravel\JsonApi\Extractors;

use Garbetjie\Laravel\JsonApi\IncludeExtractorInterface;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use function collect;
use function explode;
use function Garbetjie\Laravel\JsonApi\to_collection;
use function is_array;

/**
 * Uses Laravel's `pluck` method on collections to extract a nested path of items. All intermediate objects along the
 * pluck path will be included too.
 *
 * Either an array of pluck paths, or a single string consisting of a pluck path can be given.
 *
 */
class PluckIncludeExtractor implements IncludeExtractorInterface
{
    /**
     * @var array
     */
    private $paths = [];

    /**
     * @param string|array $pluckPaths
     */
    public function __construct($pluckPaths)
    {
        if (!is_array($pluckPaths)) {
            $pluckPaths = [$pluckPaths];
        }

        $this->paths = $pluckPaths;
    }

    /**
     * @param Paginator|Model|Collection|mixed $resource
     * @return Collection
     */
    public function __invoke($resource)
    {
        $all = collect([]);

        foreach ($this->paths as $path) {
            $tmp = to_collection($resource);

            foreach (explode('.', $path) as $property) {
                $tmp = to_collection($tmp->pluck($property))->flatten(1);

                $all = $all->concat($tmp);
            }
        }

        return $all;
    }
}
