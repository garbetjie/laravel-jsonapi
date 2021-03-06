<?php

namespace Garbetjie\Laravel\JsonApi;

use Garbetjie\Laravel\JsonApi\Helpers\Helper;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Enumerable;
use function app;
use function collect;
use function explode;
use function is_array;
use function request;

/**
 * @param Request $request
 * @return array
 */
function parse_includes($request) {
    $request = $request ?: request();

    return collect(explode(',', $request->query('include', '')))
        ->transform('trim')
        ->filter()
        ->unique()
        ->values()
        ->toArray();
}

/**
 * @param Request $request
 * @return bool
 */
function has_includes($request) {
    return ($request ?: request())->query('include', null) !== null;
}

/**
 * Convert the given item to a collection.
 *
 * @param Collection|Paginator|Model|mixed $item
 * @return Collection
 */
function to_collection($item) {
    if ($item instanceof Collection || $item instanceof Enumerable) {
        return collect($item);
    } elseif ($item instanceof Paginator) {
        return collect($item->items());
    } elseif (is_array($item)) {
        return collect($item);
    } else {
        return collect([$item]);
    }
}

/**
 * @return Helper
 */
function jsonapi() {
    return app(Helper::class);
}
