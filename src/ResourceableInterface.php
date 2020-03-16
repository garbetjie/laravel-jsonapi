<?php

namespace Garbetjie\Laravel\JsonApi;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\MissingValue;

interface ResourceableInterface
{
    /**
     * Returns a string representing the resource's type.
     *
     * @return string
     * @see https://jsonapi.org/format/#document-resource-object-identification
     */
    public function getJsonApiType();

    /**
     * Returns the unique ID for the resource.
     *
     * @return mixed
     * @see https://jsonapi.org/format/#document-resource-object-identification
     */
    public function getJsonApiId();

    /**
     * Returns an array of attributes to display, or an instance of `Illuminate\Http\Resources\MissingValue` if the attributes
     * property should not be displayed.
     *
     * @param Request $request
     * @return array|MissingValue
     */
    public function getJsonApiAttributes($request);

    /**
     * Returns an array of links to display, or an instance of `Illuminate\Http\Resources\MissingValue` if the links
     * property should not be displayed.
     *
     * @param Request $request
     * @return array|MissingValue
     */
    public function getJsonApiLinks($request);

    /**
     * Returns an array of meta to display, or an instance of `Illuminate\Http\Resources\MissingValue` if the meta
     * property should not be displayed.
     *
     * @param Request $request
     * @return array|MissingValue
     */
    public function getJsonApiMeta($request);

    /**
     * Returns an array of relationships to display, or an instance of `Illuminate\Http\Resources\MissingValue` if the
     * relationships property should not be displayed.
     *
     * @param Request $request
     * @return array|MissingValue
     */
    public function getJsonApiRelationships($request);
}
