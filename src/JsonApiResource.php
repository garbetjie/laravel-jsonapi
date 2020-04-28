<?php

namespace Garbetjie\Laravel\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use InvalidArgumentException;
use stdClass;
use function count;
use function is_array;
use function is_iterable;

class JsonApiResource extends JsonResource
{
    use IncludesRelations;

    /**
     * Converts the resource to an array that can be sent to the browser.
     *
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request): array
    {
        if ($this instanceof JsonApiResourceInterface) {
            $resource = $this;
        } elseif ($this instanceof ConvertibleToJsonApiResourceInterface) {
            $resource = $this->convertToJsonApiResource();
        } elseif ($this->resource instanceof JsonApiResourceInterface) {
            $resource = $this->resource;
        } elseif ($this->resource instanceof ConvertibleToJsonApiResourceInterface) {
            $resource = $this->resource->convertToJsonApiResource();
        } else {
            throw new InvalidArgumentException("Provided resource must be one of " . JsonApiResourceInterface::class . ' or ' . ConvertibleToJsonApiResourceInterface::class);
        }

        $type = $resource->getJsonApiType();
        $id = $resource->getJsonApiId();

        // Build up additional properties, and remove any of those that should be removed.
        $additional = $this->removeMissingValues([
            'attributes' => $resource->getJsonApiAttributes($request),
            'links' => $resource->getJsonApiLinks($request),
            'meta' => $resource->getJsonApiMeta($request),
            'relationships' => $resource->getJsonApiRelationships($request)
        ]);

        // Run through all the additional attributes, and remove any missing values from each. If we're left with an
        // empty array, then convert it to an object.
        foreach ($additional as $key => $value) {
            if (is_iterable($value)) {
                $value = $this->removeMissingValues($value);
            }

            $additional[$key] = $value;

            if (is_array($value) && count($value) < 1) {
                $additional[$key] = new stdClass();
            }
        }

        return ['type' => $type, 'id' => $id] + $additional;
    }

    /**
     * Adds in the `included` property.
     *
     * @param Request $request
     *
     * @return array
     */
    public function with($request): array
    {
        $included = $this->removeMissingValues(['included' => $this->buildJsonApiIncludes($this->resource, $request)]);

        return parent::with($request) + $included;
    }

    /**
     * @param mixed $resource
     * @return JsonApiResourceCollection
     */
    public static function collection($resource)
    {
        return new JsonApiResourceCollection($resource, static::class);
    }
}
