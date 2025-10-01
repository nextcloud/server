<?php
namespace Aws\S3;

use Aws\Credentials\CredentialsInterface;
use GuzzleHttp\Psr7\Uri;

/**
 * @deprecated
 */
class PostObject
{
    private $client;
    private $bucket;
    private $formAttributes;
    private $formInputs;
    private $jsonPolicy;

    /**
     * Constructs the PostObject.
     *
     * @param S3ClientInterface $client     Client used with the POST object
     * @param string            $bucket     Bucket to use
     * @param array             $formInputs Associative array of form input
     *                                      fields.
     * @param string|array      $jsonPolicy JSON encoded POST policy document.
     *                                      The policy will be base64 encoded
     *                                      and applied to the form on your
     *                                      behalf.
     */
    public function __construct(
        S3ClientInterface $client,
        $bucket,
        array $formInputs,
        $jsonPolicy
    ) {
        $this->client = $client;
        $this->bucket = $bucket;

        if (is_array($jsonPolicy)) {
            $jsonPolicy = json_encode($jsonPolicy);
        }

        $this->jsonPolicy = $jsonPolicy;
        $this->formAttributes = [
            'action'  => $this->generateUri(),
            'method'  => 'POST',
            'enctype' => 'multipart/form-data'
        ];

        $this->formInputs = $formInputs + ['key' => '${filename}'];
        $credentials = $client->getCredentials()->wait();
        $this->formInputs += $this->getPolicyAndSignature($credentials);
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

    /**
     * Gets the raw JSON policy.
     *
     * @return string
     */
    public function getJsonPolicy()
    {
        return $this->jsonPolicy;
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
            // Use virtual-style URLs
            $uri = $uri->withHost($this->bucket . '.' . $uri->getHost());
        }

        return (string) $uri;
    }

    protected function getPolicyAndSignature(CredentialsInterface $creds)
    {
        $jsonPolicy64 = base64_encode($this->jsonPolicy);

        return [
            'AWSAccessKeyId' => $creds->getAccessKeyId(),
            'policy'    => $jsonPolicy64,
            'signature' => base64_encode(hash_hmac(
                'sha1',
                $jsonPolicy64,
                $creds->getSecretKey(),
                true
            ))
        ];
    }
}
