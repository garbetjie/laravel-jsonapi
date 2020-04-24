<?php

namespace Garbetjie\Laravel\JsonApi\Helpers;

use Illuminate\Http\Request;
use function func_num_args;
use function request;

class PagingHelper
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param string|null $key
     * @param int|null $default
     * @return int
     */
    public function limit(string $key = null, int $default = null)
    {
        $params = $this->request->query('page', []);
        $key = $key ?: config('jsonapi.defaults.limit.key');
        $defaultValue = func_num_args() > 1 ? $default : config('json.api.defaults.limit.value');

        return (int)($params[$key] ?? $defaultValue);
    }

    public function page(string $key = null, int $default = 1)
    {
        $params = $this->request->query('page', []);
        $key = $key ?: config('jsonapi.defaults.page.key');
        $defaultValue = func_num_args() > 1 ? $default : config('jsonapi.defaults.page.value');

        return $params[$key] ?? $defaultValue;
    }

    public function cursor(string $key = null)
    {
        $params = $this->request->query('page', []);

        return $params[$key ?: config('jsonapi.defaults.cursor.key')] ?? null;
    }
}
