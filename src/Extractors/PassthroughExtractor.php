<?php

namespace Garbetjie\JsonApiResources\Extractors;

use Garbetjie\JsonApiResources\ExtractorInterface;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use function collect;

/**
 * Adds the given resource to the list of included objects.
 *
 * Used when you already have an instance or a collection of an item that needs to be added to the list of included items.
 */
class PassthroughExtractor implements ExtractorInterface
{
    /**
     * @var Collection|mixed
     */
    private $resource;

    /**
     * @param mixed|Collection $resource
     */
    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * @param mixed $resource
     *
     * @return Collection
     */
    public function __invoke($resource)
    {
        return collect($this->resource instanceof Collection ? $this->resource : [$this->resource]);
    }
}
