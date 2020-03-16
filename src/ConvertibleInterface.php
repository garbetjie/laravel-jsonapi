<?php

namespace Garbetjie\Laravel\JsonApi;

interface ConvertibleInterface
{
    /**
     * @return ResourceableInterface
     */
    public function convertToJsonApiResource(): ResourceableInterface;
}
