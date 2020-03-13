<?php

namespace Garbetjie\JsonApiResources;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use InvalidArgumentException;
use stdClass;
use Throwable;
use function call_user_func;
use function collect;
use function count;
use function get_class;
use function is_array;
use function is_callable;
use function method_exists;

class JsonApiResource extends JsonResource
{
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
     * Converts the resource to an array that can be sent to the browser.
     *
     * @param Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        // Ensure we have an instance of the ResourceableInterface, so that we can create the resource.
        if ($this->resource instanceof ResourceableInterface) {
            $resource = $this->resource;
        } elseif ($this->resource instanceof ConvertibleInterface) {
            $resource = $this->resource->convertToJsonApiResource();
        } else {
            throw new InvalidArgumentException("Provided resource must be one of " . ResourceableInterface::class . ' or ' . ConvertibleInterface::class);
        }

        $type = $resource->getJsonApiType();
        $id = $resource->getJsonApiId();

        $attributes = $this->removeMissingValues($resource->getJsonApiAttributes($request));
        $links = $this->removeMissingValues($resource->getJsonApiLinks($request));
        $meta = $this->removeMissingValues($resource->getJsonApiMeta($request));
        $relationships = $this->removeMissingValues($resource->getJsonApiRelationships($request));

        // Convert any empty arrays to objects.
        foreach (['meta', 'relationships', 'links', 'attributes'] as $var) {
            if (is_array(${$var}) && count(${$var}) < 1) {
                ${$var} = new stdClass();
            }
        }

        return $this->removeMissingValues(
            compact('type', 'id', 'attributes', 'links', 'meta', 'relationships')
        );
    }

    /**
     * Adds in the `included` property.
     *
     * @param Request $request
     * @return array
     */
    public function with($request)
    {
        $included = $this->removeMissingValues(['included' => $this->buildIncludes($request)]);

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
     * @return array|MissingValue
     */
    protected function buildIncludes($request)
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
