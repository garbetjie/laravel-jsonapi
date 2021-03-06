<?php

namespace Garbetjie\Laravel\JsonApi;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * An interface defining extractors that are used to extract objects to be included in the `included` resources property.
 *
 */
interface IncludeExtractorInterface
{
    /**
     * @param Model|Collection|Paginator|mixed $resource
     *
     * @return Collection|array
     */
    public function __invoke($resource);
}
