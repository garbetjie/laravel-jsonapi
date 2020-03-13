<?php

namespace Garbetjie\JsonApiResources;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function collect;
use function explode;
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
    if ($item instanceof Collection) {
        return $item;
    } elseif ($item instanceof Paginator) {
        return collect($item->items());
    } else {
        return collect($item);
    }
}