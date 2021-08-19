<?php
namespace Aws\CloudFront;

class CookieSigner
{
    /** @var Signer */
    private $signer;

    private static $schemes = [
        'http' => true,
        'https' => true,
    ];

    /**
     * @param $keyPairId  string ID of the key pair
     * @param $privateKey string Path to the private key used for signing
     *
     * @throws \RuntimeException if the openssl extension is missing
     * @throws \InvalidArgumentException if the private key cannot be found.
     */
    public function __construct($keyPairId, $privateKey)
    {
        $this->signer = new Signer($keyPairId, $privateKey);
    }

    /**
     * Create a signed Amazon CloudFront Cookie.
     *
     * @param string              $url     URL to sign (can include query string
     *                                     and wildcards). Not required
     *                                     when passing a custom $policy.
     * @param string|integer|null $expires UTC Unix timestamp used when signing
     *                                     with a canned policy. Not required
     *                                     when passing a custom $policy.
     * @param string              $policy  JSON policy. Use this option when
     *                                     creating a signed cookie for a custom
     *                                     policy.
     *
     * @return array The authenticated cookie parameters
     * @throws \InvalidArgumentException if the URL provided is invalid
     * @link http://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/private-content-signed-cookies.html
     */
    public function getSignedCookie($url = null, $expires = null, $policy = null)
    {
        if ($url) {
            $this->validateUrl($url);
        }

        $cookieParameters = [];
        $signature = $this->signer->getSignature($url, $expires, $policy);
        foreach ($signature as $key => $value) {
            $cookieParameters["CloudFront-$key"] = $value;
        }

        return $cookieParameters;
    }

    private function validateUrl($url)
    {
        $scheme = str_replace('*', '', explode('://', $url)[0]);
        if (empty(self::$schemes[strtolower($scheme)])) {
            throw new \InvalidArgumentException('Invalid or missing URI scheme');
        }
    }
}
