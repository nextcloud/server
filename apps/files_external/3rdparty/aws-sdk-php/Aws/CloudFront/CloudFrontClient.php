<?php
/**
 * Copyright 2010-2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace Aws\CloudFront;

use Aws\Common\Client\AbstractClient;
use Aws\Common\Client\ClientBuilder;
use Aws\Common\Credentials\Credentials;
use Aws\Common\Enum\ClientOptions as Options;
use Aws\Common\Exception\InvalidArgumentException;
use Aws\Common\Exception\Parser\DefaultXmlExceptionParser;
use Aws\Common\Exception\RequiredExtensionNotLoadedException;
use Guzzle\Common\Collection;
use Guzzle\Http\Url;
use Guzzle\Service\Resource\Model;
use Guzzle\Service\Resource\ResourceIteratorInterface;

/**
 * Client to interact with Amazon CloudFront
 *
 * @method Model createCloudFrontOriginAccessIdentity(array $args = array()) {@command CloudFront CreateCloudFrontOriginAccessIdentity}
 * @method Model createDistribution(array $args = array()) {@command CloudFront CreateDistribution}
 * @method Model createInvalidation(array $args = array()) {@command CloudFront CreateInvalidation}
 * @method Model createStreamingDistribution(array $args = array()) {@command CloudFront CreateStreamingDistribution}
 * @method Model deleteCloudFrontOriginAccessIdentity(array $args = array()) {@command CloudFront DeleteCloudFrontOriginAccessIdentity}
 * @method Model deleteDistribution(array $args = array()) {@command CloudFront DeleteDistribution}
 * @method Model deleteStreamingDistribution(array $args = array()) {@command CloudFront DeleteStreamingDistribution}
 * @method Model getCloudFrontOriginAccessIdentity(array $args = array()) {@command CloudFront GetCloudFrontOriginAccessIdentity}
 * @method Model getCloudFrontOriginAccessIdentityConfig(array $args = array()) {@command CloudFront GetCloudFrontOriginAccessIdentityConfig}
 * @method Model getDistribution(array $args = array()) {@command CloudFront GetDistribution}
 * @method Model getDistributionConfig(array $args = array()) {@command CloudFront GetDistributionConfig}
 * @method Model getInvalidation(array $args = array()) {@command CloudFront GetInvalidation}
 * @method Model getStreamingDistribution(array $args = array()) {@command CloudFront GetStreamingDistribution}
 * @method Model getStreamingDistributionConfig(array $args = array()) {@command CloudFront GetStreamingDistributionConfig}
 * @method Model listCloudFrontOriginAccessIdentities(array $args = array()) {@command CloudFront ListCloudFrontOriginAccessIdentities}
 * @method Model listDistributions(array $args = array()) {@command CloudFront ListDistributions}
 * @method Model listInvalidations(array $args = array()) {@command CloudFront ListInvalidations}
 * @method Model listStreamingDistributions(array $args = array()) {@command CloudFront ListStreamingDistributions}
 * @method Model updateCloudFrontOriginAccessIdentity(array $args = array()) {@command CloudFront UpdateCloudFrontOriginAccessIdentity}
 * @method Model updateDistribution(array $args = array()) {@command CloudFront UpdateDistribution}
 * @method Model updateStreamingDistribution(array $args = array()) {@command CloudFront UpdateStreamingDistribution}
 * @method waitUntilStreamingDistributionDeployed(array $input) Wait until a streaming distribution is deployed. The input array uses the parameters of the GetStreamingDistribution operation and waiter specific settings
 * @method waitUntilDistributionDeployed(array $input) Wait until a distribution is deployed. The input array uses the parameters of the GetDistribution operation and waiter specific settings
 * @method waitUntilInvalidationCompleted(array $input) Wait until an invalidation has completed. The input array uses the parameters of the GetInvalidation operation and waiter specific settings
 * @method ResourceIteratorInterface getListCloudFrontOriginAccessIdentitiesIterator(array $args = array()) The input array uses the parameters of the ListCloudFrontOriginAccessIdentities operation
 * @method ResourceIteratorInterface getListDistributionsIterator(array $args = array()) The input array uses the parameters of the ListDistributions operation
 * @method ResourceIteratorInterface getListInvalidationsIterator(array $args = array()) The input array uses the parameters of the ListInvalidations operation
 * @method ResourceIteratorInterface getListStreamingDistributionsIterator(array $args = array()) The input array uses the parameters of the ListStreamingDistributions operation
 *
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/guide/latest/service-cloudfront.html User guide
 * @link http://docs.aws.amazon.com/aws-sdk-php-2/latest/class-Aws.CloudFront.CloudFrontClient.html API docs
 */
