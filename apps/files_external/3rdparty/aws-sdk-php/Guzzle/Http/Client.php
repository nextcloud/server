<?php

namespace Guzzle\Http;

use Guzzle\Common\Collection;
use Guzzle\Common\AbstractHasDispatcher;
use Guzzle\Common\Exception\ExceptionCollection;
use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Common\Exception\RuntimeException;
use Guzzle\Common\Version;
use Guzzle\Parser\ParserRegistry;
use Guzzle\Parser\UriTemplate\UriTemplateInterface;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\RequestFactory;
use Guzzle\Http\Message\RequestFactoryInterface;
use Guzzle\Http\Curl\CurlMultiInterface;
use Guzzle\Http\Curl\CurlMultiProxy;
use Guzzle\Http\Curl\CurlHandle;
use Guzzle\Http\Curl\CurlVersion;

/**
 * HTTP client
 */
class Client extends AbstractHasDispatcher implements ClientInterface
{
    /** @deprecated Use [request.options][params] */
    const REQUEST_PARAMS = 'request.params';

    const REQUEST_OPTIONS = 'request.options';
    const CURL_OPTIONS = 'curl.options';
    const SSL_CERT_AUTHORITY = 'ssl.certificate_authority';
    const DISABLE_REDIRECTS = RedirectPlugin::DISABLE;
    const DEFAULT_SELECT_TIMEOUT = 1.0;
    const MAX_HANDLES = 3;

    /** @var Collection Default HTTP headers to set on each request */
    protected $defaultHeaders;

    /** @var string The user agent string to set on each request */
    protected $userAgent;

    /** @var Collection Parameter object holding configuration data */
    private $config;

    /** @var Url Base URL of the client */
    private $baseUrl;

    /** @var CurlMultiInterface CurlMulti object used internally */
    private $curlMulti;

    /** @var UriTemplateInterface URI template owned by the client */
    private $uriTemplate;

    /** @var RequestFactoryInterface Request factory used by the client */
    protected $requestFactory;

    public static function getAllEvents()
    {
        return array(self::CREATE_REQUEST);
    }

