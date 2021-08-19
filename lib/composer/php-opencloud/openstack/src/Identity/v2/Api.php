<?php

declare(strict_types=1);

namespace OpenStack\Identity\v2;

use OpenStack\Common\Api\ApiInterface;

/**
 * Represents the OpenStack Identity v2 API.
 */
class Api implements ApiInterface
{
    public function postToken(): array
    {
        return [
            'method' => 'POST',
            'path'   => 'tokens',
            'params' => [
                'username' => [
                    'type'     => 'string',
                    'required' => true,
                    'path'     => 'auth.passwordCredentials',
                ],
                'password' => [
                    'type'     => 'string',
                    'required' => true,
                    'path'     => 'auth.passwordCredentials',
                ],
                'tenantId' => [
                    'type' => 'string',
                    'path' => 'auth',
                ],
                'tenantName' => [
                    'type' => 'string',
                    'path' => 'auth',
                ],
            ],
        ];
    }
}
