<?php

declare(strict_types=1);

namespace OpenStack\Common\Api;

/**
 * All classes which implement this interface are a data representation of a remote OpenStack API.
 * They do not execute functionality, but instead return data for each API operation for other parts
 * of the SDK to use. Usually, the data is injected into {@see OpenStack\Common\Api\Operation} objects.
 * The operation is then serialized into a {@see GuzzleHttp\Message\Request} and sent to the API.
 *
 * The reason for storing all the API-specific data is to decouple service information from client
 * HTTP functionality. Too often it is mixed all across different layers, leading to duplication and
 * no separation of concerns. The choice was made for storage in PHP classes, rather than YAML or JSON
 * syntax, due to performance concerns.
 */
interface ApiInterface
{
}
