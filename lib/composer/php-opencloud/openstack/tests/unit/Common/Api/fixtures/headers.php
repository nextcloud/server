<?php

return [
    'method' => 'POST',
    'path' => 'something',
    'params' => [
        'name' => [
            'type' => 'string',
            'location' => 'header',
            'sentAs' => 'X-Foo-Name'
        ],
        'age'  => [
            'type' => 'integer',
            'location' => 'header'
        ],
        'metadata' => [
            'type'     => 'object',
            'location' => 'header',
            'items'    => [
                'prefix' => 'X-Meta-'
            ]
        ],
        'other' => ['type' => 'string'] // should not be a header
    ],
];
