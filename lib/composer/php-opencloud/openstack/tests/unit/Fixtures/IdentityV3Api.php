<?php

namespace OpenStack\Test\Fixtures;

class IdentityV3Api
{
    private function domainParam()
    {
        return [
            'type' => 'object',
            'params' => [
                'id'   => ['type' => 'string'],
                'name' => ['type' => 'string']
            ]
        ];
    }

    private function projectParam()
    {
        return [
            'type' => 'object',
            'params' => [
                'id'     => ['type' => 'string'],
                'name'   => ['type' => 'string'],
                'domain' => $this->domainParam(),
            ]
        ];
    }

    public function postTokens()
    {
        return [
            'method' => 'POST',
            'path'   => 'tokens',
            'params' => [
                'methods' => [
                    'type' => 'array',
                    'path' => 'auth.identity',
                    'items' => [
                        'type' => 'string'
                    ]
                ],
                'user' => [
                    'path'   => 'auth.identity.password',
                    'type'   => 'object',
                    'properties' => [
                        'id'       => [
                            'type' => 'string',
                        ],
                        'name'     => [
                            'type' => 'string',
                        ],
                        'password' => [
                            'type' => 'string',
                        ],
                        'domain'   => $this->domainParam()
                    ]
                ],
                'tokenId' => [
                    'type'   => 'string',
                    'path'   => 'auth.identity.token',
                    'sentAs' => 'id',
                ],
                'scope' => [
                    'type' => 'object',
                    'path' => 'auth',
                    'properties' => [
                        'project' => $this->projectParam(),
                        'domain'  => $this->domainParam()
                    ]
                ]
            ]
        ];
    }
}
