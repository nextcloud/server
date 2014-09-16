<?php

namespace Guzzle\Http\Message;

use Guzzle\Common\Version;
use Guzzle\Common\Event;
use Guzzle\Common\Collection;
use Guzzle\Common\Exception\RuntimeException;
use Guzzle\Common\Exception\InvalidArgumentException;
use Guzzle\Http\Exception\RequestException;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\ClientInterface;
use Guzzle\Http\EntityBody;
use Guzzle\Http\EntityBodyInterface;
use Guzzle\Http\Message\Header\HeaderInterface;
use Guzzle\Http\Url;
use Guzzle\Parser\ParserRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * HTTP request class to send requests
 */
class Request extends AbstractMessage implements RequestInterface
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var Url HTTP Url */
    protected $url;

    /** @var string HTTP method (GET, PUT, POST, DELETE, HEAD, OPTIONS, TRACE) */
    protected $method;

    /** @var ClientInterface */
    protected $client;

    /** @var Response Response of the request */
    protected $response;

    /** @var EntityBodyInterface Response body */
    protected $responseBody;

    /** @var string State of the request object */
    protected $state;

    /** @var string Authentication username */
    protected $username;

    /** @var string Auth password */
    protected $password;

    /** @var Collection cURL specific transfer options */
    protected $curlOptions;

    /** @var bool */
    protected $isRedirect = false;

    public static function getAllEvents()
    {
        return array(
            // Called when receiving or uploading data through cURL
            'curl.callback.read', 'curl.callback.write', 'curl.callback.progress',
            // Cloning a request
            'request.clone',
            // About to send the request, sent request, completed transaction
            'request.before_send', 'request.sent', 'request.complete',
            // A request received a successful response
            'request.success',
            // A request received an unsuccessful response
            'request.error',
            // An exception is being thrown because of an unsuccessful response
            'request.exception',
            // Received response status line
            'request.receive.status_line'
        );
    }

    /**
     * @param string           $method  HTTP method
     * @param string|Url       $url     HTTP URL to connect to. The URI scheme, host header, and URI are parsed from the
     *                                  full URL. If query string parameters are present they will be parsed as well.
     * @param array|Collection $headers HTTP headers
     */
    public function __construct($method, $url, $headers = array())
    {
        parent::__construct();
        $this->method = strtoupper($method);
        $this->curlOptions = new Collection();
        $this->setUrl($url);

        if ($headers) {
            // Special handling for multi-value headers
            foreach ($headers as $key => $value) {
                // Deal with collisions with Host and Authorization
                if ($key == 'host' || $key == 'Host') {
                    $this->setHeader($key, $value);
                } elseif ($value instanceof HeaderInterface) {
                    $this->addHeader($key, $value);
                } else {
                    foreach ((array) $value as $v) {
                        $this->addHeader($key, $v);
                    }
                }
            }
        }

        $this->setState(self::STATE_NEW);
    }

    public function __clone()
    {
        if ($this->eventDispatcher) {
            $this->eventDispatcher = clone $this->eventDispatcher;
        }
        $this->curlOptions = clone $this->curlOptions;
        $this->params = clone $this->params;
        $this->url = clone $this->url;
        $this->response = $this->responseBody = null;
        $this->headers = clone $this->headers;

        $this->setState(RequestInterface::STATE_NEW);
        $this->dispatch('request.clone', array('request' => $this));
    }

    /**
     * Get the HTTP request as a string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getRawHeaders() . "\r\n\r\n";
    }

    /**
     * Default method that will throw exceptions if an unsuccessful response is received.
     *
     * @param Event $event Received
     * @throws BadResponseException if the response is not successful
     */
    public static function onRequestError(Event $event)
    {
        $e = BadResponseException::factory($event['request'], $event['response']);
        $event['request']->setState(self::STATE_ERROR, array('exception' => $e) + $event->toArray());
        throw $e;
    }

    public function setClient(ClientInterface $client)
    {
        $this->client = $client;

        return $this;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getRawHeaders()
    {
        $protocolVersion = $this->protocolVersion ?: '1.1';

        return trim($this->method . ' ' . $this->getResource()) . ' '
            . strtoupper(str_replace('https', 'http', $this->url->getScheme()))
            . '/' . $protocolVersion . "\r\n" . implode("\r\n", $this->getHeaderLines());
    }

    public function setUrl($url)
    {
        if ($url instanceof Url) {
            $this->url = $url;
        } else {
            $this->url = Url::factory($url);
        }

        // Update the port and host header
        $this->setPort($this->url->getPort());

        if ($this->url->getUsername() || $this->url->getPassword()) {
            $this->setAuth($this->url->getUsername(), $this->url->getPassword());
            // Remove the auth info from the URL
            $this->url->setUsername(null);
            $this->url->setPassword(null);
        }

        return $this;
    }

    public function send()
    {
        if (!$this->client) {
            throw new RuntimeException('A client must be set on the request');
        }

        return $this->client->send($this);
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getQuery($asString = false)
    {
        return $asString
            ? (string) $this->url->getQuery()
            : $this->url->getQuery();
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getScheme()
    {
        return $this->url->getScheme();
    }

    public function setScheme($scheme)
    {
        $this->url->setScheme($scheme);

        return $this;
    }

    public function getHost()
    {
        return $this->url->getHost();
    }

    public function setHost($host)
    {
        $this->url->setHost($host);
        $this->setPort($this->url->getPort());

        return $this;
    }

    public function getProtocolVersion()
    {
        return $this->protocolVersion;
    }

    public function setProtocolVersion($protocol)
    {
        $this->protocolVersion = $protocol;

        return $this;
    }

    public function getPath()
    {
        return '/' . ltrim($this->url->getPath(), '/');
    }

    public function setPath($path)
    {
        $this->url->setPath($path);

        return $this;
    }

    public function getPort()
    {
        return $this->url->getPort();
    }

    public function setPort($port)
    {
        $this->url->setPort($port);

        // Include the port in the Host header if it is not the default port for the scheme of the URL
        $scheme = $this->url->getScheme();
        if ($port && (($scheme == 'http' && $port != 80) || ($scheme == 'https' && $port != 443))) {
            $this->headers['host'] = $this->headerFactory->createHeader('Host', $this->url->getHost() . ':' . $port);
        } else {
            $this->headers['host'] = $this->headerFactory->createHeader('Host', $this->url->getHost());
        }

        return $this;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setAuth($user, $password = '', $scheme = CURLAUTH_BASIC)
    {
        static $authMap = array(
            'basic'  => CURLAUTH_BASIC,
            'digest' => CURLAUTH_DIGEST,
            'ntlm'   => CURLAUTH_NTLM,
            'any'    => CURLAUTH_ANY
        );

        // If we got false or null, disable authentication
        if (!$user) {
            $this->password = $this->username = null;
            $this->removeHeader('Authorization');
            $this->getCurlOptions()->remove(CURLOPT_HTTPAUTH);
            return $this;
        }

        if (!is_numeric($scheme)) {
            $scheme = strtolower($scheme);
            if (!isset($authMap[$scheme])) {
                throw new InvalidArgumentException($scheme . ' is not a valid authentication type');
            }
            $scheme = $authMap[$scheme];
        }

        $this->username = $user;
        $this->password = $password;

        // Bypass CURL when using basic auth to promote connection reuse
        if ($scheme == CURLAUTH_BASIC) {
            $this->getCurlOptions()->remove(CURLOPT_HTTPAUTH);
            $this->setHeader('Authorization', 'Basic ' . base64_encode($this->username . ':' . $this->password));
        } else {
            $this->getCurlOptions()
                ->set(CURLOPT_HTTPAUTH, $scheme)
                ->set(CURLOPT_USERPWD, $this->username . ':' . $this->password);
        }

        return $this;
    }

    public function getResource()
    {
        $resource = $this->getPath();
        if ($query = (string) $this->url->getQuery()) {
            $resource .= '?' . $query;
        }

        return $resource;
    }

    public function getUrl($asObject = false)
    {
        return $asObject ? clone $this->url : (string) $this->url;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state, array $context = array())
    {
        $oldState = $this->state;
        $this->state = $state;

        switch ($state) {
            case self::STATE_NEW:
                $this->response = null;
                break;
            case self::STATE_TRANSFER:
                if ($oldState !== $state) {
                    // Fix Content-Length and Transfer-Encoding collisions
                    if ($this->hasHeader('Transfer-Encoding') && $this->hasHeader('Content-Length')) {
                        $this->removeHeader('Transfer-Encoding');
                    }
                    $this->dispatch('request.before_send', array('request' => $this));
                }
                break;
            case self::STATE_COMPLETE:
                if ($oldState !== $state) {
                    $this->processResponse($context);
                    $this->responseBody = null;
                }
                break;
            case self::STATE_ERROR:
                if (isset($context['exception'])) {
                    $this->dispatch('request.exception', array(
                        'request'   => $this,
                        'response'  => isset($context['response']) ? $context['response'] : $this->response,
                        'exception' => isset($context['exception']) ? $context['exception'] : null
                    ));
                }
        }

        return $this->state;
    }

    public function getCurlOptions()
    {
        return $this->curlOptions;
    }

    public function startResponse(Response $response)
    {
        $this->state = self::STATE_TRANSFER;
        $response->setEffectiveUrl((string) $this->getUrl());
        $this->response = $response;

        return $this;
    }

    public function setResponse(Response $response, $queued = false)
    {
        $response->setEffectiveUrl((string) $this->url);

        if ($queued) {
            $ed = $this->getEventDispatcher();
            $ed->addListener('request.before_send', $f = function ($e) use ($response, &$f, $ed) {
                $e['request']->setResponse($response);
                $ed->removeListener('request.before_send', $f);
            }, -9999);
        } else {
            $this->response = $response;
            // If a specific response body is specified, then use it instead of the response's body
            if ($this->responseBody && !$this->responseBody->getCustomData('default') && !$response->isRedirect()) {
                $this->getResponseBody()->write((string) $this->response->getBody());
            } else {
                $this->responseBody = $this->response->getBody();
            }
            $this->setState(self::STATE_COMPLETE);
        }

        return $this;
    }

    public function setResponseBody($body)
    {
        // Attempt to open a file for writing if a string was passed
        if (is_string($body)) {
            // @codeCoverageIgnoreStart
            if (!($body = fopen($body, 'w+'))) {
                throw new InvalidArgumentException('Could not open ' . $body . ' for writing');
            }
            // @codeCoverageIgnoreEnd
        }

        $this->responseBody = EntityBody::factory($body);

        return $this;
    }

    public function getResponseBody()
    {
        if ($this->responseBody === null) {
            $this->responseBody = EntityBody::factory()->setCustomData('default', true);
        }

        return $this->responseBody;
    }

    /**
     * Determine if the response body is repeatable (readable + seekable)
     *
     * @return bool
     * @deprecated Use getResponseBody()->isSeekable()
     * @codeCoverageIgnore
     */
    public function isResponseBodyRepeatable()
    {
        Version::warn(__METHOD__ . ' is deprecated. Use $request->getResponseBody()->isRepeatable()');
        return !$this->responseBody ? true : $this->responseBody->isRepeatable();
    }

    public function getCookies()
    {
        if ($cookie = $this->getHeader('Cookie')) {
            $data = ParserRegistry::getInstance()->getParser('cookie')->parseCookie($cookie);
            return $data['cookies'];
        }

        return array();
    }

    public function getCookie($name)
    {
        $cookies = $this->getCookies();

        return isset($cookies[$name]) ? $cookies[$name] : null;
    }

    public function addCookie($name, $value)
    {
        if (!$this->hasHeader('Cookie')) {
            $this->setHeader('Cookie', "{$name}={$value}");
        } else {
            $this->getHeader('Cookie')->add("{$name}={$value}");
        }

        // Always use semicolons to separate multiple cookie headers
        $this->getHeader('Cookie')->setGlue(';');

        return $this;
    }

    public function removeCookie($name)
    {
        if ($cookie = $this->getHeader('Cookie')) {
            foreach ($cookie as $cookieValue) {
                if (strpos($cookieValue, $name . '=') === 0) {
                    $cookie->removeValue($cookieValue);
                }
            }
        }

        return $this;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->eventDispatcher->addListener('request.error', array(__CLASS__, 'onRequestError'), -255);

        return $this;
    }

    public function getEventDispatcher()
    {
        if (!$this->eventDispatcher) {
            $this->setEventDispatcher(new EventDispatcher());
        }

        return $this->eventDispatcher;
    }

    public function dispatch($eventName, array $context = array())
    {
        $context['request'] = $this;

        return $this->getEventDispatcher()->dispatch($eventName, new Event($context));
    }

    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        $this->getEventDispatcher()->addSubscriber($subscriber);

        return $this;
    }

    /**
     * Get an array containing the request and response for event notifications
     *
     * @return array
     */
    protected function getEventArray()
    {
        return array(
            'request'  => $this,
            'response' => $this->response
        );
    }

    /**
     * Process a received response
     *
     * @param array $context Contextual information
     * @throws RequestException|BadResponseException on unsuccessful responses
     */
    protected function processResponse(array $context = array())
    {
        if (!$this->response) {
            // If no response, then processResponse shouldn't have been called
            $e = new RequestException('Error completing request');
            $e->setRequest($this);
            throw $e;
        }

        $this->state = self::STATE_COMPLETE;

        // A request was sent, but we don't know if we'll send more or if the final response will be successful
        $this->dispatch('request.sent', $this->getEventArray() + $context);

        // Some response processors will remove the response or reset the state (example: ExponentialBackoffPlugin)
        if ($this->state == RequestInterface::STATE_COMPLETE) {

            // The request completed, so the HTTP transaction is complete
            $this->dispatch('request.complete', $this->getEventArray());

            // If the response is bad, allow listeners to modify it or throw exceptions. You can change the response by
            // modifying the Event object in your listeners or calling setResponse() on the request
            if ($this->response->isError()) {
                $event = new Event($this->getEventArray());
                $this->getEventDispatcher()->dispatch('request.error', $event);
                // Allow events of request.error to quietly change the response
                if ($event['response'] !== $this->response) {
                    $this->response = $event['response'];
                }
            }

            // If a successful response was received, dispatch an event
            if ($this->response->isSuccessful()) {
                $this->dispatch('request.success', $this->getEventArray());
            }
        }
    }

    /**
     * @deprecated Use Guzzle\Plugin\Cache\DefaultCanCacheStrategy
     * @codeCoverageIgnore
     */
    public function canCache()
    {
        Version::warn(__METHOD__ . ' is deprecated. Use Guzzle\Plugin\Cache\DefaultCanCacheStrategy.');
        if (class_exists('Guzzle\Plugin\Cache\DefaultCanCacheStrategy')) {
            $canCache = new \Guzzle\Plugin\Cache\DefaultCanCacheStrategy();
            return $canCache->canCacheRequest($this);
        } else {
            return false;
        }
    }

    /**
     * @deprecated Use the history plugin (not emitting a warning as this is built-into the RedirectPlugin for now)
     * @codeCoverageIgnore
     */
    public function setIsRedirect($isRedirect)
    {
        $this->isRedirect = $isRedirect;

        return $this;
    }

    /**
     * @deprecated Use the history plugin
     * @codeCoverageIgnore
     */
    public function isRedirect()
    {
        Version::warn(__METHOD__ . ' is deprecated. Use the HistoryPlugin to track this.');
        return $this->isRedirect;
    }
}
