<?php

declare(strict_types=1);

namespace OpenStack\Common\Transport;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware as GuzzleMiddleware;
use GuzzleHttp\Utils;

class HandlerStackFactory
{
    /**
     * @deprecated use \OpenStack\Common\Transport\HandlerStackFactory::createWithOptions instead
     */
    public static function create(?callable $handler = null): HandlerStack
    {
        $stack = new HandlerStack($handler ?: Utils::chooseHandler());
        $stack->push(Middleware::httpErrors(), 'http_errors');
        $stack->push(Middleware::prepareBody(), 'prepare_body');

        return $stack;
    }

    /**
     * Creates a new HandlerStack with the given options.
     *
     * @param array{
     *     handler: callable,
     *     authHandler: callable,
     *     token: \OpenStack\Common\Auth\Token,
     *     errorVerbosity: int,
     *     debugLog: bool,
     *     logger: \Psr\Log\LoggerInterface,
     *     messageFormatter: \GuzzleHttp\MessageFormatter
     * } $options
     */
    public static function createWithOptions(array $options): HandlerStack
    {
        $stack = new HandlerStack($options['handler']                  ?? Utils::chooseHandler());
        $stack->push(Middleware::httpErrors($options['errorVerbosity'] ?? 0), 'http_errors');
        $stack->push(GuzzleMiddleware::prepareBody(), 'prepare_body');

        if (!empty($options['authHandler'])) {
            $stack->push(Middleware::authHandler($options['authHandler'], $options['token'] ?? null));
        }

        if (!empty($options['debugLog'])
            && !empty($options['logger'])
            && !empty($options['messageFormatter'])
        ) {
            $logMiddleware = GuzzleMiddleware::log($options['logger'], $options['messageFormatter']);
            $stack->push($logMiddleware, 'logger');
        }

        return $stack;
    }
}
