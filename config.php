<?php

return [
    // When true, any pagination links that are `null` will be removed. If this is not set to true, and the links are not
    // removed, the response will not conform to the JSON:API spec.
    'strip_empty_links' => true,

    'paging' => [
        'limit' => [
            'key' => 'limit',
            'default' => 50,
            'max' => 50,
            'min' => 1,
        ],

        'strategies' => [
            'page' => [
                'key' => 'index',
                'default' => 1,
            ],

            'cursor' => [
                'key' => 'cursor',
                'default' => null,
            ],
        ],
    ],

    'include_mode' => 'filter', // strict
];
