<?php

namespace Garbetjie\Laravel\JsonApi\Extractors;

use Closure;
use Garbetjie\Laravel\JsonApi\IncludeExtractorInterface;
use Illuminate\Support\Collection;
use function Garbetjie\Laravel\JsonApi\to_collection;

/**
 * Adds the collection returned by the provided closure to the list of resources to be included in the response.
 *
 * The closure will be passed the instance of the object being converted to a resource, and should return a collection
 * of items to be included.
 *
 */
class ClosureIncludeExtractor implements IncludeExtractorInterface
{
    /**
     * @var Closure
     */
    private $callback;

    /**
     * @param Closure $callback
     */
    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param \Illuminate\Contracts\Pagination\Paginator|\Illuminate\Database\Eloquent\Model|Collection|mixed $resource
     * @return Collection
     */
    public function __invoke($resource)
    {
        return to_collection(($this->callback)($resource));
    }

}
