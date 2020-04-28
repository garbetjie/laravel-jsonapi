<?php

namespace Garbetjie\Laravel\JsonApi;

interface ConvertibleToJsonApiResourceInterface
{
    /**
     * @return JsonApiResourceInterface
     */
    public function convertToJsonApiResource(): JsonApiResourceInterface;
}
