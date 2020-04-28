<?php

namespace Garbetjie\Laravel\JsonApi;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\MissingValue;
use function call_user_func;
use function collect;
use function count;

trait IncludesRelations
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
     * @var IncludeExtractorInterface[]
     */
    protected $includeExtractors = [];

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
     * @param IncludeExtractorInterface $extractor
     *
     * @return static
     */
    public function withIncludeExtractor(string $includeName, IncludeExtractorInterface $extractor)
    {
        $this->includeExtractors[$includeName] = $extractor;

        return $this;
    }

    /**
     * Builds up the list of resources to be included in the `includes` array. This will only be present in the response
     * if (1) includes were specified in the query string, or (2) no includes were specified, but default includes were
     * specified.
     *
     * @param mixed $resource
     * @param Request $request
     *
     * @return array|MissingValue
     */
    protected function buildJsonApiIncludes($resource, $request)
    {
        // Get the includes to use.
        $includes = has_includes($request) ? parse_includes($request) : $this->defaultIncludes;

        if (count($includes) < 1) {
            return new MissingValue();
        }

        // Call the loaders.
        foreach ($includes as $include) {
            if (isset($this->includeLoaders[$include])) {
                call_user_func($this->includeLoaders[$include], $resource);
            }
        }

        $all = collect([]);

        // Call the extractors.
        foreach ($includes as $include) {
            if (isset($this->includeExtractors[$include])) {
                $items = collect($this->includeExtractors[$include]($resource));
                $all = $all->concat($items);
            }
        }

        return $all
            ->filter(
                function ($item) {
                    return $item && ($item instanceof JsonApiResourceInterface || $item instanceof ConvertibleToJsonApiResourceInterface);
                }
            )
            ->map(
                function ($item) {
                    /* @var JsonApiResourceInterface|ConvertibleToJsonApiResourceInterface $item */

                    return $item instanceof ConvertibleToJsonApiResourceInterface
                        ? $item->convertToJsonApiResource()
                        : $item;
                }
            )
            ->unique(
                function (JsonApiResourceInterface $item) {
                    return [$item->getJsonApiType(), $item->getJsonApiId()];
                }
            )
            ->map(
                function (JsonApiResourceInterface $item) {
                    return new JsonApiResource($item);
                }
            )
            ->values()
            ->toArray();
    }
}
