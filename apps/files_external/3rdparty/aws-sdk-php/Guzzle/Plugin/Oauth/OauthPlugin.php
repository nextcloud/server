<?php

namespace Guzzle\Plugin\Oauth;

use Guzzle\Common\Event;
use Guzzle\Common\Collection;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\EntityEnclosingRequestInterface;
use Guzzle\Http\QueryString;
use Guzzle\Http\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * OAuth signing plugin
 * @link http://oauth.net/core/1.0/#rfc.section.9.1.1
 */
class OauthPlugin implements EventSubscriberInterface
{
    /**
     * Consumer request method constants. See http://oauth.net/core/1.0/#consumer_req_param
     */
    const REQUEST_METHOD_HEADER = 'header';
    const REQUEST_METHOD_QUERY  = 'query';

    /** @var Collection Configuration settings */
    protected $config;

    /**
     * Create a new OAuth 1.0 plugin
     *
     * @param array $config Configuration array containing these parameters:
     *     - string 'request_method'       Consumer request method. Use the class constants.
     *     - string 'callback'             OAuth callback
     *     - string 'consumer_key'         Consumer key
     *     - string 'consumer_secret'      Consumer secret
     *     - string 'token'                Token
     *     - string 'token_secret'         Token secret
     *     - string 'verifier'             OAuth verifier.
     *     - string 'version'              OAuth version.  Defaults to 1.0
     *     - string 'signature_method'     Custom signature method
     *     - bool   'disable_post_params'  Set to true to prevent POST parameters from being signed
     *     - array|Closure 'signature_callback' Custom signature callback that accepts a string to sign and a signing key
     */
    public function __construct($config)
    {
        $this->config = Collection::fromConfig($config, array(
            'version' => '1.0',
            'request_method' => self::REQUEST_METHOD_HEADER,
            'consumer_key' => 'anonymous',
            'consumer_secret' => 'anonymous',
            'signature_method' => 'HMAC-SHA1',
            'signature_callback' => function($stringToSign, $key) {
                return hash_hmac('sha1', $stringToSign, $key, true);
            }
        ), array(
            'signature_method', 'signature_callback', 'version',
            'consumer_key', 'consumer_secret'
        ));
    }

    public static function getSubscribedEvents()
    {
        return array(
            'request.before_send' => array('onRequestBeforeSend', -1000)
        );
    }

    /**
     * Request before-send event handler
     *
     * @param Event $event Event received
     * @return array
     * @throws \InvalidArgumentException
     */
    public function onRequestBeforeSend(Event $event)
    {
        $timestamp = $this->getTimestamp($event);
        $request = $event['request'];
        $nonce = $this->generateNonce($request);
        $authorizationParams = $this->getOauthParams($timestamp, $nonce);
        $authorizationParams['oauth_signature']  = $this->getSignature($request, $timestamp, $nonce);

        switch ($this->config['request_method']) {
            case self::REQUEST_METHOD_HEADER:
                $request->setHeader(
                    'Authorization',
                    $this->buildAuthorizationHeader($authorizationParams)
                );
                break;
            case self::REQUEST_METHOD_QUERY:
                foreach ($authorizationParams as $key => $value) {
                    $request->getQuery()->set($key, $value);
                }
                break;
            default:
                throw new \InvalidArgumentException(sprintf(
                    'Invalid consumer method "%s"',
                    $this->config['request_method']
                ));
        }

        return $authorizationParams;
    }

    /**
     * Builds the Authorization header for a request
     *
     * @param array $authorizationParams Associative array of authorization parameters
     *
     * @return string
     */
    private function buildAuthorizationHeader($authorizationParams)
    {
        $authorizationString = 'OAuth ';
        foreach ($authorizationParams as $key => $val) {
            if ($val) {
                $authorizationString .= $key . '="' . urlencode($val) . '", ';
            }
        }

        return substr($authorizationString, 0, -2);
    }

    /**
     * Calculate signature for request
     *
     * @param RequestInterface $request   Request to generate a signature for
     * @param integer          $timestamp Timestamp to use for nonce
     * @param string           $nonce
     *
     * @return string
     */
    public function getSignature(RequestInterface $request, $timestamp, $nonce)
    {
        $string = $this->getStringToSign($request, $timestamp, $nonce);
        $key = urlencode($this->config['consumer_secret']) . '&' . urlencode($this->config['token_secret']);

        return base64_encode(call_user_func($this->config['signature_callback'], $string, $key));
    }