class CloudFrontClient extends AbstractClient
{
    const LATEST_API_VERSION = '2013-05-12';

    /**
     * Factory method to create a new Amazon CloudFront client using an array of configuration options:
     *
     * Credential options (`key`, `secret`, and optional `token` OR `credentials` is required)
     *
     * - key: AWS Access Key ID
     * - secret: AWS secret access key
     * - credentials: You can optionally provide a custom `Aws\Common\Credentials\CredentialsInterface` object
     * - token: Custom AWS security token to use with request authentication
     * - token.ttd: UNIX timestamp for when the custom credentials expire
     * - credentials.cache: Used to cache credentials when using providers that require HTTP requests. Set the true
     *   to use the default APC cache or provide a `Guzzle\Cache\CacheAdapterInterface` object.
     * - credentials.cache.key: Optional custom cache key to use with the credentials
     * - credentials.client: Pass this option to specify a custom `Guzzle\Http\ClientInterface` to use if your
     *   credentials require a HTTP request (e.g. RefreshableInstanceProfileCredentials)
     *
     * Region and Endpoint options
     *
     * - base_url: Instead of using a `region` and `scheme`, you can specify a custom base URL for the client
     *
     * Generic client options
     *
     * - ssl.certificate_authority: Set to true to use the bundled CA cert (default), system to use the certificate
     *   bundled with your system, or pass the full path to an SSL certificate bundle. This option should be used when
     *   you encounter curl error code 60.
     * - curl.options: Array of cURL options to apply to every request.
     *   See http://www.php.net/manual/en/function.curl-setopt.php for a list of available options
     * - client.backoff.logger: `Guzzle\Log\LogAdapterInterface` object used to log backoff retries. Use
     *   'debug' to emit PHP warnings when a retry is issued.
     * - client.backoff.logger.template: Optional template to use for exponential backoff log messages. See
     *   `Guzzle\Plugin\Backoff\BackoffLogger` for formatting information.
     *
     * Options specific to CloudFront
     *
     * - key_pair_id: The ID of the key pair used to sign CloudFront URLs for private distributions.
     * - private_key: The filepath ot the private key used to sign CloudFront URLs for private distributions.
     *
     * @param array|Collection $config Client configuration data
     *
     * @return self
     */
    public static function factory($config = array())
    {
        // Decide which signature to use
        if (isset($config[Options::VERSION]) && $config[Options::VERSION] < self::LATEST_API_VERSION) {
            $config[Options::SIGNATURE] = new CloudFrontSignature();
        }

        // Instantiate the CloudFront client
        return ClientBuilder::factory(__NAMESPACE__)
            ->setConfig($config)
            ->setConfigDefaults(array(
                Options::VERSION => self::LATEST_API_VERSION,
                Options::SERVICE_DESCRIPTION => __DIR__ . '/Resources/cloudfront-%s.php',
            ))
            ->setExceptionParser(new DefaultXmlExceptionParser())
            ->setIteratorsConfig(array(
                'token_param' => 'Marker',
                'token_key'   => 'NextMarker',
                'more_key'    => 'IsTruncated',
                'result_key'  => 'Items',
                'operations'  => array(
                    'ListCloudFrontOriginAccessIdentities',
                    'ListDistributions',
                    'ListInvalidations',
                    'ListStreamingDistributions'
                )
            ))
            ->build();
    }

