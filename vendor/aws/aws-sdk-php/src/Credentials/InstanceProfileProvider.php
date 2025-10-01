<?php
namespace Aws\Credentials;

use Aws\Configuration\ConfigurationResolver;
use Aws\Exception\CredentialsException;
use Aws\Exception\InvalidJsonException;
use Aws\Sdk;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Promise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Credential provider that provides credentials from the EC2 metadata service.
 */
class InstanceProfileProvider
{
    const CRED_PATH = 'meta-data/iam/security-credentials/';
    const TOKEN_PATH = 'api/token';
    const ENV_DISABLE = 'AWS_EC2_METADATA_DISABLED';
    const ENV_TIMEOUT = 'AWS_METADATA_SERVICE_TIMEOUT';
    const ENV_RETRIES = 'AWS_METADATA_SERVICE_NUM_ATTEMPTS';
    const CFG_EC2_METADATA_V1_DISABLED = 'ec2_metadata_v1_disabled';
    const CFG_EC2_METADATA_SERVICE_ENDPOINT = 'ec2_metadata_service_endpoint';
    const CFG_EC2_METADATA_SERVICE_ENDPOINT_MODE = 'ec2_metadata_service_endpoint_mode';
    const DEFAULT_TIMEOUT = 1.0;
    const DEFAULT_RETRIES = 3;
    const DEFAULT_TOKEN_TTL_SECONDS = 21600;
    const DEFAULT_AWS_EC2_METADATA_V1_DISABLED = false;
    const ENDPOINT_MODE_IPv4 = 'IPv4';
    const ENDPOINT_MODE_IPv6 = 'IPv6';
    const DEFAULT_METADATA_SERVICE_IPv4_ENDPOINT = 'http://169.254.169.254';
    const DEFAULT_METADATA_SERVICE_IPv6_ENDPOINT = 'http://[fd00:ec2::254]';

    /** @var string */
    private $profile;

    /** @var callable */
    private $client;

    /** @var int */
    private $retries;

    /** @var int */
    private $attempts;

    /** @var float|mixed */
    private $timeout;

    /** @var bool */
    private $secureMode = true;

    /** @var bool|null */
    private $ec2MetadataV1Disabled;

    /** @var string */
    private $endpoint;

    /** @var string */
    private $endpointMode;

    /** @var array */
    private $config;

    /**
     * The constructor accepts the following options:
     *
     * - timeout: Connection timeout, in seconds.
     * - profile: Optional EC2 profile name, if known.
     * - retries: Optional number of retries to be attempted.
     * - ec2_metadata_v1_disabled: Optional for disabling the fallback to IMDSv1.
     * - endpoint: Optional for overriding the default endpoint to be used for fetching credentials.
     *   The value must contain a valid URI scheme. If the URI scheme is not https, it must
     *   resolve to a loopback address.
     * - endpoint_mode: Optional for overriding the default endpoint mode (IPv4|IPv6) to be used for
     *   resolving the default endpoint.
     * - use_aws_shared_config_files: Decides whether the shared config file should be considered when
     *   using the ConfigurationResolver::resolve method.
     *
     * @param array $config Configuration options.
     */
    public function __construct(array $config = [])
    {
        $this->timeout = (float) getenv(self::ENV_TIMEOUT) ?: ($config['timeout'] ?? self::DEFAULT_TIMEOUT);
        $this->profile = $config['profile'] ?? null;
        $this->retries = (int) getenv(self::ENV_RETRIES) ?: ($config['retries'] ?? self::DEFAULT_RETRIES);
        $this->client = $config['client'] ?? \Aws\default_http_handler();
        $this->ec2MetadataV1Disabled = $config[self::CFG_EC2_METADATA_V1_DISABLED] ?? null;
        $this->endpoint = $config[self::CFG_EC2_METADATA_SERVICE_ENDPOINT] ?? null;
        if (!empty($this->endpoint) && !$this->isValidEndpoint($this->endpoint)) {
            throw new \InvalidArgumentException('The provided URI "' . $this->endpoint . '" is invalid, or contains an unsupported host');
        }

        $this->endpointMode = $config[self::CFG_EC2_METADATA_SERVICE_ENDPOINT_MODE] ?? null;
        $this->config = $config;
    }