    /**
     * Calculate string to sign
     *
     * @param RequestInterface $request   Request to generate a signature for
     * @param int              $timestamp Timestamp to use for nonce
     * @param string           $nonce
     *
     * @return string
     */
    public function getStringToSign(RequestInterface $request, $timestamp, $nonce)
    {
        $params = $this->getParamsToSign($request, $timestamp, $nonce);

        // Convert booleans to strings.
        $params = $this->prepareParameters($params);

        // Build signing string from combined params
        $parameterString = clone $request->getQuery();
        $parameterString->replace($params);

        $url = Url::factory($request->getUrl())->setQuery('')->setFragment(null);

        return strtoupper($request->getMethod()) . '&'
             . rawurlencode($url) . '&'
             . rawurlencode((string) $parameterString);
    }

    /**
     * Get the oauth parameters as named by the oauth spec
     *
     * @param $timestamp
     * @param $nonce
     * @return Collection
     */
    protected function getOauthParams($timestamp, $nonce)
    {
        $params = new Collection(array(
            'oauth_consumer_key'     => $this->config['consumer_key'],
            'oauth_nonce'            => $nonce,
            'oauth_signature_method' => $this->config['signature_method'],
            'oauth_timestamp'        => $timestamp,
        ));

        // Optional parameters should not be set if they have not been set in the config as
        // the parameter may be considered invalid by the Oauth service.
        $optionalParams = array(
            'callback'  => 'oauth_callback',
            'token'     => 'oauth_token',
            'verifier'  => 'oauth_verifier',
            'version'   => 'oauth_version'
        );

        foreach ($optionalParams as $optionName => $oauthName) {
            if (isset($this->config[$optionName]) == true) {
                $params[$oauthName] = $this->config[$optionName];
            }
        }

        return $params;
    }

    /**
     * Get all of the parameters required to sign a request including:
     * * The oauth params
     * * The request GET params
     * * The params passed in the POST body (with a content-type of application/x-www-form-urlencoded)
     *
     * @param RequestInterface $request   Request to generate a signature for
     * @param integer          $timestamp Timestamp to use for nonce
     * @param string           $nonce
     *
     * @return array
     */
    public function getParamsToSign(RequestInterface $request, $timestamp, $nonce)
    {
        $params = $this->getOauthParams($timestamp, $nonce);

        // Add query string parameters
        $params->merge($request->getQuery());

        // Add POST fields to signing string if required
        if ($this->shouldPostFieldsBeSigned($request))
        {
            $params->merge($request->getPostFields());
        }

        // Sort params
        $params = $params->toArray();
        uksort($params, 'strcmp');

        return $params;
    }

    /**
     * Decide whether the post fields should be added to the base string that Oauth signs.
     * This implementation is correct. Non-conformant APIs may require that this method be
     * overwritten e.g. the Flickr API incorrectly adds the post fields when the Content-Type
     * is 'application/x-www-form-urlencoded'
     *
     * @param $request
     * @return bool Whether the post fields should be signed or not
     */
    public function shouldPostFieldsBeSigned($request)
    {
        if (!$this->config->get('disable_post_params') &&
            $request instanceof EntityEnclosingRequestInterface &&
            false !== strpos($request->getHeader('Content-Type'), 'application/x-www-form-urlencoded'))
        {
            return true;
        }

        return false;
    }

    /**
     * Returns a Nonce Based on the unique id and URL. This will allow for multiple requests in parallel with the same
     * exact timestamp to use separate nonce's.
     *
     * @param RequestInterface $request Request to generate a nonce for
     *
     * @return string
     */
    public function generateNonce(RequestInterface $request)
    {
        return sha1(uniqid('', true) . $request->getUrl());
    }

    /**
     * Gets timestamp from event or create new timestamp
     *
     * @param Event $event Event containing contextual information
     *
     * @return int
     */
    public function getTimestamp(Event $event)
    {
       return $event['timestamp'] ?: time();
    }

    /**
     * Convert booleans to strings, removed unset parameters, and sorts the array
     *
     * @param array $data Data array
     *
     * @return array
     */
    protected function prepareParameters($data)
    {
        ksort($data);
        foreach ($data as $key => &$value) {
            switch (gettype($value)) {
                case 'NULL':
                    unset($data[$key]);
                    break;
                case 'array':
                    $data[$key] = self::prepareParameters($value);
                    break;
                case 'boolean':
                    $data[$key] = $value ? 'true' : 'false';
                    break;
            }
        }

        return $data;
    }
}