    /**
     * Create a signed URL. Keep in mind that URLs meant for use in media/flash players may have different requirements
     * for URL formats (e.g. some require that the extension be removed, some require the file name to be prefixed -
     * mp4:<path>, some require you to add "/cfx/st" into your URL). See
     * http://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/WorkingWithStreamingDistributions.html for
     * additional details and help.
     *
     * This method accepts an array of configuration options:
     * - url:         (string)  URL of the resource being signed (can include query string and wildcards). For example:
     *                          rtmp://s5c39gqb8ow64r.cloudfront.net/videos/mp3_name.mp3
     *                          http://d111111abcdef8.cloudfront.net/images/horizon.jpg?size=large&license=yes
     * - policy:      (string)  JSON policy. Use this option when creating a signed URL for a custom policy.
     * - expires:     (int)     UTC Unix timestamp used when signing with a canned policy. Not required when passing a
     *                          custom 'policy' option.
     * - key_pair_id: (string)  The ID of the key pair used to sign CloudFront URLs for private distributions.
     * - private_key: (string)  The filepath ot the private key used to sign CloudFront URLs for private distributions.
     *
     * @param array $options Array of configuration options used when signing
     *
     * @return string                              The file URL with authentication parameters
     * @throws InvalidArgumentException            if key_pair_id and private_key have not been configured on the client
     * @throws RequiredExtensionNotLoadedException if the openssl extension is not installed
     * @link   http://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/WorkingWithStreamingDistributions.html
     */
    public function getSignedUrl(array $options)
    {
        if (!extension_loaded('openssl')) {
            //@codeCoverageIgnoreStart
            throw new RequiredExtensionNotLoadedException('The openssl extension is required to sign CloudFront urls.');
            //@codeCoverageIgnoreEnd
        }

        // Initialize the configuration data and ensure that the url was specified
        $options = Collection::fromConfig($options, array_filter(array(
            'key_pair_id' => $this->getConfig('key_pair_id'),
            'private_key' => $this->getConfig('private_key'),
        )), array('url', 'key_pair_id', 'private_key'));

        // Determine the scheme of the url
        $urlSections = explode('://', $options['url']);
        if (count($urlSections) < 2) {
            throw new InvalidArgumentException('Invalid URL: ' . $options['url']);
        }

        // Get the real scheme by removing wildcards from the scheme
        $scheme = str_replace('*', '', $urlSections[0]);
        $policy = $options['policy'] ?: $this->createCannedPolicy($scheme, $options['url'], $options['expires']);
        // Strip whitespace from the policy
        $policy = str_replace(' ', '', $policy);

        $url = Url::factory($scheme . '://' . $urlSections[1]);
        if ($options['policy']) {
            // Custom policies require that the encoded policy be specified in the URL
            $url->getQuery()->set('Policy', strtr(base64_encode($policy), '+=/', '-_~'));
        } else {
            // Canned policies require that the Expires parameter be set in the URL
            $url->getQuery()->set('Expires', $options['expires']);
        }

        // Sign the policy using the CloudFront private key
        $signedPolicy = $this->rsaSha1Sign($policy, $options['private_key']);
        // Remove whitespace, base64 encode the policy, and replace special characters
        $signedPolicy = strtr(base64_encode($signedPolicy), '+=/', '-_~');

        $url->getQuery()
            ->useUrlEncoding(false)
            ->set('Signature', $signedPolicy)
            ->set('Key-Pair-Id', $options['key_pair_id']);

        if ($scheme != 'rtmp') {
            // HTTP and HTTPS signed URLs include the full URL
            return (string) $url;
        } else {
            // Use a relative URL when creating Flash player URLs
            $url->setScheme(null)->setHost(null);
            return substr($url, 1);
        }
    }

    /**
     * Sign a policy string using OpenSSL RSA SHA1
     *
     * @param string $policy             Policy to sign
     * @param string $privateKeyFilename File containing the OpenSSL private key
     *
     * @return string
     */
    protected function rsaSha1Sign($policy, $privateKeyFilename)
    {
        $signature = '';
        openssl_sign($policy, $signature, file_get_contents($privateKeyFilename));

        return $signature;
    }

    /**
     * Create a canned policy for a particular URL and expiration
     *
     * @param string $scheme  Parsed scheme without wildcards
     * @param string $url     URL that is being signed
     * @param int    $expires Time in which the signature expires
     *
     * @return string
     * @throws InvalidArgumentException if the expiration is not set
     */
    protected function createCannedPolicy($scheme, $url, $expires)
    {
        if (!$expires) {
            throw new InvalidArgumentException('An expires option is required when using a canned policy');
        }

        // Generate a canned policy
        if ($scheme == 'http' || $scheme == 'https') {
            $resource = $url;
        } elseif ($scheme == 'rtmp') {
            $parts = parse_url($url);
            $pathParts = pathinfo($parts['path']);
            // Add path leading to file, strip file extension, and add a query string if present
            $resource = ltrim($pathParts['dirname'] . '/' . $pathParts['basename'], '/')
                . (isset($parts['query']) ? "?{$parts['query']}" : '');
        } else {
            throw new InvalidArgumentException("Invalid URI scheme: {$scheme}. Must be one of http or rtmp.");
        }

        return sprintf(
            '{"Statement":[{"Resource":"%s","Condition":{"DateLessThan":{"AWS:EpochTime":%d}}}]}',
            $resource,
            $expires
        );
    }
}
