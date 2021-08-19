<?php
namespace Aws;

use Aws\Exception\AwsException;
use Aws\Retry\ConfigurationInterface;
use Aws\Retry\QuotaManager;
use Aws\Retry\RateLimiter;
use Aws\Retry\RetryHelperTrait;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise;
use Psr\Http\Message\RequestInterface;

/**
 * Middleware that retries failures. V2 implementation that supports 'standard'
 * and 'adaptive' modes.
 *
 * @internal
 */
class RetryMiddlewareV2
{
    use RetryHelperTrait;

    private static $standardThrottlingErrors = [
        'Throttling'                                => true,
        'ThrottlingException'                       => true,
        'ThrottledException'                        => true,
        'RequestThrottledException'                 => true,
        'TooManyRequestsException'                  => true,
        'ProvisionedThroughputExceededException'    => true,
        'TransactionInProgressException'            => true,
        'RequestLimitExceeded'                      => true,
        'BandwidthLimitExceeded'                    => true,
        'LimitExceededException'                    => true,
        'RequestThrottled'                          => true,
        'SlowDown'                                  => true,
        'PriorRequestNotComplete'                   => true,
        'EC2ThrottledException'                     => true,
    ];

    private static $standardTransientErrors = [
        'RequestTimeout'            => true,
        'RequestTimeoutException'   => true,
    ];

    private static $standardTransientStatusCodes = [
        500 => true,
        502 => true,
        503 => true,
        504 => true,
    ];

    private $collectStats;
    private $decider;
    private $delayer;
    private $maxAttempts;
    private $maxBackoff;
    private $mode;
    private $nextHandler;
    private $options;
    private $quotaManager;
    private $rateLimiter;

    public static function wrap($config, $options)
    {
        return function (callable $handler) use (
            $config,
            $options
        ) {
            return new static(
                $config,
                $handler,
                $options
            );
        };
    }

    public static function createDefaultDecider(
        QuotaManager $quotaManager,
        $maxAttempts = 3,
        $options = []
    ) {
        $retryCurlErrors = [];
        if (extension_loaded('curl')) {
            $retryCurlErrors[CURLE_RECV_ERROR] = true;
        }

        return function(
            $attempts,
            CommandInterface $command,
            $result
        ) use ($options, $quotaManager, $retryCurlErrors, $maxAttempts) {

            // Release retry tokens back to quota on a successful result
            $quotaManager->releaseToQuota($result);

            // Allow command-level option to override this value
            // # of attempts = # of retries + 1
            $maxAttempts = (null !== $command['@retries'])
                ? $command['@retries'] + 1
                : $maxAttempts;

            $isRetryable = self::isRetryable(
                $result,
                $retryCurlErrors,
                $options
            );

            if ($isRetryable) {

                // Retrieve retry tokens and check if quota has been exceeded
                if (!$quotaManager->hasRetryQuota($result)) {
                    return false;
                }

                if ($attempts >= $maxAttempts) {
                    if (!empty($result) && $result instanceof AwsException) {
                        $result->setMaxRetriesExceeded();
                    }
                    return false;
                }
            }

            return $isRetryable;
        };
    }

    public function __construct(
        ConfigurationInterface $config,
        callable $handler,
        $options = []
    ) {
        $this->options = $options;
        $this->maxAttempts = $config->getMaxAttempts();
        $this->mode = $config->getMode();
        $this->nextHandler = $handler;
        $this->quotaManager = new QuotaManager();

        $this->maxBackoff = isset($options['max_backoff'])
            ? $options['max_backoff']
            : 20000;

        $this->collectStats = isset($options['collect_stats'])
            ? (bool) $options['collect_stats']
            : false;

        $this->decider = isset($options['decider'])
            ? $options['decider']
            : self::createDefaultDecider(
                $this->quotaManager,
                $this->maxAttempts,
                $options
            );

        $this->delayer = isset($options['delayer'])
            ? $options['delayer']
            : function ($attempts) {
                return $this->exponentialDelayWithJitter($attempts);
            };

        if ($this->mode === 'adaptive') {
            $this->rateLimiter = isset($options['rate_limiter'])
                ? $options['rate_limiter']
                : new RateLimiter();
        }
    }

