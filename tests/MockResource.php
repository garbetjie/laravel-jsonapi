<?php

namespace Garbetjie\Laravel\JsonApi\Tests;

use Garbetjie\Laravel\JsonApi\ResourceableInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\MissingValue;

class MockResource implements ResourceableInterface
{
    private $type;
    private $id;
    private $attributes;
    private $links;
    private $meta;
    private $relationships;

    public $related;

    public function __construct($type, $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    private function with($property, $value)
    {
        $this->{$property} = $value;

        return $this;
    }

    public function attributes(array $attributes)
    {
        return $this->with('attributes', $attributes);
    }

    public function links(array $links)
    {
        return $this->with('links', $links);
    }

    public function relationships(array $relationships)
    {
        return $this->with('relationships', $relationships);
    }

    public function meta(array $meta)
    {
        return $this->with('meta', $meta);
    }

    public function getJsonApiType()
    {
        return $this->type;
    }

    public function getJsonApiId()
    {
        return $this->id;
    }

    public function getJsonApiAttributes($request)
    {
        return $this->attributes !== null ? $this->attributes : new MissingValue();
    }

    public function getJsonApiLinks($request)
    {
        return $this->links !== null ? $this->links : new MissingValue();
    }

    public function getJsonApiMeta($request)
    {
        return $this->meta !== null ? $this->meta : new MissingValue();
    }

    public function getJsonApiRelationships($request)
    {
        return $this->relationships !== null ? $this->relationships : new MissingValue();
    }
}
