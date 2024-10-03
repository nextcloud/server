<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Adapter;

use Hybridauth\Exception\Exception;
use Hybridauth\Exception\InvalidApplicationCredentialsException;
use Hybridauth\Exception\AuthorizationDeniedException;
use Hybridauth\Exception\InvalidOauthTokenException;
use Hybridauth\Exception\InvalidAccessTokenException;
use Hybridauth\Data;
use Hybridauth\HttpClient;
use Hybridauth\Thirdparty\OAuth\OAuthConsumer;
use Hybridauth\Thirdparty\OAuth\OAuthRequest;
use Hybridauth\Thirdparty\OAuth\OAuthSignatureMethodHMACSHA1;
use Hybridauth\Thirdparty\OAuth\OAuthUtil;

/**
 * This class  can be used to simplify the authorization flow of OAuth 1 based service providers.
 *
 * Subclasses (i.e., providers adapters) can either use the already provided methods or override
 * them when necessary.
 */
abstract class OAuth1 extends AbstractAdapter implements AdapterInterface
{
    /**
     * Base URL to provider API
     *
     * This var will be used to build urls when sending signed requests
     *
     * @var string
     */
    protected $apiBaseUrl = '';

    /**
     * @var string
     */
    protected $authorizeUrl = '';

    /**
     * @var string
     */
    protected $requestTokenUrl = '';

    /**
     * @var string
     */
    protected $accessTokenUrl = '';

    /**
     * IPD API Documentation
     *
     * OPTIONAL.
     *
     * @var string
     */
    protected $apiDocumentation = '';

    /**
     * OAuth Version
     *
     *  '1.0' OAuth Core 1.0
     * '1.0a' OAuth Core 1.0 Revision A
     *
     * @var string
     */
    protected $oauth1Version = '1.0a';

    /**
     * @var string
     */
    protected $consumerKey = null;

    /**
     * @var string
     */
    protected $consumerSecret = null;

    /**
     * @var object
     */
    protected $OAuthConsumer = null;

    /**
     * @var object
     */
    protected $sha1Method = null;

    /**
     * @var object
     */
    protected $consumerToken = null;

    /**
     * Authorization Url Parameters
     *
     * @var bool
     */
    protected $AuthorizeUrlParameters = [];

    /**
     * @var string
     */
    protected $requestTokenMethod = 'POST';

    /**
     * @var array
     */
    protected $requestTokenParameters = [];

    /**
     * @var array
     */
    protected $requestTokenHeaders = [];

    /**
     * @var string
     */
    protected $tokenExchangeMethod = 'POST';

    /**
     * @var array
     */
    protected $tokenExchangeParameters = [];

    /**
     * @var array
     */
    protected $tokenExchangeHeaders = [];

    /**
     * @var array
     */
    protected $apiRequestParameters = [];

    /**
     * @var array
     */
    protected $apiRequestHeaders = [];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->consumerKey = $this->config->filter('keys')->get('id') ?: $this->config->filter('keys')->get('key');
        $this->consumerSecret = $this->config->filter('keys')->get('secret');

        if (!$this->consumerKey || !$this->consumerSecret) {
            throw new InvalidApplicationCredentialsException(
                'Your application id is required in order to connect to ' . $this->providerId
            );
        }

        if ($this->config->exists('tokens')) {
            $this->setAccessToken($this->config->get('tokens'));
        }

        $this->setCallback($this->config->get('callback'));
        $this->setApiEndpoints($this->config->get('endpoints'));
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        /**
         * Set up OAuth Signature and Consumer
         *
         * OAuth Core: All Token requests and Protected Resources requests MUST be signed
         * by the Consumer and verified by the Service Provider.
         *
         * The protocol defines three signature methods: HMAC-SHA1, RSA-SHA1, and PLAINTEXT..
         *
         * The Consumer declares a signature method in the oauth_signature_method parameter..
         *
         * http://oauth.net/core/1.0a/#signing_process
         */
        $this->sha1Method = new OAuthSignatureMethodHMACSHA1();

        $this->OAuthConsumer = new OAuthConsumer(
            $this->consumerKey,
            $this->consumerSecret
        );

