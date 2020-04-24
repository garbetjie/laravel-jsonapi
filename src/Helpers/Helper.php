<?php

namespace Garbetjie\Laravel\JsonApi\Helpers;

use Illuminate\Http\Request;
use function collect;

class Helper
{
    /**
     * @param Request|null $request
     * @return \Illuminate\Support\Collection
     */
    public function filters($request = null)
    {
        if ($filter = ($request ?: request())->query('filter', [])) {
            return collect($filter);
        }

        return collect();
    }

    /**
     * @param Request|null $request
     * @return PagingHelper
     */
    public function paging(?Request $request = null)
    {
        return new PagingHelper($request ?: request());
    }
}
