<?php

return [
    'method' => 'POST',
    'path' => 'something',
    'params' => [
        'name' => [
            'type' => 'string',
            'sentAs' => 'server_name',
        ],
        'other' => [
            'type' => 'array',
            'sentAs' => 'other_params',
            'items' => [
                'type' => 'string'
            ]
        ],
        'etc' => [
            'type' => 'object',
            'sentAs' => 'etcetc',
            'properties' => [
                'dob' => ['type' => 'string'],
                'age' => ['type' => 'integer', 'sentAs' => 'current_age'],
            ]
        ],
    ],
];
