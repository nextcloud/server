<?php
namespace Aws\CloudFront;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriInterface;

/**
 * Creates signed URLs for Amazon CloudFront resources.
 */
class UrlSigner
{
    private $signer;

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
     * Create a signed Amazon CloudFront URL.
     *
     * Keep in mind that URLs meant for use in media/flash players may have
     * different requirements for URL formats (e.g. some require that the
     * extension be removed, some require the file name to be prefixed
     * - mp4:<path>, some require you to add "/cfx/st" into your URL).
     *
     * @param string              $url     URL to sign (can include query
     *                                     string string and wildcards)
     * @param string|integer|null $expires UTC Unix timestamp used when signing
     *                                     with a canned policy. Not required
     *                                     when passing a custom $policy.
     * @param string              $policy  JSON policy. Use this option when
     *                                     creating a signed URL for a custom
     *                                     policy.
     *
     * @return string The file URL with authentication parameters
     * @throws \InvalidArgumentException if the URL provided is invalid
     * @link http://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/WorkingWithStreamingDistributions.html
     */
    public function getSignedUrl($url, $expires = null, $policy = null)
    {
        // Determine the scheme of the url
        $urlSections = explode('://', $url);

        if (count($urlSections) < 2) {
            throw new \InvalidArgumentException("Invalid URL: {$url}");
        }

        // Get the real scheme by removing wildcards from the scheme
        $scheme = str_replace('*', '', $urlSections[0]);
        $uri = new Uri($scheme . '://' . $urlSections[1]);
        $query = Psr7\Query::parse($uri->getQuery(), PHP_QUERY_RFC3986);
        $signature = $this->signer->getSignature(
            $this->createResource($scheme, (string) $uri),
            $expires,
            $policy
        );
        $uri = $uri->withQuery(
            http_build_query($query + $signature, null, '&', PHP_QUERY_RFC3986)
        );

        return $scheme === 'rtmp'
            ? $this->createRtmpUrl($uri)
            : (string) $uri;
    }

    private function createRtmpUrl(UriInterface $uri)
    {
        // Use a relative URL when creating Flash player URLs
        $result = ltrim($uri->getPath(), '/');

        if ($query = $uri->getQuery()) {
            $result .= '?' . $query;
        }

        return $result;
    }

    /**
     * @param $scheme
     * @param $url
     *
     * @return string
     */
    private function createResource($scheme, $url)
    {
        switch ($scheme) {
            case 'http':
            case 'http*':
            case 'https':
                return $url;
            case 'rtmp':
                $parts = parse_url($url);
                $pathParts = pathinfo($parts['path']);
                $resource = ltrim(
                    $pathParts['dirname'] . '/' . $pathParts['basename'],
                    '/'
                );

                // Add a query string if present.
                if (isset($parts['query'])) {
                    $resource .= "?{$parts['query']}";
                }

                return $resource;
        }

        throw new \InvalidArgumentException("Invalid URI scheme: {$scheme}. "
            . "Scheme must be one of: http, https, or rtmp");
    }
}