        if ($this->getStoredData('request_token')) {
            $this->consumerToken = new OAuthConsumer(
                $this->getStoredData('request_token'),
                $this->getStoredData('request_token_secret')
            );
        }

        if ($this->getStoredData('access_token')) {
            $this->consumerToken = new OAuthConsumer(
                $this->getStoredData('access_token'),
                $this->getStoredData('access_token_secret')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate()
    {
        $this->logger->info(sprintf('%s::authenticate()', get_class($this)));

        if ($this->isConnected()) {
            return true;
        }

        try {
            if (!$this->getStoredData('request_token')) {
                // Start a new flow.
                $this->authenticateBegin();
            } elseif (empty($_GET['oauth_token']) && empty($_GET['denied'])) {
                // A previous authentication was not finished, and this request is not finishing it.
                $this->authenticateBegin();
            } else {
                // Finish a flow.
                $this->authenticateFinish();
            }
        } catch (Exception $exception) {
            $this->clearStoredData();

            throw $exception;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        return (bool)$this->getStoredData('access_token');
    }

    /**
     * Initiate the authorization protocol
     *
     * 1. Obtaining an Unauthorized Request Token
     * 2. Build Authorization URL for Authorization Request and redirect the user-agent to the
     *    Authorization Server.
     */
    protected function authenticateBegin()
    {
        $response = $this->requestAuthToken();

        $this->validateAuthTokenRequest($response);

        $authUrl = $this->getAuthorizeUrl();

        $this->logger->debug(sprintf('%s::authenticateBegin(), redirecting user to:', get_class($this)), [$authUrl]);

        HttpClient\Util::redirect($authUrl);
    }

    /**
     * Finalize the authorization process
     *
     * @throws AuthorizationDeniedException
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     * @throws InvalidAccessTokenException
     * @throws InvalidOauthTokenException
     */
    protected function authenticateFinish()
    {
        $this->logger->debug(
            sprintf('%s::authenticateFinish(), callback url:', get_class($this)),
            [HttpClient\Util::getCurrentUrl(true)]
        );

        $denied = filter_input(INPUT_GET, 'denied');
        $oauth_problem = filter_input(INPUT_GET, 'oauth_problem');
        $oauth_token = filter_input(INPUT_GET, 'oauth_token');
        $oauth_verifier = filter_input(INPUT_GET, 'oauth_verifier');

        if ($denied) {
            throw new AuthorizationDeniedException(
                'User denied access request. Provider returned a denied token: ' . htmlentities($denied)
            );
        }

        if ($oauth_problem) {
            throw new InvalidOauthTokenException(
                'Provider returned an error. oauth_problem: ' . htmlentities($oauth_problem)
            );
        }

        if (!$oauth_token) {
            throw new InvalidOauthTokenException(
                'Expecting a non-null oauth_token to continue the authorization flow.'
            );
        }

        $response = $this->exchangeAuthTokenForAccessToken($oauth_token, $oauth_verifier);

        $this->validateAccessTokenExchange($response);

        $this->initialize();
    }

    /**
     * Build Authorization URL for Authorization Request
     *
     * @param array $parameters
     *
     * @return string
     */
    protected function getAuthorizeUrl($parameters = [])
    {
        $this->AuthorizeUrlParameters = !empty($parameters)
            ? $parameters
            : array_replace(
                (array)$this->AuthorizeUrlParameters,
                (array)$this->config->get('authorize_url_parameters')
            );

        $this->AuthorizeUrlParameters['oauth_token'] = $this->getStoredData('request_token');

        return $this->authorizeUrl . '?' . http_build_query($this->AuthorizeUrlParameters, '', '&');
    }

    /**
     * Unauthorized Request Token
     *
     * OAuth Core: The Consumer obtains an unauthorized Request Token by asking the Service Provider
     * to issue a Token. The Request Token's sole purpose is to receive User approval and can only
     * be used to obtain an Access Token.
     *
     * http://oauth.net/core/1.0/#auth_step1
     * 6.1.1. Consumer Obtains a Request Token
     *
     * @return string Raw Provider API response
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     */
    protected function requestAuthToken()
    {
        /**
         * OAuth Core 1.0 Revision A: oauth_callback: An absolute URL to which the Service Provider will redirect
         * the User back when the Obtaining User Authorization step is completed.
         *
         * http://oauth.net/core/1.0a/#auth_step1
         */
        if ('1.0a' == $this->oauth1Version) {
            $this->requestTokenParameters['oauth_callback'] = $this->callback;
        }

        $response = $this->oauthRequest(
            $this->requestTokenUrl,
            $this->requestTokenMethod,
            $this->requestTokenParameters,
            $this->requestTokenHeaders
        );

        return $response;
    }

    /**
     * Validate Unauthorized Request Token Response
     *
     * OAuth Core: The Service Provider verifies the signature and Consumer Key. If successful,
     * it generates a Request Token and Token Secret and returns them to the Consumer in the HTTP
     * response body.
     *
     * http://oauth.net/core/1.0/#auth_step1
     * 6.1.2. Service Provider Issues an Unauthorized Request Token
     *
     * @param string $response
     *
     * @return \Hybridauth\Data\Collection
     * @throws InvalidOauthTokenException
     */
    protected function validateAuthTokenRequest($response)
    {
        /**
         * The response contains the following parameters:
         *
         *    - oauth_token               The Request Token.
         *    - oauth_token_secret        The Token Secret.
         *    - oauth_callback_confirmed  MUST be present and set to true.
         *
         * http://oauth.net/core/1.0/#auth_step1
         * 6.1.2. Service Provider Issues an Unauthorized Request Token
         *
         * Example of a successful response:
         *
         *  HTTP/1.1 200 OK
         *  Content-Type: text/html; charset=utf-8
         *  Cache-Control: no-store
         *  Pragma: no-cache
         *
         *  oauth_token=80359084-clg1DEtxQF3wstTcyUdHF3wsdHM&oauth_token_secret=OIF07hPmJB:P
         *  6qiHTi1znz6qiH3tTcyUdHnz6qiH3tTcyUdH3xW3wsDvV08e&example_parameter=example_value
         *
         * OAuthUtil::parse_parameters will attempt to decode the raw response into an array.
         */
        $tokens = OAuthUtil::parse_parameters($response);

        $collection = new Data\Collection($tokens);

        if (!$collection->exists('oauth_token')) {
            throw new InvalidOauthTokenException(
                'Provider returned no oauth_token: ' . htmlentities($response)
            );
        }

        $this->consumerToken = new OAuthConsumer(
            $tokens['oauth_token'],
            $tokens['oauth_token_secret']
        );

        $this->storeData('request_token', $tokens['oauth_token']);
        $this->storeData('request_token_secret', $tokens['oauth_token_secret']);

        return $collection;
    }

    /**
     * Requests an Access Token
     *
     * OAuth Core: The Request Token and Token Secret MUST be exchanged for an Access Token and Token Secret.
     *
     * http://oauth.net/core/1.0a/#auth_step3
     * 6.3.1. Consumer Requests an Access Token
     *
     * @param string $oauth_token
     * @param string $oauth_verifier
     *
     * @return string Raw Provider API response
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     */
    protected function exchangeAuthTokenForAccessToken($oauth_token, $oauth_verifier = '')
    {
        $this->tokenExchangeParameters['oauth_token'] = $oauth_token;

        /**
         * OAuth Core 1.0 Revision A: oauth_verifier: The verification code received from the Service Provider
         * in the "Service Provider Directs the User Back to the Consumer" step.
         *
         * http://oauth.net/core/1.0a/#auth_step3
         */
        if ('1.0a' == $this->oauth1Version) {
            $this->tokenExchangeParameters['oauth_verifier'] = $oauth_verifier;
        }

        $response = $this->oauthRequest(
            $this->accessTokenUrl,
            $this->tokenExchangeMethod,
            $this->tokenExchangeParameters,
            $this->tokenExchangeHeaders
        );

        return $response;
    }

    /**
     * Validate Access Token Response
     *
     * OAuth Core: If successful, the Service Provider generates an Access Token and Token Secret and returns
     * them in the HTTP response body.
     *
     * The Access Token and Token Secret are stored by the Consumer and used when signing Protected Resources requests.
     *
     * http://oauth.net/core/1.0a/#auth_step3
     * 6.3.2. Service Provider Grants an Access Token
     *
     * @param string $response
     *
     * @return \Hybridauth\Data\Collection
     * @throws InvalidAccessTokenException
     */
    protected function validateAccessTokenExchange($response)
    {
        /**
         * The response contains the following parameters:
         *
         *    - oauth_token         The Access Token.
         *    - oauth_token_secret  The Token Secret.
         *
         * http://oauth.net/core/1.0/#auth_step3
         * 6.3.2. Service Provider Grants an Access Token
         *
         * Example of a successful response:
         *
         *  HTTP/1.1 200 OK
         *  Content-Type: text/html; charset=utf-8
         *  Cache-Control: no-store
         *  Pragma: no-cache
         *
         *  oauth_token=sHeLU7Far428zj8PzlWR75&oauth_token_secret=fXb30rzoG&oauth_callback_confirmed=true
         *
         * OAuthUtil::parse_parameters will attempt to decode the raw response into an array.
         */
        $tokens = OAuthUtil::parse_parameters($response);

        $collection = new Data\Collection($tokens);

        if (!$collection->exists('oauth_token')) {
            throw new InvalidAccessTokenException(
                'Provider returned no access_token: ' . htmlentities($response)
            );
        }

        $this->consumerToken = new OAuthConsumer(
            $collection->get('oauth_token'),
            $collection->get('oauth_token_secret')
        );

        $this->storeData('access_token', $collection->get('oauth_token'));
        $this->storeData('access_token_secret', $collection->get('oauth_token_secret'));

        $this->deleteStoredData('request_token');
        $this->deleteStoredData('request_token_secret');

        return $collection;
    }

    /**
     * Send a signed request to provider API
     *
     * Note: Since the specifics of error responses is beyond the scope of RFC6749 and OAuth specifications,
     * Hybridauth will consider any HTTP status code that is different than '200 OK' as an ERROR.
     *
     * @param string $url
     * @param string $method
     * @param array $parameters
     * @param array $headers
     * @param bool $multipart
     *
     * @return mixed
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     */
    public function apiRequest($url, $method = 'GET', $parameters = [], $headers = [], $multipart = false)
    {
        // refresh tokens if needed
        $this->maintainToken();

        if (strrpos($url, 'http://') !== 0 && strrpos($url, 'https://') !== 0) {
            $url = rtrim($this->apiBaseUrl, '/') . '/' . ltrim($url, '/');
        }

        $parameters = array_replace($this->apiRequestParameters, (array)$parameters);

        $headers = array_replace($this->apiRequestHeaders, (array)$headers);

        $response = $this->oauthRequest($url, $method, $parameters, $headers, $multipart);

        $response = (new Data\Parser())->parse($response);

        return $response;
    }

    /**
     * Setup and Send a Signed Oauth Request
     *
     * This method uses OAuth Library.
     *
     * @param string $uri
     * @param string $method
     * @param array $parameters
     * @param array $headers
     * @param bool $multipart
     *
     * @return string Raw Provider API response
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     */
    protected function oauthRequest($uri, $method = 'GET', $parameters = [], $headers = [], $multipart = false)
    {
        $signing_parameters = $parameters;
        if ($multipart) {
            $signing_parameters = [];
        }

        $request = OAuthRequest::from_consumer_and_token(
            $this->OAuthConsumer,
            $this->consumerToken,
            $method,
            $uri,
            $signing_parameters
        );

        $request->sign_request(
            $this->sha1Method,
            $this->OAuthConsumer,
            $this->consumerToken
        );

        $uri = $request->get_normalized_http_url();
        $headers = array_replace($request->to_header(), (array)$headers);

        $response = $this->httpClient->request(
            $uri,
            $method,
            $parameters,
            $headers,
            $multipart
        );

        $this->validateApiResponse('Signed API request to ' . $uri . ' has returned an error');

        return $response;
    }
}
