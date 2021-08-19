<?php
namespace Aws\CloudFront;

/**
 * @internal
 */
class Signer
{
    private $keyPairId;
    private $pkHandle;

    /**
     * A signer for creating the signature values used in CloudFront signed URLs
     * and signed cookies.
     *
     * @param $keyPairId  string ID of the key pair
     * @param $privateKey string Path to the private key used for signing
     * @param $passphrase string Passphrase to private key file, if one exists
     *
     * @throws \RuntimeException if the openssl extension is missing
     * @throws \InvalidArgumentException if the private key cannot be found.
     */
    public function __construct($keyPairId, $privateKey, $passphrase = "")
    {
        if (!extension_loaded('openssl')) {
            //@codeCoverageIgnoreStart
            throw new \RuntimeException('The openssl extension is required to '
                . 'sign CloudFront urls.');
            //@codeCoverageIgnoreEnd
        }

        $this->keyPairId = $keyPairId;

        if (!$this->pkHandle = openssl_pkey_get_private($privateKey, $passphrase)) {
            if (!file_exists($privateKey)) {
                throw new \InvalidArgumentException("PK file not found: $privateKey");
            } else {
                $this->pkHandle = openssl_pkey_get_private("file://$privateKey", $passphrase);
                if (!$this->pkHandle) {
                    throw new \InvalidArgumentException(openssl_error_string());
                }
            }
        }
    }

    public function __destruct()
    {
        if (PHP_MAJOR_VERSION < 8) {
            $this->pkHandle && openssl_pkey_free($this->pkHandle);
        } else {
            $this->pkHandle;
        }
    }

    /**
     * Create the values used to construct signed URLs and cookies.
     *
     * @param string              $resource     The CloudFront resource to which
     *                                          this signature will grant access.
     *                                          Not used when a custom policy is
     *                                          provided.
     * @param string|integer|null $expires      UTC Unix timestamp used when
     *                                          signing with a canned policy.
     *                                          Not required when passing a
     *                                          custom $policy.
     * @param string              $policy       JSON policy. Use this option when
     *                                          creating a signature for a custom
     *                                          policy.
     *
     * @return array The values needed to construct a signed URL or cookie
     * @throws \InvalidArgumentException  when not provided either a policy or a
     *                                    resource and a expires
     *
     * @link http://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/private-content-signed-cookies.html
     */
    public function getSignature($resource = null, $expires = null, $policy = null)
    {
        $signatureHash = [];
        if ($policy) {
            $policy = preg_replace('/\s/s', '', $policy);
            $signatureHash['Policy'] = $this->encode($policy);
        } elseif ($resource && $expires) {
            $expires = (int) $expires; // Handle epoch passed as string
            $policy = $this->createCannedPolicy($resource, $expires);
            $signatureHash['Expires'] = $expires;
        } else {
            throw new \InvalidArgumentException('Either a policy or a resource'
                . ' and an expiration time must be provided.');
        }

        $signatureHash['Signature'] = $this->encode($this->sign($policy));
        $signatureHash['Key-Pair-Id'] = $this->keyPairId;

        return $signatureHash;
    }

    private function createCannedPolicy($resource, $expiration)
    {
        return json_encode([
            'Statement' => [
                [
                    'Resource' => $resource,
                    'Condition' => [
                        'DateLessThan' => ['AWS:EpochTime' => $expiration],
                    ],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES);
    }

    private function sign($policy)
    {
        $signature = '';
        openssl_sign($policy, $signature, $this->pkHandle);

        return $signature;
    }

    private function encode($policy)
    {
        return strtr(base64_encode($policy), '+=/', '-_~');
    }
}
