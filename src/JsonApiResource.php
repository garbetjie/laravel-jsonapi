<?php

namespace Garbetjie\Laravel\JsonApi;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use InvalidArgumentException;
use stdClass;
use function call_user_func;
use function collect;
use function count;
use function is_array;

class JsonApiResource extends JsonResource
{
    use Includeable;

    /**
     * Converts the resource to an array that can be sent to the browser.
     *
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request): array
    {
        if ($this instanceof ResourceableInterface) {
            $resource = $this;
        } elseif ($this instanceof ConvertibleInterface) {
            $resource = $this->convertToJsonApiResource();
        } elseif ($this->resource instanceof ResourceableInterface) {
            $resource = $this->resource;
        } elseif ($this->resource instanceof ConvertibleInterface) {
            $resource = $this->resource->convertToJsonApiResource();
        } else {
            throw new InvalidArgumentException("Provided resource must be one of " . ResourceableInterface::class . ' or ' . ConvertibleInterface::class);
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
            $value = $this->removeMissingValues($value);
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
