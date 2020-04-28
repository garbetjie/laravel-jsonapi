<?php

namespace Garbetjie\Laravel\JsonApi\Helpers;

use Illuminate\Http\Request;
use function config;
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
        $key = $key ?: config('garbetjie-jsonapi.paging.limit.key');
        $defaultValue = func_num_args() > 1 ? $default : config('garbetjie-jsonapi.paging.limit.default');
        $maxValue = config('garbetjie-jsonapi.paging.limit.max');
        $minValue = config('garbetjie-jsonapi.paging.limit.min');
        $value = (int)($params[$key] ?? $defaultValue);

        if ($value > $maxValue) {
            return $maxValue;
        } elseif ($value < $minValue) {
            return $minValue;
        } else {
            return $value;
        }
    }

    /**
     * @param string|null $key
     * @param int $default
     * @return int
     */
    public function page(string $key = null, int $default = 1)
    {
        $params = $this->request->query('page', []);
        $key = $key ?: config('garbetjie-jsonapi.paging.strategies.page.key');
        $defaultValue = func_num_args() > 1 ? $default : config('garbetjie-jsonapi.paging.strategies.page.default');

        return $params[$key] ?? $defaultValue;
    }

    /**
     * @param string|null $key
     * @return string|null
     */
    public function cursor(string $key = null)
    {
        $params = $this->request->query('page', []);
        $defaultValue = config('garbetjie-jsonapi.paging.strategies.cursor.default');

        return $params[$key ?: config('garbetjie-jsonapi.paging.strategies.cursor.key')] ?? $defaultValue;
    }
}
