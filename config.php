<?php

return [
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