    /**
     * Loads instance profile credentials.
     *
     * @return PromiseInterface
     */
    public function __invoke($previousCredentials = null)
    {
        $this->attempts = 0;
        return Promise\Coroutine::of(function () use ($previousCredentials) {

            // Retrieve token or switch out of secure mode
            $token = null;
            while ($this->secureMode && is_null($token)) {
                try {
                    $token = (yield $this->request(
                        self::TOKEN_PATH,
                        'PUT',
                        [
                            'x-aws-ec2-metadata-token-ttl-seconds' => self::DEFAULT_TOKEN_TTL_SECONDS
                        ]
                    ));
                } catch (TransferException $e) {
                    if ($this->getExceptionStatusCode($e) === 500
                        && $previousCredentials instanceof Credentials
                    ) {
                        goto generateCredentials;
                    } elseif ($this->shouldFallbackToIMDSv1()
                        && (!method_exists($e, 'getResponse')
                        || empty($e->getResponse())
                        || !in_array(
                            $e->getResponse()->getStatusCode(),
                            [400, 500, 502, 503, 504]
                        ))
                    ) {
                        $this->secureMode = false;
                    } else {
                        $this->handleRetryableException(
                            $e,
                            [],
                            $this->createErrorMessage(
                                'Error retrieving metadata token'
                            )
                        );
                    }
                }
                $this->attempts++;
            }

            // Set token header only for secure mode
            $headers = [];
            if ($this->secureMode) {
                $headers = [
                    'x-aws-ec2-metadata-token' => $token
                ];
            }

            // Retrieve profile
            while (!$this->profile) {
                try {
                    $this->profile = (yield $this->request(
                        self::CRED_PATH,
                        'GET',
                        $headers
                    ));
                } catch (TransferException $e) {
                    // 401 indicates insecure flow not supported, switch to
                    // attempting secure mode for subsequent calls
                    if (!empty($this->getExceptionStatusCode($e))
                        && $this->getExceptionStatusCode($e) === 401
                    ) {
                        $this->secureMode = true;
                    }
                    $this->handleRetryableException(
                        $e,
                        [ 'blacklist' => [401, 403] ],
                        $this->createErrorMessage($e->getMessage())
                    );
                }

                $this->attempts++;
            }

            // Retrieve credentials
            $result = null;
            while ($result == null) {
                try {
                    $json = (yield $this->request(
                        self::CRED_PATH . $this->profile,
                        'GET',
                        $headers
                    ));
                    $result = $this->decodeResult($json);
                } catch (InvalidJsonException $e) {
                    $this->handleRetryableException(
                        $e,
                        [ 'blacklist' => [401, 403] ],
                        $this->createErrorMessage(
                            'Invalid JSON response, retries exhausted'
                        )
                    );
                } catch (TransferException $e) {
                    // 401 indicates insecure flow not supported, switch to
                    // attempting secure mode for subsequent calls
                    if (($this->getExceptionStatusCode($e) === 500
                            || strpos($e->getMessage(), "cURL error 28") !== false)
                        && $previousCredentials instanceof Credentials
                    ) {
                        goto generateCredentials;
                    } elseif (!empty($this->getExceptionStatusCode($e))
                        && $this->getExceptionStatusCode($e) === 401
                    ) {
                        $this->secureMode = true;
                    }
                    $this->handleRetryableException(
                        $e,
                        [ 'blacklist' => [401, 403] ],
                        $this->createErrorMessage($e->getMessage())
                    );
                }
                $this->attempts++;
            }
            generateCredentials:

            if (!isset($result)) {
                $credentials = $previousCredentials;
            } else {
                $credentials = new Credentials(
                    $result['AccessKeyId'],
                    $result['SecretAccessKey'],
                    $result['Token'],
                    strtotime($result['Expiration']),
                    $result['AccountId'] ?? null,
                    CredentialSources::IMDS
                );
            }

            if ($credentials->isExpired()) {
                $credentials->extendExpiration();
            }

            yield $credentials;
        });
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $headers
     * @return PromiseInterface Returns a promise that is fulfilled with the
     *                          body of the response as a string.
     */
    private function request($url, $method = 'GET', $headers = [])
    {
        $disabled = getenv(self::ENV_DISABLE) ?: false;
        if (strcasecmp($disabled, 'true') === 0) {
            throw new CredentialsException(
                $this->createErrorMessage('EC2 metadata service access disabled')
            );
        }

        $fn = $this->client;
        $request = new Request($method, $this->resolveEndpoint() . $url);
        $userAgent = 'aws-sdk-php/' . Sdk::VERSION;
        if (defined('HHVM_VERSION')) {
            $userAgent .= ' HHVM/' . HHVM_VERSION;
        }
        $userAgent .= ' ' . \Aws\default_user_agent();
        $request = $request->withHeader('User-Agent', $userAgent);
        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        return $fn($request, ['timeout' => $this->timeout])
            ->then(function (ResponseInterface $response) {
                return (string) $response->getBody();
            })->otherwise(function (array $reason) {
                $reason = $reason['exception'];
                if ($reason instanceof TransferException) {
                    throw $reason;
                }
                $msg = $reason->getMessage();
                throw new CredentialsException(
                    $this->createErrorMessage($msg)
                );
            });
    }

    private function handleRetryableException(
        \Exception $e,
        $retryOptions,
        $message
    ) {
        $isRetryable = true;
        if (!empty($status = $this->getExceptionStatusCode($e))
            && isset($retryOptions['blacklist'])
            && in_array($status, $retryOptions['blacklist'])
        ) {
            $isRetryable = false;
        }
        if ($isRetryable && $this->attempts < $this->retries) {
            sleep((int) pow(1.2, $this->attempts));
        } else {
            throw new CredentialsException($message);
        }
    }

    private function getExceptionStatusCode(\Exception $e)
    {
        if (method_exists($e, 'getResponse')
            && !empty($e->getResponse())
        ) {
            return $e->getResponse()->getStatusCode();
        }
        return null;
    }

    private function createErrorMessage($previous)
    {
        return "Error retrieving credentials from the instance profile "
            . "metadata service. ({$previous})";
    }

    private function decodeResult($response)
    {
        $result = json_decode($response, true);

        if (json_last_error() > 0) {
            throw new InvalidJsonException();
        }

        if ($result['Code'] !== 'Success') {
            throw new CredentialsException('Unexpected instance profile '
                .  'response code: ' . $result['Code']);
        }

        return $result;
    }

    /**
     * This functions checks for whether we should fall back to IMDSv1 or not.
     * If $ec2MetadataV1Disabled is null then we will try to resolve this value from
     * the following sources:
     * - From environment: "AWS_EC2_METADATA_V1_DISABLED".
     * - From config file: aws_ec2_metadata_v1_disabled
     * - Defaulted to false
     *
     * @return bool
     */
    private function shouldFallbackToIMDSv1(): bool
    {
        $isImdsV1Disabled = \Aws\boolean_value($this->ec2MetadataV1Disabled)
            ?? \Aws\boolean_value(
                ConfigurationResolver::resolve(
                    self::CFG_EC2_METADATA_V1_DISABLED,
                    self::DEFAULT_AWS_EC2_METADATA_V1_DISABLED,
                    'bool',
                    $this->config
                )
            )
            ?? self::DEFAULT_AWS_EC2_METADATA_V1_DISABLED;

        return !$isImdsV1Disabled;
    }

    /**
     * Resolves the metadata service endpoint. If the endpoint is not provided
     * or configured then, the default endpoint, based on the endpoint mode resolved,
     * will be used.
     * Example: if endpoint_mode is resolved to be IPv4 and the endpoint is not provided
     * then, the endpoint to be used will be http://169.254.169.254.
     *
     * @return string
     */
    private function resolveEndpoint(): string
    {
        $endpoint = $this->endpoint;
        if (is_null($endpoint)) {
            $endpoint = ConfigurationResolver::resolve(
                self::CFG_EC2_METADATA_SERVICE_ENDPOINT,
                $this->getDefaultEndpoint(),
                'string',
                $this->config
            );
        }

        if (!$this->isValidEndpoint($endpoint)) {
            throw new CredentialsException('The provided URI "' . $endpoint . '" is invalid, or contains an unsupported host');
        }

        if (substr($endpoint, strlen($endpoint) - 1) !== '/') {
            $endpoint = $endpoint . '/';
        }

        return $endpoint . 'latest/';
    }

    /**
     * Resolves the default metadata service endpoint.
     * If endpoint_mode is resolved as IPv4 then:
     * - endpoint = http://169.254.169.254
     * If endpoint_mode is resolved as IPv6 then:
     * - endpoint = http://[fd00:ec2::254]
     *
     * @return string
     */
    private function getDefaultEndpoint(): string
    {
        $endpointMode = $this->resolveEndpointMode();
        switch ($endpointMode) {
            case self::ENDPOINT_MODE_IPv4:
                return self::DEFAULT_METADATA_SERVICE_IPv4_ENDPOINT;
            case self::ENDPOINT_MODE_IPv6:
                return self::DEFAULT_METADATA_SERVICE_IPv6_ENDPOINT;
        }

        throw new CredentialsException("Invalid endpoint mode '$endpointMode' resolved");
    }

    /**
     * Resolves the endpoint mode to be considered when resolving the default
     * metadata service endpoint.
     *
     * @return string
     */
    private function resolveEndpointMode(): string
    {
        $endpointMode = $this->endpointMode;
        if (is_null($endpointMode)) {
            $endpointMode = ConfigurationResolver::resolve(
                self::CFG_EC2_METADATA_SERVICE_ENDPOINT_MODE,
                    self::ENDPOINT_MODE_IPv4,
                'string',
                $this->config
            );
        }

        return $endpointMode;
    }

    /**
     * This method checks for whether a provide URI is valid.
     * @param string $uri this parameter is the uri to do the validation against to.
     *
     * @return string|null
     */
    private function isValidEndpoint(
        $uri
    ): bool
    {
        // We make sure first the provided uri is a valid URL
        $isValidURL = filter_var($uri, FILTER_VALIDATE_URL) !== false;
        if (!$isValidURL) {
            return false;
        }

        // We make sure that if is a no secure host then it must be a loop back address.
        $parsedUri = parse_url($uri);
        if ($parsedUri['scheme'] !== 'https') {
            $host = trim($parsedUri['host'], '[]');

            return CredentialsUtils::isLoopBackAddress(gethostbyname($host))
                || in_array(
                    $uri,
                    [self::DEFAULT_METADATA_SERVICE_IPv4_ENDPOINT, self::DEFAULT_METADATA_SERVICE_IPv6_ENDPOINT]
                );
        }

        return true;
    }
}
