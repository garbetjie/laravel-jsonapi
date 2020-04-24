<?php

namespace Garbetjie\Laravel\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class JsonApiResourceCollection extends ResourceCollection
{
    use IncludesRelations;

    /**
     * ResourceCollection constructor.
     * @param mixed $resource
     * @param string $collects
     */
    public function __construct($resource, $collects)
    {
        $this->collects = $collects;

        parent::__construct($resource);
    }

    /**
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        return $this->with($request) + [
            'data' => $this->collection->map->toArray($request)->all(),
            'included' => $this->buildJsonApiIncludes($this->resource, $request)
        ];
    }
}
