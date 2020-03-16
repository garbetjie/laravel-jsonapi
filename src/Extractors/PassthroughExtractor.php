<?php

namespace Garbetjie\Laravel\JsonApi\Extractors;

use Garbetjie\Laravel\JsonApi\ExtractorInterface;
use Illuminate\Support\Collection;
use function Garbetjie\Laravel\JsonApi\to_collection;

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
        return to_collection($this->resource);
    }
}
