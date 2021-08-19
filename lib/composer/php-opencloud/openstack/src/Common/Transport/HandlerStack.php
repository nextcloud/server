<?php

declare(strict_types=1);

namespace OpenStack\Common\Transport;

use GuzzleHttp\HandlerStack as GuzzleStack;
use GuzzleHttp\Utils;

class HandlerStack extends GuzzleStack
{
    public static function create(?callable $handler = null): GuzzleStack
    {
        $stack = new self($handler ?: Utils::chooseHandler());
        $stack->push(Middleware::httpErrors(), 'http_errors');
        $stack->push(Middleware::prepareBody(), 'prepare_body');

        return $stack;
    }
}