    /**
     * @param string           $baseUrl Base URL of the web service
     * @param array|Collection $config  Configuration settings
     *
     * @throws RuntimeException if cURL is not installed
     */
    public function __construct($baseUrl = '', $config = null)
    {
        if (!extension_loaded('curl')) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('The PHP cURL extension must be installed to use Guzzle.');
            // @codeCoverageIgnoreEnd
        }
        $this->setConfig($config ?: new Collection());
        $this->initSsl();
        $this->setBaseUrl($baseUrl);
        $this->defaultHeaders = new Collection();
        $this->setRequestFactory(RequestFactory::getInstance());
        $this->userAgent = $this->getDefaultUserAgent();
        if (!$this->config[self::DISABLE_REDIRECTS]) {
            $this->addSubscriber(new RedirectPlugin());
        }
    }

    final public function setConfig($config)
    {
        if ($config instanceof Collection) {
            $this->config = $config;
        } elseif (is_array($config)) {
            $this->config = new Collection($config);
        } else {
            throw new InvalidArgumentException('Config must be an array or Collection');
        }

        return $this;
    }

    final public function getConfig($key = false)
    {
        return $key ? $this->config[$key] : $this->config;
    }

    /**
     * Set a default request option on the client that will be used as a default for each request
     *
     * @param string $keyOrPath request.options key (e.g. allow_redirects) or path to a nested key (e.g. headers/foo)
     * @param mixed  $value     Value to set
     *
     * @return $this
     */
    public function setDefaultOption($keyOrPath, $value)
    {
        $keyOrPath = self::REQUEST_OPTIONS . '/' . $keyOrPath;
        $this->config->setPath($keyOrPath, $value);

        return $this;
    }

    /**
     * Retrieve a default request option from the client
     *
     * @param string $keyOrPath request.options key (e.g. allow_redirects) or path to a nested key (e.g. headers/foo)
     *
     * @return mixed|null
     */
    public function getDefaultOption($keyOrPath)
    {
        $keyOrPath = self::REQUEST_OPTIONS . '/' . $keyOrPath;

        return $this->config->getPath($keyOrPath);
    }

    final public function setSslVerification($certificateAuthority = true, $verifyPeer = true, $verifyHost = 2)
    {
        $opts = $this->config[self::CURL_OPTIONS] ?: array();

        if ($certificateAuthority === true) {
            // use bundled CA bundle, set secure defaults
            $opts[CURLOPT_CAINFO] = __DIR__ . '/Resources/cacert.pem';
            $opts[CURLOPT_SSL_VERIFYPEER] = true;
            $opts[CURLOPT_SSL_VERIFYHOST] = 2;
        } elseif ($certificateAuthority === false) {
            unset($opts[CURLOPT_CAINFO]);
            $opts[CURLOPT_SSL_VERIFYPEER] = false;
            $opts[CURLOPT_SSL_VERIFYHOST] = 0;
        } elseif ($verifyPeer !== true && $verifyPeer !== false && $verifyPeer !== 1 && $verifyPeer !== 0) {
            throw new InvalidArgumentException('verifyPeer must be 1, 0 or boolean');
        } elseif ($verifyHost !== 0 && $verifyHost !== 1 && $verifyHost !== 2) {
            throw new InvalidArgumentException('verifyHost must be 0, 1 or 2');
        } else {
            $opts[CURLOPT_SSL_VERIFYPEER] = $verifyPeer;
            $opts[CURLOPT_SSL_VERIFYHOST] = $verifyHost;
            if (is_file($certificateAuthority)) {
                unset($opts[CURLOPT_CAPATH]);
                $opts[CURLOPT_CAINFO] = $certificateAuthority;
            } elseif (is_dir($certificateAuthority)) {
                unset($opts[CURLOPT_CAINFO]);
                $opts[CURLOPT_CAPATH] = $certificateAuthority;
            } else {
                throw new RuntimeException(
                    'Invalid option passed to ' . self::SSL_CERT_AUTHORITY . ': ' . $certificateAuthority
                );
            }
        }

        $this->config->set(self::CURL_OPTIONS, $opts);

        return $this;
    }

    public function createRequest($method = 'GET', $uri = null, $headers = null, $body = null, array $options = array())
    {
        if (!$uri) {
            $url = $this->getBaseUrl();
        } else {
            if (!is_array($uri)) {
                $templateVars = null;
            } else {
                list($uri, $templateVars) = $uri;
            }
            if (strpos($uri, '://')) {
                // Use absolute URLs as-is
                $url = $this->expandTemplate($uri, $templateVars);
            } else {
                $url = Url::factory($this->getBaseUrl())->combine($this->expandTemplate($uri, $templateVars));
            }
        }

        // If default headers are provided, then merge them under any explicitly provided headers for the request
        if (count($this->defaultHeaders)) {
            if (!$headers) {
                $headers = $this->defaultHeaders->toArray();
            } elseif (is_array($headers)) {
                $headers += $this->defaultHeaders->toArray();
            } elseif ($headers instanceof Collection) {
                $headers = $headers->toArray() + $this->defaultHeaders->toArray();
            }
        }

        return $this->prepareRequest($this->requestFactory->create($method, (string) $url, $headers, $body), $options);
    }

    public function getBaseUrl($expand = true)
    {
        return $expand ? $this->expandTemplate($this->baseUrl) : $this->baseUrl;
    }

    public function setBaseUrl($url)
    {
        $this->baseUrl = $url;

        return $this;
    }

    public function setUserAgent($userAgent, $includeDefault = false)
    {
        if ($includeDefault) {
            $userAgent .= ' ' . $this->getDefaultUserAgent();
        }
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * Get the default User-Agent string to use with Guzzle
     *
     * @return string
     */
    public function getDefaultUserAgent()
    {
        return 'Guzzle/' . Version::VERSION
            . ' curl/' . CurlVersion::getInstance()->get('version')
            . ' PHP/' . PHP_VERSION;
    }

    public function get($uri = null, $headers = null, $options = array())
    {
        // BC compat: $options can be a string, resource, etc to specify where the response body is downloaded
        return is_array($options)
            ? $this->createRequest('GET', $uri, $headers, null, $options)
            : $this->createRequest('GET', $uri, $headers, $options);
    }

    public function head($uri = null, $headers = null, array $options = array())
    {
        return $this->createRequest('HEAD', $uri, $headers, null, $options);
    }

    public function delete($uri = null, $headers = null, $body = null, array $options = array())
    {
        return $this->createRequest('DELETE', $uri, $headers, $body, $options);
    }

    public function put($uri = null, $headers = null, $body = null, array $options = array())
    {
        return $this->createRequest('PUT', $uri, $headers, $body, $options);
    }

    public function patch($uri = null, $headers = null, $body = null, array $options = array())
    {
        return $this->createRequest('PATCH', $uri, $headers, $body, $options);
    }

    public function post($uri = null, $headers = null, $postBody = null, array $options = array())
    {
        return $this->createRequest('POST', $uri, $headers, $postBody, $options);
    }

    public function options($uri = null, array $options = array())
    {
        return $this->createRequest('OPTIONS', $uri, $options);
    }

    public function send($requests)
    {
        if (!($requests instanceof RequestInterface)) {
            return $this->sendMultiple($requests);
        }

        try {
            /** @var $requests RequestInterface  */
            $this->getCurlMulti()->add($requests)->send();
            return $requests->getResponse();
        } catch (ExceptionCollection $e) {
            throw $e->getFirst();
        }
    }

    /**
     * Set a curl multi object to be used internally by the client for transferring requests.
     *
     * @param CurlMultiInterface $curlMulti Multi object
     *
     * @return self
     */
    public function setCurlMulti(CurlMultiInterface $curlMulti)
    {
        $this->curlMulti = $curlMulti;

        return $this;
    }

    /**
     * @return CurlMultiInterface|CurlMultiProxy
     */
    public function getCurlMulti()
    {
        if (!$this->curlMulti) {
            $this->curlMulti = new CurlMultiProxy(
                self::MAX_HANDLES,
                $this->getConfig('select_timeout') ?: self::DEFAULT_SELECT_TIMEOUT
            );
        }

        return $this->curlMulti;
    }

    public function setRequestFactory(RequestFactoryInterface $factory)
    {
        $this->requestFactory = $factory;

        return $this;
    }

    /**
     * Set the URI template expander to use with the client
     *
     * @param UriTemplateInterface $uriTemplate URI template expander
     *
     * @return self
     */
    public function setUriTemplate(UriTemplateInterface $uriTemplate)
    {
        $this->uriTemplate = $uriTemplate;

        return $this;
    }

    /**
     * Expand a URI template while merging client config settings into the template variables
     *
     * @param string $template  Template to expand
     * @param array  $variables Variables to inject
     *
     * @return string
     */
    protected function expandTemplate($template, array $variables = null)
    {
        $expansionVars = $this->getConfig()->toArray();
        if ($variables) {
            $expansionVars = $variables + $expansionVars;
        }

        return $this->getUriTemplate()->expand($template, $expansionVars);
    }

    /**
     * Get the URI template expander used by the client
     *
     * @return UriTemplateInterface
     */
    protected function getUriTemplate()
    {
        if (!$this->uriTemplate) {
            $this->uriTemplate = ParserRegistry::getInstance()->getParser('uri_template');
        }

        return $this->uriTemplate;
    }

    /**
     * Send multiple requests in parallel
     *
     * @param array $requests Array of RequestInterface objects
     *
     * @return array Returns an array of Response objects
     */
    protected function sendMultiple(array $requests)
    {
        $curlMulti = $this->getCurlMulti();
        foreach ($requests as $request) {
            $curlMulti->add($request);
        }
        $curlMulti->send();

        /** @var $request RequestInterface */
        $result = array();
        foreach ($requests as $request) {
            $result[] = $request->getResponse();
        }

        return $result;
    }

    /**
     * Prepare a request to be sent from the Client by adding client specific behaviors and properties to the request.
     *
     * @param RequestInterface $request Request to prepare for the client
     * @param array            $options Options to apply to the request
     *
     * @return RequestInterface
     */
    protected function prepareRequest(RequestInterface $request, array $options = array())
    {
        $request->setClient($this)->setEventDispatcher(clone $this->getEventDispatcher());

        if ($curl = $this->config[self::CURL_OPTIONS]) {
            $request->getCurlOptions()->overwriteWith(CurlHandle::parseCurlConfig($curl));
        }

        if ($params = $this->config[self::REQUEST_PARAMS]) {
            Version::warn('request.params is deprecated. Use request.options to add default request options.');
            $request->getParams()->overwriteWith($params);
        }

        if ($this->userAgent && !$request->hasHeader('User-Agent')) {
            $request->setHeader('User-Agent', $this->userAgent);
        }

        if ($defaults = $this->config[self::REQUEST_OPTIONS]) {
            $this->requestFactory->applyOptions($request, $defaults, RequestFactoryInterface::OPTIONS_AS_DEFAULTS);
        }

        if ($options) {
            $this->requestFactory->applyOptions($request, $options);
        }

        $this->dispatch('client.create_request', array('client' => $this, 'request' => $request));

        return $request;
    }

    /**
     * Initializes SSL settings
     */
    protected function initSsl()
    {
        $authority = $this->config[self::SSL_CERT_AUTHORITY];

        if ($authority === 'system') {
            return;
        }

        if ($authority === null) {
            $authority = true;
        }

        if ($authority === true && substr(__FILE__, 0, 7) == 'phar://') {
            $authority = self::extractPharCacert(__DIR__ . '/Resources/cacert.pem');
        }

        $this->setSslVerification($authority);
    }

    /**
     * @deprecated
     */
    public function getDefaultHeaders()
    {
        Version::warn(__METHOD__ . ' is deprecated. Use the request.options array to retrieve default request options');
        return $this->defaultHeaders;
    }

    /**
     * @deprecated
     */
    public function setDefaultHeaders($headers)
    {
        Version::warn(__METHOD__ . ' is deprecated. Use the request.options array to specify default request options');
        if ($headers instanceof Collection) {
            $this->defaultHeaders = $headers;
        } elseif (is_array($headers)) {
            $this->defaultHeaders = new Collection($headers);
        } else {
            throw new InvalidArgumentException('Headers must be an array or Collection');
        }

        return $this;
    }

    /**
     * @deprecated
     */
    public function preparePharCacert($md5Check = true)
    {
        return sys_get_temp_dir() . '/guzzle-cacert.pem';
    }

    /**
     * Copies the phar cacert from a phar into the temp directory.
     *
     * @param string $pharCacertPath Path to the phar cacert. For example:
     *                               'phar://aws.phar/Guzzle/Http/Resources/cacert.pem'
     *
     * @return string Returns the path to the extracted cacert file.
     * @throws \RuntimeException Throws if the phar cacert cannot be found or
     *                           the file cannot be copied to the temp dir.
     */
    public static function extractPharCacert($pharCacertPath)
    {
        // Copy the cacert.pem file from the phar if it is not in the temp
        // folder.
        $certFile = sys_get_temp_dir() . '/guzzle-cacert.pem';

        if (!file_exists($pharCacertPath)) {
            throw new \RuntimeException("Could not find $pharCacertPath");
        }

        if (!file_exists($certFile) ||
            filesize($certFile) != filesize($pharCacertPath)
        ) {
            if (!copy($pharCacertPath, $certFile)) {
                throw new \RuntimeException(
                    "Could not copy {$pharCacertPath} to {$certFile}: "
                    . var_export(error_get_last(), true)
                );
            }
        }

        return $certFile;
    }
}
