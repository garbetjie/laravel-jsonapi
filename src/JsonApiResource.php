<?php

namespace Garbetjie\Laravel\JsonApi;

use Closure;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Enumerable;
use InvalidArgumentException;
use stdClass;
use function array_keys;
use function call_user_func;
use function collect;
use function count;
use function is_array;
use function range;

class JsonApiResource extends JsonResource
{
    /**
     * @var Closure[]
     */
    protected $collectionExtractors = [];

    /**
     * @var string[]
     */
    protected $defaultIncludes = [];

    /**
     * @var Closure[]
     */
    protected $includeLoaders = [];

    /**
     * @var ExtractorInterface[]
     */
    protected $includeExtractors = [];

    /**
     * @param $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);

        $this->setDefaultCollectionExtractors();
    }

    /**
     * Sets the default builders used to determine whether a resource is a collection or not.
     *
     * @return void
     */
    protected function setDefaultCollectionExtractors(): void
    {
        $this->collectionExtractors[Paginator::class] = function (Paginator $paginator) {
            return $paginator->items();
        };

        $this->collectionExtractors[Enumerable::class] = function (Enumerable $enumerable) {
            return $enumerable->all();
        };

        $this->collectionExtractors['array'] = function (array $array) {
            return $array;
        };
    }

    /**
     * Converts the resource to an array that can be sent to the browser.
     *
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request): array
    {
        // We're building a collection
        if ($builder = $this->getCollectionExtractor()) {
            return collect($this->collectionExtractors[$builder]($this->resource))
                ->map(
                    function ($resource) use ($request) {
                        return (new static($resource))->toArray($request);
                    }
                )
                ->toArray();
        }

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

        // Convert any empty arrays to objects.
        foreach ($additional as $key => $value) {
            if (is_array($value) && count($value) < 1) {
                $additional[$key] = new stdClass();
            }
        }

        return ['type' => $type, 'id' => $id] + $additional;
    }

    /**
     * Adds a custom collection extractor.
     *
     * Collection extractors are used to detect whether a JSON:API resource is a collection of objects. The closure
     * passed in here should handle the conversion of the given resource to an array of items - however that is implemented.
     *
     * @param string $className
     * @param Closure $builder
     *
     * @return static
     */
    public function withCollectionExtractor($className, Closure $builder)
    {
        $this->collectionExtractors[$className] = $builder;

        return $this;
    }

    /**
     * Retrieve the collection extractor required for the current resource. If there is no collection extractor defined,
     * then it returns null.
     *
     * @return string|null
     */
    protected function getCollectionExtractor(): ?string
    {
        // Short-circuit to returning true if it is a numerically-indexed PHP array.
        if (is_array($this->resource) && array_keys($this->resource) === range(0, count($this->resource) - 1)) {
            return 'array';
        }

        foreach (array_keys($this->collectionExtractors) as $className) {
            if ($this->resource instanceof $className) {
                return $className;
            }
        }

        return null;
    }

    /**
     * Return a new resource that is made up of a collection.
     *
     * @param mixed $resource
     * @return static
     */
    public static function collection($resource)
    {
        return new static(to_collection($resource));
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
        $included = $this->removeMissingValues(['included' => $this->buildJsonApiIncludes($request)]);

        return parent::with($request) + $included;
    }

    /**
     * Set the includes that are loaded & used when nothing is specified in the URL.
     *
     * @param array $defaultIncludes
     *
     * @return static
     */
    public function withDefaultIncludes(array $defaultIncludes)
    {
        $this->defaultIncludes = $defaultIncludes;

        return $this;
    }

    /**
     * Add a closure that will be called to load additional information.
     *
     * This will only be called if the include specified in `$include` is provided in the URL, or if there are no includes
     * provided in the URL, but it is part of the default includes.
     *
     * @param string $include
     * @param Closure $loader
     *
     * @return static
     */
    public function withIncludeLoader(string $include, Closure $loader)
    {
        $this->includeLoaders[$include] = $loader;

        return $this;
    }

    /**
     * Add an instance of an extractor that will be called to extract resources to be included in the `include` array.
     *
     * This will only be called if the include specified in `$include` is provided in the URL, or if there are no includes
     * provided in the URL, but it is part of the default includes.
     *
     * @param string $includeName
     * @param ExtractorInterface $extractor
     *
     * @return static
     */
    public function withIncludeExtractor(string $includeName, ExtractorInterface $extractor)
    {
        $this->includeExtractors[$includeName] = $extractor;

        return $this;
    }

    /**
     * Builds up the list of resources to be included in the `includes` array. This will only be present in the response
     * if (1) includes were specified in the query string, or (2) no includes were specified, but default includes were
     * specified.
     *
     * @param Request $request
     *
     * @return array|MissingValue
     */
    protected function buildJsonApiIncludes($request)
    {
        // Get the includes to use.
        $includes = has_includes($request) ? parse_includes($request) : $this->defaultIncludes;

        if (count($includes) < 1) {
            return new MissingValue();
        }

        // Call the loaders.
        foreach ($includes as $include) {
            if (isset($this->includeLoaders[$include])) {
                call_user_func($this->includeLoaders[$include], $this->resource);
            }
        }

        $all = collect([]);

        // Call the extractors.
        foreach ($includes as $include) {
            if (isset($this->includeExtractors[$include])) {
                $items = collect($this->includeExtractors[$include]($this->resource));
                $all = $all->concat($items);
            }
        }

        return $all
            ->filter(
                function ($item) {
                    return $item && ($item instanceof ResourceableInterface || $item instanceof ConvertibleInterface);
                }
            )
            ->map(
                function ($item) {
                    /* @var ResourceableInterface|ConvertibleInterface $item */

                    return $item instanceof ConvertibleInterface
                        ? $item->convertToJsonApiResource()
                        : $item;
                }
            )
            ->unique(
                function (ResourceableInterface $item) {
                    return [$item->getJsonApiType(), $item->getJsonApiId()];
                }
            )
            ->map(
                function (ResourceableInterface $item) {
                    return new static($item);
                }
            )
            ->values()
            ->toArray();
    }
}
