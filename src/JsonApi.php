<?php

namespace Garbetjie\Laravel\JsonApi;

use Garbetjie\Laravel\JsonApi\Helpers\Helper;
use Garbetjie\Laravel\JsonApi\Helpers\PagingHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection filters(Request $request = null)
 * @method static PagingHelper paging(Request $request = null)
 */
class JsonApi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Helpers\Helper::class;
    }
}
