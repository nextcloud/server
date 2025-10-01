<?php
namespace Aws;

use Aws\Api\Parser\Exception\ParserException;
use Aws\Exception\AwsException;
use GuzzleHttp\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Converts an HTTP handler into a Command HTTP handler.
 *
 * HTTP handlers have the following signature:
 *     function(RequestInterface $request, array $options) : PromiseInterface
 *
 * The promise returned form an HTTP handler must resolve to a PSR-7 response
 * object when fulfilled or an error array when rejected. The error array
 * can contain the following data:
 *
 * - exception: (required, Exception) Exception that was encountered.
 * - response: (ResponseInterface) PSR-7 response that was received (if a
 *   response) was received.
 * - connection_error: (bool) True if the error is the result of failing to
 *   connect.
 */
class WrappedHttpHandler
{
    private $httpHandler;
    private $parser;
    private $errorParser;
    private $exceptionClass;
    private $collectStats;

    /**
     * @param callable $httpHandler    Function that accepts a request and array
     *                                 of request options and returns a promise
     *                                 that fulfills with a response or rejects
     *                                 with an error array.
     * @param callable $parser         Function that accepts a response object
     *                                 and returns an AWS result object.
     * @param callable $errorParser    Function that parses a response object
     *                                 into AWS error data.
     * @param string   $exceptionClass Exception class to throw.
     * @param bool     $collectStats   Whether to collect HTTP transfer
     *                                 information.
     */
    public function __construct(
        callable $httpHandler,
        callable $parser,
        callable $errorParser,
        $exceptionClass = AwsException::class,
        $collectStats = false
    ) {
        $this->httpHandler = $httpHandler;
        $this->parser = $parser;
        $this->errorParser = $errorParser;
        $this->exceptionClass = $exceptionClass;
        $this->collectStats = $collectStats;
    }

    /**
     * Calls the simpler HTTP specific handler and wraps the returned promise
     * with AWS specific values (e.g., a result object or AWS exception).
     *
     * @param CommandInterface $command Command being executed.
     * @param RequestInterface $request Request to send.
     *
     * @return Promise\PromiseInterface
     */
    public function __invoke(
        CommandInterface $command,
        RequestInterface $request
    ) {
        $fn = $this->httpHandler;
        $options = $command['@http'] ?: [];
        $stats = [];
        if ($this->collectStats || !empty($options['collect_stats'])) {
            $options['http_stats_receiver'] = static function (
                array $transferStats
            ) use (&$stats) {
                $stats = $transferStats;
            };
        } elseif (isset($options['http_stats_receiver'])) {
            throw new \InvalidArgumentException('Providing a custom HTTP stats'
                . ' receiver to Aws\WrappedHttpHandler is not supported.');
        }

        return Promise\Create::promiseFor($fn($request, $options))
            ->then(
                function (
                    ResponseInterface $res
                ) use ($command, $request, &$stats) {
                    return $this->parseResponse($command, $request, $res, $stats);
                },
                function ($err) use ($request, $command, &$stats) {
                    if (is_array($err)) {
                        $err = $this->parseError(
                            $err,
                            $request,
                            $command,
                            $stats
                        );
                    }
                    return new Promise\RejectedPromise($err);
                }
            );
    }

    /**
     * @param CommandInterface  $command
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array             $stats
     *
     * @return ResultInterface
     */
    private function parseResponse(
        CommandInterface $command,
        RequestInterface $request,
        ResponseInterface $response,
        array $stats
    ) {
        $parser = $this->parser;
        $status = $response->getStatusCode();
        $result = $status < 300
            ? $parser($command, $response)
            : new Result();

        $metadata = [
            'statusCode'    => $status,
            'effectiveUri'  => (string) $request->getUri(),
            'headers'       => [],
            'transferStats' => [],
        ];
        if (!empty($stats)) {
            $metadata['transferStats']['http'] = [$stats];
        }

        // Bring headers into the metadata array.
        foreach ($response->getHeaders() as $name => $values) {
            $metadata['headers'][strtolower($name)] = $values[0];
        }

        $result['@metadata'] = $metadata;

        return $result;
    }

    /**
     * Parses a rejection into an AWS error.
     *
     * @param array            $err     Rejection error array.
     * @param RequestInterface $request Request that was sent.
     * @param CommandInterface $command Command being sent.
     * @param array            $stats   Transfer statistics
     *
     * @return \Exception
     */
    private function parseError(
        array $err,
        RequestInterface $request,
        CommandInterface $command,
        array $stats
    ) {
        if (!isset($err['exception'])) {
            throw new \RuntimeException('The HTTP handler was rejected without an "exception" key value pair.');
        }

        $serviceError = "AWS HTTP error: " . $err['exception']->getMessage();

        if (!isset($err['response'])) {
            $parts = ['response' => null];
        } else {
            try {
                $parts = call_user_func(
                    $this->errorParser,
                    $err['response'],
                    $command
                );
                $serviceError .= " {$parts['code']} ({$parts['type']}): "
                    . "{$parts['message']} - " . $err['response']->getBody();
            } catch (ParserException $e) {
                $parts = [];
                $serviceError .= ' Unable to parse error information from '
                    . "response - {$e->getMessage()}";
            }

            $parts['response'] = $err['response'];
        }

        $parts['exception'] = $err['exception'];
        $parts['request'] = $request;
        $parts['connection_error'] = !empty($err['connection_error']);
        $parts['transfer_stats'] = $stats;

        return new $this->exceptionClass(
            sprintf(
                'Error executing "%s" on "%s"; %s',
                $command->getName(),
                $request->getUri(),
                $serviceError
            ),
            $command,
            $parts,
            $err['exception']
        );
    }
}
