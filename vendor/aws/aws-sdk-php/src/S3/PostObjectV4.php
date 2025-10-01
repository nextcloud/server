<?php
namespace Aws\S3;

use Aws\Credentials\CredentialsInterface;
use GuzzleHttp\Psr7\Uri;
use Aws\Signature\SignatureTrait;
use Aws\Signature\SignatureV4 as SignatureV4;
use Aws\Api\TimestampShape as TimestampShape;

/**
 * Encapsulates the logic for getting the data for an S3 object POST upload form
 *
 * @link http://docs.aws.amazon.com/AmazonS3/latest/API/RESTObjectPOST.html
 * @link http://docs.aws.amazon.com/AmazonS3/latest/API/sigv4-post-example.html
 */
class PostObjectV4
{
    use SignatureTrait;

    private $client;
    private $bucket;
    private $formAttributes;
    private $formInputs;

    /**
     * Constructs the PostObject.
     *
     * The options array accepts the following keys:
     * @link http://docs.aws.amazon.com/AmazonS3/latest/API/sigv4-query-string-auth.html
     *
     * @param S3ClientInterface $client     Client used with the POST object
     * @param string            $bucket     Bucket to use
     * @param array             $formInputs Associative array of form input
     *                                      fields.
     * @param array             $options    Policy condition options
     * @param mixed             $expiration Upload expiration time value. By
     *                                      default: 1 hour valid period.
     */
    public function __construct(
        S3ClientInterface $client,
        $bucket,
        array $formInputs,
        array $options = [],
        $expiration = '+1 hours'
    ) {
        $this->client = $client;
        $this->bucket = $bucket;

        // setup form attributes
        $this->formAttributes = [
            'action'  => $this->generateUri(),
            'method'  => 'POST',
            'enctype' => 'multipart/form-data'
        ];

        $credentials   = $this->client->getCredentials()->wait();

        if ($securityToken = $credentials->getSecurityToken()) {
            $options [] = ['x-amz-security-token' => $securityToken];
            $formInputs['X-Amz-Security-Token'] = $securityToken;
        }

        // setup basic policy
        $policy = [
            'expiration' => TimestampShape::format($expiration, 'iso8601'),
            'conditions' => $options,
        ];

        // setup basic formInputs
        $this->formInputs = $formInputs + ['key' => '${filename}'];

        // finalize policy and signature

        $this->formInputs += $this->getPolicyAndSignature(
            $credentials,
            $policy
        );
    }

    /**
     * Gets the S3 client.
     *
     * @return S3ClientInterface
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Gets the bucket name.
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Gets the form attributes as an array.
     *
     * @return array
     */
    public function getFormAttributes()
    {
        return $this->formAttributes;
    }

    /**
     * Set a form attribute.
     *
     * @param string $attribute Form attribute to set.
     * @param string $value     Value to set.
     */
    public function setFormAttribute($attribute, $value)
    {
        $this->formAttributes[$attribute] = $value;
    }

    /**
     * Gets the form inputs as an array.
     *
     * @return array
     */
    public function getFormInputs()
    {
        return $this->formInputs;
    }

    /**
     * Set a form input.
     *
     * @param string $field Field name to set
     * @param string $value Value to set.
     */
    public function setFormInput($field, $value)
    {
        $this->formInputs[$field] = $value;
    }

    private function generateUri()
    {
        $uri = new Uri($this->client->getEndpoint());

        if ($this->client->getConfig('use_path_style_endpoint') === true
            || ($uri->getScheme() === 'https'
            && strpos($this->bucket, '.') !== false)
        ) {
            // Use path-style URLs
            $uri = $uri->withPath("/{$this->bucket}");
        } else {
            // Use virtual-style URLs if haven't been set up already
            if (strpos($uri->getHost(), $this->bucket . '.') !== 0) {
                $uri = $uri->withHost($this->bucket . '.' . $uri->getHost());
            }
        }

        return (string) $uri;
    }

    protected function getPolicyAndSignature(
        CredentialsInterface $credentials,
        array $policy
    ){
        $ldt = gmdate(SignatureV4::ISO8601_BASIC);
        $sdt = substr($ldt, 0, 8);
        $policy['conditions'][] = ['X-Amz-Date' => $ldt];

        $region = $this->client->getRegion();
        $scope = $this->createScope($sdt, $region, 's3');
        $creds = "{$credentials->getAccessKeyId()}/$scope";
        $policy['conditions'][] = ['X-Amz-Credential' => $creds];

        $policy['conditions'][] = ['X-Amz-Algorithm' => "AWS4-HMAC-SHA256"];

        $jsonPolicy64 = base64_encode(json_encode($policy));
        $key = $this->getSigningKey(
            $sdt,
            $region,
            's3',
            $credentials->getSecretKey()
        );

        return [
            'X-Amz-Credential' => $creds,
            'X-Amz-Algorithm' => "AWS4-HMAC-SHA256",
            'X-Amz-Date' => $ldt,
            'Policy'           => $jsonPolicy64,
            'X-Amz-Signature'  => bin2hex(
                hash_hmac('sha256', $jsonPolicy64, $key, true)
            ),
        ];
    }
}
