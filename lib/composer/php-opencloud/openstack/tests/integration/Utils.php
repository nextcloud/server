<?php

namespace OpenStack\Integration;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use OpenStack\Identity\v2\Service;
use OpenStack\Common\Transport\Utils as TransportUtils;
use OpenStack\OpenStack;

class Utils
{
    public static function getOpenStack(): OpenStack
    {
        return new OpenStack(self::getAuthOpts());
    }

    public static function getAuthOptsV3()
    {
        return [
            'authUrl' => getenv('OS_AUTH_URL'),
            'region'  => getenv('OS_REGION_NAME'),
            'user'    => [
                'id'       => getenv('OS_USER_ID'),
                'password' => getenv('OS_PASSWORD'),
            ],
            'scope'   => [
                'project' => [
                    'id' => getenv('OS_PROJECT_ID'),
                ]
            ]
        ];
    }

    public static function getAuthOptsV2()
    {
        $httpClient = new Client([
            'base_uri' => TransportUtils::normalizeUrl(getenv('OS_AUTH_URL')),
            'handler'  => HandlerStack::create(),
        ]);

        return [
            'authUrl'         => getenv('OS_AUTH_URL'),
            'region'          => getenv('OS_REGION_NAME'),
            'username'        => getenv('OS_USERNAME'),
            'password'        => getenv('OS_PASSWORD'),
            'tenantName'      => getenv('OS_TENANT_NAME'),
            'identityService' => Service::factory($httpClient),
        ];
    }

    public static function getAuthOpts(array $options = [])
    {
        $authOptions = getenv('OS_IDENTITY_API_VERSION') == '2.0'
            ? self::getAuthOptsV2()
            : self::getAuthOptsV3();

        return array_merge($authOptions, $options);
    }

    public static function toCamelCase($word, $separator = '_')
    {
        return str_replace($separator, '', ucwords($word, $separator));
    }
}
