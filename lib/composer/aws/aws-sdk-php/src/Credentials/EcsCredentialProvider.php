<?php
namespace Aws\Credentials;

use Aws\Exception\CredentialsException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Credential provider that fetches credentials with GET request.
 * ECS environment variable is used in constructing request URI.
 */
class EcsCredentialProvider
{
    const SERVER_URI = 'http://169.254.170.2';
    const ENV_URI = "AWS_CONTAINER_CREDENTIALS_RELATIVE_URI";
    const ENV_TIMEOUT = 'AWS_METADATA_SERVICE_TIMEOUT';

    /** @var callable */
    private $client;

    /** @var float|mixed */
    private $timeout;

    /**
     *  The constructor accepts following options:
     *  - timeout: (optional) Connection timeout, in seconds, default 1.0
     *  - client: An EcsClient to make request from
     *
     * @param array $config Configuration options
     */
    public function __construct(array $config = [])
    {
        $timeout = getenv(self::ENV_TIMEOUT);

        if (!$timeout) {
            $timeout = isset($_SERVER[self::ENV_TIMEOUT])
                ? $_SERVER[self::ENV_TIMEOUT]
                : (isset($config['timeout']) ? $config['timeout'] : 1.0);
        }

        $this->timeout = (float) $timeout;
        $this->client = isset($config['client'])
            ? $config['client']
            : \Aws\default_http_handler();
    }

    /**
     * Load ECS credentials
     *
     * @return PromiseInterface
     */
    public function __invoke()
    {
        $client = $this->client;
        $request = new Request('GET', self::getEcsUri());
        return $client(
            $request,
            [
                'timeout' => $this->timeout,
                'proxy' => '',
            ]
        )->then(function (ResponseInterface $response) {
            $result = $this->decodeResult((string) $response->getBody());
            return new Credentials(
                $result['AccessKeyId'],
                $result['SecretAccessKey'],
                $result['Token'],
                strtotime($result['Expiration'])
            );
        })->otherwise(function ($reason) {
            $reason = is_array($reason) ? $reason['exception'] : $reason;
            $msg = $reason->getMessage();
            throw new CredentialsException(
                "Error retrieving credential from ECS ($msg)"
            );
        });
    }

    /**
     * Fetch credential URI from ECS environment variable
     *
     * @return string Returns ECS URI
     */
    private function getEcsUri()
    {
        $credsUri = getenv(self::ENV_URI);

        if ($credsUri === false) {
            $credsUri = isset($_SERVER[self::ENV_URI]) ? $_SERVER[self::ENV_URI] : '';
        }
        
        return self::SERVER_URI . $credsUri;
    }

    private function decodeResult($response)
    {
        $result = json_decode($response, true);

        if (!isset($result['AccessKeyId'])) {
            throw new CredentialsException('Unexpected ECS credential value');
        }
        return $result;
    }
}
