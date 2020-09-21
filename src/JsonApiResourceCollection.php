<?php

namespace Garbetjie\Laravel\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use function is_array;

class JsonApiResourceCollection extends ResourceCollection
{
    use IncludesRelations;

    /**
     * @var Collection
     */
    protected $resourceCollection;

    /**
     * @param mixed $resource
     * @param string $collects
     */
    public function __construct($resource, string $collects)
    {
        $this->collects = $collects;
        $this->resourceCollection = to_collection($resource);

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
            'included' => $this->buildJsonApiIncludes($this->resourceCollection, $request)
        ];
    }

    /**
     * @inheritdoc
     */
    protected function collectResource($resource)
    {
        return parent::collectResource(to_collection($resource));
    }
}
