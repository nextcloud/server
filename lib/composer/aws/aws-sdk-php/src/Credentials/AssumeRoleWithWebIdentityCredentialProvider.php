<?php
namespace Aws\Credentials;

use Aws\Exception\AwsException;
use Aws\Exception\CredentialsException;
use Aws\Result;
use Aws\Sts\StsClient;
use GuzzleHttp\Promise;

/**
 * Credential provider that provides credentials via assuming a role with a web identity
 * More Information, see: https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sts-2011-06-15.html#assumerolewithwebidentity
 */
class AssumeRoleWithWebIdentityCredentialProvider
{
    const ERROR_MSG = "Missing required 'AssumeRoleWithWebIdentityCredentialProvider' configuration option: ";
    const ENV_RETRIES = 'AWS_METADATA_SERVICE_NUM_ATTEMPTS';

    /** @var string */
    private $tokenFile;

    /** @var string */
    private $arn;

    /** @var string */
    private $session;

    /** @var StsClient */
    private $client;

    /** @var integer */
    private $retries;

    /** @var integer */
    private $authenticationAttempts;

    /** @var integer */
    private $tokenFileReadAttempts;

    /**
     * The constructor attempts to load config from environment variables.
     * If not set, the following config options are used:
     *  - WebIdentityTokenFile: full path of token filename
     *  - RoleArn: arn of role to be assumed
     *  - SessionName: (optional) set by SDK if not provided
     *
     * @param array $config Configuration options
     * @throws \InvalidArgumentException
     */
    public function __construct(array $config = [])
    {
        if (!isset($config['RoleArn'])) {
            throw new \InvalidArgumentException(self::ERROR_MSG . "'RoleArn'.");
        }
        $this->arn = $config['RoleArn'];

        if (!isset($config['WebIdentityTokenFile'])) {
            throw new \InvalidArgumentException(self::ERROR_MSG . "'WebIdentityTokenFile'.");
        }
        $this->tokenFile = $config['WebIdentityTokenFile'];

        if (!preg_match("/^\w\:|^\/|^\\\/", $this->tokenFile)) {
            throw new \InvalidArgumentException("'WebIdentityTokenFile' must be an absolute path.");
        }

        $this->retries = (int) getenv(self::ENV_RETRIES) ?: (isset($config['retries']) ? $config['retries'] : 3);
        $this->authenticationAttempts = 0;
        $this->tokenFileReadAttempts = 0;

        $this->session = isset($config['SessionName'])
            ? $config['SessionName']
            : 'aws-sdk-php-' . round(microtime(true) * 1000);

        $region = isset($config['region'])
            ? $config['region']
            : 'us-east-1';

        if (isset($config['client'])) {
            $this->client = $config['client'];
        } else {
            $this->client = new StsClient([
                'credentials' => false,
                'region' => $region,
                'version' => 'latest'
            ]);
        }
    }

    /**
     * Loads assume role with web identity credentials.
     *
     * @return Promise\PromiseInterface
     */
    public function __invoke()
    {
        return Promise\coroutine(function () {
            $client = $this->client;
            $result = null;
            while ($result == null) {
                try {
                    $token = is_readable($this->tokenFile)
                        ? file_get_contents($this->tokenFile)
                        : false;
                    if (false === $token) {
                        clearstatcache(true, dirname($this->tokenFile) . "/" . readlink($this->tokenFile));
                        clearstatcache(true, dirname($this->tokenFile) . "/" . dirname(readlink($this->tokenFile)));
                        clearstatcache(true, $this->tokenFile);
                        if (!is_readable($this->tokenFile)) {
                            throw new CredentialsException(
                                "Unreadable tokenfile at location {$this->tokenFile}"
                            );
                        }
                        $token = file_get_contents($this->tokenFile);
                    }
                    if (empty($token)) {
                        if ($this->tokenFileReadAttempts < $this->retries) {
                            sleep(pow(1.2, $this->tokenFileReadAttempts));
                            $this->tokenFileReadAttempts++;
                            continue;
                        }
                        throw new CredentialsException("InvalidIdentityToken from file: {$this->tokenFile}");
                    }
                } catch (\Exception $exception) {
                    throw new CredentialsException(
                        "Error reading WebIdentityTokenFile from " . $this->tokenFile,
                        0,
                        $exception
                    );
                }

                $assumeParams = [
                    'RoleArn' => $this->arn,
                    'RoleSessionName' => $this->session,
                    'WebIdentityToken' => $token
                ];

                try {
                    $result = $client->assumeRoleWithWebIdentity($assumeParams);
                } catch (AwsException $e) {
                    if ($e->getAwsErrorCode() == 'InvalidIdentityToken') {
                        if ($this->authenticationAttempts < $this->retries) {
                            sleep(pow(1.2, $this->authenticationAttempts));
                        } else {
                            throw new CredentialsException(
                                "InvalidIdentityToken, retries exhausted"
                            );
                        }
                    } else {
                        throw new CredentialsException(
                            "Error assuming role from web identity credentials",
                            0,
                            $e
                        );
                    }
                } catch (\Exception $e) {
                    throw new CredentialsException(
                        "Error retrieving web identity credentials: " . $e->getMessage()
                        . " (" . $e->getCode() . ")"
                    );
                }
                $this->authenticationAttempts++;
            }

            yield $this->client->createCredentials($result);
        });
    }
}
