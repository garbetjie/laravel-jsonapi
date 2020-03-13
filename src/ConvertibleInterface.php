<?php

namespace Garbetjie\JsonApiResources;

interface ConvertibleInterface
{
    /**
     * @return ResourceableInterface
     */
    public function convertToJsonApiResource(): ResourceableInterface;
}
