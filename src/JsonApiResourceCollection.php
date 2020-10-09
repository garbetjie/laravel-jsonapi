<?php

namespace Garbetjie\Laravel\JsonApi;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Support\Collection;
use function config;
use function is_array;
use function is_object;

class JsonApiResourceCollection extends ResourceCollection
{
    use IncludesRelations;

    /**
     * @var Collection
     */
    protected $resourceAsCollection;

    /**
     * @param mixed $resource
     * @param string $collects
     */
    public function __construct($resource, string $collects)
    {
        $this->collects = $collects;
        $this->resourceAsCollection = to_collection($resource);

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
            'included' => $this->buildJsonApiIncludes($this->resourceAsCollection, $request)
        ];
    }

    public function withResponse($request, $response)
    {
        parent::withResponse($request, $response);

        // If we're not stripping empty links, don't do anything.
        if (!config('garbetjie-jsonapi.strip_empty_links', true)) {
            return;
        }

        // Get the response data as an object.
        $data = $response->getData(false);

        // Filter any links with null values. These don't conform to the JSON:API spec.
        if (isset($data->links) && is_object($data->links)) {
            $data->links = (object)array_filter(
                (array)$data->links,
                function ($href) {
                    return $href !== null;
                }
            );

            $response->setData($data);
        }
    }

    /**
     * @inheritdoc
     */
    protected function collectResource($resource)
    {
        return parent::collectResource(
            $resource instanceof Paginator
                ? $resource
                : to_collection($resource)
        );
    }
}