    public function __invoke(CommandInterface $cmd, RequestInterface $req)
    {
        $decider = $this->decider;
        $delayer = $this->delayer;
        $handler = $this->nextHandler;

        $attempts = 1;
        $monitoringEvents = [];
        $requestStats = [];

        $req = $this->addRetryHeader($req, 0, 0);

        $callback = function ($value) use (
            $handler,
            $cmd,
            $req,
            $decider,
            $delayer,
            &$attempts,
            &$requestStats,
            &$monitoringEvents,
            &$callback
        ) {
            if ($this->mode === 'adaptive') {
                $this->rateLimiter->updateSendingRate($this->isThrottlingError($value));
            }

            $this->updateHttpStats($value, $requestStats);

            if ($value instanceof MonitoringEventsInterface) {
                $reversedEvents = array_reverse($monitoringEvents);
                $monitoringEvents = array_merge($monitoringEvents, $value->getMonitoringEvents());
                foreach ($reversedEvents as $event) {
                    $value->prependMonitoringEvent($event);
                }
            }
            if ($value instanceof \Exception || $value instanceof \Throwable) {
                if (!$decider($attempts, $cmd, $value)) {
                    return Promise\rejection_for(
                        $this->bindStatsToReturn($value, $requestStats)
                    );
                }
            } elseif ($value instanceof ResultInterface
                && !$decider($attempts, $cmd, $value)
            ) {
                return $this->bindStatsToReturn($value, $requestStats);
            }

            $delayBy = $delayer($attempts++);
            $cmd['@http']['delay'] = $delayBy;
            if ($this->collectStats) {
                $this->updateStats($attempts - 1, $delayBy, $requestStats);
            }

            // Update retry header with retry count and delayBy
            $req = $this->addRetryHeader($req, $attempts - 1, $delayBy);

            // Get token from rate limiter, which will sleep if necessary
            if ($this->mode === 'adaptive') {
                $this->rateLimiter->getSendToken();
            }

            return $handler($cmd, $req)->then($callback, $callback);
        };

        // Get token from rate limiter, which will sleep if necessary
        if ($this->mode === 'adaptive') {
            $this->rateLimiter->getSendToken();
        }

        return $handler($cmd, $req)->then($callback, $callback);
    }

    /**
     * Amount of milliseconds to delay as a function of attempt number
     *
     * @param $attempts
     * @return mixed
     */
    public function exponentialDelayWithJitter($attempts)
    {
        $rand = mt_rand() / mt_getrandmax();
        return min(1000 * $rand * pow(2, $attempts) , $this->maxBackoff);
    }

    private static function isRetryable(
        $result,
        $retryCurlErrors,
        $options = []
    ) {
        $errorCodes = self::$standardThrottlingErrors + self::$standardTransientErrors;
        if (!empty($options['transient_error_codes'])
            && is_array($options['transient_error_codes'])
        ) {
            foreach($options['transient_error_codes'] as $code) {
                $errorCodes[$code] = true;
            }
        }
        if (!empty($options['throttling_error_codes'])
            && is_array($options['throttling_error_codes'])
        ) {
            foreach($options['throttling_error_codes'] as $code) {
                $errorCodes[$code] = true;
            }
        }

        $statusCodes = self::$standardTransientStatusCodes;
        if (!empty($options['status_codes'])
            && is_array($options['status_codes'])
        ) {
            foreach($options['status_codes'] as $code) {
                $statusCodes[$code] = true;
            }
        }

        if (!empty($options['curl_errors'])
            && is_array($options['curl_errors'])
        ) {
            foreach($options['curl_errors'] as $code) {
                $retryCurlErrors[$code] = true;
            }
        }

        if ($result instanceof \Exception || $result instanceof \Throwable) {
            $isError = true;
        } else {
            $isError = false;
        }

        if (!$isError) {
            if (!isset($result['@metadata']['statusCode'])) {
                return false;
            }
            return isset($statusCodes[$result['@metadata']['statusCode']]);
        }

        if (!($result instanceof AwsException)) {
            return false;
        }

        if ($result->isConnectionError()) {
            return true;
        }

        if (!empty($errorCodes[$result->getAwsErrorCode()])) {
            return true;
        }

        if (!empty($statusCodes[$result->getStatusCode()])) {
            return true;
        }

        if (count($retryCurlErrors)
            && ($previous = $result->getPrevious())
            && $previous instanceof RequestException
        ) {
            if (method_exists($previous, 'getHandlerContext')) {
                $context = $previous->getHandlerContext();
                return !empty($context['errno'])
                    && isset($retryCurlErrors[$context['errno']]);
            }

            $message = $previous->getMessage();
            foreach (array_keys($retryCurlErrors) as $curlError) {
                if (strpos($message, 'cURL error ' . $curlError . ':') === 0) {
                    return true;
                }
            }
        }

        // Check error shape for the retryable trait
        if (!empty($errorShape = $result->getAwsErrorShape())) {
            $definition = $errorShape->toArray();
            if (!empty($definition['retryable'])) {
                return true;
            }
        }

        return false;
    }

    private function isThrottlingError($result)
    {
        if ($result instanceof AwsException) {
            // Check pre-defined throttling errors
            $throttlingErrors = self::$standardThrottlingErrors;
            if (!empty($this->options['throttling_error_codes'])
                && is_array($this->options['throttling_error_codes'])
            ) {
                foreach($this->options['throttling_error_codes'] as $code) {
                    $throttlingErrors[$code] = true;
                }
            }
            if (!empty($result->getAwsErrorCode())
                && !empty($throttlingErrors[$result->getAwsErrorCode()])
            ) {
                return true;
            }

            // Check error shape for the throttling trait
            if (!empty($errorShape = $result->getAwsErrorShape())) {
                $definition = $errorShape->toArray();
                if (!empty($definition['retryable']['throttling'])) {
                    return true;
                }
            }
        }

        return false;
    }
}
