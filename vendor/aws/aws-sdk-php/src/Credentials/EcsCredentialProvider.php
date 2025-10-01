<?php
namespace Aws\Credentials;

use Aws\Arn\Arn;
use Aws\Exception\CredentialsException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Credential provider that fetches container credentials with GET request.
 * container environment variables are used in constructing request URI.
 */
class EcsCredentialProvider
{
    const SERVER_URI = 'http://169.254.170.2';
    const ENV_URI = "AWS_CONTAINER_CREDENTIALS_RELATIVE_URI";
    const ENV_FULL_URI = "AWS_CONTAINER_CREDENTIALS_FULL_URI";
    const ENV_AUTH_TOKEN = "AWS_CONTAINER_AUTHORIZATION_TOKEN";
    const ENV_AUTH_TOKEN_FILE = "AWS_CONTAINER_AUTHORIZATION_TOKEN_FILE";
    const ENV_TIMEOUT = 'AWS_METADATA_SERVICE_TIMEOUT';
    const EKS_SERVER_HOST_IPV4 = '169.254.170.23';
    const EKS_SERVER_HOST_IPV6 = 'fd00:ec2::23';
    const ENV_RETRIES = 'AWS_METADATA_SERVICE_NUM_ATTEMPTS';

    const DEFAULT_ENV_TIMEOUT = 1.0;
    const DEFAULT_ENV_RETRIES = 3;

    /** @var callable */
    private $client;

    /** @var float|mixed */
    private $timeout;

    /** @var int */
    private $retries;

    /** @var int */
    private $attempts;

    /**
     *  The constructor accepts following options:
     *  - timeout: (optional) Connection timeout, in seconds, default 1.0
     *  - retries: Optional number of retries to be attempted, default 3.
     *  - client: An EcsClient to make request from
     *
     * @param array $config Configuration options
     */
    public function __construct(array $config = [])
    {
        $this->timeout = (float) isset($config['timeout'])
            ? $config['timeout']
            : (getenv(self::ENV_TIMEOUT) ?: self::DEFAULT_ENV_TIMEOUT);
        $this->retries = (int) isset($config['retries'])
            ? $config['retries']
            : ((int) getenv(self::ENV_RETRIES) ?: self::DEFAULT_ENV_RETRIES);

        $this->client = $config['client'] ?? \Aws\default_http_handler();
    }

    /**
     * Load container credentials.
     *
     * @return PromiseInterface
     * @throws GuzzleException
     */
    public function __invoke()
    {
        $this->attempts = 0;
        $uri = $this->getEcsUri();
        if ($this->isCompatibleUri($uri)) {
            return Promise\Coroutine::of(function () {
                $client = $this->client;
                $request = new Request('GET', $this->getEcsUri());
                $headers = $this->getHeadersForAuthToken();
                $credentials = null;
                while ($credentials === null) {
                    $credentials = (yield $client(
                        $request,
                        [
                            'timeout' => $this->timeout,
                            'proxy' => '',
                            'headers' => $headers,
                        ]
                    )->then(function (ResponseInterface $response) {
                        $result = $this->decodeResult((string)$response->getBody());
                        if (!isset($result['AccountId']) && isset($result['RoleArn'])) {
                            try {
                                $parsedArn = new Arn($result['RoleArn']);
                                $result['AccountId'] = $parsedArn->getAccountId();
                            } catch (\Exception $e) {
                                // AccountId will be null
                            }
                        }

                        return new Credentials(
                            $result['AccessKeyId'],
                            $result['SecretAccessKey'],
                            $result['Token'],
                            strtotime($result['Expiration']),
                            $result['AccountId'] ?? null,
                            CredentialSources::ECS
                        );
                    })->otherwise(function ($reason) {
                        $reason = is_array($reason) ? $reason['exception'] : $reason;

                        $isRetryable = $reason instanceof ConnectException;
                        if ($isRetryable && ($this->attempts < $this->retries)) {
                            sleep((int)pow(1.2, $this->attempts));
                        } else {
                            $msg = $reason->getMessage();
                            throw new CredentialsException(
                                sprintf('Error retrieving credentials from container metadata after attempt %d/%d (%s)', $this->attempts, $this->retries, $msg)
                            );
                        }
                    }));
                    $this->attempts++;
                }

                yield $credentials;
            });
        }

        throw new CredentialsException("Uri '{$uri}' contains an unsupported host.");
    }

    /**
     * Returns the number of attempts that have been done.
     *
     * @return int
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * Retrieves authorization token.
     *
     * @return array|false|string
     */
    private function getEcsAuthToken()
    {
        if (!empty($path = getenv(self::ENV_AUTH_TOKEN_FILE))) {
            $token =  @file_get_contents($path);
            if (false === $token) {
                clearstatcache(true, dirname($path) . DIRECTORY_SEPARATOR . @readlink($path));
                clearstatcache(true, dirname($path) . DIRECTORY_SEPARATOR . dirname(@readlink($path)));
                clearstatcache(true, $path);
            }

            if (!is_readable($path)) {
                throw new CredentialsException("Failed to read authorization token from '{$path}': no such file or directory.");
            }

            $token = @file_get_contents($path);

            if (empty($token)) {
                throw new CredentialsException("Invalid authorization token read from `$path`. Token file is empty!");
            }

            return $token;
        }

        return getenv(self::ENV_AUTH_TOKEN);
    }

    /**
     * Provides headers for credential metadata request.
     *
     * @return array|array[]|string[]
     */
    private function getHeadersForAuthToken()
    {
        $authToken = self::getEcsAuthToken();
        $headers = [];

        if (!empty($authToken))
            $headers = ['Authorization' => $authToken];

        return $headers;
    }

    /** @deprecated */
    public function setHeaderForAuthToken()
    {
        $authToken = self::getEcsAuthToken();
        $headers = [];
        if (!empty($authToken))
            $headers = ['Authorization' => $authToken];

        return $headers;
    }

    /**
     * Fetch container metadata URI from container environment variable.
     *
     * @return string Returns container metadata URI
     */
    private function getEcsUri()
    {
        $credsUri = getenv(self::ENV_URI);

        if ($credsUri === false) {
            $credsUri = $_SERVER[self::ENV_URI] ?? '';
        }

        if (empty($credsUri)){
            $credFullUri = getenv(self::ENV_FULL_URI);
            if ($credFullUri === false){
                $credFullUri = $_SERVER[self::ENV_FULL_URI] ?? '';
            }

            if (!empty($credFullUri))
                return $credFullUri;
        }

        return self::SERVER_URI . $credsUri;
    }

    private function decodeResult($response)
    {
        $result = json_decode($response, true);

        if (!isset($result['AccessKeyId'])) {
            throw new CredentialsException('Unexpected container metadata credentials value');
        }
        return $result;
    }

    /**
     * Determines whether or not a given request URI is a valid
     * container credential request URI.
     *
     * @param $uri
     *
     * @return bool
     */
    private function isCompatibleUri($uri)
    {
        $parsed = parse_url($uri);

        if ($parsed['scheme'] !== 'https') {
            $host = trim($parsed['host'], '[]');
            $ecsHost = parse_url(self::SERVER_URI)['host'];
            $eksHost = self::EKS_SERVER_HOST_IPV4;

            if ($host !== $ecsHost
                && $host !== $eksHost
                && $host !== self::EKS_SERVER_HOST_IPV6
                && !CredentialsUtils::isLoopBackAddress(gethostbyname($host))
            ) {
                return false;
            }
        }

        return true;
    }
}
