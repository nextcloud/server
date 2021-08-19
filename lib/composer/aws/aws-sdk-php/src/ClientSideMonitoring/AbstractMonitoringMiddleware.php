<?php

namespace Aws\ClientSideMonitoring;

use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\MonitoringEventsInterface;
use Aws\ResponseContainerInterface;
use Aws\ResultInterface;
use GuzzleHttp\Promise;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * @internal
 */
abstract class AbstractMonitoringMiddleware
    implements MonitoringMiddlewareInterface
{
    private static $socket;

    private $nextHandler;
    private $options;
    protected $credentialProvider;
    protected $region;
    protected $service;

    protected static function getAwsExceptionHeader(AwsException $e, $headerName)
    {
        $response = $e->getResponse();
        if ($response !== null) {
            $header = $response->getHeader($headerName);
            if (!empty($header[0])) {
                return $header[0];
            }
        }
        return null;
    }

    protected static function getResultHeader(ResultInterface $result, $headerName)
    {
        if (isset($result['@metadata']['headers'][$headerName])) {
            return $result['@metadata']['headers'][$headerName];
        }
        return null;
    }

    protected static function getExceptionHeader(\Exception $e, $headerName)
    {
        if ($e instanceof ResponseContainerInterface) {
            $response = $e->getResponse();
            if ($response instanceof ResponseInterface) {
                $header = $response->getHeader($headerName);
                if (!empty($header[0])) {
                    return $header[0];
                }
            }
        }
        return null;
    }

    /**
     * Constructor stores the passed in handler and options.
     *
     * @param callable $handler
     * @param callable $credentialProvider
     * @param $options
     * @param $region
     * @param $service
     */
    public function __construct(
        callable $handler,
        callable $credentialProvider,
        $options,
        $region,
        $service
    ) {
        $this->nextHandler = $handler;
        $this->credentialProvider = $credentialProvider;
        $this->options = $options;
        $this->region = $region;
        $this->service = $service;
    }

    /**
     * Standard invoke pattern for middleware execution to be implemented by
     * child classes.
     *
     * @param  CommandInterface $cmd
     * @param  RequestInterface $request
     * @return Promise\PromiseInterface
     */
    public function __invoke(CommandInterface $cmd, RequestInterface $request)
    {
        $handler = $this->nextHandler;
        $eventData = null;
        $enabled = $this->isEnabled();

        if ($enabled) {
            $cmd['@http']['collect_stats'] = true;
            $eventData = $this->populateRequestEventData(
                $cmd,
                $request,
                $this->getNewEvent($cmd, $request)
            );
        }

        $g = function ($value) use ($eventData, $enabled) {
            if ($enabled) {
                $eventData = $this->populateResultEventData(
                    $value,
                    $eventData
                );
                $this->sendEventData($eventData);

                if ($value instanceof MonitoringEventsInterface) {
                    $value->appendMonitoringEvent($eventData);
                }
            }
            if ($value instanceof \Exception || $value instanceof \Throwable) {
                return Promise\rejection_for($value);
            }
            return $value;
        };

        return Promise\promise_for($handler($cmd, $request))->then($g, $g);
    }

    private function getClientId()
    {
        return $this->unwrappedOptions()->getClientId();
    }

    private function getNewEvent(
        CommandInterface $cmd,
        RequestInterface $request
    ) {
        $event = [
            'Api' => $cmd->getName(),
            'ClientId' => $this->getClientId(),
            'Region' => $this->getRegion(),
            'Service' => $this->getService(),
            'Timestamp' => (int) floor(microtime(true) * 1000),
            'UserAgent' => substr(
                $request->getHeaderLine('User-Agent') . ' ' . \Aws\default_user_agent(),
                0,
                256
            ),
            'Version' => 1
        ];
        return $event;
    }

    private function getHost()
    {
        return $this->unwrappedOptions()->getHost();
    }

    private function getPort()
    {
        return $this->unwrappedOptions()->getPort();
    }

    private function getRegion()
    {
        return $this->region;
    }

    private function getService()
    {
        return $this->service;
    }

    /**
     * Returns enabled flag from options, unwrapping options if necessary.
     *
     * @return bool
     */
    private function isEnabled()
    {
        return $this->unwrappedOptions()->isEnabled();
    }

    /**
     * Returns $eventData array with information from the request and command.
     *
     * @param CommandInterface $cmd
     * @param RequestInterface $request
     * @param array $event
     * @return array
     */
    protected function populateRequestEventData(
        CommandInterface $cmd,
        RequestInterface $request,
        array $event
    ) {
        $dataFormat = static::getRequestData($request);
        foreach ($dataFormat as $eventKey => $value) {
            if ($value !== null) {
                $event[$eventKey] = $value;
            }
        }
        return $event;
    }

    /**
     * Returns $eventData array with information from the response, including
     * the calculation for attempt latency.
     *
     * @param ResultInterface|\Exception $result
     * @param array $event
     * @return array
     */
    protected function populateResultEventData(
        $result,
        array $event
    ) {
        $dataFormat = static::getResponseData($result);
        foreach ($dataFormat as $eventKey => $value) {
            if ($value !== null) {
                $event[$eventKey] = $value;
            }
        }
        return $event;
    }

    /**
     * Creates a UDP socket resource and stores it with the class, or retrieves
     * it if already instantiated and connected. Handles error-checking and
     * re-connecting if necessary. If $forceNewConnection is set to true, a new
     * socket will be created.
     *
     * @param bool $forceNewConnection
     * @return Resource
     */
    private function prepareSocket($forceNewConnection = false)
    {
        if (!is_resource(self::$socket)
            || $forceNewConnection
            || socket_last_error(self::$socket)
        ) {
            self::$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            socket_clear_error(self::$socket);
            socket_connect(self::$socket, $this->getHost(), $this->getPort());
        }

        return self::$socket;
    }

    /**
     * Sends formatted monitoring event data via the UDP socket connection to
     * the CSM agent endpoint.
     *
     * @param array $eventData
     * @return int
     */
    private function sendEventData(array $eventData)
    {
        $socket = $this->prepareSocket();
        $datagram = json_encode($eventData);
        $result = socket_write($socket, $datagram, strlen($datagram));
        if ($result === false) {
            $this->prepareSocket(true);
        }
        return $result;
    }

    /**
     * Unwraps options, if needed, and returns them.
     *
     * @return ConfigurationInterface
     */
    private function unwrappedOptions()
    {
        if (!($this->options instanceof ConfigurationInterface)) {
            try {
                $this->options = ConfigurationProvider::unwrap($this->options);
            } catch (\Exception $e) {
                // Errors unwrapping CSM config defaults to disabling it
                $this->options = new Configuration(
                    false,
                    ConfigurationProvider::DEFAULT_HOST,
                    ConfigurationProvider::DEFAULT_PORT
                );
            }
        }
        return $this->options;
    }
}