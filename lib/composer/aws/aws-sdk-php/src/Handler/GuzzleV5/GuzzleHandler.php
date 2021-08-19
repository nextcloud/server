<?php
namespace Aws\Handler\GuzzleV5;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Event\EndEvent;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\ResponseInterface as GuzzleResponse;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Stream\Stream;
use Psr\Http\Message\RequestInterface as Psr7Request;
use Psr\Http\Message\StreamInterface as Psr7StreamInterface;

/**
 * A request handler that sends PSR-7-compatible requests with Guzzle 5.
 *
 * The handler accepts a PSR-7 Request object and an array of transfer options
 * and returns a Guzzle 6 Promise. The promise is either resolved with a
 * PSR-7 Response object or rejected with an array of error data.
 *
 * @codeCoverageIgnore
 */
class GuzzleHandler
{
    private static $validOptions = [
        'proxy'             => true,
        'expect'            => true,
        'cert'              => true,
        'verify'            => true,
        'timeout'           => true,
        'debug'             => true,
        'connect_timeout'   => true,
        'stream'            => true,
        'delay'             => true,
        'sink'              => true,
    ];

    /** @var ClientInterface */
    private $client;

    /**
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client = null)
    {
        $this->client = $client ?: new Client();
    }

    /**
     * @param Psr7Request $request
     * @param array $options
     * @return Promise\Promise|Promise\PromiseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function __invoke(Psr7Request $request, array $options = [])
    {
        // Create and send a Guzzle 5 request
        $guzzlePromise = $this->client->send(
            $this->createGuzzleRequest($request, $options)
        );

        $promise = new Promise\Promise(
            function () use ($guzzlePromise) {
                try {
                    $guzzlePromise->wait();
                } catch (\Exception $e) {
                    // The promise is already delivered when the exception is
                    // thrown, so don't rethrow it.
                }
            },
            [$guzzlePromise, 'cancel']
        );

        $guzzlePromise->then([$promise, 'resolve'], [$promise, 'reject']);

        return $promise->then(
            function (GuzzleResponse $response) {
                // Adapt the Guzzle 5 Future to a Guzzle 6 ResponsePromise.
                return $this->createPsr7Response($response);
            },
            function (Exception $exception) use ($options) {
                // If we got a 'sink' that's a path, set the response body to
                // the contents of the file. This will build the resulting
                // exception with more information.
                if ($exception instanceof RequestException) {
                    if (isset($options['sink'])) {
                        if (!($options['sink'] instanceof Psr7StreamInterface)) {
                            $exception->getResponse()->setBody(
                                Stream::factory(
                                    file_get_contents($options['sink'])
                                )
                            );
                        }
                    }
                }
                // Reject with information about the error.
                return new Promise\RejectedPromise($this->prepareErrorData($exception));
            }
        );
    }

    private function createGuzzleRequest(Psr7Request $psrRequest, array $options)
    {
        $ringConfig = [];
        $statsCallback = isset($options['http_stats_receiver'])
            ? $options['http_stats_receiver']
            : null;
        unset($options['http_stats_receiver']);

        // Remove unsupported options.
        foreach (array_keys($options) as $key) {
            if (!isset(self::$validOptions[$key])) {
                unset($options[$key]);
            }
        }

        // Handle delay option.
        if (isset($options['delay'])) {
            $ringConfig['delay'] = $options['delay'];
            unset($options['delay']);
        }

        // Prepare sink option.
        if (isset($options['sink'])) {
            $ringConfig['save_to'] = ($options['sink'] instanceof Psr7StreamInterface)
                ? new GuzzleStream($options['sink'])
                : $options['sink'];
            unset($options['sink']);
        }

        // Ensure that all requests are async and lazy like Guzzle 6.
        $options['future'] = 'lazy';

        // Create the Guzzle 5 request from the provided PSR7 request.
        $request = $this->client->createRequest(
            $psrRequest->getMethod(),
            $psrRequest->getUri(),
            $options
        );

        if (is_callable($statsCallback)) {
            $request->getEmitter()->on(
                'end',
                function (EndEvent $event) use ($statsCallback) {
                    $statsCallback($event->getTransferInfo());
                }
            );
        }

        // For the request body, adapt the PSR stream to a Guzzle stream.
        $body = $psrRequest->getBody();
        if ($body->getSize() === 0) {
            $request->setBody(null);
        } else {
            $request->setBody(new GuzzleStream($body));
        }

        $request->setHeaders($psrRequest->getHeaders());

        $request->setHeader(
            'User-Agent',
            $request->getHeader('User-Agent')
                . ' ' . Client::getDefaultUserAgent()
        );

        // Make sure the delay is configured, if provided.
        if ($ringConfig) {
            foreach ($ringConfig as $k => $v) {
                $request->getConfig()->set($k, $v);
            }
        }

        return $request;
    }

    private function createPsr7Response(GuzzleResponse $response)
    {
        if ($body = $response->getBody()) {
            $body = new PsrStream($body);
        }

        return new Psr7Response(
            $response->getStatusCode(),
            $response->getHeaders(),
            $body,
            $response->getReasonPhrase()
        );
    }

    private function prepareErrorData(Exception $e)
    {
        $error = [
            'exception'        => $e,
            'connection_error' => false,
            'response'         => null,
        ];

        if ($e instanceof ConnectException) {
            $error['connection_error'] = true;
        }

        if ($e instanceof RequestException && $e->getResponse()) {
            $error['response'] = $this->createPsr7Response($e->getResponse());
        }

        return $error;
    }
}
